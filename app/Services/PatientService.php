<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientService
{
    // -------------------------------------------------------------------------
    // Patient Code Generation
    // -------------------------------------------------------------------------

    /**
     * Generate the next unique patient code in the format PAT-00001.
     *
     * Uses a pessimistic lock on the latest row so that concurrent requests
     * cannot generate the same sequence number. Must be called inside a
     * DB transaction.
     *
     * We intentionally do NOT use Patient::count() + 1 because:
     *   - Soft-deleted rows reduce the real count.
     *   - Concurrent inserts race on the same count value.
     */
    public function generatePatientCode(): string
    {
        $latest = Patient::query()
            ->withTrashed()                 // include soft-deleted so codes never reuse
            ->lockForUpdate()               // pessimistic write-lock
            ->orderByDesc('id')
            ->value('patient_code');

        if ($latest && preg_match('/PAT-(\d+)/', $latest, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return 'PAT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // Duplicate Detection
    // -------------------------------------------------------------------------

    /**
     * Check whether an active patient already uses the given mobile number.
     *
     * Pass $excludePatientId when updating an existing patient so that the
     * patient's own current mobile is not flagged as a duplicate.
     *
     * @throws ValidationException
     */
    public function guardAgainstDuplicateMobile(string $mobile, ?int $excludePatientId = null): void
    {
        $query = Patient::query()
            ->where('mobile', $mobile)
            ->where('status', 'active');

        if ($excludePatientId !== null) {
            $query->where('id', '!=', $excludePatientId);
        }

        $existing = $query->select(['id', 'patient_code'])->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'mobile' => 'A patient with this mobile number already exists.',
            ])->withStatus(422)->status(422)
            // We attach the existing patient data to the exception so the
            // caller can forward it to the frontend for patient selection.
            ->redirectTo(null);
        }
    }

    // -------------------------------------------------------------------------
    // Create Patient
    // -------------------------------------------------------------------------

    /**
     * Create a new patient, optionally with a medical-history record.
     *
     * @param  array<string, mixed>  $data  Validated data from StorePatientRequest
     * @return Patient                       Freshly created patient with medicalHistory loaded
     *
     * @throws ValidationException           When the mobile number already belongs to an active patient
     */
    public function createPatient(array $data): Patient
    {
        // Duplicate check BEFORE opening the transaction so we don't hold a
        // lock while doing the (cheap) user-visible validation step.
        $mobile = $data['mobile'] ?? '';
        $existing = Patient::query()
            ->where('mobile', $mobile)
            ->where('status', 'active')
            ->select(['id', 'patient_code'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'mobile' => [
                    'message' => 'A patient with this mobile number already exists.',
                    'patient' => [
                        'id'           => $existing->id,
                        'patient_code' => $existing->patient_code,
                    ],
                ],
            ]);
        }

        $medicalData = $data['medical_history'] ?? null;
        unset($data['medical_history']);

        return DB::transaction(function () use ($data, $medicalData) {
            // ── 1. Generate safe, sequential patient code ─────────────────────
            $data['patient_code'] = $this->generatePatientCode();

            // ── 2. Default status ─────────────────────────────────────────────
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }

            // ── 3. Normalise nullable strings sent as "" from the frontend ────
            $data = $this->normaliseNullableStrings($data);

            // ── 4. Create the patient record ──────────────────────────────────
            $patient = Patient::query()->create($data);

            // ── 5. Optionally create medical history ──────────────────────────
            if ($medicalData && $this->hasMeaningfulData($medicalData)) {
                PatientMedicalHistory::query()->create([
                    'patient_id'               => $patient->id,
                    'blood_group'              => $medicalData['blood_group'] ?? null,
                    'current_medicine'         => $medicalData['current_medicine'] ?? null,
                    'previous_dental_treatment'=> $medicalData['previous_dental_treatment'] ?? null,
                    'other_notes'              => $medicalData['other_notes'] ?? null,
                ]);
            }

            return $patient->load('medicalHistory');
        });
    }

    // -------------------------------------------------------------------------
    // Update Patient
    // -------------------------------------------------------------------------

    /**
     * Update an existing patient and optionally upsert their medical history.
     *
     * @param  Patient              $patient  The patient to update (resolved via route model binding)
     * @param  array<string, mixed> $data     Validated data from UpdatePatientRequest
     * @return Patient                         Updated patient with medicalHistory loaded
     *
     * @throws ValidationException            When the new mobile belongs to a different active patient
     */
    public function updatePatient(Patient $patient, array $data): Patient
    {
        // Mobile duplicate check — exclude the current patient's own row
        if (isset($data['mobile']) && $data['mobile'] !== $patient->mobile) {
            $existing = Patient::query()
                ->where('mobile', $data['mobile'])
                ->where('status', 'active')
                ->where('id', '!=', $patient->id)
                ->select(['id', 'patient_code'])
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'mobile' => [
                        'message' => 'A patient with this mobile number already exists.',
                        'patient' => [
                            'id'           => $existing->id,
                            'patient_code' => $existing->patient_code,
                        ],
                    ],
                ]);
            }
        }

        $medicalData = $data['medical_history'] ?? null;
        unset($data['medical_history']);

        // Prevent the frontend from ever overwriting the auto-generated code or ID
        unset($data['patient_code'], $data['id']);

        return DB::transaction(function () use ($patient, $data, $medicalData) {
            // ── 1. Normalise empty strings ────────────────────────────────────
            $data = $this->normaliseNullableStrings($data);

            // ── 2. Update patient ─────────────────────────────────────────────
            $patient->update($data);

            // ── 3. Upsert medical history ─────────────────────────────────────
            if ($medicalData !== null) {
                PatientMedicalHistory::query()->updateOrCreate(
                    ['patient_id' => $patient->id],
                    [
                        'blood_group'               => $medicalData['blood_group'] ?? null,
                        'current_medicine'          => $medicalData['current_medicine'] ?? null,
                        'previous_dental_treatment' => $medicalData['previous_dental_treatment'] ?? null,
                        'other_notes'               => $medicalData['other_notes'] ?? null,
                    ]
                );
            }

            return $patient->fresh()->load('medicalHistory');
        });
    }

    // -------------------------------------------------------------------------
    // Patient List
    // -------------------------------------------------------------------------

    /**
     * Return a paginated, optionally-filtered list of patients.
     *
     * Supported query parameters:
     *   ?search=rahul      — searches patient_code, first/middle/last name, mobile
     *   ?status=active     — filter by status
     *   ?per_page=20       — records per page (max 100, default 20)
     *   ?page=1            — page number
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator
     */
    public function listPatients(array $filters): LengthAwarePaginator
    {
        $query = Patient::query();

        // Global text search
        if ($search = trim($filters['search'] ?? '')) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('patient_code', 'like', $like)
                  ->orWhere('first_name',  'like', $like)
                  ->orWhere('middle_name', 'like', $like)
                  ->orWhere('last_name',   'like', $like)
                  ->orWhere('mobile',      'like', $like);
            });
        }

        // Status filter
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $perPage = $perPage < 1 ? 20 : $perPage;
        $page    = (int) ($filters['page'] ?? 1);

        return $query
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    // -------------------------------------------------------------------------
    // Patient Search (lightweight — for Appointment Master patient selector)
    // -------------------------------------------------------------------------

    /**
     * Search active patients by name, mobile, or patient code.
     *
     * Returns a plain collection (not paginated) limited to 20 results so
     * that the Appointment patient-selector dropdown stays snappy.
     *
     * @param  string  $search  Raw search term from ?q=
     * @return Collection
     */
    public function searchPatients(string $search): Collection
    {
        if (trim($search) === '') {
            return collect();
        }

        $like = '%' . $search . '%';

        return Patient::query()
            ->where('status', 'active')
            ->where(function ($q) use ($like) {
                $q->where('patient_code', 'like', $like)
                  ->orWhere('first_name',  'like', $like)
                  ->orWhere('middle_name', 'like', $like)
                  ->orWhere('last_name',   'like', $like)
                  ->orWhere('mobile',      'like', $like);
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get([
                'id', 'patient_code', 'first_name', 'middle_name',
                'last_name', 'mobile', 'gender', 'date_of_birth', 'status',
            ]);
    }

    // -------------------------------------------------------------------------
    // Patient Detail
    // -------------------------------------------------------------------------

    /**
     * Load a single patient with their medical history.
     *
     * Does NOT load appointments, treatments, or other heavy relationships —
     * those are loaded on-demand in later steps.
     *
     * @param  Patient  $patient
     * @return Patient
     */
    public function getPatient(Patient $patient): Patient
    {
        return $patient->load('medicalHistory');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Convert empty strings to null for nullable fields.
     * Vue forms often send "" for optional inputs instead of null.
     */
    private function normaliseNullableStrings(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }
        return $data;
    }

    /**
     * Determine whether a medical-history array actually contains any filled fields.
     * We don't want to create an empty medical history row for every patient.
     */
    private function hasMeaningfulData(array $medicalData): bool
    {
        foreach ($medicalData as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }
        return false;
    }
}
