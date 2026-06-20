<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Profile;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an owner can save amenities for their venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['amenities' => null]);

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('amenities', ['parking', 'wifi'])
        ->call('save')
        ->assertHasNoErrors();

    expect($venue->fresh()->amenities)->toBe(['parking', 'wifi']);
});

test('saving rejects amenity keys that are not in the config list', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('amenities', ['parking', 'not_a_real_amenity'])
        ->call('save')
        ->assertHasErrors('amenities.1');

    expect($venue->fresh()->amenities)->toBeNull();
});

test('a stranger cannot open another owners venue profile', function () {
    $stranger = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create();

    $this->actingAs($stranger)
        ->get(route('owner.venues.profile', $venue))
        ->assertForbidden();
});

test('the profile page renders for the owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('owner.venues.profile', $venue))
        ->assertOk()
        ->assertSeeLivewire(Profile::class);
});
