<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\BlockedDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'date' => fake()->unique()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'reason' => fake()->randomElement(['Public holiday', 'Maintenance', 'Private event']),
        ];
    }
}
