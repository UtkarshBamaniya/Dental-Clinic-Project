<?php

namespace App\Repositories;

use App\Models\Patient;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientRepo
{
    /**
     * Paginated, filtered, and sorted patient list for the DataTable AJAX response.
     * Mirrors the AreaMasterRepo::index() pattern from the reference project.
     */
    public function index(array $input): LengthAwarePaginator
    {
        $query = Patient::with('branch:id,name');

        // ── Text search filters (column-level) ───────────────────────────────
        if (!empty($input['name'])) {
            $query->where('name', 'like', '%' . $input['name'] . '%');
        }
        if (!empty($input['phone'])) {
            $query->where('phone', 'like', '%' . $input['phone'] . '%');
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
        if (!empty($input['blood_group'])) {
            $query->where('blood_group', 'like', '%' . $input['blood_group'] . '%');
        }
        if (!empty($input['branch_id'])) {
            $query->where('branch_id', $input['branch_id']);
        }

        // ── Date range filters ───────────────────────────────────────────────
        if (!empty($input['from_date'])) {
            $query->whereDate('created_at', '>=', $input['from_date']);
        }
        if (!empty($input['to_date'])) {
            $query->whereDate('created_at', '<=', $input['to_date']);
        }

        // ── Sorting – guard against SQL injection via arbitrary column names ─
        $allowed = [
            'id', 'patient_code', 'name', 'phone', 'email',
            'gender', 'blood_group', 'date_of_birth', 'created_at',
        ];
        $sortField = in_array($input['sortField'] ?? '', $allowed)
            ? $input['sortField']
            : 'id';
        $sortOrder = ($input['sortOrder'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortOrder);

        // ── Pagination ───────────────────────────────────────────────────────
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
            $input['patient_code'] = 'PAT-' . Carbon::now()->format('ymd')
                . '-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT);

            return Patient::create($input);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find a single patient with branch relationship (for show/edit).
     */
    public function find(int $id): ?Patient
    {
        return Patient::with('branch:id,name')->find($id);
    }

    /**
     * Update patient fields by ID.
     */
    public function update(array $input, int $id): bool
    {
        try {
            Patient::findOrFail($id)->update($input);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete a patient by ID.
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