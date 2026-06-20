<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->state(['role' => UserRole::Owner]),
            'name' => fake()->company().' Sports Centre',
            'description' => fake()->sentence(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['Selangor', 'Kuala Lumpur', 'Penang', 'Johor']),
            // Approved by default so existing tests build live venues; use pending()
            // for the awaiting-approval case.
            'approved_at' => now(),
        ];
    }

    /**
     * Indicate that the venue is still awaiting admin approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
        ]);
    }
}
