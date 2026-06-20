<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\VenuePhoto;

test('the venue page shows amenities, gallery, and directions', function () {
    $venue = Venue::factory()->create([
        'amenities' => ['parking', 'wifi'],
        'address' => 'Jalan PJS 11', 'city' => 'Subang Jaya', 'state' => 'Selangor',
    ]);
    VenuePhoto::factory()->for($venue)->create(['path' => 'venues/gallery/x.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', $venue))
        ->assertOk()
        ->assertSee('Parking')                                    // amenity label
        ->assertSee('Free WiFi')
        ->assertSee('venues/gallery/x.jpg')                       // gallery image path in <img src>
        ->assertSee('https://www.waze.com/ul', escape: false);    // directions present
});
