<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\AppointmentType;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Repositories\AppointmentRepo;
use Carbon\Carbon;
use App\Services\AppointmentAssignmentService;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentAssignmentService $assignmentService,
        protected AppointmentRepo $appointmentRepo,
    ) {
    }

    public function index()
    {
        $input      = request()->all();
        $bookingDraft = null;
        $inquiryId  = request()->integer('inquiry');
        $fromDate   = request('from_date');
        $toDate     = request('to_date');

        if ($inquiryId) {
            $inquiry = Inquiry::query()->with(['patient', 'branch'])->find($inquiryId);

            if ($inquiry) {
                $bookingDraft = [
                    'inquiry_id'     => $inquiry->id,
                    'branch_id'      => $inquiry->branch_id,
                    'patient_id'     => $inquiry->patient_id,
                    'patient_name'   => $inquiry->patient?->name ?? $inquiry->name,
                    'phone'          => $inquiry->patient?->phone ?? $inquiry->phone,
                    'email'          => $inquiry->patient?->email ?? $inquiry->email,
                    'specialty'      => $inquiry->treatment_interest,
                    'treatment_name' => $inquiry->treatment_interest,
                    'notes'          => $inquiry->notes,
                ];
            }
        }

        if (request()->wantsJson()) {
            return response()->json($this->appointmentRepo->index($input));
        }

        return Inertia::render('Appointments/Index', [
            'title'            => 'Appointments',
            'desc'             => 'Manage appointment bookings and schedules',
            'routeName'        => 'appointments',
            'branches'         => Branch::query()->orderBy('name')->get(['id', 'name']),
            'patients'         => Patient::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'doctors'          => DoctorProfile::query()->with('user')->orderBy('specialty')->get(),
            'specialties'      => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
            'appointmentTypes' => AppointmentType::query()->orderBy('name')->get(['id', 'name', 'color', 'duration_minutes']),
            'bookingDraft'     => $bookingDraft,
            'filters'          => [
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ],
        ]);
    }

    public function publicCreate(): Response
    {
        return Inertia::render('Public/BookAppointment', [
            'branches'    => Branch::query()->orderBy('name')->get(['id', 'name', 'city']),
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function store(AppointmentRequest $request)
    {
        $this->appointmentRepo->create($request->validated());

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment booked.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function publicStore()
    {
        $validated = request()->validate([
            'branch_id'        => ['required', 'exists:branches,id'],
            'patient_name'     => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'start_time'       => ['required'],
            'end_time'         => ['required'],
            'specialty'        => ['required', 'string', 'max:100'],
            'treatment_name'   => ['required', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        $patient = Patient::query()->create([
            'branch_id'    => $validated['branch_id'],
            'patient_code' => 'PAT-' . Carbon::now()->format('ymd') . '-' . str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
            'name'         => $validated['patient_name'],
            'phone'        => $validated['phone'],
            'email'        => $validated['email'] ?? null,
            'gender'       => 'Other',
            'notes'        => $validated['notes'] ?? null,
        ]);

        $inquiry = Inquiry::query()->create([
            'branch_id'          => $validated['branch_id'],
            'patient_id'         => $patient->id,
            'name'               => $validated['patient_name'],
            'phone'              => $validated['phone'],
            'email'              => $validated['email'] ?? null,
            'source'             => 'Website',
            'treatment_interest' => $validated['treatment_name'],
            'status'             => 'converted',
            'priority'           => 'warm',
            'notes'              => $validated['notes'] ?? null,
        ]);

        $doctor = $this->assignmentService->assign(
            branchId:        (int) $validated['branch_id'],
            specialty:       $validated['specialty'],
            appointmentDate: $validated['appointment_date'],
            startTime:       $validated['start_time'],
        );

        $appointment = Appointment::query()->create([
            'branch_id'         => $validated['branch_id'],
            'patient_id'        => $patient->id,
            'doctor_profile_id' => $doctor?->id,
            'booked_by'         => null,
            'appointment_date'  => $validated['appointment_date'],
            'start_time'        => $validated['start_time'],
            'end_time'          => $validated['end_time'],
            'specialty'         => $validated['specialty'],
            'treatment_name'    => $validated['treatment_name'],
            'status'            => 'booked',
            'notes'             => trim(($validated['notes'] ?? '') . "\nWebsite inquiry #{$inquiry->id}"),
        ]);

        // Create a zeroed billing record so the appointment always has one
        AppointmentBilling::query()->create([
            'appointment_id'   => $appointment->id,
            'estimated_amount' => 0,
            'paid_amount'      => 0,
            'discount'         => 0,
            'payment_status'   => 'unpaid',
        ]);

        return redirect()->route('public.booking')->with('success', 'Your appointment request has been submitted successfully.');
    }

    public function updateStatus(Appointment $appointment)
    {
        $validated = request()->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);

        $appointment->update($validated);

        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function show(Appointment $appointment)
    {
        $appointment = $this->appointmentRepo->find($appointment->id);

        if (request()->wantsJson()) {
            return response()->json($appointment);
        }

        return Inertia::render('Appointments/Index', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $appointment = $this->appointmentRepo->find($appointment->id);

        if (request()->wantsJson()) {
            return response()->json($appointment);
        }

        return Inertia::render('Appointments/Index', compact('appointment'));
    }

    public function update(AppointmentRequest $request, Appointment $appointment)
    {
        $this->appointmentRepo->update($request->validated(), $appointment->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment updated.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->appointmentRepo->destroy($appointment->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment deleted.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }
}
