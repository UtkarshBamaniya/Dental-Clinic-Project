<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\AppointmentRepo;
use Illuminate\Http\Request;

/**
 * Legacy patient appointment controller — kept for route compatibility.
 *
 * Updated to use dental_* architecture:
 *   - Removed AppointmentAssignmentService (doctor_profiles dependency removed)
 *   - AppointmentRepo now queries dental_appointments / dental_patients
 *   - No references to branches, doctor_profiles, or old appointments table
 */
class PatientAppointmentController extends Controller
{
    public function __construct(
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
     * Links previous_appointment_id to patient's latest appointment (dental_appointments).
     */
    public function storeFollowUp(AppointmentRequest $request, Patient $patient)
    {
        $validated = $request->validated();

        // Link to the patient's latest appointment as previous
        $previousAppointment = Appointment::query()
            ->where('patient_id', $patient->id)
            ->latest('appointment_date')
            ->first();

        if ($previousAppointment) {
            $validated['previous_appointment_id'] = $previousAppointment->id;
        }

        $validated['patient_id'] = $patient->id;
        $validated['visit_type'] = $validated['visit_type'] ?? 'Follow-up';

        $this->appointmentRepo->create($validated);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Follow-up appointment created.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Follow-up appointment created.');
    }
}
