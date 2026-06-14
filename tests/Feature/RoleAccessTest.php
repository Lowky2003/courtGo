<?php

use App\Enums\UserRole;
use App\Models\User;

test('a customer cannot open an owner-only route', function () {
    $customer = User::factory()->create(); // defaults to customer

    $this->actingAs($customer)->get('/owner')->assertForbidden();
});

test('an owner can open an owner-only route', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner')->assertOk();
});

test('a guest is redirected to login from an owner-only route', function () {
    $this->get('/owner')->assertRedirect('/login');
});
