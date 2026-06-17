<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Courts;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('the wizard creates numbered courts that share one schedule', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 3)
        ->set('namingStyle', 'number')
        ->set('prefix', 'Court')
        ->set('startNumber', 1)
        ->call('toStep2')
        ->assertHasNoErrors()
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.day_of_week', 1)
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->pluck('name')->sort()->values()->all())
        ->toBe(['Court 1', 'Court 2', 'Court 3']);

    // Every court got the shared session.
    expect($venue->courts->every(fn ($court) => $court->sessionTemplates()->count() === 1))->toBeTrue();
});

test('the wizard creates lettered courts with different schedules', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Futsal')
        ->set('count', 2)
        ->set('namingStyle', 'letter')
        ->set('prefix', 'Court')
        ->set('startLetter', 'A')
        ->call('toStep2')
        ->assertHasNoErrors()
        ->set('scheduleMode', 'different')
        ->call('toStep3')
        ->set('courtSessions.0.0.day_of_week', 1)
        ->set('courtSessions.0.0.start_time', '18:00')
        ->set('courtSessions.0.0.end_time', '19:00')
        ->set('courtSessions.0.0.price', 30)
        ->set('courtSessions.1.0.day_of_week', 2)
        ->set('courtSessions.1.0.start_time', '09:00')
        ->set('courtSessions.1.0.end_time', '10:00')
        ->set('courtSessions.1.0.price', 50)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->pluck('name')->sort()->values()->all())->toBe(['Court A', 'Court B']);

    $a = $venue->courts()->where('name', 'Court A')->first();
    expect($a->sessionTemplates()->count())->toBe(1)
        ->and((int) $a->sessionTemplates()->first()->day_of_week)->toBe(1);
});

test('going back and forward keeps per-court schedule edits', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Tennis')
        ->set('count', 2)
        ->set('namingStyle', 'letter')
        ->set('startLetter', 'A')
        ->call('toStep2')
        ->set('scheduleMode', 'different')
        ->call('toStep3')
        ->set('courtSessions.0.0.start_time', '18:00')
        ->call('back')        // back to step 2
        ->call('toStep3')     // re-enter step 3
        ->assertSet('courtSessions.0.0.start_time', '18:00'); // edit preserved
});

test('the wizard requires a sport and at least one court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', '')
        ->set('count', 0)
        ->call('toStep2')
        ->assertHasErrors(['sport', 'count']);
});

test('lettered naming cannot run past Z', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Tennis')
        ->set('count', 3)
        ->set('namingStyle', 'letter')
        ->set('startLetter', 'Z')
        ->call('toStep2')
        ->assertHasErrors(['startLetter']);
});

test('an owner can delete a court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('deleteCourt', $court->id)
        ->assertHasNoErrors();

    expect(Court::whereKey($court->id)->exists())->toBeFalse();
});

test('an owner can toggle a court active status', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('toggleActive', $court->id);

    expect($court->fresh()->is_active)->toBeFalse();
});

test('an owner cannot manage courts of another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $stranger = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($stranger)
        ->get(route('owner.venues.courts', $venue))
        ->assertForbidden();
});

test('the courts page renders for the venue owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('owner.venues.courts', $venue))
        ->assertOk()
        ->assertSeeLivewire(Courts::class);
});
