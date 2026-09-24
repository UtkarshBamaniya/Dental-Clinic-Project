<?php

namespace App\Repositories;

use App\Models\Patient;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Patient repository for dental_patients table.
 *
 * Updated from old patients table (name, phone, branch_id) to new
 * dental_patients table (first_name, last_name, mobile, etc.).
 */
class PatientRepo
{
    /**
     * Paginated, filtered, and sorted patient list.
     */
    public function index(array $input): LengthAwarePaginator
    {
        $query = Patient::with('medicalHistory')
            ->select([
                'dental_patients.*',
                DB::raw("TRIM(CONCAT(first_name, middle_name, last_name)) as full_name"),
                DB::raw("DATE_FORMAT(date_of_birth, '%d-%m-%Y') as dob_formatted")
            ]);

        // Text search filters
        if (!empty($input['first_name'])) {
            $query->where('first_name', 'like', '%' . $input['first_name'] . '%');
        }
        if (!empty($input['last_name'])) {
            $query->where('last_name', 'like', '%' . $input['last_name'] . '%');
        }
        if (!empty($input['name'])) {
            $query->where(function($q) use ($input) {
                $q->where('first_name', 'like', '%' . $input['name'] . '%')
                  ->orWhere('last_name', 'like', '%' . $input['name'] . '%');
            });
        }
        if (!empty($input['mobile'])) {
            $query->where('mobile', 'like', '%' . $input['mobile'] . '%');
        }
        if (!empty($input['patient_code'])) {
            $query->where('patient_code', 'like', '%' . $input['patient_code'] . '%');
        }
        if (!empty($input['email'])) {
            $query->where('email', 'like', '%' . $input['email'] . '%');
        }
        if (!empty($input['gender'])) {
            $query->where('gender', $input['gender']);
        }
        if (!empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (!empty($input['blood_group'])) {
            $query->whereHas('medicalHistory', function($q) use ($input) {
                $q->where('blood_group', 'like', '%' . $input['blood_group'] . '%');
            });
        }

        // Date range filters
        if (!empty($input['from_date'])) {
            $query->whereDate('created_at', '>=', $input['from_date']);
        }
        if (!empty($input['to_date'])) {
            $query->whereDate('created_at', '<=', $input['to_date']);
        }

        // Sorting
        $allowed = [
            'id', 'patient_code', 'first_name', 'last_name', 'full_name', 'mobile', 'email',
            'gender', 'date_of_birth', 'created_at',
        ];
        $sortField = in_array($input['sortField'] ?? '', $allowed)
            ? $input['sortField']
            : 'id';
        $sortOrder = ($input['sortOrder'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortField === 'full_name') {
            $query->orderBy('first_name', $sortOrder)->orderBy('last_name', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $size = isset($input['size']) && is_numeric($input['size']) ? (int) $input['size'] : 50;
        $page = isset($input['page']) && is_numeric($input['page']) ? (int) $input['page'] : 1;

        return $query->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Create a new patient with an auto-generated patient_code.
     */
    public function create(array $input): Patient|false
    {
        try {
            $medicalData = $input['medical_history'] ?? null;
            unset($input['medical_history']);

            $input['patient_code'] = 'PAT-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT);

            $patient = Patient::create($input);

            if ($medicalData) {
                \App\Models\PatientMedicalHistory::create(array_merge(
                    $medicalData,
                    ['patient_id' => $patient->id]
                ));
            }

            return $patient;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find a single patient with relationships.
     */
    public function find(int $id): ?Patient
    {
        return Patient::with(['medicalHistory'])->find($id);
    }

    /**
     * Update patient fields by ID.
     */
    public function update(array $input, int $id): bool
    {
        try {
            $medicalData = $input['medical_history'] ?? null;
            unset($input['medical_history']);

            $patient = Patient::findOrFail($id);
            $patient->update($input);

            if ($medicalData !== null) {
                \App\Models\PatientMedicalHistory::updateOrCreate(
                    ['patient_id' => $patient->id],
                    [
                        'blood_group'               => $medicalData['blood_group'] ?? null,
                        'current_medicine'          => $medicalData['current_medicine'] ?? null,
                        'previous_dental_treatment' => $medicalData['previous_dental_treatment'] ?? null,
                        'other_notes'               => $medicalData['other_notes'] ?? null,
                    ]
                );
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Soft-delete a patient by ID.
     */
    public function destroy(int $id): bool
    {
        try {
            Patient::findOrFail($id)->delete();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}