<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

test('an owner sees a court booked on the calendar today', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $court = Court::factory()
        ->for(Venue::factory()->for($owner, 'owner')->create(['name' => 'Sunway Arena']))
        ->create(['name' => 'Court A']);

    $customer = User::factory()->create(['name' => 'Alice Tan']);
    Booking::factory()->for($court)->for($customer, 'customer')->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->toDateString(),
        'start_time' => '09:00', 'end_time' => '10:00',
    ]);

    $this->actingAs($owner)->get(route('owner.bookings'))
        ->assertOk()
        ->assertSee('Court A')   // a column on the calendar
        ->assertSee('Alice');    // the booked cell shows who's playing
});

test('an owner does not see another owners bookings', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    Venue::factory()->for($owner, 'owner')->create();

    $rivalCourt = Court::factory()->for(Venue::factory()->create(['name' => 'Rival Arena']))->create();
    Booking::factory()->for($rivalCourt)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->toDateString(),
    ]);

    $this->actingAs($owner)->get(route('owner.bookings'))
        ->assertOk()
        ->assertDontSee('Rival Arena');
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
