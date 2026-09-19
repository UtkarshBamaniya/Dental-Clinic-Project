<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'patient_code'     => 'PAT-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
            'first_name'       => fake()->firstName(),
            'middle_name'      => null,
            'last_name'        => fake()->lastName(),
            'gender'           => fake()->randomElement(['male', 'female']),
            'date_of_birth'    => fake()->date('Y-m-d', '-20 years'),
            'mobile'           => fake()->numerify('98########'),
            'alternate_mobile' => null,
            'email'            => fake()->unique()->safeEmail(),
            'address'          => fake()->streetAddress(),
            'city'             => fake()->city(),
            'state'            => fake()->state(),
            'pincode'          => fake()->numerify('######'),
            'occupation'       => fake()->jobTitle(),
            'referred_by'      => null,
            'status'           => 'active',
        ];
    }
}
