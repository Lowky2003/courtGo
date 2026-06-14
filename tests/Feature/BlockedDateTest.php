<?php

use App\Models\BlockedDate;
use App\Models\Court;

test('a blocked date belongs to a court', function () {
    $court = Court::factory()->create();
    $blocked = BlockedDate::factory()->for($court)->create();

    expect($blocked->court->id)->toBe($court->id);
});

test('a court has many blocked dates', function () {
    $court = Court::factory()->create();
    BlockedDate::factory()->count(2)->for($court)->create();

    expect($court->blockedDates)->toHaveCount(2);
});

test('the date is cast to a date object', function () {
    $blocked = BlockedDate::factory()->create(['date' => '2026-12-25']);

    expect($blocked->date->format('Y-m-d'))->toBe('2026-12-25');
});
