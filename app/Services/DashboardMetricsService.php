<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Inquiry;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

class DashboardMetricsService
{
    public function forUser(User $user): array
    {
        $branchId = $user->role === 'super_admin' ? null : $user->branch_id;

        $inquiryQuery = Inquiry::query();
        $patientQuery = Patient::query();
        $appointmentQuery = Appointment::query();
        $paymentQuery = Payment::query()->where('status', 'captured');
        $expenseQuery = Expense::query();
        $payrollQuery = PayrollRecord::query();
        $journalQuery = JournalEntry::query();

        foreach ([$inquiryQuery, $patientQuery, $appointmentQuery, $paymentQuery, $expenseQuery, $payrollQuery, $journalQuery] as $query) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }

        $today = Carbon::today();
        $start = $today->copy()->subDays(6);
        $chartLabels = [];
        $appointmentSeries = [];
        $revenueSeries = [];

        for ($cursor = $start->copy(); $cursor <= $today; $cursor->addDay()) {
            $chartLabels[] = $cursor->format('D');
            $appointmentSeries[] = (clone $appointmentQuery)->whereDate('appointment_date', $cursor)->count();
            $revenueSeries[] = (float) (clone $paymentQuery)->whereDate('payment_date', $cursor)->sum('amount');
        }

        $collections = (float) $paymentQuery->sum('amount');
        $expenses = (float) $expenseQuery->sum('amount');
        $payroll = (float) $payrollQuery->sum('net_salary');

        return [
            'overview' => [
                ['label' => 'Total Inquiries', 'value' => $inquiryQuery->count(), 'trend' => '+18%', 'accent' => '#0f766e'],
                ['label' => 'Patients', 'value' => $patientQuery->count(), 'trend' => '+12%', 'accent' => '#ea580c'],
                ['label' => 'Completed Appointments', 'value' => (clone $appointmentQuery)->where('status', 'completed')->count(), 'trend' => '+9%', 'accent' => '#1d4ed8'],
                ['label' => 'Collections', 'value' => 'Rs. '.number_format($collections, 2), 'trend' => '+14%', 'accent' => '#7c3aed'],
            ],
            'finance' => [
                'collections' => $collections,
                'expenses' => $expenses,
                'payroll' => $payroll,
                'profit' => $collections - $expenses - $payroll,
                'journalBalance' => (float) $journalQuery->sum('debit') - (float) $journalQuery->sum('credit'),
            ],
            'charts' => [
                'labels' => $chartLabels,
                'appointments' => $appointmentSeries,
                'revenue' => $revenueSeries,
            ],
            'statusBreakdown' => [
                'booked' => (clone $appointmentQuery)->where('status', 'booked')->count(),
                'confirmed' => (clone $appointmentQuery)->where('status', 'confirmed')->count(),
                'completed' => (clone $appointmentQuery)->where('status', 'completed')->count(),
                'cancelled' => (clone $appointmentQuery)->where('status', 'cancelled')->count(),
                'no_show' => (clone $appointmentQuery)->where('status', 'no_show')->count(),
            ],
            'recentAppointments' => (clone $appointmentQuery)
                ->with(['patient', 'doctorProfile.user', 'branch'])
                ->latest('appointment_date')
                ->limit(6)
                ->get(),
            'recentPayments' => (clone $paymentQuery)
                ->with(['patient', 'appointment', 'branch'])
                ->latest('payment_date')
                ->limit(6)
                ->get(),
        ];
    }
}
