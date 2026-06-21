<?php

use App\Enums\UserRole;
use App\Livewire\Admin\VenueShow;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\VenueApproved;
use App\Notifications\VenueRejected;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can open a venue and see its information', function () {
    $venue = Venue::factory()->create([
        'name' => 'Sunway Arena',
        'city' => 'Subang Jaya',
        'description' => 'Eight feature courts with parking.',
        'policy' => 'No outside food allowed.',
    ]);
    Court::factory()->for($venue)->create(['name' => 'Court A', 'sport' => 'Badminton']);

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues.show', $venue))
        ->assertOk()
        ->assertSee('Sunway Arena')
        ->assertSee($venue->owner->email)        // owner details
        ->assertSee('Court A')                   // courts
        ->assertSee('Badminton')
        ->assertSee('Eight feature courts with parking.') // description
        ->assertSee('No outside food allowed.'); // policy
});

test('an admin can approve a venue from its detail page once fully verified', function () {
    $venue = Venue::factory()->pending()->verified()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertSee('Pending approval')
        ->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('a venue cannot be approved until every verification item is ticked', function () {
    $venue = Venue::factory()->pending()->create(); // no items verified
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('approve'); // gated — should do nothing

    expect($venue->fresh()->isApproved())->toBeFalse();

    // The owner uploads each document, then the admin ticks each item.
    foreach (Venue::verificationKeys() as $type) {
        $venue->documents()->create(['type' => $type, 'path' => "venue-documents/{$type}.pdf", 'original_name' => "{$type}.pdf"]);
    }

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm')
        ->call('toggleVerified', 'right_to_occupy')
        ->call('toggleVerified', 'council_licence')
        ->call('toggleVerified', 'address_proof')
        ->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('approving a venue emails the owner', function () {
    Notification::fake();
    $venue = Venue::factory()->pending()->verified()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
    Notification::assertSentTo($venue->owner, VenueApproved::class);
});

test('an admin can reject a venue with a reason that is emailed to the owner', function () {
    Notification::fake();
    $venue = Venue::factory()->pending()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->call('startReject')
        ->set('rejectionReason', 'The SSM certificate has expired — please upload a current one.')
        ->call('reject')
        ->assertHasNoErrors();

    $venue->refresh();
    expect($venue->isRejected())->toBeTrue()
        ->and($venue->rejection_reason)->toContain('SSM certificate has expired');
    Notification::assertSentTo($venue->owner, VenueRejected::class);
});

test('a non-admin cannot run admin venue actions over livewire', function () {
    $venue = Venue::factory()->pending()->verified()->create();
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    // The role middleware only guards the page load; the component must re-check.
    Livewire::actingAs($owner)
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertForbidden();
});

test('a whitespace-only rejection reason is rejected', function () {
    Notification::fake();
    $venue = Venue::factory()->pending()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->set('rejectionReason', '       ')
        ->call('reject')
        ->assertHasErrors('rejectionReason');

    expect($venue->fresh()->isRejected())->toBeFalse();
    Notification::assertNothingSent();
});

test('the venue approval and rejection emails build without error', function () {
    $venue = Venue::factory()->create(['rejection_reason' => 'Needs a clearer SSM document.']);

    expect((new VenueApproved($venue))->toMail($venue->owner))
        ->toBeInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class);
    expect((new VenueRejected($venue))->toMail($venue->owner))
        ->toBeInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class);
});

test('rejecting requires a reason', function () {
    Notification::fake();
    $venue = Venue::factory()->pending()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->set('rejectionReason', '')
        ->call('reject')
        ->assertHasErrors('rejectionReason');

    expect($venue->fresh()->isRejected())->toBeFalse();
    Notification::assertNothingSent();
});

test('an admin can reject a single verification item with a reason, which removes its file and un-verifies it', function () {
    Storage::fake('local');
    $venue = Venue::factory()->pending()->create(['verified_items' => ['ssm']]);
    $venue->documents()->create(['type' => 'ssm', 'path' => 'venue-documents/ssm.pdf', 'original_name' => 'ssm.pdf']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->call('startRejectItem', 'ssm')
        ->set('itemReason', 'The SSM certificate has expired.')
        ->call('rejectItem')
        ->assertHasNoErrors();

    $venue->refresh();
    expect($venue->isItemRejected('ssm'))->toBeTrue()
        ->and($venue->itemRejectionReason('ssm'))->toContain('expired')
        ->and($venue->isItemVerified('ssm'))->toBeFalse()
        ->and($venue->documents()->where('type', 'ssm')->count())->toBe(0);
});

test('a rejected item cannot be marked verified until the owner re-uploads', function () {
    $venue = Venue::factory()->pending()->create(['item_rejections' => ['ssm' => 'expired']]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm');

    expect($venue->fresh()->isItemVerified('ssm'))->toBeFalse(); // no document → blocked
});

test('rejecting a single item requires a reason', function () {
    $venue = Venue::factory()->pending()->create();
    $venue->documents()->create(['type' => 'ssm', 'path' => 'p', 'original_name' => 'ssm.pdf']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->call('startRejectItem', 'ssm')
        ->set('itemReason', '   ')
        ->call('rejectItem')
        ->assertHasErrors('itemReason');

    expect($venue->fresh()->isItemRejected('ssm'))->toBeFalse();
});

test('whole-venue rejection removes every uploaded document', function () {
    Notification::fake();
    Storage::fake('local');
    $venue = Venue::factory()->pending()->create();
    foreach (Venue::verificationKeys() as $t) {
        $venue->documents()->create(['type' => $t, 'path' => "venue-documents/{$t}.pdf", 'original_name' => "{$t}.pdf"]);
    }
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])
        ->call('startReject')
        ->set('rejectionReason', 'Everything needs redoing properly.')
        ->call('reject');

    $venue->refresh();
    expect($venue->isRejected())->toBeTrue()
        ->and($venue->documents()->count())->toBe(0);
    Notification::assertSentTo($venue->owner, VenueRejected::class);
});

test('approving a previously rejected venue clears the rejection', function () {
    Notification::fake();
    $venue = Venue::factory()->pending()->verified()->create(['rejected_at' => now(), 'rejection_reason' => 'old reason']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)->test(VenueShow::class, ['venue' => $venue])->call('approve');

    $venue->refresh();
    expect($venue->isApproved())->toBeTrue()
        ->and($venue->isRejected())->toBeFalse()
        ->and($venue->rejection_reason)->toBeNull();
});

test('an admin cannot mark an item verified when the owner uploaded no document', function () {
    $venue = Venue::factory()->pending()->create(); // no documents
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm');

    expect($venue->fresh()->isItemVerified('ssm'))->toBeFalse(); // blocked — nothing uploaded

    // Once the document exists, the same action verifies it.
    $venue->documents()->create(['type' => 'ssm', 'path' => 'venue-documents/ssm.pdf', 'original_name' => 'ssm.pdf']);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm');

    expect($venue->fresh()->isItemVerified('ssm'))->toBeTrue();
});

test('the venue list links through to the detail page', function () {
    $venue = Venue::factory()->create(['name' => 'Linkable Venue']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues'))
        ->assertOk()
        ->assertSee(route('admin.venues.show', $venue), escape: false);
});

test('a non-admin cannot open the admin venue detail', function () {
    $venue = Venue::factory()->create();
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('admin.venues.show', $venue))->assertForbidden();
});
