<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchMasterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Masters/Branches/Index', [
            'branches' => Branch::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
        ]);

        Branch::query()->create($validated);

        return redirect()->route('masters.branches.index')->with('success', 'Branch created.');
    }

    public function update(Branch $branch): RedirectResponse
    {
        $validated = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
        ]);

        $branch->update($validated);

        return redirect()->route('masters.branches.index')->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->route('masters.branches.index')->with('success', 'Branch archived.');
    }
}
