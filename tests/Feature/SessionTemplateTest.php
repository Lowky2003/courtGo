<?php

use App\Models\Court;
use App\Models\SessionTemplate;

test('a session template belongs to a court', function () {
    $court = Court::factory()->create();
    $template = SessionTemplate::factory()->for($court)->create();

    expect($template->court->id)->toBe($court->id);
});

test('a court has many session templates', function () {
    $court = Court::factory()->create();
    SessionTemplate::factory()->count(2)->for($court)->create();

    expect($court->sessionTemplates)->toHaveCount(2);
});

test('session template casts its fields', function () {
    $template = SessionTemplate::factory()->create([
        'day_of_week' => 1,
        'price' => 40,
        'is_active' => 1,
    ]);

    expect($template->day_of_week)->toBe(1)
        ->and($template->is_active)->toBeTrue()
        ->and((float) $template->price)->toBe(40.0);
});
