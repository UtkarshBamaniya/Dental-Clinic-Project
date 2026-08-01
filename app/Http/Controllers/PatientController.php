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
        $fromDate = request('from_date');
        $toDate   = request('to_date');

        $query = Patient::query()->with('branch')->latest();

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return Inertia::render('Patients/Index', [
            'patients' => $query->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ],
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

    public function update(Patient $patient)
    {
        $validated = request()->validate([
            'branch_id'     => ['required', 'exists:branches,id'],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'gender'        => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'blood_group'   => ['nullable', 'string', 'max:10'],
            'address'       => ['nullable', 'string'],
            'allergies'     => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')->with('success', 'Patient updated.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Patient deleted.');
    }
}
