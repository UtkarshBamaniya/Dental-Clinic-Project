<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Services\AppointmentAssignmentService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AppointmentRepo
{
    public function __construct(protected AppointmentAssignmentService $assignmentService)
    {
    }

    public function index(array $input): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with(['branch:id,name', 'patient:id,name,phone,email', 'doctorProfile.user:id,name', 'bookedBy:id,name']);

        if (!empty($input['search'])) {
            $search = $input['search'];
            $query->where(function ($query) use ($search) {
                $query->where('treatment_name', 'like', '%' . $search . '%')
                    ->orWhere('specialty', 'like', '%' . $search . '%')
                    ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('doctorProfile.user', fn ($doctorQuery) => $doctorQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if (!empty($input['appointment_date'])) {
            $query->whereDate('appointment_date', $input['appointment_date']);
        }

        if (!empty($input['patient'])) {
            $query->whereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', '%' . $input['patient'] . '%'));
        }

        if (!empty($input['treatment_name'])) {
            $query->where('treatment_name', 'like', '%' . $input['treatment_name'] . '%');
        }

        if (!empty($input['doctor'])) {
            $query->whereHas('doctorProfile.user', fn ($doctorQuery) => $doctorQuery->where('name', 'like', '%' . $input['doctor'] . '%'));
        }

        if (!empty($input['branch_id'])) {
            $query->where('branch_id', $input['branch_id']);
        }

        if (!empty($input['status'])) {
            $query->where('status', $input['status']);
        }

        if (!empty($input['token_no'])) {
            $query->where('token_no', $input['token_no']);
        }

        if (!empty($input['specialty'])) {
            $query->where('specialty', $input['specialty']);
        }

        if (!empty($input['from_date'])) {
            $query->whereDate('appointment_date', '>=', $input['from_date']);
        }

        if (!empty($input['to_date'])) {
            $query->whereDate('appointment_date', '<=', $input['to_date']);
        }

        $allowedSorts = [
            'id',
            'appointment_date',
            'start_time',
            'treatment_name',
            'specialty',
            'status',
            'token_no',
            'paid_amount',
            'estimated_amount',
            'created_at',
        ];
        $sortField = in_array($input['sortField'] ?? '', $allowedSorts, true)
            ? $input['sortField']
            : 'appointment_date';
        $sortOrder = ($input['sortOrder'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortOrder);

        $size = isset($input['size']) && is_numeric($input['size']) ? (int) $input['size'] : 50;
        $page = isset($input['page']) && is_numeric($input['page']) ? (int) $input['page'] : 1;

        return $query->paginate($size, ['*'], 'page', $page);
    }

    public function create(array $input): Appointment
    {
        if (empty($input['patient_id'])) {
            $input['patient_id'] = Patient::query()->create([
                'branch_id' => $input['branch_id'],
                'patient_code' => 'PAT-' . Carbon::now()->format('ymd') . '-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
                'name' => $input['patient_name'] ?? 'Walk-in Patient',
                'phone' => $input['phone'] ?? '',
                'email' => $input['email'] ?? null,
                'gender' => 'Other',
                'notes' => $input['notes'] ?? null,
            ])->id;
        }

        if (empty($input['doctor_profile_id'])) {
            $doctor = $this->assignmentService->assign(
                branchId: (int) $input['branch_id'],
                specialty: $input['specialty'],
                appointmentDate: $input['appointment_date'],
                startTime: $input['start_time'],
            );

            $input['doctor_profile_id'] = $doctor?->id;
        }

        $input['booked_by'] = request()->user()->id;
        $input['token_no'] = $this->assignmentService->nextToken(
            branchId: (int) $input['branch_id'],
            appointmentDate: $input['appointment_date'],
        );

        $appointment = Appointment::query()->create(collect($input)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
        ])->all());

        if (!empty($input['inquiry_id'])) {
            Inquiry::query()->whereKey($input['inquiry_id'])->update([
                'patient_id' => $input['patient_id'],
                'status' => 'converted',
            ]);
        }

        return $appointment;
    }

    public function find(int $id): ?Appointment
    {
        return Appointment::query()
            ->with(['branch:id,name', 'patient:id,name,phone,email', 'doctorProfile.user:id,name', 'bookedBy:id,name'])
            ->find($id);
    }

    public function update(array $input, int $id): bool
    {
        return Appointment::query()->findOrFail($id)->update(collect($input)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
        ])->all());
    }

    public function destroy(int $id): bool
    {
        return (bool) Appointment::query()->findOrFail($id)->delete();
    }
}
