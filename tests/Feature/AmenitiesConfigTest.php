<?php

test('the amenities config is a non-empty keyed list with labels and icons', function () {
    $amenities = config('courtgo.amenities');

    expect($amenities)->toBeArray()->not->toBeEmpty()
        ->and($amenities)->toHaveKey('parking')
        ->and($amenities['parking'])->toHaveKeys(['label', 'icon'])
        ->and($amenities['parking']['label'])->toBe('Parking');
});
