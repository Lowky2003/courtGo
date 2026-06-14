<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;

/** A session on a court whose owner is live (subscribed + Connect-onboarded). */
function liveCourtSession(Carbon $date): SessionTemplate
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['city' => 'Subang Jaya']);
    $court = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);

    return SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00', 'price' => 40,
    ]);
}

test('a guest is redirected to login from browse', function () {
    $this->get('/courts')->assertRedirect('/login');
});

test('the browse page shows a bookable court', function () {
    $session = liveCourtSession(Carbon::parse('2026-07-06'));

    $this->actingAs(User::factory()->create())->get('/courts')
        ->assertOk()
        ->assertSee($session->court->venue->name);
});

test('the browse page hides courts whose owner is not live', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]); // not subscribed/onboarded
    $venue = Venue::factory()->for($owner, 'owner')->create();
    Court::factory()->for($venue)->create(['is_active' => true, 'name' => 'Hidden Court']);

    $this->actingAs(User::factory()->create())->get('/courts')->assertDontSee('Hidden Court');
});

test('a customer can book a session (demo mode confirms it)', function () {
    config()->set('cashier.secret', null); // demo mode (no real Stripe)
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]))
        ->assertRedirect();

    $booking = $customer->bookings()->first();
    expect($booking)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatus::Confirmed);
});

test('booking the same slot twice is rejected', function () {
    config()->set('cashier.secret', null);
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]));

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]))
        ->assertSessionHas('booking_error');

    expect(Booking::where('court_id', $session->court_id)->count())->toBe(1);
});

test('my bookings shows the customer booking', function () {
    config()->set('cashier.secret', null);
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]));

    $this->actingAs($customer)->get('/my-bookings')
        ->assertOk()
        ->assertSee($session->court->venue->name);
});
