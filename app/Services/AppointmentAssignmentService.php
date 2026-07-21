<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use Carbon\Carbon;

class AppointmentAssignmentService
{
    public function assign(int $branchId, string $specialty, string $appointmentDate, string $startTime): ?DoctorProfile
    {
        $dayOfWeek = Carbon::parse($appointmentDate)->dayOfWeek;

        return DoctorProfile::query()
            ->where('branch_id', $branchId)
            ->where('is_available', true)
            ->where('specialty', $specialty)
            ->whereHas('schedules', function ($query) use ($dayOfWeek, $startTime) {
                $query
                    ->where('day_of_week', $dayOfWeek)
                    ->where('start_time', '<=', $startTime)
                    ->where('end_time', '>=', $startTime);
            })
            ->withCount([
                'appointments as appointment_load' => function ($query) use ($appointmentDate) {
                    $query->whereDate('appointment_date', $appointmentDate);
                },
            ])
            ->orderBy('appointment_load')
            ->orderBy('user_id')
            ->first();
    }

    public function nextToken(int $branchId, string $appointmentDate): int
    {
        $latest = Appointment::query()
            ->where('branch_id', $branchId)
            ->whereDate('appointment_date', $appointmentDate)
            ->max('token_no');

        return ((int) $latest) + 1;
    }
}
