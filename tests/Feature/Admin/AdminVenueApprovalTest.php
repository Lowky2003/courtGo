<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Venues;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

/** A subscribed + Connect-onboarded owner (billing-ready, so only venue approval gates them). */
function approvalLiveOwner(): User
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

test('a customer cannot open the admin venues page', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.venues'))->assertForbidden();
});

test('the admin venues page renders for an admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues'))
        ->assertOk()
        ->assertSeeLivewire(Venues::class);
});

test('pending venues are listed before approved ones', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Venue::factory()->create(['name' => 'Approved Arena']);           // approved by default
    Venue::factory()->pending()->create(['name' => 'Pending Place']); // awaiting approval

    $this->actingAs($admin)->get(route('admin.venues'))
        ->assertOk()
        ->assertSeeInOrder(['Pending Place', 'Approved Arena']);
});

test('approving one venue makes its courts bookable while the owners other pending venue stays hidden', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $owner = approvalLiveOwner();

    $approved = Venue::factory()->pending()->for($owner, 'owner')->create();
    Court::factory()->for($approved)->create(['is_active' => true]);

    $stillPending = Venue::factory()->pending()->for($owner, 'owner')->create();
    Court::factory()->for($stillPending)->create(['is_active' => true]);

    Livewire::actingAs($admin)->test(Venues::class)->call('approve', $approved->id);

    expect($approved->fresh()->isApproved())->toBeTrue()
        ->and($stillPending->fresh()->isApproved())->toBeFalse()
        ->and(Court::bookable()->count())->toBe(1)  // only the approved venue's court
        ->and(Venue::bookable()->count())->toBe(1);
});
