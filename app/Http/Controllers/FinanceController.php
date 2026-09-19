<?php

namespace App\Http\Controllers;

use App\Services\AccountingPostingService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Finance controller (LEGACY)
 *
 * The legacy finance tables (payments, expenses, payroll_records, journal_entries)
 * have been removed from the database.
 * This controller has been neutralized to prevent crashes.
 */
class FinanceController extends Controller
{
    public function __construct(protected AccountingPostingService $accountingPostingService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Finance/Index', [
            'payments'      => [],
            'expenses'      => [],
            'payrolls'      => [],
            'journals'      => [],
            'staff'         => [],
            'ledgerSummary' => [],
            'profitLoss'    => [
                'income'             => 0,
                'operating_expenses' => 0,
                'payroll_expenses'   => 0,
                'net_profit'         => 0,
            ],
            'filters' => [
                'from_date' => null,
                'to_date'   => null,
            ],
        ]);
    }

    public function storePayment()
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }

    public function storeExpense()
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }

    public function storePayroll()
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }

    public function destroyPayment($payment)
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }

    public function destroyExpense($expense)
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }

    public function destroyPayroll($payroll)
    {
        return redirect()->route('finance.index')->with('success', 'Legacy finance module is disabled.');
    }
}

