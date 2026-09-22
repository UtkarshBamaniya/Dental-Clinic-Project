<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentBilling;
use App\Models\AppointmentExamination;
use App\Models\AppointmentNote;
use App\Models\AppointmentType;
use App\Models\Chair;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PaymentTransaction;
use App\Models\Prescription;
use App\Models\Treatment;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 6 — Appointment Master API Tests.
 *
 * Tests cover:
 *  - List endpoint: pagination, search, all filters, sorting, empty results
 *  - Detail endpoint: all relationships, previous/follow-up appointments
 *  - Validation: invalid filters rejected correctly
 *  - Existing create/update functionality preserved
 *  - Soft-deleted appointments excluded
 */
class AppointmentMasterApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Doctor $doctor;
    private Chair $chair;
    private AppointmentType $appointmentType;
    private Treatment $treatment;
    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user            = User::factory()->create();
        $this->patient         = Patient::factory()->create();
        $this->doctor          = Doctor::factory()->create(['consultation_fee' => 300.00]);
        $this->chair           = Chair::factory()->create();
        $this->appointmentType = AppointmentType::factory()->create();
        $this->treatment       = Treatment::factory()->create(['default_price' => 1000.00]);
        $this->service         = app(AppointmentService::class);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'patient_id'          => $this->patient->id,
            'doctor_id'           => $this->doctor->id,
            'chair_id'            => $this->chair->id,
            'appointment_type_id' => $this->appointmentType->id,
            'appointment_date'    => now()->toDateString(),
            'appointment_time'    => '10:00:00',
            'visit_type'          => 'First Visit',
            'priority'            => 'normal',
        ], $overrides);
    }

    private function makeAppointment(array $overrides = []): Appointment
    {
        return $this->service->create($this->baseData($overrides));
    }

    // =========================================================================
    // 1. Appointment list returns 200
    // =========================================================================

    public function test_appointment_list_returns_200(): void
    {
        $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson('/appointments')
             ->assertOk()
             ->assertJsonStructure(['data', 'meta']);
    }

    // =========================================================================
    // 2. Appointment list is paginated
    // =========================================================================

    public function test_appointment_list_is_paginated(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeAppointment();
        }

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?per_page=3&page=1');

        $response->assertOk()
                 ->assertJsonPath('meta.per_page', 3)
                 ->assertJsonPath('meta.total', 5)
                 ->assertJsonCount(3, 'data');
    }

    // =========================================================================
    // 3. Search by appointment number
    // =========================================================================

    public function test_search_by_appointment_number(): void
    {
        $appointment = $this->makeAppointment();
        $this->makeAppointment(); // noise

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?search=' . $appointment->appointment_no);

        $response->assertOk()
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.appointment_no', $appointment->appointment_no);
    }

    // =========================================================================
    // 4. Search by patient name
    // =========================================================================

    public function test_search_by_patient_name(): void
    {
        $this->makeAppointment();

        $firstName = $this->patient->first_name;

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?search=' . urlencode($firstName));

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 5. Search by patient mobile
    // =========================================================================

    public function test_search_by_patient_mobile(): void
    {
        $this->makeAppointment();

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?search=' . $this->patient->mobile);

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 6. Search by patient code
    // =========================================================================

    public function test_search_by_patient_code(): void
    {
        $this->makeAppointment();

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?search=' . $this->patient->patient_code);

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 7. Date filter — exact date
    // =========================================================================

    public function test_date_filter_exact_date(): void
    {
        $this->makeAppointment(['appointment_date' => '2026-09-19']);
        $this->makeAppointment(['appointment_date' => '2026-09-20']);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?date=2026-09-19');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.appointment_date', '2026-09-19');
    }

    // =========================================================================
    // 8. Date range filter
    // =========================================================================

    public function test_date_range_filter(): void
    {
        $this->makeAppointment(['appointment_date' => '2026-09-01']);
        $this->makeAppointment(['appointment_date' => '2026-09-15']);
        $this->makeAppointment(['appointment_date' => '2026-10-01']); // outside range

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?date_from=2026-09-01&date_to=2026-09-30');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 2);
    }

    // =========================================================================
    // 9. Doctor filter
    // =========================================================================

    public function test_doctor_filter(): void
    {
        $otherDoctor = Doctor::factory()->create();
        $this->makeAppointment(['doctor_id' => $this->doctor->id]);
        $this->makeAppointment(['doctor_id' => $otherDoctor->id]);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?doctor_id=' . $this->doctor->id);

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.doctor.id', $this->doctor->id);
    }

    // =========================================================================
    // 10. Chair filter
    // =========================================================================

    public function test_chair_filter(): void
    {
        $otherChair = Chair::factory()->create();
        $this->makeAppointment(['chair_id' => $this->chair->id]);
        $this->makeAppointment(['chair_id' => $otherChair->id]);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?chair_id=' . $this->chair->id);

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 11. Appointment type filter
    // =========================================================================

    public function test_appointment_type_filter(): void
    {
        $otherType = AppointmentType::factory()->create();
        $this->makeAppointment(['appointment_type_id' => $this->appointmentType->id]);
        $this->makeAppointment(['appointment_type_id' => $otherType->id]);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?appointment_type_id=' . $this->appointmentType->id);

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 12. Visit type filter
    // =========================================================================

    public function test_visit_type_filter(): void
    {
        $this->makeAppointment(['visit_type' => 'First Visit']);
        $this->makeAppointment(['visit_type' => 'Follow-up']);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?visit_type=First+Visit');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.visit_type', 'First Visit');
    }

    // =========================================================================
    // 13. Priority filter
    // =========================================================================

    public function test_priority_filter(): void
    {
        $this->makeAppointment(['priority' => 'normal']);
        $this->makeAppointment(['priority' => 'high']);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?priority=high');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.priority', 'high');
    }

    // =========================================================================
    // 14. Appointment status filter
    // =========================================================================

    public function test_appointment_status_filter(): void
    {
        $appt = $this->makeAppointment();
        // Manually update status to 'waiting' since service always starts as 'scheduled'
        $appt->update(['status' => 'waiting']);
        $this->makeAppointment(); // stays 'scheduled'

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?status=waiting');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.status', 'waiting');
    }

    // =========================================================================
    // 15. Payment status filter
    // =========================================================================

    public function test_payment_status_filter(): void
    {
        $paidAppt = $this->makeAppointment();
        $this->makeAppointment(); // stays 'unpaid'

        // Update the billing of the first appointment to 'paid'
        AppointmentBilling::where('appointment_id', $paidAppt->id)
            ->update(['payment_status' => 'paid']);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?payment_status=paid');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1)
                 ->assertJsonPath('data.0.billing.payment_status', 'paid');
    }

    // =========================================================================
    // 16. Multiple filters work together
    // =========================================================================

    public function test_multiple_filters_combined(): void
    {
        $otherDoctor = Doctor::factory()->create();
        $this->makeAppointment([
            'doctor_id'   => $this->doctor->id,
            'appointment_date' => '2026-09-19',
            'priority'    => 'high',
        ]);
        $this->makeAppointment([
            'doctor_id'   => $otherDoctor->id,
            'appointment_date' => '2026-09-19',
            'priority'    => 'high',
        ]);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?doctor_id=' . $this->doctor->id . '&date=2026-09-19&priority=high');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 1);
    }

    // =========================================================================
    // 17. Sorting by allowed columns works
    // =========================================================================

    public function test_sorting_by_appointment_date_asc(): void
    {
        $this->makeAppointment(['appointment_date' => '2026-09-20']);
        $this->makeAppointment(['appointment_date' => '2026-09-19']);

        $response = $this->actingAs($this->user)
             ->getJson('/appointments?sort_by=appointment_date&sort_order=asc');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertLessThanOrEqual($data[1]['appointment_date'], $data[0]['appointment_date']);
    }

    // =========================================================================
    // 18. Invalid sort field is rejected
    // =========================================================================

    public function test_invalid_sort_field_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?sort_by=password')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['sort_by']);
    }

    // =========================================================================
    // 19. Invalid appointment status is rejected
    // =========================================================================

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?status=not_a_real_status')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['status']);
    }

    // =========================================================================
    // 20. Invalid doctor_id is rejected
    // =========================================================================

    public function test_invalid_doctor_id_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?doctor_id=99999')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['doctor_id']);
    }

    // =========================================================================
    // 21. Invalid chair_id is rejected
    // =========================================================================

    public function test_invalid_chair_id_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?chair_id=99999')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['chair_id']);
    }

    // =========================================================================
    // 22. per_page maximum is 100
    // =========================================================================

    public function test_per_page_maximum_is_100(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?per_page=101')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['per_page']);
    }

    // =========================================================================
    // 23. Soft-deleted appointments do not appear
    // =========================================================================

    public function test_soft_deleted_appointments_are_excluded(): void
    {
        $appt = $this->makeAppointment();
        $appt->delete(); // soft delete

        $response = $this->actingAs($this->user)
             ->getJson('/appointments');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 0);
    }

    // =========================================================================
    // 24-33. Appointment detail returns all relationships
    // =========================================================================

    public function test_appointment_detail_contains_patient(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonPath('data.patient.id', $this->patient->id)
             ->assertJsonStructure(['data' => ['patient' => ['id', 'patient_code', 'full_name', 'mobile']]]);
    }

    public function test_appointment_detail_contains_doctor(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonPath('data.doctor.id', $this->doctor->id)
             ->assertJsonStructure(['data' => ['doctor' => ['id', 'doctor_code', 'full_name']]]);
    }

    public function test_appointment_detail_contains_chair(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonPath('data.chair.id', $this->chair->id);
    }

    public function test_appointment_detail_contains_appointment_type(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonPath('data.appointment_type.id', $this->appointmentType->id);
    }

    public function test_appointment_detail_contains_examination(): void
    {
        $appt = $this->makeAppointment(['examination' => [
            'symptoms'  => 'Toothache',
            'diagnosis' => 'Cavity',
        ]]);

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonPath('data.examination.symptoms', 'Toothache');
    }

    public function test_appointment_detail_contains_treatments(): void
    {
        $appt = $this->makeAppointment(['treatments' => [
            ['treatment_id' => $this->treatment->id, 'quantity' => 1],
        ]]);

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data.treatments')
             ->assertJsonPath('data.treatments.0.treatment.id', $this->treatment->id);
    }

    public function test_appointment_detail_contains_billing(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonStructure(['data' => ['billing' => [
                 'grand_total', 'paid_amount', 'balance_amount', 'payment_status',
             ]]]);
    }

    public function test_appointment_detail_contains_payment_transactions(): void
    {
        $appt    = $this->makeAppointment();
        $billing = AppointmentBilling::where('appointment_id', $appt->id)->firstOrFail();

        PaymentTransaction::create([
            'appointment_id'        => $appt->id,
            'billing_id'            => $billing->id,
            'payment_date'          => now()->toDateString(),
            'amount'                => 300,
            'payment_mode'          => 'cash',
            'transaction_reference' => null,
            'remarks'               => null,
        ]);

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data.payment_transactions');
    }

    public function test_appointment_detail_contains_notes(): void
    {
        $appt = $this->makeAppointment();
        $appt->appointmentNotes()->create(['note_type' => 'general', 'notes' => 'Test note']);

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data.appointment_notes')
             ->assertJsonPath('data.appointment_notes.0.notes', 'Test note');
    }

    public function test_appointment_detail_contains_prescriptions(): void
    {
        $appt = $this->makeAppointment();
        $appt->prescriptions()->create([
            'medicine_name' => 'Ibuprofen',
            'dosage'        => '400mg',
            'frequency'     => 'Twice daily',
            'duration'      => '5 days',
            'instructions'  => 'After meals',
        ]);

        $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data.prescriptions')
             ->assertJsonPath('data.prescriptions.0.medicine_name', 'Ibuprofen');
    }

    // =========================================================================
    // 34. Previous appointment summary
    // =========================================================================

    public function test_previous_appointment_summary_is_shallow(): void
    {
        $prev = $this->makeAppointment();
        $appt = $this->makeAppointment(['previous_appointment_id' => $prev->id]);

        $response = $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}");

        $response->assertOk()
                 ->assertJsonPath('data.previous_appointment.id', $prev->id)
                 ->assertJsonPath('data.previous_appointment.appointment_no', $prev->appointment_no)
                 ->assertJsonStructure([
                     'data' => ['previous_appointment' => ['id', 'appointment_no', 'appointment_date', 'status']],
                 ]);
    }

    // =========================================================================
    // 35. Follow-up appointment summaries
    // =========================================================================

    public function test_follow_up_appointments_are_shallow(): void
    {
        $parent = $this->makeAppointment();
        $fu     = $this->makeAppointment(['previous_appointment_id' => $parent->id]);

        $response = $this->actingAs($this->user)
             ->getJson("/appointments/{$parent->id}");

        $response->assertOk()
                 ->assertJsonCount(1, 'data.follow_up_appointments')
                 ->assertJsonPath('data.follow_up_appointments.0.id', $fu->id);
    }

    // =========================================================================
    // 36. Empty search returns 200 with empty data
    // =========================================================================

    public function test_empty_result_returns_200_with_empty_data(): void
    {
        $response = $this->actingAs($this->user)
             ->getJson('/appointments?search=NONEXISTENT_XYZ_99999');

        $response->assertOk()
                 ->assertJsonPath('meta.total', 0)
                 ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // 37. Existing appointment creation still works
    // =========================================================================

    public function test_existing_appointment_creation_still_works(): void
    {
        $response = $this->actingAs($this->user)
             ->postJson('/appointments', $this->baseData());

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Appointment created successfully.')
                 ->assertJsonStructure(['data' => ['id', 'appointment_no', 'status']]);
    }

    // =========================================================================
    // 38. Existing appointment update still works
    // =========================================================================

    public function test_existing_appointment_update_still_works(): void
    {
        $appt = $this->makeAppointment();

        $response = $this->actingAs($this->user)
             ->putJson("/appointments/{$appt->id}", [
                 'status'   => 'confirmed',
                 'priority' => 'high',
             ]);

        $response->assertOk()
                 ->assertJsonPath('message', 'Appointment updated successfully.')
                 ->assertJsonPath('data.status', 'confirmed')
                 ->assertJsonPath('data.priority', 'high');
    }

    // =========================================================================
    // Additional: doctor full name includes "Dr." prefix
    // =========================================================================

    public function test_doctor_full_name_includes_dr_prefix(): void
    {
        $appt = $this->makeAppointment();

        $response = $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}");

        $doctorFullName = $response->json('data.doctor.full_name');
        $this->assertStringStartsWith('Dr.', $doctorFullName);
    }

    // =========================================================================
    // Additional: payment status is independent from appointment status
    // =========================================================================

    public function test_payment_status_is_independent_from_appointment_status(): void
    {
        $appt = $this->makeAppointment();
        $appt->update(['status' => 'completed']);
        AppointmentBilling::where('appointment_id', $appt->id)
            ->update(['payment_status' => 'unpaid']);

        $response = $this->actingAs($this->user)
             ->getJson("/appointments/{$appt->id}");

        $response->assertOk()
                 ->assertJsonPath('data.status', 'completed')
                 ->assertJsonPath('data.billing.payment_status', 'unpaid');
    }

    // =========================================================================
    // Additional: invalid payment_status rejected
    // =========================================================================

    public function test_invalid_payment_status_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?payment_status=overdue')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['payment_status']);
    }

    // =========================================================================
    // Additional: date_to before date_from is rejected
    // =========================================================================

    public function test_date_to_before_date_from_is_rejected(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments?date_from=2026-09-30&date_to=2026-09-01')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['date_to']);
    }

    // =========================================================================
    // Additional: list response includes billing payment status
    // =========================================================================

    public function test_list_response_includes_billing_payment_status(): void
    {
        $this->makeAppointment();

        $response = $this->actingAs($this->user)
             ->getJson('/appointments');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [['billing' => ['grand_total', 'paid_amount', 'balance_amount', 'payment_status']]],
                 ]);
    }

    // =========================================================================
    // Additional: 404 for non-existent appointment detail
    // =========================================================================

    public function test_show_returns_404_for_nonexistent_appointment(): void
    {
        $this->actingAs($this->user)
             ->getJson('/appointments/99999')
             ->assertNotFound();
    }
}
