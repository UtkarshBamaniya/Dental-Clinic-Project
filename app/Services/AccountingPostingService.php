<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PayrollRecord;

class AccountingPostingService
{
    public function recordPayment(Payment $payment): void
    {
        $this->createBalancedEntry(
            branchId: $payment->branch_id,
            date: $payment->payment_date->format('Y-m-d'),
            referenceType: 'payment',
            referenceId: $payment->id,
            narration: "Receipt {$payment->invoice_number}",
            debitHead: $payment->method === 'razorpay' ? 'Bank - Razorpay' : 'Cash / Bank',
            creditHead: 'Patient Receivables',
            amount: (float) $payment->amount,
        );
    }

    public function recordExpense(Expense $expense): void
    {
        $this->createBalancedEntry(
            branchId: $expense->branch_id,
            date: $expense->expense_date->format('Y-m-d'),
            referenceType: 'expense',
            referenceId: $expense->id,
            narration: $expense->title,
            debitHead: "Expense - {$expense->category}",
            creditHead: $expense->paid_via === 'cash' ? 'Cash' : 'Bank',
            amount: (float) $expense->amount,
        );
    }

    public function recordPayroll(PayrollRecord $payrollRecord): void
    {
        $creditHead = $payrollRecord->payment_status === 'paid' ? 'Bank' : 'Salary Payable';

        $this->createBalancedEntry(
            branchId: $payrollRecord->branch_id,
            date: optional($payrollRecord->paid_on)->format('Y-m-d') ?? now()->toDateString(),
            referenceType: 'payroll',
            referenceId: $payrollRecord->id,
            narration: "Payroll {$payrollRecord->salary_month->format('F Y')}",
            debitHead: 'Salary Expense',
            creditHead: $creditHead,
            amount: (float) $payrollRecord->net_salary,
        );
    }

    protected function createBalancedEntry(
        int $branchId,
        string $date,
        string $referenceType,
        int $referenceId,
        string $narration,
        string $debitHead,
        string $creditHead,
        float $amount,
    ): void {
        JournalEntry::query()->create([
            'branch_id' => $branchId,
            'entry_date' => $date,
            'account_head' => $debitHead,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'narration' => $narration,
            'debit' => $amount,
            'credit' => 0,
        ]);

        JournalEntry::query()->create([
            'branch_id' => $branchId,
            'entry_date' => $date,
            'account_head' => $creditHead,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'narration' => $narration,
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
