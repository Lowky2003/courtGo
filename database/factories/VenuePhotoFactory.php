<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenuePhoto>
 */
class VenuePhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'path' => 'venues/gallery/'.fake()->uuid().'.jpg',
            'position' => 0,
        ];
    }
}
