<?php

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\SessionTemplate;
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

test('the venue page shows opening hours, price range, announcement, policy and contact', function () {
    // A subscribed venue so the price range (which only counts bookable slots) shows.
    $venue = Venue::factory()->subscribed()->create([
        'announcement' => 'Open house this weekend', 'announcement_active' => true,
        'opening_hours' => [1 => ['closed' => false, 'open' => '08:00', 'close' => '22:00']],
        'pricing_note' => 'Peak RM45', 'policy' => 'No smoking',
        'contact_email' => 'hi@venue.test', 'contact_whatsapp' => '60123456789',
    ]);
    $court = Court::factory()->for($venue)->create(['is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 40, 'is_active' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', $venue))
        ->assertOk()
        ->assertSee('Open house this weekend')              // announcement banner
        ->assertSee('Opening hours')
        ->assertSee('RM 40')                                // price range
        ->assertSee('Peak RM45')                            // pricing note
        ->assertSee('No smoking')                           // policy
        ->assertSee('wa.me/60123456789', escape: false);    // whatsapp contact link
});
