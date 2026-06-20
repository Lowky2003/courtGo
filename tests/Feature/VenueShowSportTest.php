<?php

use App\Enums\UserRole;
use App\Livewire\VenueShow;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** A live, admin-approved venue (subscribed + Connect-onboarded owner). */
function multiSportVenue(): Venue
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active', 'stripe_price' => 'price_test', 'quantity' => 1,
    ]);

    return Venue::factory()->for($owner, 'owner')->create();
}

test('a multi-sport venue shows one sport at a time and can switch', function () {
    $venue = multiSportVenue();
    $date = Carbon::tomorrow();

    $badminton = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton', 'name' => 'Court A']);
    $futsal = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Futsal', 'name' => 'Pitch 1']);
    SessionTemplate::factory()->for($badminton)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00', 'price' => 40]);
    SessionTemplate::factory()->for($futsal)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '18:00', 'end_time' => '20:00', 'price' => 80]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString());

    // Both sports offered; defaults to the first alphabetically (Badminton).
    $component->assertSet('sport', 'Badminton');
    expect($component->viewData('sports'))->toBe(['Badminton', 'Futsal'])
        ->and($component->viewData('courts')->pluck('sport')->unique()->values()->all())->toBe(['Badminton']);

    // Switching shows only the other sport's courts and clears any selection.
    $component->call('selectSport', 'Futsal')->assertSet('sport', 'Futsal');
    expect($component->viewData('courts')->pluck('sport')->unique()->values()->all())->toBe(['Futsal']);
});

test('the venue page opens on the sport the customer searched for', function () {
    $venue = multiSportVenue();
    Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);
    Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Futsal']);

    // Arriving with ?sport=Futsal (the link the browse page builds) keeps that sport.
    Livewire::withQueryParams(['sport' => 'Futsal'])
        ->actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertSet('sport', 'Futsal');
});

test('changing sport via the url binding clears the selection', function () {
    $venue = multiSportVenue();
    $date = Carbon::tomorrow();
    $badminton = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);
    $futsal = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Futsal']);
    $bSession = SessionTemplate::factory()->for($badminton)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00', 'price' => 40]);
    SessionTemplate::factory()->for($futsal)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '18:00', 'end_time' => '20:00', 'price' => 80]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->call('toggleSlot', $badminton->id, $bSession->id)
        ->assertSet('selected', [$badminton->id.'-'.$bSession->id]);

    // Changing the sport property directly (as the URL/back button would) clears it.
    $component->set('sport', 'Futsal')->assertSet('selected', []);
});

test('a single-sport venue still works and defaults to that sport', function () {
    $venue = multiSportVenue();
    Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);

    Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertSet('sport', 'Badminton');
});
