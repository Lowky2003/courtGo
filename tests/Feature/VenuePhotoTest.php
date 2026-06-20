<?php

use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a venue has many gallery photos ordered by position', function () {
    $venue = Venue::factory()->create();
    VenuePhoto::factory()->for($venue)->create(['position' => 2, 'path' => 'b.jpg']);
    VenuePhoto::factory()->for($venue)->create(['position' => 1, 'path' => 'a.jpg']);

    expect($venue->photos->pluck('path')->all())->toBe(['a.jpg', 'b.jpg']);
});

test('deleting a gallery photo removes its stored file', function () {
    Storage::fake('public');
    $venue = Venue::factory()->create();
    $path = UploadedFile::fake()->image('g.jpg')->store('venues/gallery', 'public');
    $photo = VenuePhoto::factory()->for($venue)->create(['path' => $path]);
    Storage::disk('public')->assertExists($path);

    $photo->delete();

    Storage::disk('public')->assertMissing($path);
});

test('deleting a venue removes its gallery files', function () {
    Storage::fake('public');
    $venue = Venue::factory()->create();
    $path = UploadedFile::fake()->image('g.jpg')->store('venues/gallery', 'public');
    VenuePhoto::factory()->for($venue)->create(['path' => $path]);

    $venue->delete();

    Storage::disk('public')->assertMissing($path);
});
