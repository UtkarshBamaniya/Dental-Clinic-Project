<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * BranchMasterController (LEGACY)
 * The branches table has been removed from the architecture.
 * This controller is neutralized to prevent crashes while the routes remain.
 */
class BranchMasterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Masters/Branches/Index', [
            'branches' => [],
        ]);
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('masters.branches.index')->with('success', 'Branches are no longer supported.');
    }

    public function update($id): RedirectResponse
    {
        return redirect()->route('masters.branches.index')->with('success', 'Branches are no longer supported.');
    }

    public function destroy($id): RedirectResponse
    {
        return redirect()->route('masters.branches.index')->with('success', 'Branches are no longer supported.');
    }
}

