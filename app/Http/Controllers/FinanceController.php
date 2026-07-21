<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\AccountingPostingService;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(protected AccountingPostingService $accountingPostingService)
    {
    }

    public function index(): Response
    {
        $payments = Payment::query()->with(['branch', 'patient', 'appointment'])->latest('payment_date')->get();
        $expenses = Expense::query()->with('branch')->latest('expense_date')->get();
        $payrolls = PayrollRecord::query()->with(['branch', 'user'])->latest('salary_month')->get();
        $journals = JournalEntry::query()->with('branch')->orderByDesc('entry_date')->orderByDesc('id')->get();

        $income = (float) $payments->where('status', 'captured')->sum('amount');
        $operatingExpenses = (float) $expenses->sum('amount');
        $payrollExpense = (float) $payrolls->sum('net_salary');
        $ledgerSummary = $journals
            ->groupBy('account_head')
            ->map(function ($entries, $accountHead) {
                return [
                    'account_head' => $accountHead,
                    'debit' => (float) $entries->sum('debit'),
                    'credit' => (float) $entries->sum('credit'),
                    'balance' => (float) $entries->sum('debit') - (float) $entries->sum('credit'),
                ];
            })
            ->sortBy('account_head')
            ->values();

        return Inertia::render('Finance/Index', [
            'payments' => $payments,
            'expenses' => $expenses,
            'payrolls' => $payrolls,
            'journals' => $journals,
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'patients' => Patient::query()->orderBy('name')->get(['id', 'name']),
            'appointments' => Appointment::query()->with('patient')->orderByDesc('appointment_date')->get(['id', 'patient_id', 'appointment_date', 'treatment_name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name', 'monthly_salary']),
            'ledgerSummary' => $ledgerSummary,
            'profitLoss' => [
                'income' => $income,
                'operating_expenses' => $operatingExpenses,
                'payroll_expenses' => $payrollExpense,
                'net_profit' => $income - $operatingExpenses - $payrollExpense,
            ],
        ]);
    }

    public function storePayment()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'method' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'razorpay_order_id' => ['nullable', 'string', 'max:255'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:255'],
            'razorpay_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['invoice_number'] = 'INV-'.str_pad((string) (Payment::count() + 1), 5, '0', STR_PAD_LEFT);

        $payment = Payment::query()->create($validated);
        $this->accountingPostingService->recordPayment($payment);

        return redirect()->route('finance.index')->with('success', 'Payment recorded.');
    }

    public function storeExpense()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'paid_via' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense = Expense::query()->create($validated);
        $this->accountingPostingService->recordExpense($expense);

        return redirect()->route('finance.index')->with('success', 'Expense recorded.');
    }

    public function storePayroll()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'user_id' => ['required', 'exists:users,id'],
            'salary_month' => ['required', 'date'],
            'gross_salary' => ['required', 'numeric'],
            'bonus' => ['nullable', 'numeric'],
            'deductions' => ['nullable', 'numeric'],
            'payment_status' => ['required', 'string', 'max:50'],
            'paid_on' => ['nullable', 'date'],
        ]);

        $validated['bonus'] = $validated['bonus'] ?? 0;
        $validated['deductions'] = $validated['deductions'] ?? 0;
        $validated['net_salary'] = $validated['gross_salary'] + $validated['bonus'] - $validated['deductions'];

        $payroll = PayrollRecord::query()->create($validated);
        $this->accountingPostingService->recordPayroll($payroll);

        return redirect()->route('finance.index')->with('success', 'Payroll processed.');
    }
}
