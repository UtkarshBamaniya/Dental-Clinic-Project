<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Chair;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Focused tests for the AppointmentService (Step 4 — Appointment Backend Structure).
 *
 * Uses SQLite in-memory database (configured in phpunit.xml).
 * All tests run in a transaction that is rolled back after each test (RefreshDatabase).
 */
class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;
    private Patient $patient;
    private Doctor $doctor;
    private Chair $chair;
    private AppointmentType $appointmentType;
    private Treatment $treatment;

    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AppointmentService::class);

        // Create shared reference records
        $this->patient         = Patient::factory()->create();
        $this->doctor          = Doctor::factory()->create(['consultation_fee' => 300.00]);
        $this->chair           = Chair::factory()->create();
        $this->appointmentType = AppointmentType::factory()->create();
        $this->treatment       = Treatment::factory()->create(['default_price' => 1000.00]);
    }

    // -------------------------------------------------------------------------
    // Helper: minimal valid appointment data
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

    // =========================================================================
    // TEST 1: Appointment can be created
    // =========================================================================

    public function test_appointment_can_be_created(): void
    {
        $appointment = $this->service->create($this->baseData());

        $this->assertNotNull($appointment);
        $this->assertDatabaseHas('dental_appointments', [
            'id'         => $appointment->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    // =========================================================================
    // TEST 2: Appointment number is generated
    // =========================================================================

    public function test_appointment_number_is_generated(): void
    {
        $appointment = $this->service->create($this->baseData());

        $this->assertNotEmpty($appointment->appointment_no);
        $this->assertMatchesRegularExpression('/^APP-\d{5,}$/', $appointment->appointment_no);
    }

    public function test_appointment_numbers_are_sequential_and_unique(): void
    {
        $first  = $this->service->create($this->baseData());
        $second = $this->service->create($this->baseData());

        $this->assertNotEquals($first->appointment_no, $second->appointment_no);

        // Extract sequence numbers and verify second > first
        preg_match('/APP-(\d+)/', $first->appointment_no, $m1);
        preg_match('/APP-(\d+)/', $second->appointment_no, $m2);
        $this->assertGreaterThan((int) $m1[1], (int) $m2[1]);
    }

    // =========================================================================
    // TEST 3: Patient relationship works
    // =========================================================================

    public function test_patient_relationship_is_loaded(): void
    {
        $appointment = $this->service->create($this->baseData());
        $appointment->load('patient');

        $this->assertNotNull($appointment->patient);
        $this->assertEquals($this->patient->id, $appointment->patient->id);
    }

    // =========================================================================
    // TEST 4: Doctor relationship works
    // =========================================================================

    public function test_doctor_relationship_is_loaded(): void
    {
        $appointment = $this->service->create($this->baseData());
        $appointment->load('doctor');

        $this->assertNotNull($appointment->doctor);
        $this->assertEquals($this->doctor->id, $appointment->doctor->id);
    }

    // =========================================================================
    // TEST 5: Chair relationship works
    // =========================================================================

    public function test_chair_relationship_is_loaded(): void
    {
        $appointment = $this->service->create($this->baseData());
        $appointment->load('chair');

        $this->assertNotNull($appointment->chair);
        $this->assertEquals($this->chair->id, $appointment->chair->id);
    }

    // =========================================================================
    // TEST 6: AppointmentType relationship works
    // =========================================================================

    public function test_appointment_type_relationship_is_loaded(): void
    {
        $appointment = $this->service->create($this->baseData());
        $appointment->load('appointmentType');

        $this->assertNotNull($appointment->appointmentType);
        $this->assertEquals($this->appointmentType->id, $appointment->appointmentType->id);
    }

    // =========================================================================
    // TEST 7: Examination can be created
    // =========================================================================

    public function test_examination_is_created_when_data_is_provided(): void
    {
        $data = $this->baseData([
            'examination' => [
                'symptoms'    => 'Tooth pain',
                'diagnosis'   => 'Cavity',
                'observations'=> 'Upper molar affected',
                'doctor_notes'=> 'Recommend filling',
            ],
        ]);

        $appointment = $this->service->create($data);

        $this->assertDatabaseHas('dental_appointment_examinations', [
            'appointment_id' => $appointment->id,
            'symptoms'       => 'Tooth pain',
            'diagnosis'      => 'Cavity',
        ]);
    }

    public function test_examination_is_not_created_when_no_data_provided(): void
    {
        $appointment = $this->service->create($this->baseData());

        $this->assertDatabaseMissing('dental_appointment_examinations', [
            'appointment_id' => $appointment->id,
        ]);
    }

    // =========================================================================
    // TEST 8: Multiple treatments can be created
    // =========================================================================

    public function test_multiple_treatments_can_be_created(): void
    {
        $treatment2 = Treatment::factory()->create(['default_price' => 500.00]);

        $data = $this->baseData([
            'treatments' => [
                ['treatment_id' => $this->treatment->id, 'quantity' => 1],
                ['treatment_id' => $treatment2->id, 'quantity' => 2],
            ],
        ]);

        $appointment = $this->service->create($data);

        $this->assertDatabaseCount('dental_appointment_treatments', 2);
        $this->assertDatabaseHas('dental_appointment_treatments', [
            'appointment_id' => $appointment->id,
            'treatment_id'   => $this->treatment->id,
        ]);
        $this->assertDatabaseHas('dental_appointment_treatments', [
            'appointment_id' => $appointment->id,
            'treatment_id'   => $treatment2->id,
        ]);
    }

    // =========================================================================
    // TEST 9: Treatment totals are calculated server-side
    // =========================================================================

    public function test_treatment_total_is_calculated_server_side(): void
    {
        // Treatment has default_price = 1000.00; send quantity = 3
        // Frontend sends an incorrect total_amount that must be ignored
        $data = $this->baseData([
            'treatments' => [
                [
                    'treatment_id' => $this->treatment->id,
                    'quantity'     => 3,
                    'unit_price'   => 1000.00,
                    // Sending wrong total_amount — backend must ignore it
                    'total_amount' => 99999.00,
                ],
            ],
        ]);

        $appointment = $this->service->create($data);
        $treatment   = $appointment->treatments()->first();

        // Backend should calculate: 3 × 1000 = 3000, NOT trust 99999
        $this->assertEquals('3000.00', $treatment->total_amount);
    }

    public function test_unit_price_falls_back_to_treatment_default_price(): void
    {
        // Do NOT supply unit_price — service must fall back to Treatment.default_price (1000.00)
        $data = $this->baseData([
            'treatments' => [
                ['treatment_id' => $this->treatment->id, 'quantity' => 2],
            ],
        ]);

        $appointment = $this->service->create($data);
        $treatment   = $appointment->treatments()->first();

        $this->assertEquals('2000.00', $treatment->total_amount);
        $this->assertEquals('1000.00', $treatment->unit_price);
    }

    // =========================================================================
    // TEST 10: Billing is calculated server-side
    // =========================================================================

    public function test_billing_is_created_with_correct_totals(): void
    {
        // Doctor consultation_fee = 300; treatment 2 × 1000 = 2000; no discount
        // grand_total = 300 + 2000 = 2300
        $data = $this->baseData([
            'treatments' => [
                ['treatment_id' => $this->treatment->id, 'quantity' => 2, 'unit_price' => 1000.00],
            ],
        ]);

        $appointment = $this->service->create($data);
        $billing     = $appointment->billing()->first();

        $this->assertNotNull($billing);
        $this->assertEquals('300.00', $billing->consultation_fee);
        $this->assertEquals('2000.00', $billing->treatment_amount);
        $this->assertEquals('2300.00', $billing->grand_total);
        $this->assertEquals('0.00', $billing->paid_amount);
        $this->assertEquals('2300.00', $billing->balance_amount);
    }

    public function test_billing_applies_discount_correctly(): void
    {
        $data = $this->baseData([
            'billing' => ['discount' => 200],
        ]);

        $appointment = $this->service->create($data);
        $billing     = $appointment->billing()->first();

        // consultation_fee=300 + treatment_amount=0 - discount=200 = 100
        $this->assertEquals('200.00', $billing->discount);
        $this->assertEquals('100.00', $billing->grand_total);
    }

    // =========================================================================
    // TEST 11: Payment status is calculated correctly
    // =========================================================================

    public function test_new_appointment_has_unpaid_status(): void
    {
        $appointment = $this->service->create($this->baseData());
        $billing     = $appointment->billing()->first();

        $this->assertEquals('unpaid', $billing->payment_status);
        $this->assertEquals('0.00', $billing->paid_amount);
    }

    public function test_partial_payment_sets_partial_status(): void
    {
        $data = $this->baseData([
            'payment' => [
                'amount'       => 100.00,
                'payment_mode' => 'cash',
                'payment_date' => now()->toDateString(),
            ],
        ]);

        $appointment = $this->service->create($data);
        $billing     = $appointment->billing()->first();

        // grand_total = 300 (consultation only, no treatments), paid = 100 → partial
        $this->assertEquals('partial', $billing->payment_status);
        $this->assertEquals('100.00', $billing->paid_amount);
    }

    public function test_full_payment_sets_paid_status(): void
    {
        // Only consultation fee (300), no treatments, full payment
        $data = $this->baseData([
            'payment' => [
                'amount'       => 300.00,
                'payment_mode' => 'upi',
                'payment_date' => now()->toDateString(),
            ],
        ]);

        $appointment = $this->service->create($data);
        $billing     = $appointment->billing()->first();

        $this->assertEquals('paid', $billing->payment_status);
        $this->assertEquals('300.00', $billing->paid_amount);
        $this->assertEquals('0.00', $billing->balance_amount);
    }

    // =========================================================================
    // TEST 12: Follow-up can reference previous_appointment_id
    // =========================================================================

    public function test_follow_up_stores_previous_appointment_id(): void
    {
        $original = $this->service->create($this->baseData());

        $followUp = $this->service->create($this->baseData([
            'visit_type'              => 'Follow-up',
            'previous_appointment_id' => $original->id,
        ]));

        $this->assertEquals($original->id, $followUp->previous_appointment_id);
        $this->assertDatabaseHas('dental_appointments', [
            'id'                      => $followUp->id,
            'previous_appointment_id' => $original->id,
        ]);
    }

    // =========================================================================
    // TEST 13: Follow-up keeps the same patient
    // =========================================================================

    public function test_follow_up_keeps_same_patient(): void
    {
        $original = $this->service->create($this->baseData());

        $followUp = $this->service->create($this->baseData([
            'visit_type'              => 'Follow-up',
            'previous_appointment_id' => $original->id,
        ]));

        // Both appointments share the same patient
        $this->assertEquals($original->patient_id, $followUp->patient_id);
        $this->assertEquals($this->patient->id, $followUp->patient_id);

        // Verify the relationship loads correctly
        $followUp->load('previousAppointment');
        $this->assertEquals($original->id, $followUp->previousAppointment->id);
    }

    // =========================================================================
    // TEST 14: Failed transaction does not leave partial records
    // =========================================================================

    public function test_failed_transaction_does_not_leave_partial_records(): void
    {
        // Pass an invalid treatment_id that does not exist to trigger an exception
        // inside the transaction (constraint violation or similar).
        // We catch the expected exception and then verify nothing was persisted.
        $countBefore = Appointment::count();

        try {
            $this->service->create($this->baseData([
                'treatments' => [
                    ['treatment_id' => 99999, 'quantity' => 1], // non-existent
                ],
            ]));
            $this->fail('Expected exception was not thrown.');
        } catch (\Throwable $e) {
            // Confirmed an exception occurred
        }

        // The appointment count must not have changed
        $this->assertEquals($countBefore, Appointment::count());
        $this->assertDatabaseMissing('dental_appointment_billings', []);
    }

    // =========================================================================
    // Additional: Payment transaction is recorded
    // =========================================================================

    public function test_payment_transaction_is_recorded(): void
    {
        $data = $this->baseData([
            'payment' => [
                'amount'                => 150.00,
                'payment_mode'          => 'card',
                'payment_date'          => now()->toDateString(),
                'transaction_reference' => 'TXN-ABC-123',
            ],
        ]);

        $appointment = $this->service->create($data);

        $this->assertDatabaseHas('dental_payment_transactions', [
            'appointment_id'        => $appointment->id,
            'amount'                => 150.00,
            'payment_mode'          => 'card',
            'transaction_reference' => 'TXN-ABC-123',
        ]);
    }

    public function test_payment_transaction_not_created_for_zero_payment(): void
    {
        $appointment = $this->service->create($this->baseData());

        $this->assertDatabaseMissing('dental_payment_transactions', [
            'appointment_id' => $appointment->id,
        ]);
    }

    // =========================================================================
    // Additional: Status defaults to scheduled on creation
    // =========================================================================

    public function test_new_appointment_status_defaults_to_scheduled(): void
    {
        // Even if the request tries to set a different status, service locks it to 'scheduled'
        $appointment = $this->service->create($this->baseData([
            'status' => 'completed', // should be ignored by service
        ]));

        $this->assertEquals('scheduled', $appointment->status);
    }

    // =========================================================================
    // Additional: Update recalculates billing from payment transactions
    // =========================================================================

    public function test_update_recalculates_billing_from_payment_transactions(): void
    {
        // Create appointment with a payment
        $data = $this->baseData([
            'payment' => [
                'amount'       => 100.00,
                'payment_mode' => 'cash',
                'payment_date' => now()->toDateString(),
            ],
        ]);

        $appointment = $this->service->create($data);

        // Now update with new treatment
        $updatedAppointment = $this->service->update($appointment, [
            'treatments' => [
                ['treatment_id' => $this->treatment->id, 'quantity' => 1, 'unit_price' => 500.00],
            ],
        ]);

        $billing = $updatedAppointment->billing()->first();

        // Paid amount should still be 100 (from payment transaction, not reset)
        $this->assertEquals('100.00', $billing->paid_amount);
        // New grand_total = 300 (consultation) + 500 (treatment) = 800
        $this->assertEquals('800.00', $billing->grand_total);
        $this->assertEquals('700.00', $billing->balance_amount);
        $this->assertEquals('partial', $billing->payment_status);
    }

    public function test_update_does_not_delete_payment_transactions(): void
    {
        $data = $this->baseData([
            'payment' => [
                'amount'       => 50.00,
                'payment_mode' => 'upi',
                'payment_date' => now()->toDateString(),
            ],
        ]);

        $appointment = $this->service->create($data);
        $txnCount    = $appointment->paymentTransactions()->count();

        // Update appointment — payment transactions must not be deleted
        $this->service->update($appointment, ['priority' => 'high']);

        $this->assertEquals($txnCount, $appointment->paymentTransactions()->count());
    }
}
