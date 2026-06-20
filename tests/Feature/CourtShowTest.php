<?php

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;

/** A subscribed + Connect-onboarded owner (billing-ready, so only venue approval gates them). */
function courtShowLiveOwner(): User
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    return $owner;
}

/** A court with one session on tomorrow's weekday (the date CourtShow defaults to). */
function courtShowCourtIn(Venue $venue): Court
{
    $court = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => Carbon::tomorrow()->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
        'price' => 40,
    ]);

    return $court;
}

test('a court in an approved live venue shows bookable sessions', function () {
    $venue = Venue::factory()->for(courtShowLiveOwner(), 'owner')->create(); // approved by default
    $court = courtShowCourtIn($venue);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.show', $court))
        ->assertOk()
        ->assertSee('Book & pay');
});

test('a court in a pending venue shows a not-open notice and no book button', function () {
    $venue = Venue::factory()->pending()->for(courtShowLiveOwner(), 'owner')->create();
    $court = courtShowCourtIn($venue);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.show', $court))
        ->assertOk()
        ->assertSee('open for booking yet')
        ->assertDontSee('Book & pay');
});
