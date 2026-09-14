<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\AppointmentBilling;
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
            ->select([
                'appointments.id',
                'appointments.status',
                'appointments.treatment_name',
                'appointments.specialty',
                'appointments.appointment_type_id',
                'appointments.parent_appointment_id',
                'appointments.start_time',
                'appointments.end_time',
                'appointments.notes',
                'appointments.branch_id',
                'appointments.patient_id',
                'appointments.doctor_profile_id',
                'appointments.created_at',
            ])
            ->selectRaw("DATE_FORMAT(appointments.appointment_date, '%d-%m-%y') AS appointment_date")
            ->selectRaw('patients.name AS patient_name, patients.phone AS patient_phone, patients.email AS patient_email')
            ->selectRaw('users.name AS doctor_name')
            ->selectRaw('branches.name AS branch_name')
            ->selectRaw('appointment_types.name AS appointment_type_name')
            ->selectRaw('COALESCE(appointment_billings.paid_amount, 0) AS paid_amount')
            ->selectRaw('COALESCE(appointment_billings.estimated_amount, 0) AS estimated_amount')
            ->selectRaw('COALESCE(appointment_billings.discount, 0) AS discount')
            ->selectRaw('COALESCE(appointment_billings.payment_status, "unpaid") AS payment_status')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('doctor_profiles', 'doctor_profiles.id', '=', 'appointments.doctor_profile_id')
            ->leftJoin('users', 'users.id', '=', 'doctor_profiles.user_id')
            ->leftJoin('branches', 'branches.id', '=', 'appointments.branch_id')
            ->leftJoin('appointment_types', 'appointment_types.id', '=', 'appointments.appointment_type_id')
            ->leftJoin('appointment_billings', 'appointment_billings.appointment_id', '=', 'appointments.id');

        if (!empty($input['search'])) {
            $search = '%' . $input['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('appointments.treatment_name', 'like', $search)
                    ->orWhere('appointments.specialty', 'like', $search)
                    ->orWhere('patients.name', 'like', $search)
                    ->orWhere('users.name', 'like', $search);
            });
        }

        if (!empty($input['appointment_date'])) {
            $query->whereDate('appointments.appointment_date', $input['appointment_date']);
        }

        if (!empty($input['patient'])) {
            $query->where('patients.name', 'like', '%' . $input['patient'] . '%');
        }

        if (!empty($input['treatment_name'])) {
            $query->where('appointments.treatment_name', 'like', '%' . $input['treatment_name'] . '%');
        }

        if (!empty($input['doctor'])) {
            $query->where('users.name', 'like', '%' . $input['doctor'] . '%');
        }

        if (!empty($input['branch_id'])) {
            $query->where('appointments.branch_id', $input['branch_id']);
        }

        if (!empty($input['status'])) {
            $query->where('appointments.status', $input['status']);
        }

        if (!empty($input['specialty'])) {
            $query->where('appointments.specialty', $input['specialty']);
        }

        if (!empty($input['from_date'])) {
            $query->whereDate('appointments.appointment_date', '>=', $input['from_date']);
        }

        if (!empty($input['to_date'])) {
            $query->whereDate('appointments.appointment_date', '<=', $input['to_date']);
        }

        $allowedSorts = [
            'id'               => 'appointments.id',
            'appointment_date' => 'appointments.appointment_date',
            'start_time'       => 'appointments.start_time',
            'treatment_name'   => 'appointments.treatment_name',
            'specialty'        => 'appointments.specialty',
            'status'           => 'appointments.status',
            'paid_amount'      => 'appointment_billings.paid_amount',
            'estimated_amount' => 'appointment_billings.estimated_amount',
            'created_at'       => 'appointments.created_at',
        ];

        $sortKey   = $input['sortField'] ?? 'appointment_date';
        $sortCol   = $allowedSorts[$sortKey] ?? 'appointments.appointment_date';
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
                'branch_id'    => $input['branch_id'],
                'patient_code' => 'PAT-' . Carbon::now()->format('ymd') . '-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
                'name'         => $input['patient_name'] ?? 'Walk-in Patient',
                'phone'        => $input['phone'] ?? '',
                'email'        => $input['email'] ?? null,
                'gender'       => 'Other',
                'notes'        => $input['notes'] ?? null,
            ])->id;
        }

        if (empty($input['doctor_profile_id'])) {
            $doctor = $this->assignmentService->assign(
                branchId:        (int) $input['branch_id'],
                specialty:       $input['specialty'],
                appointmentDate: $input['appointment_date'],
                startTime:       $input['start_time'],
            );

            $input['doctor_profile_id'] = $doctor?->id;
        }

        $input['booked_by'] = request()->user()->id;

        $appointment = Appointment::query()->create(collect($input)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
            'billing',
        ])->all());

        // Save billing details in separate table
        $billing = $input['billing'] ?? [];
        AppointmentBilling::query()->create([
            'appointment_id'   => $appointment->id,
            'estimated_amount' => $billing['estimated_amount'] ?? 0,
            'paid_amount'      => $billing['paid_amount'] ?? 0,
            'discount'         => $billing['discount'] ?? 0,
            'payment_status'   => $billing['payment_status'] ?? 'unpaid',
        ]);

        if (!empty($input['inquiry_id'])) {
            Inquiry::query()->whereKey($input['inquiry_id'])->update([
                'patient_id' => $input['patient_id'],
                'status'     => 'converted',
            ]);
        }

        return $appointment;
    }

    public function find(int $id): ?Appointment
    {
        return Appointment::query()
            ->with([
                'branch:id,name',
                'patient:id,name,phone,email',
                'doctorProfile.user:id,name',
                'bookedBy:id,name',
                'appointmentType:id,name,color,duration_minutes',
                'billing',
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

        // Update or create billing record
        $billing = $input['billing'] ?? [];
        if (!empty($billing)) {
            AppointmentBilling::query()->updateOrCreate(
                ['appointment_id' => $id],
                [
                    'estimated_amount' => $billing['estimated_amount'] ?? 0,
                    'paid_amount'      => $billing['paid_amount'] ?? 0,
                    'discount'         => $billing['discount'] ?? 0,
                    'payment_status'   => $billing['payment_status'] ?? 'unpaid',
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
