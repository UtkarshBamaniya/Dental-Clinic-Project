<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentIndexRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Chair;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Appointment Master controller — Step 6 dental architecture.
 *
 * Thin controller: all query/filter/pagination/business logic
 * lives in AppointmentService.
 *
 * Dual-response pattern:
 *   wantsJson()  → JSON (AJAX / DataTable / API)
 *   default      → Inertia page render
 */
class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated, filtered, sorted Appointment Master list
    // -------------------------------------------------------------------------

    /**
     * GET /appointments
     *
     * Supported query params (all optional, validated by AppointmentIndexRequest):
     *   search, date, date_from, date_to,
     *   doctor_id, chair_id, appointment_type_id,
     *   visit_type, priority, status, payment_status,
     *   sort_by, sort_order, per_page, page
     */
    public function index(AppointmentIndexRequest $request): JsonResponse|Response
    {
        if ($request->wantsJson()) {
            $appointments = $this->appointmentService->getAppointments(
                $request->validated()
            );

            return response()->json([
                'data' => AppointmentResource::collection($appointments->items()),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page'    => $appointments->lastPage(),
                    'per_page'     => $appointments->perPage(),
                    'total'        => $appointments->total(),
                ],
            ]);
        }

        // Inertia render — pass reference data for the filter dropdowns
        return Inertia::render('Appointments/Index', [
            'title'            => 'Appointments',
            'desc'             => 'Manage appointment bookings and schedules',
            'routeName'        => 'appointments',
            'patients'         => Patient::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'patient_code', 'first_name', 'middle_name', 'last_name', 'mobile']),
            'doctors'          => Doctor::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'doctor_code', 'first_name', 'last_name', 'specialization', 'consultation_fee']),
            'chairs'           => Chair::query()
                ->where('status', 'active')
                ->orderBy('chair_number')
                ->get(['id', 'chair_name', 'chair_number']),
            'appointmentTypes' => AppointmentType::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters'          => $request->only([
                'search', 'date', 'date_from', 'date_to',
                'doctor_id', 'chair_id', 'appointment_type_id',
                'visit_type', 'priority', 'status', 'payment_status',
                'sort_by', 'sort_order',
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store — create a new appointment
    // -------------------------------------------------------------------------

    /**
     * POST /appointments
     *
     * All creation logic (appointment number, billing, payment) is delegated
     * to AppointmentService::create().  This method is unchanged from Step 4.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $appointment = $this->appointmentService->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message'     => 'Appointment created successfully.',
                'data'        => new AppointmentResource($appointment),
            ], 201);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment ' . $appointment->appointment_no . ' created successfully.');
    }

    // -------------------------------------------------------------------------
    // Show — full appointment detail
    // -------------------------------------------------------------------------

    /**
     * GET /appointments/{appointment}
     *
     * Loads all detail relationships via AppointmentService::getAppointmentDetails().
     * Previous/follow-up appointments are loaded shallowly — no recursive nesting.
     */
    public function show(Appointment $appointment): JsonResponse|Response
    {
        $appointment = $this->appointmentService->getAppointmentDetails($appointment);

        if (request()->wantsJson()) {
            return response()->json([
                'data' => new AppointmentResource($appointment),
            ]);
        }

        return Inertia::render('Appointments/Index', [
            'appointment' => new AppointmentResource($appointment),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update — update an existing appointment
    // -------------------------------------------------------------------------

    /**
     * PUT /appointments/{appointment}
     *
     * All update logic (billing recalculation, treatment replacement) is
     * delegated to AppointmentService::update().  Unchanged from Step 4.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $appointment = $this->appointmentService->update($appointment, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Appointment updated successfully.',
                'data'    => new AppointmentResource($appointment),
            ]);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment ' . $appointment->appointment_no . ' updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Public booking (pre-existing functionality — kept for route compatibility)
    // -------------------------------------------------------------------------

    public function publicCreate(): Response
    {
        return Inertia::render('Public/BookAppointment', [
            'specialties' => [
                'Orthodontics', 'Root Canal', 'Implants',
                'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry',
            ],
        ]);
    }

    public function publicStore(): \Illuminate\Http\RedirectResponse
    {
        request()->validate([
            'patient_name'     => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'specialty'        => ['required', 'string', 'max:100'],
            'treatment_name'   => ['required', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        return redirect()->route('public.booking')
            ->with('success', 'Your appointment request has been submitted successfully.');
    }
}
