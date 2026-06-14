<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\SessionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionTemplate>
 */
class SessionTemplateFactory extends Factory
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
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'price' => fake()->randomElement([30, 40, 50, 60]),
            'is_active' => true,
        ];
    }
}
