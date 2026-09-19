<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Repositories\PatientRepo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient controller — updated for dental_patients architecture.
 *
 * Removed: Branch dependency (branches table removed).
 */
class PatientController extends Controller
{
    protected PatientRepo $patientRepo;

    public function __construct(PatientRepo $patientRepo)
    {
        $this->patientRepo = $patientRepo;
    }

    /**
     * Display the patient list page.
     * Returns JSON for DataTable AJAX fetches, Inertia page for normal requests.
     */
    public function index()
    {
        $input = request()->all();

        if (request()->wantsJson()) {
            return response()->json($this->patientRepo->index($input));
        }

        return Inertia::render('Patients/Index', [
            'title'     => 'Patients',
            'desc'      => 'Manage patient records – Add / Edit / Delete',
            'routeName' => 'patients',
        ]);
    }

    /**
     * Show create form (handled via dialog in Index page).
     */
    public function create(): Response
    {
        return Inertia::render('Patients/Index');
    }

    /**
     * Store a newly created patient.
     */
    public function store(PatientRequest $request)
    {
        $this->patientRepo->create($request->validated());

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Patient registered successfully.']);
        }

        return back()->with('success', 'Patient registered successfully.');
    }

    /**
     * Return a single patient record.
     */
    public function show(Patient $patient)
    {
        if (request()->wantsJson()) {
            return response()->json($patient);
        }

        return Inertia::render('Patients/Index', compact('patient'));
    }

    /**
     * Load data for edit dialog.
     */
    public function edit(Patient $patient)
    {
        if (request()->wantsJson()) {
            return response()->json($patient);
        }

        return Inertia::render('Patients/Index', compact('patient'));
    }

    /**
     * Update the specified patient.
     */
    public function update(PatientRequest $request, Patient $patient)
    {
        $this->patientRepo->update($request->validated(), $patient->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Patient updated successfully.']);
        }

        return back()->with('success', 'Patient updated successfully.');
    }

    /**
     * Delete the specified patient.
     */
    public function destroy(Patient $patient)
    {
        $this->patientRepo->destroy($patient->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Patient deleted successfully.']);
        }

        return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
    }
}
