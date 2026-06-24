<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Livewire\Owner\Bookings;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('the calendar defaults to the owners first venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)->test(Bookings::class)
        ->assertSet('venueId', $venue->id);
});

test('selecting an hour shows which courts are booked and which are free', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $courtA = Court::factory()->for($venue)->create(['name' => 'Court A']);
    Court::factory()->for($venue)->create(['name' => 'Court B']); // stays free

    $customer = User::factory()->create(['name' => 'Alice Tan']);
    $date = now()->addDays(2)->toDateString();
    Booking::factory()->for($courtA)->for($customer, 'customer')->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => $date,
        'start_time' => '19:00', 'end_time' => '20:00',
    ]);

    Livewire::actingAs($owner)->test(Bookings::class)
        ->set('date', $date)
        ->call('selectHour', 19)
        ->assertSee('7 PM')
        ->assertSee('Court A')      // booked column + chip
        ->assertSee('Court B')      // free column + chip
        ->assertSee('Alice Tan');   // who's playing, in the detail line
});

test('only confirmed bookings count toward the calendar', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['name' => 'Court A']);

    $date = now()->addDays(2)->toDateString();
    Booking::factory()->for($court)->pending()->create([
        'booking_date' => $date,
        'start_time' => '19:00', 'end_time' => '20:00',
    ]);

    Livewire::actingAs($owner)->test(Bookings::class)
        ->set('date', $date)
        ->assertSee('every court is free'); // the pending hold isn't shown
});

test('an owner cannot view another owners venue on the calendar', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $myVenue = Venue::factory()->for($owner, 'owner')->create(['name' => 'My Arena']);
    $rivalVenue = Venue::factory()->create(['name' => 'Rival Arena']);

    Livewire::actingAs($owner)->test(Bookings::class)
        ->set('venueId', $rivalVenue->id)
        ->assertSet('venueId', $myVenue->id) // reset to a venue the owner owns
        ->assertDontSee('Rival Arena');
});

test('stepping the day forward moves off today and clears the now-selection', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)->test(Bookings::class)
        ->assertSet('date', now()->toDateString())
        ->call('changeDay', 1)
        ->assertSet('date', now()->addDay()->toDateString())
        ->assertSet('selectedHour', null)
        ->call('goToday')
        ->assertSet('date', now()->toDateString());
});
