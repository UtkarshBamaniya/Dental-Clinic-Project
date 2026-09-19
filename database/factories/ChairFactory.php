<?php

namespace Database\Factories;

use App\Models\Chair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chair>
 */
class ChairFactory extends Factory
{
    protected $model = Chair::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'chair_name'   => 'Chair ' . $seq,
            'chair_number' => $seq,
            'status'       => 'active',
        ];
    }
}
