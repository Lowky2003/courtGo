<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'court_id' => Court::factory(),
            'session_template_id' => null,
            'booking_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'price' => 40,
            'status' => BookingStatus::Confirmed,
            'payment_status' => 'paid',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Pending,
            'payment_status' => 'unpaid',
            'hold_expires_at' => now()->addMinutes(10),
        ]);
    }
}

