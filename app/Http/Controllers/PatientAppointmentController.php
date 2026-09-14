<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\Patient;
use App\Repositories\AppointmentRepo;
use App\Services\AppointmentAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientAppointmentController extends Controller
{
    public function __construct(
        protected AppointmentAssignmentService $assignmentService,
        protected AppointmentRepo $appointmentRepo,
    ) {
    }

    /**
     * Display a listing of appointments for a patient.
     * Falls back to global appointment list if no patient scoped.
     */
    public function index(Request $request)
    {
        $input = $request->all();

        if ($request->wantsJson()) {
            return response()->json($this->appointmentRepo->index($input));
        }

        return redirect()->route('appointments.index');
    }

    public function store(AppointmentRequest $request)
    {
        $this->appointmentRepo->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment booked.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function show(Appointment $patientAppointment)
    {
        $appointment = $this->appointmentRepo->find($patientAppointment->id);

        if (request()->wantsJson()) {
            return response()->json($appointment);
        }

        return redirect()->route('appointments.index');
    }

    public function edit(Appointment $patientAppointment)
    {
        $appointment = $this->appointmentRepo->find($patientAppointment->id);

        if (request()->wantsJson()) {
            return response()->json($appointment);
        }

        return redirect()->route('appointments.index');
    }

    public function update(AppointmentRequest $request, Appointment $patientAppointment)
    {
        $this->appointmentRepo->update($request->validated(), $patientAppointment->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment updated.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $patientAppointment)
    {
        $this->appointmentRepo->destroy($patientAppointment->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment deleted.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }

    /**
     * Store a follow-up appointment for a given patient.
     * Creates a new appointment with parent_appointment_id pointing to the patient's latest appointment.
     */
    public function storeFollowUp(AppointmentRequest $request, Patient $patient)
    {
        $validated = $request->validated();

        // Link to the patient's latest appointment as parent
        $parentAppointment = Appointment::query()
            ->where('patient_id', $patient->id)
            ->latest('appointment_date')
            ->first();

        if ($parentAppointment) {
            $validated['parent_appointment_id'] = $parentAppointment->id;
        }

        $validated['patient_id'] = $patient->id;

        if (empty($validated['doctor_profile_id'])) {
            $doctor = $this->assignmentService->assign(
                branchId:        (int) $validated['branch_id'],
                specialty:       $validated['specialty'],
                appointmentDate: $validated['appointment_date'],
                startTime:       $validated['start_time'],
            );
            $validated['doctor_profile_id'] = $doctor?->id;
        }

        $validated['booked_by'] = $request->user()->id;

        $appointment = Appointment::query()->create(collect($validated)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
            'billing',
        ])->all());

        $billing = $validated['billing'] ?? [];
        AppointmentBilling::query()->create([
            'appointment_id'   => $appointment->id,
            'estimated_amount' => $billing['estimated_amount'] ?? 0,
            'paid_amount'      => $billing['paid_amount'] ?? 0,
            'discount'         => $billing['discount'] ?? 0,
            'payment_status'   => $billing['payment_status'] ?? 'unpaid',
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Follow-up appointment created.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Follow-up appointment created.');
    }
}
