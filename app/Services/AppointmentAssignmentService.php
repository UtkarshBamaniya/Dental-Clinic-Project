<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;

/**
 * Appointment assignment helper using the new dental_doctors architecture.
 *
 * NOTE: doctor_profiles and doctor_schedules tables have been removed.
 * This service now operates on dental_doctors (Doctor model).
 * Schedule-based assignment is simplified — the old DoctorSchedule model
 * and its doctor_schedules table no longer exist.
 */
class AppointmentAssignmentService
{
    /**
     * Find the least-loaded available doctor for a given specialization and date.
     * Returns null if no doctor is found (assignment becomes manual).
     */
    public function assign(int $branchId, string $specialization, string $appointmentDate, string $startTime): ?Doctor
    {
        return Doctor::query()
            ->where('status', 'active')
            ->where('specialization', $specialization)
            ->withCount([
                'appointments as appointment_load' => function ($query) use ($appointmentDate) {
                    $query->whereDate('appointment_date', $appointmentDate);
                },
            ])
            ->orderBy('appointment_load')
            ->orderBy('id')
            ->first();
    }

    /**
     * Returns the next sequential token number for appointments on a given date.
     * Uses dental_appointments (appointment_date column).
     */
    public function nextToken(string $appointmentDate): int
    {
        $latest = Appointment::query()
            ->whereDate('appointment_date', $appointmentDate)
            ->max('id');

        return ((int) $latest) + 1;
    }
}
