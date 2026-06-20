<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an owner can add a gallery photo', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('owner.venues.media.photos.store', $venue), [
            'photo' => UploadedFile::fake()->image('court.jpg'),
        ])
        ->assertRedirect();

    $photo = $venue->photos()->first();
    expect($photo)->not->toBeNull();
    Storage::disk('public')->assertExists($photo->path);
});

test('an owner can remove a gallery photo', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $path = UploadedFile::fake()->image('g.jpg')->store('venues/gallery', 'public');
    $photo = VenuePhoto::factory()->for($venue)->create(['path' => $path]);

    $this->actingAs($owner)
        ->delete(route('owner.venues.media.photos.destroy', [$venue, $photo]))
        ->assertRedirect();

    expect(VenuePhoto::whereKey($photo->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});

test('the gallery is capped at 12 photos', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    VenuePhoto::factory()->count(12)->for($venue)->create();

    $this->actingAs($owner)
        ->post(route('owner.venues.media.photos.store', $venue), [
            'photo' => UploadedFile::fake()->image('court.jpg'),
        ])
        ->assertSessionHasErrors('photo');

    expect($venue->photos()->count())->toBe(12);
});

test('an owner cannot add a photo to another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create(); // someone else's venue

    $this->actingAs($owner)
        ->post(route('owner.venues.media.photos.store', $venue), [
            'photo' => UploadedFile::fake()->image('court.jpg'),
        ])
        ->assertForbidden();
});

test('removing a photo that belongs to another venue 404s', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $otherPhoto = VenuePhoto::factory()->create(); // belongs to a different venue

    $this->actingAs($owner)
        ->delete(route('owner.venues.media.photos.destroy', [$venue, $otherPhoto]))
        ->assertNotFound();
});
