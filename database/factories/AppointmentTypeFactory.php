<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentType>
 */
class AppointmentTypeFactory extends Factory
{
    protected $model = AppointmentType::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->randomElement(['Checkup', 'Cleaning', 'Root Canal', 'Extraction', 'Filling', 'Whitening', 'Braces', 'Crown', 'Bridge', 'Denture']),
            'description' => fake()->sentence(),
            'status'      => 'active',
        ];
    }
}
