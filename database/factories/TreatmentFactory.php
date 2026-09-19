<?php

namespace Database\Factories;

use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'treatment_code'   => 'TRT-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'name'             => fake()->unique()->randomElement(['Scaling', 'Root Canal', 'Extraction', 'Filling', 'Crown', 'Braces', 'Whitening', 'Implant', 'Veneers', 'Bridge']),
            'description'      => fake()->sentence(),
            'default_price'    => fake()->numberBetween(500, 5000),
            'duration_minutes' => fake()->numberBetween(15, 90),
            'status'           => 'active',
        ];
    }
}
