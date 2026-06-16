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

test('the browse page lists the place (venue), not individual courts', function () {
    $session = liveCourtSession(Carbon::parse('2026-07-06'));

    $this->actingAs(User::factory()->create())->get('/courts')
        ->assertOk()
        ->assertSee($session->court->venue->name);
});

test('the browse page hides places whose owner is not live', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]); // not subscribed/onboarded
    $venue = Venue::factory()->for($owner, 'owner')->create(['name' => 'Hidden Hall']);
    Court::factory()->for($venue)->create(['is_active' => true]);

    $this->actingAs(User::factory()->create())->get('/courts')->assertDontSee('Hidden Hall');
});

test('the browse page can filter places by name', function () {
    $a = liveCourtSession(Carbon::parse('2026-07-06'));
    $a->court->venue->update(['name' => 'Alpha Hall']);
    $b = liveCourtSession(Carbon::parse('2026-07-06'));
    $b->court->venue->update(['name' => 'Beta Hall']);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['name' => 'Alpha']))
        ->assertSee('Alpha Hall')
        ->assertDontSee('Beta Hall');
});

test('the venue page shows available courts for the chosen date', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', ['venue' => $session->court->venue, 'date' => $date->toDateString()]))
        ->assertOk()
        ->assertSee($session->court->venue->name)
        ->assertSee($session->court->name);
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

test('a customer can resume payment for a pending booking', function () {
    config()->set('cashier.secret', null); // demo mode confirms
    $customer = User::factory()->create();
    $booking = Booking::factory()->pending()->create(['customer_id' => $customer->id]);

    $this->actingAs($customer)->get(route('bookings.pay', $booking))->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a customer cannot resume payment for an expired hold', function () {
    $customer = User::factory()->create();
    $booking = Booking::factory()->pending()->create([
        'customer_id' => $customer->id,
        'hold_expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($customer)->get(route('bookings.pay', $booking))
        ->assertRedirect(route('bookings.mine'));

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('my bookings can filter to awaiting payment', function () {
    $customer = User::factory()->create();

    $cVenue = Venue::factory()->create(['name' => 'Confirmed Venue']);
    Booking::factory()->for(Court::factory()->for($cVenue)->create())
        ->create(['customer_id' => $customer->id, 'status' => BookingStatus::Confirmed]);

    $aVenue = Venue::factory()->create(['name' => 'Awaiting Venue']);
    Booking::factory()->pending()->for(Court::factory()->for($aVenue)->create())
        ->create(['customer_id' => $customer->id]);

    $this->actingAs($customer)->get(route('bookings.mine', ['filter' => 'awaiting']))
        ->assertSee('Awaiting Venue')
        ->assertDontSee('Confirmed Venue');
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
