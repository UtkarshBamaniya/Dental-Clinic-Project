<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\Patient;
use App\Services\AppointmentAssignmentService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Legacy appointment repository — now updated to use dental_* tables.
 *
 * Tables referenced here:
 *   - dental_appointments (Appointment model)
 *   - dental_appointment_billings (AppointmentBilling model)
 *   - dental_patients (Patient model)
 *
 * Removed dependencies:
 *   - doctor_profiles (DoctorProfile) — use dental_doctors (Doctor) instead
 *   - branches — removed from architecture
 *   - appointments — old table, replaced by dental_appointments
 *   - appointment_types — table is dental_appointment_types
 *
 * NOTE: PatientAppointmentController still uses this repo.
 * The new AppointmentController uses AppointmentService instead.
 * This repo will eventually be replaced, but is kept functional for now.
 */
class AppointmentRepo
{
    public function __construct(protected AppointmentAssignmentService $assignmentService)
    {
    }

    public function index(array $input): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with(['patient', 'doctor', 'appointmentType', 'billing']);

        if (!empty($input['search'])) {
            $search = '%' . $input['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', fn ($p) => $p->where('first_name', 'like', $search)->orWhere('last_name', 'like', $search))
                  ->orWhereHas('doctor', fn ($d) => $d->where('first_name', 'like', $search)->orWhere('last_name', 'like', $search));
            });
        }

        if (!empty($input['appointment_date'])) {
            $query->whereDate('appointment_date', $input['appointment_date']);
        }

        if (!empty($input['from_date'])) {
            $query->whereDate('appointment_date', '>=', $input['from_date']);
        }

        if (!empty($input['to_date'])) {
            $query->whereDate('appointment_date', '<=', $input['to_date']);
        }

        if (!empty($input['status'])) {
            $query->where('status', $input['status']);
        }

        if (!empty($input['doctor_id'])) {
            $query->where('doctor_id', $input['doctor_id']);
        }

        $allowedSorts = [
            'id'               => 'appointment_date',
            'appointment_date' => 'appointment_date',
            'status'           => 'status',
            'created_at'       => 'created_at',
        ];

        $sortKey   = $input['sortField'] ?? 'appointment_date';
        $sortCol   = $allowedSorts[$sortKey] ?? 'appointment_date';
        $sortOrder = ($input['sortOrder'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortCol, $sortOrder);

        $size = isset($input['size']) && is_numeric($input['size']) ? (int) $input['size'] : 50;
        $page = isset($input['page']) && is_numeric($input['page']) ? (int) $input['page'] : 1;

        return $query->paginate($size, ['*'], 'page', $page);
    }

    public function create(array $input): Appointment
    {
        if (empty($input['patient_id'])) {
            $input['patient_id'] = Patient::query()->create([
                'patient_code' => 'PAT-' . Carbon::now()->format('ymd') . '-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
                'first_name'   => $input['patient_name'] ?? 'Walk-in',
                'last_name'    => 'Patient',
                'mobile'       => $input['phone'] ?? '',
                'email'        => $input['email'] ?? null,
                'gender'       => 'Other',
                'status'       => 'active',
            ])->id;
        }

        $input['booked_by'] = request()->user()->id;

        $appointment = Appointment::query()->create(collect($input)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
            'billing',
        ])->all());

        $billing = $input['billing'] ?? [];
        AppointmentBilling::query()->create([
            'appointment_id' => $appointment->id,
            'discount'       => $billing['discount'] ?? 0,
            'paid_amount'    => $billing['paid_amount'] ?? 0,
            'payment_status' => $billing['payment_status'] ?? 'unpaid',
        ]);

        return $appointment;
    }

    public function find(int $id): ?Appointment
    {
        return Appointment::query()
            ->with([
                'patient',
                'doctor.user',
                'appointmentType',
                'billing',
                'examination',
                'treatments',
                'notes',
            ])
            ->find($id);
    }

    public function update(array $input, int $id): bool
    {
        $appointment = Appointment::query()->findOrFail($id);

        $result = $appointment->update(collect($input)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
            'billing',
        ])->all());

        $billing = $input['billing'] ?? [];
        if (!empty($billing)) {
            AppointmentBilling::query()->updateOrCreate(
                ['appointment_id' => $id],
                [
                    'discount'       => $billing['discount'] ?? 0,
                    'paid_amount'    => $billing['paid_amount'] ?? 0,
                    'payment_status' => $billing['payment_status'] ?? 'unpaid',
                ]
            );
        }

        return $result;
    }

    public function destroy(int $id): bool
    {
        return (bool) Appointment::query()->findOrFail($id)->delete();
    }
}
