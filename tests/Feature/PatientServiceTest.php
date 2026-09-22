<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature tests for Step 5 — Patient Management Backend.
 *
 * Uses SQLite in-memory database (phpunit.xml).
 * All tests run inside a transaction rolled back by RefreshDatabase.
 *
 * Covered scenarios:
 *  1.  Create patient successfully (201 + correct shape)
 *  2.  Patient code generated as PAT-00001
 *  3.  Medical history created alongside patient
 *  4.  Search by first name
 *  5.  Search by last name
 *  6.  Search by mobile
 *  7.  Search by patient code
 *  8.  Update patient fields
 *  9.  Update medical history via updateOrCreate
 *  10. Duplicate active mobile rejected on create (422)
 *  11. Updating patient's own mobile is allowed
 *  12. Updating to another patient's mobile is rejected (422)
 *  13. Patient detail returns medical history
 *  14. Pagination works on index
 *  15. Soft-deleted patients excluded from search
 *  16. Patient codes are sequential (PAT-00001, PAT-00002, …)
 *  17. Empty medical_history does not create a history row
 *  18. Patient code not overridable via request
 */
class PatientServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientService $service;

    /** Reusable valid patient payload */
    private array $basePayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PatientService::class);

        $this->basePayload = [
            'first_name'    => 'Rahul',
            'middle_name'   => null,
            'last_name'     => 'Patel',
            'gender'        => 'Male',
            'date_of_birth' => '1998-05-10',
            'mobile'        => '9876543210',
            'email'         => 'rahul@example.com',
            'city'          => 'Rajkot',
            'state'         => 'Gujarat',
            'status'        => 'active',
            'medical_history' => [
                'blood_group'               => 'B+',
                'current_medicine'          => 'None',
                'previous_dental_treatment' => 'Filling',
                'other_notes'               => 'No known issues',
            ],
        ];
    }

    // =========================================================================
    // 1. Create patient successfully
    // =========================================================================

    public function test_can_create_patient_successfully(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $this->assertInstanceOf(Patient::class, $patient);
        $this->assertDatabaseHas('dental_patients', [
            'first_name' => 'Rahul',
            'last_name'  => 'Patel',
            'mobile'     => '9876543210',
        ]);
    }

    // =========================================================================
    // 2. Patient code generated as PAT-00001
    // =========================================================================

    public function test_patient_code_is_generated_as_pat_00001_for_first_patient(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $this->assertEquals('PAT-00001', $patient->patient_code);
    }

    // =========================================================================
    // 3. Medical history created alongside patient
    // =========================================================================

    public function test_medical_history_is_created_with_patient(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $this->assertNotNull($patient->medicalHistory);
        $this->assertDatabaseHas('dental_patient_medical_histories', [
            'patient_id'  => $patient->id,
            'blood_group' => 'B+',
        ]);
    }

    // =========================================================================
    // 4. Search by first name
    // =========================================================================

    public function test_search_finds_patient_by_first_name(): void
    {
        $this->service->createPatient($this->basePayload);

        $results = $this->service->searchPatients('Rahul');

        $this->assertCount(1, $results);
        $this->assertEquals('Rahul', $results->first()->first_name);
    }

    // =========================================================================
    // 5. Search by last name
    // =========================================================================

    public function test_search_finds_patient_by_last_name(): void
    {
        $this->service->createPatient($this->basePayload);

        $results = $this->service->searchPatients('Patel');

        $this->assertCount(1, $results);
    }

    // =========================================================================
    // 6. Search by mobile
    // =========================================================================

    public function test_search_finds_patient_by_mobile(): void
    {
        $this->service->createPatient($this->basePayload);

        $results = $this->service->searchPatients('9876543210');

        $this->assertCount(1, $results);
        $this->assertEquals('9876543210', $results->first()->mobile);
    }

    // =========================================================================
    // 7. Search by patient code
    // =========================================================================

    public function test_search_finds_patient_by_patient_code(): void
    {
        $this->service->createPatient($this->basePayload);

        $results = $this->service->searchPatients('PAT-00001');

        $this->assertCount(1, $results);
        $this->assertEquals('PAT-00001', $results->first()->patient_code);
    }

    // =========================================================================
    // 8. Update patient fields
    // =========================================================================

    public function test_can_update_patient_fields(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $updated = $this->service->updatePatient($patient, [
            'first_name' => 'Rajesh',
            'city'       => 'Ahmedabad',
        ]);

        $this->assertEquals('Rajesh', $updated->first_name);
        $this->assertEquals('Ahmedabad', $updated->city);
    }

    // =========================================================================
    // 9. Update medical history via updateOrCreate
    // =========================================================================

    public function test_updating_patient_upserts_medical_history(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        // First update — change existing history
        $this->service->updatePatient($patient, [
            'medical_history' => [
                'blood_group'   => 'O+',
                'other_notes'   => 'Updated notes',
            ],
        ]);

        // Second update — should not create a duplicate row
        $this->service->updatePatient($patient->fresh(), [
            'medical_history' => [
                'blood_group' => 'A-',
            ],
        ]);

        $this->assertCount(1, PatientMedicalHistory::where('patient_id', $patient->id)->get());

        $this->assertDatabaseHas('dental_patient_medical_histories', [
            'patient_id'  => $patient->id,
            'blood_group' => 'A-',
        ]);
    }

    // =========================================================================
    // 10. Duplicate active mobile rejected on create
    // =========================================================================

    public function test_create_rejects_duplicate_mobile(): void
    {
        $this->service->createPatient($this->basePayload);

        $this->expectException(ValidationException::class);

        // Second patient with same mobile
        $this->service->createPatient(array_merge($this->basePayload, [
            'first_name' => 'Duplicate',
            'email'      => 'duplicate@example.com',
        ]));
    }

    // =========================================================================
    // 11. Updating patient's own mobile is allowed
    // =========================================================================

    public function test_updating_own_mobile_is_allowed(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        // Updating with the same mobile should NOT throw
        $updated = $this->service->updatePatient($patient, [
            'mobile' => '9876543210',
        ]);

        $this->assertEquals('9876543210', $updated->mobile);
    }

    // =========================================================================
    // 12. Updating to another patient's mobile is rejected
    // =========================================================================

    public function test_update_rejects_mobile_already_used_by_another_patient(): void
    {
        $this->service->createPatient($this->basePayload);

        $secondPayload = array_merge($this->basePayload, [
            'first_name' => 'Priya',
            'mobile'     => '9000000001',
            'email'      => 'priya@example.com',
        ]);
        $secondPatient = $this->service->createPatient($secondPayload);

        $this->expectException(ValidationException::class);

        // Try to change Priya's mobile to Rahul's existing mobile
        $this->service->updatePatient($secondPatient, [
            'mobile' => '9876543210',
        ]);
    }

    // =========================================================================
    // 13. Patient detail returns medical history
    // =========================================================================

    public function test_get_patient_loads_medical_history(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $result = $this->service->getPatient($patient);

        $this->assertTrue($result->relationLoaded('medicalHistory'));
        $this->assertEquals('B+', $result->medicalHistory->blood_group);
    }

    // =========================================================================
    // 14. Pagination works on index
    // =========================================================================

    public function test_list_patients_paginates_correctly(): void
    {
        // Create 5 patients with distinct mobiles
        for ($i = 1; $i <= 5; $i++) {
            $this->service->createPatient(array_merge($this->basePayload, [
                'mobile' => '98765432' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email'  => "patient{$i}@example.com",
            ]));
        }

        $page1 = $this->service->listPatients(['per_page' => 3, 'page' => 1]);
        $page2 = $this->service->listPatients(['per_page' => 3, 'page' => 2]);

        $this->assertEquals(5, $page1->total());
        $this->assertCount(3, $page1->items());
        $this->assertCount(2, $page2->items());
    }

    // =========================================================================
    // 15. Soft-deleted patients excluded from search
    // =========================================================================

    public function test_soft_deleted_patients_are_excluded_from_search(): void
    {
        $patient = $this->service->createPatient($this->basePayload);
        $patient->delete(); // soft delete

        $results = $this->service->searchPatients('Rahul');

        $this->assertCount(0, $results);
    }

    // =========================================================================
    // 16. Patient codes are sequential
    // =========================================================================

    public function test_patient_codes_are_sequential(): void
    {
        $p1 = $this->service->createPatient(array_merge($this->basePayload, ['mobile' => '9000000001', 'email' => 'p1@x.com']));
        $p2 = $this->service->createPatient(array_merge($this->basePayload, ['mobile' => '9000000002', 'email' => 'p2@x.com']));
        $p3 = $this->service->createPatient(array_merge($this->basePayload, ['mobile' => '9000000003', 'email' => 'p3@x.com']));

        $this->assertEquals('PAT-00001', $p1->patient_code);
        $this->assertEquals('PAT-00002', $p2->patient_code);
        $this->assertEquals('PAT-00003', $p3->patient_code);
    }

    // =========================================================================
    // 17. Empty medical_history does not create a history row
    // =========================================================================

    public function test_empty_medical_history_does_not_create_row(): void
    {
        $payload = array_merge($this->basePayload, [
            'mobile'          => '9111111111',
            'email'           => 'nohistory@example.com',
            'medical_history' => null,
        ]);

        $patient = $this->service->createPatient($payload);

        $this->assertCount(0, PatientMedicalHistory::where('patient_id', $patient->id)->get());
    }

    // =========================================================================
    // 18. Patient code not overridable via request
    // =========================================================================

    public function test_patient_code_cannot_be_overridden_on_update(): void
    {
        $patient = $this->service->createPatient($this->basePayload);

        $original = $patient->patient_code;

        $updated = $this->service->updatePatient($patient, [
            'patient_code' => 'PAT-HACKED',
            'first_name'   => 'Changed',
        ]);

        $this->assertEquals($original, $updated->patient_code);
    }

    // =========================================================================
    // Additional: HTTP layer tests
    // =========================================================================

    public function test_post_patients_returns_201_with_patient_data(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/patients', $this->basePayload);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Patient created successfully.')
                 ->assertJsonPath('data.first_name', 'Rahul')
                 ->assertJsonPath('data.patient_code', 'PAT-00001')
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id', 'patient_code', 'first_name', 'last_name',
                         'full_name', 'mobile', 'medical_history',
                     ],
                 ]);
    }

    public function test_post_patients_returns_422_for_duplicate_mobile(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->service->createPatient($this->basePayload);

        $response = $this->actingAs($user)
            ->postJson('/patients', array_merge($this->basePayload, [
                'first_name' => 'Duplicate',
                'email'      => 'dup@example.com',
            ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['mobile']);
    }

    public function test_get_patients_search_returns_matching_patients(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->service->createPatient($this->basePayload);

        $response = $this->actingAs($user)
            ->getJson('/patients/search?q=Rahul');

        $response->assertOk()
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.full_name', 'Rahul Patel');
    }

    public function test_get_patients_search_returns_empty_for_soft_deleted(): void
    {
        $user    = \App\Models\User::factory()->create();
        $patient = $this->service->createPatient($this->basePayload);
        $patient->delete();

        $response = $this->actingAs($user)
            ->getJson('/patients/search?q=Rahul');

        $response->assertOk()
                 ->assertJsonCount(0, 'data');
    }

    public function test_get_patient_show_returns_medical_history(): void
    {
        $user    = \App\Models\User::factory()->create();
        $patient = $this->service->createPatient($this->basePayload);

        $response = $this->actingAs($user)
            ->getJson("/patients/{$patient->id}");

        $response->assertOk()
                 ->assertJsonPath('data.medical_history.blood_group', 'B+');
    }

    public function test_put_patient_update_changes_fields(): void
    {
        $user    = \App\Models\User::factory()->create();
        $patient = $this->service->createPatient($this->basePayload);

        $response = $this->actingAs($user)
            ->putJson("/patients/{$patient->id}", [
                'first_name' => 'Rajesh',
                'city'       => 'Mumbai',
            ]);

        $response->assertOk()
                 ->assertJsonPath('data.first_name', 'Rajesh')
                 ->assertJsonPath('data.city', 'Mumbai');
    }
}
