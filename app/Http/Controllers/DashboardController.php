<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\DashboardMetricsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected DashboardMetricsService $dashboardMetricsService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => $this->dashboardMetricsService->forUser(request()->user()),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'city']),
        ]);
    }
}
