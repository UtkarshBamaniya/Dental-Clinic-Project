<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient controller — Step 5 dental architecture.
 *
 * Thin controller: all business logic (code generation, duplicate
 * detection, medical history, transactions) lives in PatientService.
 *
 * Dual-response pattern:
 *   wantsJson()  → JSON (used by AJAX / Appointment patient-selector)
 *   default      → Inertia page (used by the full patient management UI)
 */
class PatientController extends Controller
{
    public function __construct(
        protected PatientService $patientService,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated patient list
    // -------------------------------------------------------------------------

    /**
     * GET /patients
     *
     * Query params: ?search=&status=&per_page=&page=
     */
    public function index(Request $request): JsonResponse|Response
    {
        if ($request->wantsJson()) {
            $patients = $this->patientService->listPatients($request->only([
                'search', 'status', 'per_page', 'page',
            ]));

            return response()->json([
                'data' => PatientResource::collection($patients->items()),
                'meta' => [
                    'current_page' => $patients->currentPage(),
                    'last_page'    => $patients->lastPage(),
                    'per_page'     => $patients->perPage(),
                    'total'        => $patients->total(),
                ],
            ]);
        }

        return Inertia::render('Patients/Index', [
            'title'     => 'Patients',
            'desc'      => 'Manage patient records',
            'routeName' => 'patients',
            'filters'   => $request->only(['search', 'status']),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store — create a new patient
    // -------------------------------------------------------------------------

    /**
     * POST /patients
     *
     * Returns 201 with the created patient on success.
     * Returns 422 with duplicate-patient info if mobile already exists.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        try {
            $patient = $this->patientService->createPatient($request->validated());
        } catch (ValidationException $e) {
            // Re-throw so Laravel's exception handler returns the correct 422.
            // The errors bag may contain structured data (patient id/code)
            // for the frontend to offer "select existing patient" UX.
            throw $e;
        }

        return response()->json([
            'message' => 'Patient created successfully.',
            'data'    => new PatientResource($patient),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Show — patient detail with medical history
    // -------------------------------------------------------------------------

    /**
     * GET /patients/{patient}
     */
    public function show(Patient $patient): JsonResponse|Response
    {
        $patient = $this->patientService->getPatient($patient);

        if (request()->wantsJson()) {
            return response()->json([
                'data' => new PatientResource($patient),
            ]);
        }

        return Inertia::render('Patients/Show', [
            'patient' => new PatientResource($patient),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update — update patient + optional medical history
    // -------------------------------------------------------------------------

    /**
     * PUT /patients/{patient}
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        try {
            $patient = $this->patientService->updatePatient($patient, $request->validated());
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => 'Patient updated successfully.',
            'data'    => new PatientResource($patient),
        ]);
    }

    // -------------------------------------------------------------------------
    // Search — lightweight endpoint for Appointment Master patient selector
    // -------------------------------------------------------------------------

    /**
     * GET /patients/search?q=rahul
     *
     * Returns up to 20 active patients matching the search term.
     * Does NOT load medicalHistory — kept intentionally lightweight.
     */
    public function search(Request $request): JsonResponse
    {
        $q        = (string) ($request->query('q', ''));
        $patients = $this->patientService->searchPatients($q);

        // Transform manually to include full_name without loading a full Resource
        $data = $patients->map(function (Patient $patient) {
            return [
                'id'           => $patient->id,
                'patient_code' => $patient->patient_code,
                'first_name'   => $patient->first_name,
                'middle_name'  => $patient->middle_name,
                'last_name'    => $patient->last_name,
                'full_name'    => trim(collect([
                    $patient->first_name,
                    $patient->middle_name,
                    $patient->last_name,
                ])->filter()->implode(' ')),
                'mobile'        => $patient->mobile,
                'gender'        => $patient->gender,
                'date_of_birth' => $patient->date_of_birth?->toDateString(),
                'status'        => $patient->status,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
