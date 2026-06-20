<?php

use App\Models\Venue;
use Illuminate\Support\Facades\Blade;

test('the directions component renders google maps and waze links for the address', function () {
    $venue = Venue::factory()->create([
        'address' => 'Jalan PJS 11', 'city' => 'Subang Jaya', 'state' => 'Selangor',
    ]);

    $html = Blade::render('<x-venue-directions :venue="$venue" />', ['venue' => $venue]);
    $query = urlencode('Jalan PJS 11, Subang Jaya, Selangor');

    // Blade escapes the "&" in hrefs to "&amp;" (correct HTML), so assert the
    // parts around it rather than spanning the ampersand.
    expect($html)->toContain('https://www.google.com/maps/search/?api=1')
        ->and($html)->toContain('https://www.waze.com/ul?q='.$query)
        ->and($html)->toContain($query)            // the encoded address
        ->and($html)->toContain('Google Maps')
        ->and($html)->toContain('Waze');
});
