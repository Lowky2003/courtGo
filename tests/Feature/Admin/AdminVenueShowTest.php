<?php

use App\Enums\UserRole;
use App\Livewire\Admin\VenueShow;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an admin can open a venue and see its information', function () {
    $venue = Venue::factory()->create([
        'name' => 'Sunway Arena',
        'city' => 'Subang Jaya',
        'description' => 'Eight feature courts with parking.',
        'policy' => 'No outside food allowed.',
    ]);
    Court::factory()->for($venue)->create(['name' => 'Court A', 'sport' => 'Badminton']);

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues.show', $venue))
        ->assertOk()
        ->assertSee('Sunway Arena')
        ->assertSee($venue->owner->email)        // owner details
        ->assertSee('Court A')                   // courts
        ->assertSee('Badminton')
        ->assertSee('Eight feature courts with parking.') // description
        ->assertSee('No outside food allowed.'); // policy
});

test('an admin can approve a venue from its detail page', function () {
    $venue = Venue::factory()->pending()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertSee('Pending approval')
        ->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('the venue list links through to the detail page', function () {
    $venue = Venue::factory()->create(['name' => 'Linkable Venue']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues'))
        ->assertOk()
        ->assertSee(route('admin.venues.show', $venue), escape: false);
});

test('a non-admin cannot open the admin venue detail', function () {
    $venue = Venue::factory()->create();
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('admin.venues.show', $venue))->assertForbidden();
});
