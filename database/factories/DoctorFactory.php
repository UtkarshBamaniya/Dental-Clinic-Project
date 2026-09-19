<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            // Create a user with role_id=0 (no role) to satisfy NOT NULL constraint in SQLite test DB
            'user_id'          => User::factory()->state(['role_id' => 0]),
            'doctor_code'      => 'DOC-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'first_name'       => fake()->firstName(),
            'last_name'        => fake()->lastName(),
            'mobile'           => fake()->numerify('98########'),
            'email'            => fake()->unique()->safeEmail(),
            'specialization'   => fake()->randomElement(['General Dentistry', 'Orthodontics', 'Root Canal']),
            'consultation_fee' => fake()->numberBetween(200, 800),
            'status'           => 'active',
        ];
    }
}
