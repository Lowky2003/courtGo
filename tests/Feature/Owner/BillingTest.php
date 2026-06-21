<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Billing;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('the billing page renders for an owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/billing')
        ->assertOk()
        ->assertSeeLivewire(Billing::class);
});

test('a customer cannot open the billing page', function () {
    $customer = User::factory()->create(); // customer

    $this->actingAs($customer)->get('/owner/billing')->assertForbidden();
});

test('an owner can save their business registration number', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)
        ->test(Billing::class)
        ->set('business_registration_number', 'BRN-998877')
        ->call('saveBrn')
        ->assertHasNoErrors();

    expect($owner->fresh()->business_registration_number)->toBe('BRN-998877');
});

test('subscribing a venue without stripe configured redirects back safely', function () {
    config()->set('cashier.secret', null);
    config()->set('services.stripe.price_id', null);
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->get(route('owner.billing.subscribe', $venue))
        ->assertRedirect(route('owner.billing'));
});

test('subscribing an already-subscribed venue just returns to billing (no double charge)', function () {
    config()->set('cashier.secret', 'sk_test_x'); // pretend Stripe is configured
    config()->set('services.stripe.price_id', 'price_x');
    $venue = Venue::factory()->subscribed()->create();

    $this->actingAs($venue->owner)
        ->get(route('owner.billing.subscribe', $venue))
        ->assertRedirect(route('owner.billing'));
});

test('an owner cannot subscribe another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create(); // someone else's venue

    $this->actingAs($owner)->get(route('owner.billing.subscribe', $venue))
        ->assertForbidden();
});

test('the checkout return route redirects to billing', function () {
    config()->set('cashier.secret', null); // no Stripe → no sync, just redirect
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)
        ->get(route('owner.billing.subscribed', $venue))
        ->assertRedirect(route('owner.billing'));
});

test('an owner cannot hit another owners checkout return', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create(); // someone else's venue

    $this->actingAs($owner)
        ->get(route('owner.billing.subscribed', $venue))
        ->assertForbidden();
});

test('returning from the billing portal redirects to billing', function () {
    config()->set('cashier.secret', null); // no Stripe → no sync, just redirect
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)
        ->get(route('owner.billing.portal.return'))
        ->assertRedirect(route('owner.billing'));
});

test('the billing page shows a pending cancellation date', function () {
    $venue = Venue::factory()->subscribed()->create();
    // Subscription set to cancel at the end of the period (ends_at in the future).
    $venue->owner->subscriptions()->first()->update(['ends_at' => now()->addDays(10)]);

    $this->actingAs($venue->owner)->get(route('owner.billing'))
        ->assertOk()
        ->assertSee('Cancels'); // still subscribed, but shows it's ending
});

test('connecting a bank without stripe configured redirects back safely', function () {
    config()->set('cashier.secret', null);
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/connect')
        ->assertRedirect(route('owner.billing'));
});
