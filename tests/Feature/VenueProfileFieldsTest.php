<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Profile;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('price range is the min and max of active slot prices', function () {
    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();
    SessionTemplate::factory()->for($court)->create(['price' => 30, 'is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 50, 'is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 999, 'is_active' => false]); // ignored

    expect($venue->priceRange())->toBe(['min' => 30.0, 'max' => 50.0]);
});

test('price range is null when the venue has no priced slots', function () {
    expect(Venue::factory()->create()->priceRange())->toBeNull();
});

test('an announcement is visible only when active, present and not expired', function () {
    expect(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true])->announcementVisible())->toBeTrue()
        ->and(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => false])->announcementVisible())->toBeFalse()
        ->and(Venue::factory()->create(['announcement' => null, 'announcement_active' => true])->announcementVisible())->toBeFalse();

    Carbon::setTestNow('2026-07-01');
    expect(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true, 'announcement_until' => '2026-06-30'])->announcementVisible())->toBeFalse()
        ->and(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true, 'announcement_until' => '2026-07-01'])->announcementVisible())->toBeTrue();
    Carbon::setTestNow();
});

test('an owner can save venue details', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('pricingNote', 'Peak RM45')
        ->set('policy', 'No outside food')
        ->set('contactEmail', 'hi@venue.test')
        ->set('contactWhatsapp', '60123456789')
        ->set('openingHours.1.closed', false)
        ->set('openingHours.1.open', '08:00')
        ->set('openingHours.1.close', '22:00')
        ->call('saveInfo')
        ->assertHasNoErrors();

    $venue->refresh();
    expect($venue->pricing_note)->toBe('Peak RM45')
        ->and($venue->policy)->toBe('No outside food')
        ->and($venue->contact_email)->toBe('hi@venue.test')
        ->and($venue->opening_hours[1]['open'])->toBe('08:00');
});

test('saving details rejects a bad email and a backwards opening time', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('contactEmail', 'not-an-email')
        ->call('saveInfo')
        ->assertHasErrors('contactEmail');

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('openingHours.1.closed', false)
        ->set('openingHours.1.open', '22:00')
        ->set('openingHours.1.close', '08:00')
        ->call('saveInfo')
        ->assertHasErrors('openingHours.1.close');
});
