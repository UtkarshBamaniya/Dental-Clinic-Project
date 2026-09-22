<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\AppointmentExamination;
use App\Models\AppointmentTreatment;
use App\Models\Doctor;
use App\Models\PaymentTransaction;
use App\Models\Treatment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AppointmentService
{
    // ---------------------------------------------------------------------------
    // Appointment Number Generation
    // ---------------------------------------------------------------------------

    /**
     * Generate the next unique appointment number in the format APP-00001.
     * Uses a pessimistic lock on the max row to avoid race conditions.
     */
    public function generateAppointmentNo(): string
    {
        // Lock the latest appointment row so concurrent requests cannot steal the same sequence.
        $latest = Appointment::query()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('appointment_no');

        if ($latest && preg_match('/APP-(\d+)/', $latest, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        // Pad to at least 5 digits; grows naturally beyond that.
        return 'APP-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ---------------------------------------------------------------------------
    // Create Appointment
    // ---------------------------------------------------------------------------

    /**
     * Create a new appointment with optional examination, treatments, billing,
     * and payment transaction.  All writes happen inside a single DB transaction.
     *
     * @param  array<string, mixed>  $data  Validated data from StoreAppointmentRequest
     * @return Appointment
     */
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            // ── 1. Generate appointment number ─────────────────────────────────
            $appointmentNo = $this->generateAppointmentNo();

            // ── 2. Resolve consultation fee ────────────────────────────────────
            $consultationFee = $this->resolveConsultationFee($data);

            // ── 3. Create the appointment record ───────────────────────────────
            $appointment = Appointment::query()->create([
                'appointment_no'          => $appointmentNo,
                'patient_id'              => $data['patient_id'],
                'doctor_id'               => $data['doctor_id'] ?? null,
                'chair_id'                => $data['chair_id'] ?? null,
                'appointment_type_id'     => $data['appointment_type_id'] ?? null,
                'appointment_date'        => $data['appointment_date'],
                'appointment_time'        => $data['appointment_time'],
                'visit_type'              => $data['visit_type'],
                'chief_complaint'         => $data['chief_complaint'] ?? null,
                'problem_area'            => $data['problem_area'] ?? null,
                'tooth_no'                => $data['tooth_no'] ?? null,
                'priority'                => $data['priority'] ?? 'normal',
                'status'                  => 'scheduled',   // always default on creation
                'previous_appointment_id' => $data['previous_appointment_id'] ?? null,
                'notes'                   => $data['notes'] ?? null,
            ]);

            // ── 4. Examination (optional) ──────────────────────────────────────
            $this->syncExamination($appointment, $data['examination'] ?? null);

            // ── 5. Treatments ──────────────────────────────────────────────────
            $treatmentAmount = $this->createTreatments($appointment, $data['treatments'] ?? []);

            // ── 6. Billing ─────────────────────────────────────────────────────
            $this->createBilling($appointment, $consultationFee, $treatmentAmount, $data);

            // ── 7. Payment transaction (only when actual payment data supplied) ─
            $billing = $appointment->billing()->firstOrFail();
            $this->maybeCreatePaymentTransaction($appointment, $billing, $data['payment'] ?? null);

            return $appointment->load($this->defaultWith());
        });
    }

    // ---------------------------------------------------------------------------
    // Update Appointment
    // ---------------------------------------------------------------------------

    /**
     * Update an existing appointment.  Treatments are replaced (delete + recreate).
     * Billing is recalculated from actual payment transactions.
     * Payment transaction history is never deleted.
     *
     * @param  Appointment            $appointment
     * @param  array<string, mixed>   $data  Validated data from UpdateAppointmentRequest
     * @return Appointment
     */
    public function update(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {

            // ── 1. Update core appointment fields ──────────────────────────────
            $appointment->update([
                'doctor_id'               => $data['doctor_id'] ?? $appointment->doctor_id,
                'chair_id'                => $data['chair_id'] ?? $appointment->chair_id,
                'appointment_type_id'     => $data['appointment_type_id'] ?? $appointment->appointment_type_id,
                'appointment_date'        => $data['appointment_date'] ?? $appointment->appointment_date,
                'appointment_time'        => $data['appointment_time'] ?? $appointment->appointment_time,
                'visit_type'              => $data['visit_type'] ?? $appointment->visit_type,
                'chief_complaint'         => array_key_exists('chief_complaint', $data) ? $data['chief_complaint'] : $appointment->chief_complaint,
                'problem_area'            => array_key_exists('problem_area', $data) ? $data['problem_area'] : $appointment->problem_area,
                'tooth_no'                => array_key_exists('tooth_no', $data) ? $data['tooth_no'] : $appointment->tooth_no,
                'priority'                => $data['priority'] ?? $appointment->priority,
                'status'                  => $data['status'] ?? $appointment->status,
                'notes'                   => array_key_exists('notes', $data) ? $data['notes'] : $appointment->notes,
            ]);

            // ── 2. Examination (optional) ──────────────────────────────────────
            if (array_key_exists('examination', $data)) {
                $this->syncExamination($appointment, $data['examination']);
            }

            // ── 3. Treatments (replace strategy, safe at this stage) ───────────
            if (array_key_exists('treatments', $data)) {
                $appointment->treatments()->delete();
                $treatmentAmount = $this->createTreatments($appointment, $data['treatments'] ?? []);
            } else {
                $treatmentAmount = (float) $appointment->treatments()->sum('total_amount');
            }

            // ── 4. Recalculate billing ─────────────────────────────────────────
            $consultationFee = $this->resolveConsultationFee($data, $appointment);
            $this->recalculateBilling($appointment, $consultationFee, $treatmentAmount, $data);

            return $appointment->fresh()->load($this->defaultWith());
        });
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * Resolve the consultation fee to use.
     * Priority: explicit value in data → doctor's default → 0.
     */
    private function resolveConsultationFee(array $data, ?Appointment $appointment = null): float
    {
        if (isset($data['consultation_fee']) && is_numeric($data['consultation_fee'])) {
            return (float) $data['consultation_fee'];
        }

        // Try from doctor_id in data, then from existing appointment
        $doctorId = $data['doctor_id'] ?? $appointment?->doctor_id;

        if ($doctorId) {
            $fee = Doctor::query()->where('id', $doctorId)->value('consultation_fee');
            if ($fee !== null) {
                return (float) $fee;
            }
        }

        return 0.0;
    }

    /**
     * Create or update the appointment examination record.
     * Skips creation when $examinationData is null or empty.
     *
     * @param  Appointment       $appointment
     * @param  array|null        $examinationData
     */
    private function syncExamination(Appointment $appointment, ?array $examinationData): void
    {
        if (empty($examinationData)) {
            return;
        }

        $fields = array_intersect_key($examinationData, array_flip([
            'symptoms',
            'diagnosis',
            'observations',
            'doctor_notes',
        ]));

        if (empty(array_filter($fields, fn ($v) => $v !== null && $v !== ''))) {
            return;
        }

        $appointment->examination()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            $fields
        );
    }

    /**
     * Create appointment treatment rows and return the calculated treatment_amount sum.
     *
     * Backend always calculates:   total_amount = quantity × unit_price
     * If unit_price is not supplied, uses Treatment.default_price.
     *
     * @param  Appointment       $appointment
     * @param  array             $treatments
     * @return float
     */
    private function createTreatments(Appointment $appointment, array $treatments): float
    {
        $treatmentAmount = 0.0;

        if (empty($treatments)) {
            return $treatmentAmount;
        }

        // Pre-load treatment records to avoid N+1 during unit_price resolution.
        $treatmentIds    = array_column($treatments, 'treatment_id');
        $treatmentModels = Treatment::query()
            ->whereIn('id', $treatmentIds)
            ->get(['id', 'default_price'])
            ->keyBy('id');

        foreach ($treatments as $row) {
            $treatmentId = $row['treatment_id'];
            $quantity    = max(1, (int) ($row['quantity'] ?? 1));

            // Resolve unit_price: use supplied value if present, else default_price
            if (isset($row['unit_price']) && is_numeric($row['unit_price']) && (float) $row['unit_price'] > 0) {
                $unitPrice = (float) $row['unit_price'];
            } else {
                $unitPrice = isset($treatmentModels[$treatmentId])
                    ? (float) $treatmentModels[$treatmentId]->default_price
                    : 0.0;
            }

            // Backend calculates total — never trust frontend total_amount
            $totalAmount = round($quantity * $unitPrice, 2);
            $treatmentAmount += $totalAmount;

            AppointmentTreatment::query()->create([
                'appointment_id' => $appointment->id,
                'treatment_id'   => $treatmentId,
                'tooth_no'       => $row['tooth_no'] ?? null,
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'total_amount'   => $totalAmount,
                'status'         => $row['status'] ?? 'planned',
                'remarks'        => $row['remarks'] ?? null,
            ]);
        }

        return round($treatmentAmount, 2);
    }

    /**
     * Create the initial billing record for a new appointment.
     *
     * @param  Appointment  $appointment
     * @param  float        $consultationFee
     * @param  float        $treatmentAmount
     * @param  array        $data
     */
    private function createBilling(
        Appointment $appointment,
        float $consultationFee,
        float $treatmentAmount,
        array $data
    ): void {
        $discount   = (float) ($data['billing']['discount'] ?? 0);
        $grandTotal = max(0, $consultationFee + $treatmentAmount - $discount);
        $paidAmount = 0.0;  // New appointments start with paid_amount = 0

        AppointmentBilling::query()->create([
            'appointment_id'   => $appointment->id,
            'consultation_fee' => $consultationFee,
            'treatment_amount' => $treatmentAmount,
            'discount'         => $discount,
            'grand_total'      => $grandTotal,
            'paid_amount'      => $paidAmount,
            'balance_amount'   => $grandTotal,
            'payment_status'   => 'unpaid',
            'remarks'          => $data['billing']['remarks'] ?? null,
        ]);
    }

    /**
     * Recalculate billing for an existing appointment.
     * paid_amount is derived from SUM of payment transactions (source of truth).
     *
     * @param  Appointment  $appointment
     * @param  float        $consultationFee
     * @param  float        $treatmentAmount
     * @param  array        $data
     */
    private function recalculateBilling(
        Appointment $appointment,
        float $consultationFee,
        float $treatmentAmount,
        array $data
    ): void {
        $discount   = isset($data['billing']['discount'])
            ? (float) $data['billing']['discount']
            : (float) ($appointment->billing?->discount ?? 0);

        $grandTotal = max(0, $consultationFee + $treatmentAmount - $discount);

        // Derive paid_amount from actual payment transaction records (source of truth)
        $paidAmount = (float) PaymentTransaction::query()
            ->where('appointment_id', $appointment->id)
            ->sum('amount');

        $paidAmount    = max(0, $paidAmount);
        $balanceAmount = max(0, $grandTotal - $paidAmount);
        $paymentStatus = $this->derivePaymentStatus($paidAmount, $grandTotal);

        $billingData = [
            'consultation_fee' => $consultationFee,
            'treatment_amount' => $treatmentAmount,
            'discount'         => $discount,
            'grand_total'      => $grandTotal,
            'paid_amount'      => $paidAmount,
            'balance_amount'   => $balanceAmount,
            'payment_status'   => $paymentStatus,
        ];

        if (isset($data['billing']['remarks'])) {
            $billingData['remarks'] = $data['billing']['remarks'];
        }

        AppointmentBilling::query()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            $billingData
        );
    }

    /**
     * Derive the payment status from paid_amount vs grand_total.
     */
    private function derivePaymentStatus(float $paidAmount, float $grandTotal): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $grandTotal) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * Create a payment transaction only when payment data is explicitly supplied
     * and amount > 0.  Never creates a transaction for paid_amount = 0.
     *
     * @param  Appointment        $appointment
     * @param  AppointmentBilling $billing
     * @param  array|null         $paymentData
     */
    private function maybeCreatePaymentTransaction(
        Appointment $appointment,
        AppointmentBilling $billing,
        ?array $paymentData
    ): void {
        if (empty($paymentData)) {
            return;
        }

        $amount = (float) ($paymentData['amount'] ?? 0);

        if ($amount <= 0) {
            return;
        }

        // Validate that payment does not exceed grand total
        if ($amount > (float) $billing->grand_total) {
            throw new InvalidArgumentException(
                'Payment amount (' . $amount . ') cannot exceed the grand total (' . $billing->grand_total . ').'
            );
        }

        PaymentTransaction::query()->create([
            'appointment_id'        => $appointment->id,
            'billing_id'            => $billing->id,
            'payment_date'          => $paymentData['payment_date'] ?? now()->toDateString(),
            'amount'                => $amount,
            'payment_mode'          => $paymentData['payment_mode'],
            'transaction_reference' => $paymentData['transaction_reference'] ?? null,
            'remarks'               => $paymentData['remarks'] ?? null,
        ]);

        // Refresh billing paid_amount from transaction records
        $paidAmount = (float) PaymentTransaction::query()
            ->where('appointment_id', $appointment->id)
            ->sum('amount');

        $grandTotal    = (float) $billing->grand_total;
        $balanceAmount = max(0, $grandTotal - $paidAmount);

        $billing->update([
            'paid_amount'    => $paidAmount,
            'balance_amount' => $balanceAmount,
            'payment_status' => $this->derivePaymentStatus($paidAmount, $grandTotal),
        ]);
    }

    // ---------------------------------------------------------------------------
    // Appointment Master — List (Step 6)
    // ---------------------------------------------------------------------------

    /**
     * Return a paginated, filtered, and sorted list of appointments for the
     * Appointment Master screen.
     *
     * Supported $filters keys (all optional):
     *   search, date, date_from, date_to, doctor_id, chair_id,
     *   appointment_type_id, visit_type, priority, status, payment_status,
     *   sort_by, sort_order, per_page, page
     *
     * @param  array<string, mixed>  $filters  Validated data from AppointmentIndexRequest
     * @return LengthAwarePaginator
     */
    public function getAppointments(array $filters): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,mobile,gender',
                'doctor:id,doctor_code,first_name,last_name',
                'chair:id,chair_name,chair_number',
                'appointmentType:id,name',
                'billing:id,appointment_id,consultation_fee,treatment_amount,discount,grand_total,paid_amount,balance_amount,payment_status,remarks',
            ]);

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyDateFilter($query, $filters);
        $this->applyRelationshipFilters($query, $filters);
        $this->applyStatusFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $perPage = $perPage < 1 ? 20 : $perPage;
        $page    = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    // ---------------------------------------------------------------------------
    // Appointment Master — Detail (Step 6)
    // ---------------------------------------------------------------------------

    /**
     * Load the complete appointment with all detail relationships.
     *
     * Previous appointment is loaded shallowly (no recursive nesting).
     * Follow-up appointments are loaded shallowly.
     *
     * @param  Appointment  $appointment
     * @return Appointment
     */
    public function getAppointmentDetails(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'patient:id,patient_code,first_name,middle_name,last_name,mobile,gender,date_of_birth,email,address,city,state',
            'doctor:id,doctor_code,first_name,last_name,specialization,consultation_fee',
            'chair:id,chair_name,chair_number',
            'appointmentType:id,name,description',
            'examination',
            'treatments.treatment:id,name,description,default_price',
            'billing',
            'paymentTransactions',
            'appointmentNotes',
            'prescriptions',
            // Shallow: only the summary fields, no further nesting
            'previousAppointment:id,appointment_no,appointment_date,status',
            'followUpAppointments:id,appointment_no,appointment_date,status,previous_appointment_id',
        ]);
    }


    // ---------------------------------------------------------------------------
    // Private query helpers (Step 6)
    // ---------------------------------------------------------------------------

    /**
     * Apply full-text search across appointment_no, patient fields.
     *
     * Correct grouping ensures:
     *   (status = X) AND (search conditions…)
     *
     * rather than polluting AND conditions with OR.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search || trim($search) === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('appointment_no', 'like', $like)
              ->orWhereHas('patient', function (Builder $pq) use ($like) {
                  $pq->where('patient_code', 'like', $like)
                     ->orWhere('first_name',  'like', $like)
                     ->orWhere('middle_name', 'like', $like)
                     ->orWhere('last_name',   'like', $like)
                     ->orWhere('mobile',      'like', $like);
              });
        });
    }

    /**
     * Apply date / date-range filters.
     *
     * ?date=Y-m-d              → exact date match
     * ?date_from=Y-m-d         → from date (inclusive)
     * ?date_to=Y-m-d           → to date (inclusive)
     * Both from+to together    → range
     */
    private function applyDateFilter(Builder $query, array $filters): void
    {
        if ($date = $filters['date'] ?? null) {
            $query->whereDate('appointment_date', $date);
            return; // ?date takes precedence over from/to
        }

        if ($from = $filters['date_from'] ?? null) {
            $query->whereDate('appointment_date', '>=', $from);
        }

        if ($to = $filters['date_to'] ?? null) {
            $query->whereDate('appointment_date', '<=', $to);
        }
    }

    /**
     * Apply filters for doctor, chair, appointment type, visit type, priority.
     */
    private function applyRelationshipFilters(Builder $query, array $filters): void
    {
        if ($doctorId = $filters['doctor_id'] ?? null) {
            $query->where('doctor_id', $doctorId);
        }

        if ($chairId = $filters['chair_id'] ?? null) {
            $query->where('chair_id', $chairId);
        }

        if ($typeId = $filters['appointment_type_id'] ?? null) {
            $query->where('appointment_type_id', $typeId);
        }

        if ($visitType = $filters['visit_type'] ?? null) {
            $query->where('visit_type', $visitType);
        }

        if ($priority = $filters['priority'] ?? null) {
            $query->where('priority', $priority);
        }
    }

    /**
     * Apply appointment status and payment status filters.
     *
     * These are intentionally separated:
     *   - status lives on dental_appointments
     *   - payment_status lives on dental_appointment_billings
     *
     * They are completely independent — a completed appointment can be unpaid.
     */
    private function applyStatusFilters(Builder $query, array $filters): void
    {
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $filters['payment_status'] ?? null) {
            $query->whereHas('billing', function (Builder $bq) use ($paymentStatus) {
                $bq->where('payment_status', $paymentStatus);
            });
        }
    }

    /**
     * Apply sorting with a strict whitelist to prevent column injection.
     *
     * Default: appointment_date DESC, appointment_time DESC
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $allowed = [
            'appointment_no',
            'appointment_date',
            'appointment_time',
            'created_at',
            'updated_at',
        ];

        $sortBy    = in_array($filters['sort_by'] ?? '', $allowed, true)
            ? $filters['sort_by']
            : 'appointment_date';

        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Secondary sort for deterministic ordering when primary values are equal
        if ($sortBy !== 'appointment_time') {
            $query->orderBy('appointment_time', $sortOrder);
        }
    }

    // ---------------------------------------------------------------------------
    // Existing helpers — preserved untouched
    // ---------------------------------------------------------------------------

    /**
     * Default eager-load relationships for controller responses.
     *
     * @return array<string>
     */
    public function defaultWith(): array
    {
        return [
            'patient',
            'doctor',
            'chair',
            'appointmentType',
            'examination',
            'treatments.treatment',
            'billing',
            'paymentTransactions',
            'appointmentNotes',
            'prescriptions',
            'previousAppointment',
        ];
    }
}
