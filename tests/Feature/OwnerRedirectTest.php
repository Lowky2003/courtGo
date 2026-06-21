<?php

use App\Enums\UserRole;
use App\Models\User;

test('a logged-in owner is redirected from the homepage to the dashboard', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('home'))->assertRedirect(route('dashboard'));
});

test('an owner is redirected away from the customer browse page', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('courts.browse'))->assertRedirect(route('dashboard'));
});

test('a customer can still see the homepage and browse', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('home'))->assertOk();
    $this->actingAs($customer)->get(route('courts.browse'))->assertOk();
});

test('a guest can still see the homepage', function () {
    $this->get(route('home'))->assertOk();
});
