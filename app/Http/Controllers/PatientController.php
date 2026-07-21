<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Patient;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Patients/Index', [
            'patients' => Patient::query()->with('branch')->latest()->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['patient_code'] = 'PAT-'.Carbon::now()->format('ymd').'-'.str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT);

        Patient::query()->create($validated);

        return redirect()->route('patients.index')->with('success', 'Patient registered.');
    }
}
