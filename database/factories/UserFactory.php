<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('98########'),
            'email_verified_at' => now(),
            'role' => fake()->randomElement(['branch_admin', 'receptionist', 'doctor', 'accountant']),
            'role_id' => Role::query()->inRandomOrder()->value('id'),
            'job_title' => fake()->jobTitle(),
            'status' => true,
            'monthly_salary' => fake()->numberBetween(25000, 90000),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
