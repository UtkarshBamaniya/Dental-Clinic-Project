<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\Inquiry;
use App\Models\Patient;
use Carbon\Carbon;
use App\Services\AppointmentAssignmentService;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(protected AppointmentAssignmentService $assignmentService)
    {
    }

    public function index(): Response
    {
        $bookingDraft = null;
        $inquiryId = request()->integer('inquiry');

        if ($inquiryId) {
            $inquiry = Inquiry::query()->with(['patient', 'branch'])->find($inquiryId);

            if ($inquiry) {
                $bookingDraft = [
                    'inquiry_id' => $inquiry->id,
                    'branch_id' => $inquiry->branch_id,
                    'patient_id' => $inquiry->patient_id,
                    'patient_name' => $inquiry->patient?->name ?? $inquiry->name,
                    'phone' => $inquiry->patient?->phone ?? $inquiry->phone,
                    'email' => $inquiry->patient?->email ?? $inquiry->email,
                    'specialty' => $inquiry->treatment_interest,
                    'treatment_name' => $inquiry->treatment_interest,
                    'notes' => $inquiry->notes,
                ];
            }
        }

        return Inertia::render('Appointments/Index', [
            'appointments' => Appointment::query()
                ->with(['branch', 'patient', 'doctorProfile.user', 'bookedBy'])
                ->latest('appointment_date')
                ->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'patients' => Patient::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'doctors' => DoctorProfile::query()->with('user')->orderBy('specialty')->get(),
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
            'bookingDraft' => $bookingDraft,
        ]);
    }

    public function publicCreate(): Response
    {
        return Inertia::render('Public/BookAppointment', [
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'city']),
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'doctor_profile_id' => ['nullable', 'exists:doctor_profiles,id'],
            'inquiry_id' => ['nullable', 'exists:inquiries,id'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'specialty' => ['required', 'string', 'max:100'],
            'treatment_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'visit_type' => ['required', 'string', 'max:50'],
            'estimated_amount' => ['nullable', 'numeric'],
            'paid_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($validated['patient_id'])) {
            $validated['patient_id'] = Patient::query()->create([
                'branch_id' => $validated['branch_id'],
                'patient_code' => 'PAT-'.Carbon::now()->format('ymd').'-'.str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
                'name' => $validated['patient_name'] ?? 'Walk-in Patient',
                'phone' => $validated['phone'] ?? '',
                'email' => $validated['email'] ?? null,
                'gender' => 'Other',
                'notes' => $validated['notes'] ?? null,
            ])->id;
        }

        if (empty($validated['doctor_profile_id'])) {
            $doctor = $this->assignmentService->assign(
                branchId: (int) $validated['branch_id'],
                specialty: $validated['specialty'],
                appointmentDate: $validated['appointment_date'],
                startTime: $validated['start_time'],
            );

            $validated['doctor_profile_id'] = $doctor?->id;
        }

        $validated['booked_by'] = request()->user()->id;
        $validated['token_no'] = $this->assignmentService->nextToken(
            branchId: (int) $validated['branch_id'],
            appointmentDate: $validated['appointment_date'],
        );

        Appointment::query()->create(collect($validated)->except([
            'patient_name',
            'phone',
            'email',
            'inquiry_id',
        ])->all());

        if (!empty($validated['inquiry_id'])) {
            Inquiry::query()->whereKey($validated['inquiry_id'])->update([
                'patient_id' => $validated['patient_id'],
                'status' => 'converted',
            ]);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function publicStore()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'specialty' => ['required', 'string', 'max:100'],
            'treatment_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $patient = Patient::query()->create([
            'branch_id' => $validated['branch_id'],
            'patient_code' => 'PAT-'.Carbon::now()->format('ymd').'-'.str_pad((string) (Patient::count() + 1), 3, '0', STR_PAD_LEFT),
            'name' => $validated['patient_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'gender' => 'Other',
            'notes' => $validated['notes'] ?? null,
        ]);

        $inquiry = Inquiry::query()->create([
            'branch_id' => $validated['branch_id'],
            'patient_id' => $patient->id,
            'name' => $validated['patient_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'source' => 'Website',
            'treatment_interest' => $validated['treatment_name'],
            'status' => 'converted',
            'priority' => 'warm',
            'notes' => $validated['notes'] ?? null,
        ]);

        $doctor = $this->assignmentService->assign(
            branchId: (int) $validated['branch_id'],
            specialty: $validated['specialty'],
            appointmentDate: $validated['appointment_date'],
            startTime: $validated['start_time'],
        );

        Appointment::query()->create([
            'branch_id' => $validated['branch_id'],
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor?->id,
            'booked_by' => null,
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'specialty' => $validated['specialty'],
            'treatment_name' => $validated['treatment_name'],
            'status' => 'booked',
            'visit_type' => 'consultation',
            'token_no' => $this->assignmentService->nextToken(
                branchId: (int) $validated['branch_id'],
                appointmentDate: $validated['appointment_date'],
            ),
            'estimated_amount' => 0,
            'paid_amount' => 0,
            'notes' => trim(($validated['notes'] ?? '')."\nWebsite inquiry #{$inquiry->id}"),
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
}
