<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/** A bookable session (live owner + admin-approved venue). */
function notifyBookableSession(Carbon $date): SessionTemplate
{
    $venue = Venue::factory()->subscribed()->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    return SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00', 'price' => 40,
    ]);
}

test('confirming a demo booking emails the customer', function () {
    Notification::fake();
    config()->set('cashier.secret', null); // demo confirm path (no Stripe keys)

    $date = Carbon::tomorrow();
    $session = notifyBookableSession($date);
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('bookings.checkout', ['court' => $session->court_id, 'session' => $session->id, 'date' => $date->toDateString()]))
        ->assertRedirect();

    Notification::assertSentTo($customer, BookingConfirmed::class);
});

test('the payment webhook emails the customer when it confirms a booking', function () {
    Notification::fake();

    $booking = Booking::factory()->pending()->create();

    $this->postJson('/stripe/bookings/webhook', [
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'payment_status' => 'paid',
            'metadata' => ['booking_ids' => (string) $booking->id],
        ]],
    ])->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    Notification::assertSentTo($booking->customer, BookingConfirmed::class);
});

test('the confirmation email lists the venue, every slot and the total', function () {
    $date = Carbon::tomorrow();
    $session = notifyBookableSession($date);
    $court = $session->court;
    $customer = User::factory()->create();

    // Two confirmed slots booked together on the same court.
    $b1 = Booking::factory()->for($court)->create([
        'customer_id' => $customer->id, 'booking_group' => 'g', 'status' => BookingStatus::Confirmed,
        'booking_date' => $date->toDateString(), 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'price' => 40,
    ]);
    $b2 = Booking::factory()->for($court)->create([
        'customer_id' => $customer->id, 'booking_group' => 'g', 'status' => BookingStatus::Confirmed,
        'booking_date' => $date->toDateString(), 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'price' => 40,
    ]);

    $mail = (new BookingConfirmed(collect([$b1, $b2])))->toMail($customer);
    $body = implode("\n", array_merge($mail->introLines, $mail->outroLines));

    expect($mail->subject)->toBe('Your CourtGo booking is confirmed')
        ->and($body)->toContain($court->venue->name)
        ->and($body)->toContain($court->name)
        ->and($body)->toContain('Total paid: RM 80.00')
        ->and($body)->toContain('https://www.google.com/maps/search/') // location map link
        ->and($mail->actionText)->toBe('View my booking')
        ->and($mail->actionUrl)->toBe(route('bookings.show', $b1)); // detail page, not the list
});

test('a webhook retry does not email the customer twice', function () {
    Notification::fake();

    $booking = Booking::factory()->pending()->create();
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['payment_status' => 'paid', 'metadata' => ['booking_ids' => (string) $booking->id]]],
    ];

    $this->postJson('/stripe/bookings/webhook', $payload)->assertOk();
    $this->postJson('/stripe/bookings/webhook', $payload)->assertOk(); // Stripe retry

    Notification::assertSentToTimes($booking->customer, BookingConfirmed::class, 1);
});
