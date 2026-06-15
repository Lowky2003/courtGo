<?php

use App\Models\User;

test('the google redirect is disabled (sends back to login) when google is not configured', function () {
    config()->set('services.google.client_id', null);

    $this->get('/auth/google/redirect')->assertRedirect('/login');
});

test('the google redirect goes to google when configured', function () {
    config()->set('services.google.client_id', 'test-client-id.apps.googleusercontent.com');
    config()->set('services.google.client_secret', 'test-secret');

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirectContains('accounts.google.com');
});

test('the login page hides the google button when google is not configured', function () {
    config()->set('services.google.client_id', null);

    $this->get('/login')->assertDontSee('Sign in with Google');
});
