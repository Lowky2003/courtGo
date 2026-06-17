<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the photo page renders for the venue owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['name' => 'Smash Arena']);

    $this->actingAs($owner)
        ->get(route('owner.venues.photo.edit', $venue))
        ->assertOk()
        ->assertSee('Smash Arena');
});

test('an owner can upload a venue photo via a single request', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('owner.venues.photo.update', $venue), [
            'photo' => UploadedFile::fake()->image('v.jpg'),
        ])
        ->assertRedirect(route('owner.venues.index'));

    $venue->refresh();
    expect($venue->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($venue->image_path);
});

test('uploading a new photo deletes the old one', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $old = UploadedFile::fake()->image('old.jpg')->store('venues', 'public');
    $venue = Venue::factory()->for($owner, 'owner')->create(['image_path' => $old]);

    $this->actingAs($owner)
        ->post(route('owner.venues.photo.update', $venue), [
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect();

    $venue->refresh();
    expect($venue->image_path)->not->toBe($old);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($venue->image_path);
});

test('the uploaded photo must be an image', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('owner.venues.photo.update', $venue), [
            'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
});

test('an owner cannot upload to another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create(); // a different owner's venue

    $this->actingAs($owner)
        ->post(route('owner.venues.photo.update', $venue), [
            'photo' => UploadedFile::fake()->image('x.jpg'),
        ])->assertForbidden();

    $this->actingAs($owner)
        ->get(route('owner.venues.photo.edit', $venue))
        ->assertForbidden();
});
