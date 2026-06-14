<?php

use App\Enums\BookingStatus;
use App\Models\Booking;

function checkoutCompleted(int $bookingId, string $paymentStatus = 'paid'): array
{
    return [
        'id' => 'evt_'.uniqid(),
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_123',
            'payment_status' => $paymentStatus,
            'payment_intent' => 'pi_test_123',
            'metadata' => ['booking_id' => (string) $bookingId],
        ]],
    ];
}

test('it confirms a booking on checkout.session.completed', function () {
    $booking = Booking::factory()->pending()->create();

    $this->postJson('/stripe/bookings/webhook', checkoutCompleted($booking->id))->assertOk();

    $fresh = $booking->fresh();
    expect($fresh->status)->toBe(BookingStatus::Confirmed)
        ->and($fresh->payment_status)->toBe('paid')
        ->and($fresh->stripe_payment_intent_id)->toBe('pi_test_123');
});

test('it is idempotent when the same event is delivered twice', function () {
    $booking = Booking::factory()->pending()->create();
    $payload = checkoutCompleted($booking->id);

    $this->postJson('/stripe/bookings/webhook', $payload)->assertOk();
    $this->postJson('/stripe/bookings/webhook', $payload)->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('it does not confirm while payment is still unpaid (async)', function () {
    $booking = Booking::factory()->pending()->create();

    $this->postJson('/stripe/bookings/webhook', checkoutCompleted($booking->id, 'unpaid'))->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('it releases a pending hold on async payment failure', function () {
    $booking = Booking::factory()->pending()->create();

    $this->postJson('/stripe/bookings/webhook', [
        'type' => 'checkout.session.async_payment_failed',
        'data' => ['object' => ['metadata' => ['booking_id' => (string) $booking->id]]],
    ])->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});
