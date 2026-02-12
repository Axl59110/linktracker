<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backlink>
 */
class BacklinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_url' => fake()->url(),
            'target_url' => fake()->url(),
            'anchor_text' => fake()->words(3, true),
            'status' => fake()->randomElement(['active', 'lost', 'changed']),
            'http_status' => fake()->randomElement([200, 301, 404, null]),
            'rel_attributes' => fake()->randomElement(['follow', 'nofollow', null]),
            'is_dofollow' => fake()->boolean(80),
            'first_seen_at' => now(),
            'last_checked_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
