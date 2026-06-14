<?php

use App\Enums\UserRole;
use App\Models\User;

test('the connect webhook marks an owner onboarded when their account is ready', function () {
    $owner = User::factory()->create([
        'role' => UserRole::Owner,
        'stripe_connect_account_id' => 'acct_ready_123',
        'connect_onboarded' => false,
    ]);

    $this->postJson('/stripe/connect/webhook', [
        'type' => 'account.updated',
        'account' => 'acct_ready_123',
        'data' => ['object' => [
            'id' => 'acct_ready_123',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]],
    ])->assertOk();

    expect($owner->fresh()->connect_onboarded)->toBeTrue();
});

test('the connect webhook leaves an owner not-onboarded when the account is incomplete', function () {
    $owner = User::factory()->create([
        'role' => UserRole::Owner,
        'stripe_connect_account_id' => 'acct_incomplete_456',
        'connect_onboarded' => false,
    ]);

    $this->postJson('/stripe/connect/webhook', [
        'type' => 'account.updated',
        'data' => ['object' => [
            'id' => 'acct_incomplete_456',
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'details_submitted' => true,
        ]],
    ])->assertOk();

    expect($owner->fresh()->connect_onboarded)->toBeFalse();
});

test('the connect webhook ignores unrelated events', function () {
    $owner = User::factory()->create([
        'role' => UserRole::Owner,
        'stripe_connect_account_id' => 'acct_other_789',
        'connect_onboarded' => true,
    ]);

    $this->postJson('/stripe/connect/webhook', [
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_123']],
    ])->assertOk();

    expect($owner->fresh()->connect_onboarded)->toBeTrue();
});
