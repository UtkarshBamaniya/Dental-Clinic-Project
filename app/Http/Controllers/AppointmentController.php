<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\AppointmentType;
use App\Models\Chair;
use App\Models\Doctor;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
    ) {
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    /**
     * Return the Appointment Master list.
     *
     * Dual response:
     *   – JSON (wantsJson)  → paginated appointment data for AJAX / DataTable
     *   – Inertia           → full page render with reference data
     */
    public function index()
    {
        if (request()->wantsJson()) {
            return response()->json($this->buildIndexQuery()->paginate(
                perPage: (int) (request('size', 50)),
                page:    (int) (request('page', 1))
            ));
        }

        return Inertia::render('Appointments/Index', [
            'title'            => 'Appointments',
            'desc'             => 'Manage appointment bookings and schedules',
            'routeName'        => 'appointments',
            'patients'         => Patient::query()
                ->orderBy('first_name')
                ->get(['id', 'patient_code', 'first_name', 'last_name', 'mobile']),
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
                ->get(['id', 'name', 'description']),
            'filters'          => request()->only(['from_date', 'to_date', 'status', 'doctor_id', 'search']),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Create a new appointment.
     *
     * Delegates all creation logic (appointment number, examination, treatments,
     * billing, payment transaction) to AppointmentService.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $appointment = $this->appointmentService->create($request->validated());

        if (request()->wantsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Appointment created successfully.',
                'appointment' => $appointment,
            ], 201);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment ' . $appointment->appointment_no . ' created successfully.');
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Return the complete appointment detail.
     *
     * Loads all relationships required by the Appointment Detail Drawer.
     */
    public function show(Appointment $appointment)
    {
        $appointment->load([
            'patient',
            'doctor',
            'chair',
            'appointmentType',
            'examination',
            'treatments.treatment',
            'billing',
            'paymentTransactions',
            'notes',
            'prescriptions',
            'previousAppointment',
            'followUpAppointments',
        ]);

        if (request()->wantsJson()) {
            return response()->json($appointment);
        }

        return Inertia::render('Appointments/Index', compact('appointment'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    /**
     * Update an existing appointment.
     *
     * Delegates all update logic (treatment recalculation, billing recalculation
     * from payment transactions) to AppointmentService.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment = $this->appointmentService->update($appointment, $request->validated());

        if (request()->wantsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Appointment updated successfully.',
                'appointment' => $appointment,
            ]);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment ' . $appointment->appointment_no . ' updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Public booking (pre-existing functionality — not part of dental_* step 4)
    // -------------------------------------------------------------------------

    public function publicCreate(): Response
    {
        return Inertia::render('Public/BookAppointment', [
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function publicStore()
    {
        $validated = request()->validate([
            'patient_name'     => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'specialty'        => ['required', 'string', 'max:100'],
            'treatment_name'   => ['required', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        // Public booking creates a walk-in patient inquiry; handled outside dental_* appointment flow
        return redirect()->route('public.booking')->with('success', 'Your appointment request has been submitted successfully.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build the index query with all needed eager-loaded relationships for the
     * Appointment Master list view.  Avoids N+1 queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildIndexQuery()
    {
        $query = Appointment::query()
            ->with([
                'patient:id,patient_code,first_name,last_name,mobile',
                'doctor:id,doctor_code,first_name,last_name,specialization',
                'chair:id,chair_name,chair_number',
                'appointmentType:id,name',
                'examination:id,appointment_id,symptoms,diagnosis',
                'treatments.treatment:id,name,default_price',
                'billing',
                'paymentTransactions',
                'notes',
                'prescriptions',
                'previousAppointment:id,appointment_no,appointment_date',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time');

        // Filters
        if ($search = request('search')) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('appointment_no', 'like', $like)
                  ->orWhere('chief_complaint', 'like', $like)
                  ->orWhereHas('patient', fn ($pq) => $pq->where('first_name', 'like', $like)
                      ->orWhere('last_name', 'like', $like)
                      ->orWhere('mobile', 'like', $like));
            });
        }

        if ($fromDate = request('from_date')) {
            $query->whereDate('appointment_date', '>=', $fromDate);
        }

        if ($toDate = request('to_date')) {
            $query->whereDate('appointment_date', '<=', $toDate);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($doctorId = request('doctor_id')) {
            $query->where('doctor_id', $doctorId);
        }

        return $query;
    }
}
