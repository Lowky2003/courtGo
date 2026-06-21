<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Livewire\Owner\Bookings;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an owner sees their confirmed bookings with court and time', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $court = Court::factory()
        ->for(Venue::factory()->for($owner, 'owner')->create(['name' => 'Sunway Arena']))
        ->create(['name' => 'Court A']);

    Booking::factory()->for($court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addDays(2)->toDateString(),
        'start_time' => '09:00', 'end_time' => '10:00',
    ]);

    $this->actingAs($owner)->get(route('owner.bookings'))
        ->assertOk()
        ->assertSee('Sunway Arena')
        ->assertSee('Court A')
        ->assertSee('9:00 AM');
});

test('an owner does not see another owners bookings', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $rivalCourt = Court::factory()->for(Venue::factory()->create(['name' => 'Rival Arena']))->create();
    Booking::factory()->for($rivalCourt)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($owner)->get(route('owner.bookings'))
        ->assertOk()
        ->assertDontSee('Rival Arena');
});

test('the bookings page filters upcoming vs past', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $court = Court::factory()->for(Venue::factory()->for($owner, 'owner')->create())->create(['name' => 'Court A']);

    Booking::factory()->for($court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->subDays(5)->toDateString(), // a past booking
        'start_time' => '09:00', 'end_time' => '10:00',
    ]);

    Livewire::actingAs($owner)->test(Bookings::class)
        ->assertDontSee('Court A')  // default = upcoming, so the past one is hidden
        ->set('filter', 'past')
        ->assertSee('Court A');
});

test('a non-owner cannot open the owner bookings page', function () {
    $customer = User::factory()->create(); // customer

    $this->actingAs($customer)->get(route('owner.bookings'))->assertForbidden();
});

test('the owner sidebar links to the bookings page', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee(route('owner.bookings'), false);
});
