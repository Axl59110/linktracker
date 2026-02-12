<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BacklinkCheck>
 */
class BacklinkCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'http_status' => 200,
            'is_present' => true,
            'anchor_text' => fake()->words(3, true),
            'rel_attributes' => 'follow',
            'response_time' => fake()->numberBetween(100, 2000),
            'checked_at' => now(),
        ];
    }

    /**
     * Indicate that the check was successful (backlink found).
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status' => 200,
            'is_present' => true,
            'response_time' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Indicate that the backlink was not found (404).
     */
    public function notFound(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status' => 404,
            'is_present' => false,
            'anchor_text' => null,
            'response_time' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * Indicate that the check failed (timeout or error).
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status' => null,
            'is_present' => false,
            'response_time' => null,
        ]);
    }
}
