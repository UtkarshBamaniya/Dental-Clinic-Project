<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

/**
 * Provides dashboard metrics for the new dental_* architecture.
 *
 * Legacy dependencies removed:
 *   - Inquiry (inquiries table removed)
 *   - Payment (old payments table removed)
 *   - Expense (expenses table removed)
 *   - PayrollRecord (payroll_records table removed)
 *   - JournalEntry (journal_entries table removed)
 *   - Branch (branches table removed)
 *
 * Appointment / Patient now use dental_appointments / dental_patients.
 */
class DashboardMetricsService
{
    public function forUser(User $user): array
    {
        $appointmentQuery = Appointment::query();
        $patientQuery     = Patient::query();

        $today  = Carbon::today();
        $start  = $today->copy()->subDays(6);
        $chartLabels      = [];
        $appointmentSeries = [];

        for ($cursor = $start->copy(); $cursor <= $today; $cursor->addDay()) {
            $chartLabels[]       = $cursor->format('D');
            $appointmentSeries[] = (clone $appointmentQuery)
                ->whereDate('appointment_date', $cursor)
                ->count();
        }

        return [
            'overview' => [
                ['label' => 'Total Patients',            'value' => $patientQuery->count(),                                              'trend' => '', 'accent' => '#ea580c'],
                ['label' => 'Scheduled Appointments',    'value' => (clone $appointmentQuery)->where('status', 'scheduled')->count(),    'trend' => '', 'accent' => '#0f766e'],
                ['label' => 'Completed Appointments',    'value' => (clone $appointmentQuery)->where('status', 'completed')->count(),   'trend' => '', 'accent' => '#1d4ed8'],
                ['label' => "Today's Appointments",      'value' => (clone $appointmentQuery)->whereDate('appointment_date', $today)->count(), 'trend' => '', 'accent' => '#7c3aed'],
            ],
            'charts' => [
                'labels'       => $chartLabels,
                'appointments' => $appointmentSeries,
            ],
            'statusBreakdown' => [
                'scheduled'  => (clone $appointmentQuery)->where('status', 'scheduled')->count(),
                'completed'  => (clone $appointmentQuery)->where('status', 'completed')->count(),
                'cancelled'  => (clone $appointmentQuery)->where('status', 'cancelled')->count(),
                'no_show'    => (clone $appointmentQuery)->where('status', 'no_show')->count(),
            ],
            'recentAppointments' => (clone $appointmentQuery)
                ->with(['patient', 'doctor'])
                ->latest('appointment_date')
                ->limit(6)
                ->get(),
        ];
    }
}
