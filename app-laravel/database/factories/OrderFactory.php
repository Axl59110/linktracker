<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'platform_id' => null,
            'backlink_id' => null,
            'status'      => 'pending',
            'target_url'  => 'https://' . $this->faker->domainName() . '/' . $this->faker->slug(),
            'source_url'  => 'https://' . $this->faker->domainName() . '/' . $this->faker->slug(),
            'anchor_text' => $this->faker->words(3, true),
            'tier_level'  => 'tier1',
            'spot_type'   => 'external',
            'price'       => $this->faker->randomFloat(2, 10, 500),
            'currency'    => 'EUR',
            'invoice_paid' => false,
            'ordered_at'  => now(),
        ];
    }
}
