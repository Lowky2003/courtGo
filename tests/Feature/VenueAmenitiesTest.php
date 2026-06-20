<?php

use App\Models\Venue;

test('a venue stores amenity keys and returns their config labels in config order', function () {
    $venue = Venue::factory()->create(['amenities' => ['cafe', 'parking', 'nonsense']]);

    $labels = collect($venue->fresh()->amenityLabels());

    // Invalid keys dropped; valid ones returned in config order (parking before cafe).
    expect($labels->pluck('key')->all())->toBe(['parking', 'cafe'])
        ->and($labels->firstWhere('key', 'parking')['label'])->toBe('Parking')
        ->and($labels->firstWhere('key', 'parking')['icon'])->toBe('truck');
});

test('a venue with no amenities returns an empty list', function () {
    $venue = Venue::factory()->create(['amenities' => null]);

    expect($venue->amenityLabels())->toBe([]);
});
