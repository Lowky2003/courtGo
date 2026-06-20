<?php

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

/** Give an owner an active Stripe subscription row (no Stripe call needed). */
function activeSubscriptionFor(User $owner): void
{
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
}

test('an owner cannot accept bookings without a subscription', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);

    expect($owner->canAcceptBookings())->toBeFalse();
});

test('an owner cannot accept bookings without completing connect onboarding', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => false]);
    activeSubscriptionFor($owner);

    expect($owner->fresh()->canAcceptBookings())->toBeFalse();
});

test('an owner with subscription and onboarding can accept bookings', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    activeSubscriptionFor($owner);

    expect($owner->fresh()->canAcceptBookings())->toBeTrue();
});

test('a court is bookable only when active and its owner can accept bookings', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    activeSubscriptionFor($owner);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $active = Court::factory()->for($venue)->create(['is_active' => true]);
    $inactive = Court::factory()->for($venue)->create(['is_active' => false]);

    expect($active->isBookable())->toBeTrue()
        ->and($inactive->isBookable())->toBeFalse();
});

test('a court is not bookable when the owner is not subscribed', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    expect($court->isBookable())->toBeFalse();
});

test('a court in a pending venue is not bookable even when the owner is live', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    activeSubscriptionFor($owner);
    $venue = Venue::factory()->pending()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    expect($court->fresh()->isBookable())->toBeFalse();
});

test('a pending venue is hidden from customers', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    activeSubscriptionFor($owner);
    $venue = Venue::factory()->pending()->for($owner, 'owner')->create();
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0)
        ->and(Venue::bookable()->count())->toBe(0);
});

test('approving a pending venue makes its courts bookable', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    activeSubscriptionFor($owner);
    $venue = Venue::factory()->pending()->for($owner, 'owner')->create();
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0);

    $venue->update(['approved_at' => now()]);

    expect(Court::bookable()->count())->toBe(1);
});
