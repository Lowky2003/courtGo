<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;

test('a venue belongs to an owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    expect($venue->owner->id)->toBe($owner->id);
});

test('an owner has many venues', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    Venue::factory()->count(2)->for($owner, 'owner')->create();

    expect($owner->venues)->toHaveCount(2);
});
