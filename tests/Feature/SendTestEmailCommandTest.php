<?php

test('mail:test sends without error', function () {
    // The test environment uses the in-memory "array" mailer, so this exercises
    // the command end-to-end without delivering anything.
    $this->artisan('mail:test', ['email' => 'someone@example.com'])
        ->assertSuccessful();
});
