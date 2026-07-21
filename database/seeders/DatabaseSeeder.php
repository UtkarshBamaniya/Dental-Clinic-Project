<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\Expense;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mainBranch = Branch::query()->create([
            'name' => 'SmileWorks Dental - Ahmedabad',
            'code' => 'AMD01',
            'phone' => '+91 98765 00001',
            'email' => 'ahmedabad@smileworks.test',
            'city' => 'Ahmedabad',
            'address' => 'Satellite Road, Ahmedabad',
            'manager_name' => 'Dr. Mehul Shah',
        ]);

        $secondBranch = Branch::query()->create([
            'name' => 'SmileWorks Dental - Surat',
            'code' => 'SRT01',
            'phone' => '+91 98765 00002',
            'email' => 'surat@smileworks.test',
            'city' => 'Surat',
            'address' => 'Vesu Main Road, Surat',
            'manager_name' => 'Dr. Priya Mehta',
        ]);

        $admin = User::query()->create([
            'branch_id' => $mainBranch->id,
            'name' => 'Clinic Super Admin',
            'email' => 'admin@smileworks.test',
            'phone' => '9876500000',
            'role' => 'super_admin',
            'job_title' => 'Operations Head',
            'status' => true,
            'monthly_salary' => 95000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $reception = User::query()->create([
            'branch_id' => $mainBranch->id,
            'name' => 'Nisha Reception',
            'email' => 'reception@smileworks.test',
            'phone' => '9876500001',
            'role' => 'receptionist',
            'job_title' => 'Front Desk Executive',
            'status' => true,
            'monthly_salary' => 28000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $accountant = User::query()->create([
            'branch_id' => $secondBranch->id,
            'name' => 'Rakesh Finance',
            'email' => 'accounts@smileworks.test',
            'phone' => '9876500002',
            'role' => 'accountant',
            'job_title' => 'Accounts Manager',
            'status' => true,
            'monthly_salary' => 52000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $doctorOne = User::query()->create([
            'branch_id' => $mainBranch->id,
            'name' => 'Dr. Mehul Shah',
            'email' => 'mehul@smileworks.test',
            'phone' => '9876500003',
            'role' => 'doctor',
            'job_title' => 'Senior Implantologist',
            'status' => true,
            'monthly_salary' => 125000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $doctorTwo = User::query()->create([
            'branch_id' => $secondBranch->id,
            'name' => 'Dr. Priya Mehta',
            'email' => 'priya@smileworks.test',
            'phone' => '9876500004',
            'role' => 'doctor',
            'job_title' => 'Orthodontist',
            'status' => true,
            'monthly_salary' => 118000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $doctorProfileOne = DoctorProfile::query()->create([
            'user_id' => $doctorOne->id,
            'branch_id' => $mainBranch->id,
            'specialty' => 'Implants',
            'room_number' => 'R-02',
            'consultation_fee' => 700,
            'theme_color' => '#0f766e',
            'is_available' => true,
        ]);

        $doctorProfileTwo = DoctorProfile::query()->create([
            'user_id' => $doctorTwo->id,
            'branch_id' => $secondBranch->id,
            'specialty' => 'Orthodontics',
            'room_number' => 'R-04',
            'consultation_fee' => 650,
            'theme_color' => '#ea580c',
            'is_available' => true,
        ]);

        foreach (range(1, 6) as $day) {
            DoctorSchedule::query()->create([
                'doctor_profile_id' => $doctorProfileOne->id,
                'day_of_week' => $day,
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
                'max_appointments' => 14,
            ]);

            DoctorSchedule::query()->create([
                'doctor_profile_id' => $doctorProfileTwo->id,
                'day_of_week' => $day,
                'start_time' => '11:00:00',
                'end_time' => '19:00:00',
                'max_appointments' => 12,
            ]);
        }

        $patientOne = Patient::query()->create([
            'branch_id' => $mainBranch->id,
            'patient_code' => 'PAT-260717-001',
            'name' => 'Aarav Patel',
            'email' => 'aarav@example.com',
            'phone' => '9999900001',
            'gender' => 'Male',
            'date_of_birth' => '1993-08-15',
            'blood_group' => 'B+',
            'address' => 'Ahmedabad',
            'allergies' => 'Penicillin',
            'notes' => 'Interested in implant treatment.',
            'last_visit_at' => now()->subDay(),
        ]);

        $patientTwo = Patient::query()->create([
            'branch_id' => $secondBranch->id,
            'patient_code' => 'PAT-260717-002',
            'name' => 'Diya Sharma',
            'email' => 'diya@example.com',
            'phone' => '9999900002',
            'gender' => 'Female',
            'date_of_birth' => '2001-03-02',
            'blood_group' => 'O+',
            'address' => 'Surat',
            'allergies' => null,
            'notes' => 'Orthodontic follow-up patient.',
            'last_visit_at' => now()->subDays(2),
        ]);

        Inquiry::query()->create([
            'branch_id' => $mainBranch->id,
            'assigned_to' => $reception->id,
            'name' => 'Rohan Jain',
            'phone' => '9999900003',
            'email' => 'rohan@example.com',
            'source' => 'Instagram',
            'treatment_interest' => 'Smile Makeover',
            'status' => 'follow_up',
            'priority' => 'hot',
            'next_follow_up_at' => now()->addDay(),
            'notes' => 'Requested Saturday slot.',
        ]);

        $appointmentOne = Appointment::query()->create([
            'branch_id' => $mainBranch->id,
            'patient_id' => $patientOne->id,
            'doctor_profile_id' => $doctorProfileOne->id,
            'booked_by' => $admin->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '11:45:00',
            'specialty' => 'Implants',
            'treatment_name' => 'Implant Consultation',
            'status' => 'completed',
            'visit_type' => 'consultation',
            'token_no' => 1,
            'estimated_amount' => 2500,
            'paid_amount' => 2500,
            'notes' => 'X-ray reviewed.',
        ]);

        $appointmentTwo = Appointment::query()->create([
            'branch_id' => $secondBranch->id,
            'patient_id' => $patientTwo->id,
            'doctor_profile_id' => $doctorProfileTwo->id,
            'booked_by' => $reception->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '12:30:00',
            'end_time' => '13:00:00',
            'specialty' => 'Orthodontics',
            'treatment_name' => 'Braces Adjustment',
            'status' => 'confirmed',
            'visit_type' => 'follow_up',
            'token_no' => 1,
            'estimated_amount' => 1800,
            'paid_amount' => 500,
            'notes' => 'Reminder sent.',
        ]);

        Payment::query()->create([
            'branch_id' => $mainBranch->id,
            'patient_id' => $patientOne->id,
            'appointment_id' => $appointmentOne->id,
            'invoice_number' => 'INV-00001',
            'payment_date' => now()->toDateString(),
            'amount' => 2500,
            'method' => 'razorpay',
            'status' => 'captured',
            'razorpay_order_id' => 'order_demo_001',
            'razorpay_payment_id' => 'pay_demo_001',
            'razorpay_reference' => 'rzp-demo-ref-001',
            'notes' => 'Paid online at front desk.',
        ]);

        Payment::query()->create([
            'branch_id' => $secondBranch->id,
            'patient_id' => $patientTwo->id,
            'appointment_id' => $appointmentTwo->id,
            'invoice_number' => 'INV-00002',
            'payment_date' => now()->subDay()->toDateString(),
            'amount' => 500,
            'method' => 'cash',
            'status' => 'captured',
            'notes' => 'Advance collected.',
        ]);

        Expense::query()->create([
            'branch_id' => $mainBranch->id,
            'category' => 'Dental Supplies',
            'title' => 'Implant Kit Refill',
            'vendor_name' => 'OralCare Supply Co.',
            'expense_date' => now()->subDay()->toDateString(),
            'amount' => 18000,
            'paid_via' => 'bank',
            'notes' => 'Monthly consumables refill.',
        ]);

        PayrollRecord::query()->create([
            'branch_id' => $mainBranch->id,
            'user_id' => $doctorOne->id,
            'salary_month' => now()->startOfMonth()->toDateString(),
            'gross_salary' => 125000,
            'bonus' => 5000,
            'deductions' => 2500,
            'net_salary' => 127500,
            'payment_status' => 'processed',
            'paid_on' => now()->toDateString(),
        ]);
    }
}
