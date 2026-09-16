<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Chair;
use App\Models\AppointmentType;
use App\Models\Treatment;
use App\Models\Tooth;
use App\Models\User;
use Illuminate\Database\Seeder;

class DentalMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedDoctors();
        $this->seedChairs();
        $this->seedAppointmentTypes();
        $this->seedTreatments();
        $this->seedTeeth();
    }

    protected function seedDoctors(): void
    {
        $doctors = [
            [
                'user_id' => 4,
                'doctor_code' => 'DOC-0001',
                'first_name' => 'Mehul',
                'last_name' => 'Shah',
                'specialization' => 'Implants',
                'consultation_fee' => 700,
                'status' => 'active',
            ],
            [
                'user_id' => 5,
                'doctor_code' => 'DOC-0002',
                'first_name' => 'Priya',
                'last_name' => 'Mehta',
                'specialization' => 'Orthodontics',
                'consultation_fee' => 650,
                'status' => 'active',
            ],
        ];

        foreach ($doctors as $doctorData) {
            // Check if user exists before seeding to avoid foreign key constraints
            if (User::find($doctorData['user_id'])) {
                $user_id = $doctorData['user_id'];
                unset($doctorData['user_id']);
                Doctor::updateOrCreate(
                    ['user_id' => $user_id],
                    $doctorData
                );
            }
        }
    }

    protected function seedChairs(): void
    {
        $chairs = [
            ['chair_name' => 'Chair 1', 'chair_number' => 1, 'status' => 'active'],
            ['chair_name' => 'Chair 2', 'chair_number' => 2, 'status' => 'active'],
            ['chair_name' => 'Chair 3', 'chair_number' => 3, 'status' => 'active'],
            ['chair_name' => 'Chair 4', 'chair_number' => 4, 'status' => 'active'],
        ];

        foreach ($chairs as $chairData) {
            Chair::updateOrCreate(
                ['chair_number' => $chairData['chair_number']],
                [
                    'chair_name' => $chairData['chair_name'],
                    'status' => $chairData['status'],
                ]
            );
        }
    }

    protected function seedAppointmentTypes(): void
    {
        $types = [
            ['name' => 'Consultation', 'description' => 'Initial or general dental consultation'],
            ['name' => 'Treatment', 'description' => 'Dental treatment appointment'],
            ['name' => 'Follow-up', 'description' => 'Follow-up appointment after previous consultation or treatment'],
            ['name' => 'Emergency', 'description' => 'Urgent dental care appointment'],
        ];

        foreach ($types as $typeData) {
            AppointmentType::updateOrCreate(
                ['name' => $typeData['name']],
                [
                    'description' => $typeData['description'],
                    'status' => 'active',
                ]
            );
        }
    }

    protected function seedTreatments(): void
    {
        $treatments = [
            ['treatment_code' => 'TRT-001', 'name' => 'Consultation', 'description' => 'General dental consultation', 'default_price' => 500, 'duration_minutes' => 30],
            ['treatment_code' => 'TRT-002', 'name' => 'Dental Cleaning', 'description' => 'Professional dental cleaning and scaling', 'default_price' => 1200, 'duration_minutes' => 45],
            ['treatment_code' => 'TRT-003', 'name' => 'Dental Filling', 'description' => 'Dental filling for tooth decay or cavity', 'default_price' => 1500, 'duration_minutes' => 45],
            ['treatment_code' => 'TRT-004', 'name' => 'Root Canal Treatment', 'description' => 'Root canal treatment for an infected or damaged tooth', 'default_price' => 3500, 'duration_minutes' => 90],
            ['treatment_code' => 'TRT-005', 'name' => 'Dental Crown', 'description' => 'Dental crown placement', 'default_price' => 2000, 'duration_minutes' => 60],
            ['treatment_code' => 'TRT-006', 'name' => 'Tooth Extraction', 'description' => 'Dental tooth extraction', 'default_price' => 1500, 'duration_minutes' => 30],
            ['treatment_code' => 'TRT-007', 'name' => 'Dental Implant', 'description' => 'Dental implant procedure', 'default_price' => 25000, 'duration_minutes' => 120],
            ['treatment_code' => 'TRT-008', 'name' => 'Teeth Whitening', 'description' => 'Professional teeth whitening treatment', 'default_price' => 5000, 'duration_minutes' => 60],
            ['treatment_code' => 'TRT-009', 'name' => 'Denture', 'description' => 'Removable dental denture treatment', 'default_price' => 8000, 'duration_minutes' => 90],
            ['treatment_code' => 'TRT-010', 'name' => 'Braces Consultation', 'description' => 'Orthodontic consultation for braces', 'default_price' => 1000, 'duration_minutes' => 45],
        ];

        foreach ($treatments as $treatmentData) {
            $code = $treatmentData['treatment_code'];
            unset($treatmentData['treatment_code']);
            $treatmentData['status'] = 'active';
            Treatment::updateOrCreate(
                ['treatment_code' => $code],
                $treatmentData
            );
        }
    }

    protected function seedTeeth(): void
    {
        $teethGroups = [
            // Upper Right (11-18)
            [11, 'Central Incisor', 'Incisor', 'Upper Right'],
            [12, 'Lateral Incisor', 'Incisor', 'Upper Right'],
            [13, 'Canine', 'Canine', 'Upper Right'],
            [14, 'First Premolar', 'Premolar', 'Upper Right'],
            [15, 'Second Premolar', 'Premolar', 'Upper Right'],
            [16, 'First Molar', 'Molar', 'Upper Right'],
            [17, 'Second Molar', 'Molar', 'Upper Right'],
            [18, 'Third Molar', 'Molar', 'Upper Right'],
            
            // Upper Left (21-28)
            [21, 'Central Incisor', 'Incisor', 'Upper Left'],
            [22, 'Lateral Incisor', 'Incisor', 'Upper Left'],
            [23, 'Canine', 'Canine', 'Upper Left'],
            [24, 'First Premolar', 'Premolar', 'Upper Left'],
            [25, 'Second Premolar', 'Premolar', 'Upper Left'],
            [26, 'First Molar', 'Molar', 'Upper Left'],
            [27, 'Second Molar', 'Molar', 'Upper Left'],
            [28, 'Third Molar', 'Molar', 'Upper Left'],
            
            // Lower Left (31-38)
            [31, 'Central Incisor', 'Incisor', 'Lower Left'],
            [32, 'Lateral Incisor', 'Incisor', 'Lower Left'],
            [33, 'Canine', 'Canine', 'Lower Left'],
            [34, 'First Premolar', 'Premolar', 'Lower Left'],
            [35, 'Second Premolar', 'Premolar', 'Lower Left'],
            [36, 'First Molar', 'Molar', 'Lower Left'],
            [37, 'Second Molar', 'Molar', 'Lower Left'],
            [38, 'Third Molar', 'Molar', 'Lower Left'],
            
            // Lower Right (41-48)
            [41, 'Central Incisor', 'Incisor', 'Lower Right'],
            [42, 'Lateral Incisor', 'Incisor', 'Lower Right'],
            [43, 'Canine', 'Canine', 'Lower Right'],
            [44, 'First Premolar', 'Premolar', 'Lower Right'],
            [45, 'Second Premolar', 'Premolar', 'Lower Right'],
            [46, 'First Molar', 'Molar', 'Lower Right'],
            [47, 'Second Molar', 'Molar', 'Lower Right'],
            [48, 'Third Molar', 'Molar', 'Lower Right'],
        ];

        foreach ($teethGroups as $toothData) {
            Tooth::updateOrCreate(
                ['tooth_no' => $toothData[0]],
                [
                    'tooth_name' => $toothData[1],
                    'tooth_type' => $toothData[2],
                    'quadrant'   => $toothData[3],
                ]
            );
        }
    }
}
