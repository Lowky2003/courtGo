<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an owner can upload and remove a verification document', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'ssm',
            'document' => UploadedFile::fake()->create('ssm.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

    $doc = $venue->documents()->first();
    expect($doc)->not->toBeNull()
        ->and($doc->type)->toBe('ssm')
        ->and($doc->original_name)->toBe('ssm.pdf');
    Storage::disk('local')->assertExists($doc->path);

    $this->actingAs($venue->owner)
        ->delete(route('owner.venues.documents.destroy', [$venue, $doc]))
        ->assertRedirect();

    expect($venue->documents()->count())->toBe(0);
    Storage::disk('local')->assertMissing($doc->path);
});

test('a stranger cannot upload a document to someone elses venue', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();
    $other = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($other)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'ssm',
            'document' => UploadedFile::fake()->create('ssm.pdf', 50, 'application/pdf'),
        ])->assertForbidden();
});

test('an invalid document type is rejected', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'totally_made_up',
            'document' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('type');
});

test('only the venue owner and an admin can view a document', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)->post(route('owner.venues.documents.store', $venue), [
        'type' => 'ssm',
        'document' => UploadedFile::fake()->create('ssm.pdf', 30, 'application/pdf'),
    ]);
    $doc = $venue->documents()->first();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $stranger = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($venue->owner)->get(route('venue-documents.show', $doc))->assertOk();
    $this->actingAs($admin)->get(route('venue-documents.show', $doc))->assertOk();
    $this->actingAs($stranger)->get(route('venue-documents.show', $doc))->assertForbidden();
});

test('a guest is sent to login when opening a document', function () {
    $venue = Venue::factory()->create();
    $doc = $venue->documents()->create([
        'type' => 'ssm', 'path' => 'venue-documents/x.pdf', 'original_name' => 'ssm.pdf',
    ]);

    $this->get(route('venue-documents.show', $doc))->assertRedirect();
});

/** Upload a document row for each required verification type (no real files needed). */
function uploadAllDocs(Venue $venue): void
{
    foreach (Venue::verificationKeys() as $type) {
        $venue->documents()->create([
            'type' => $type, 'path' => "venue-documents/{$type}.pdf", 'original_name' => "{$type}.pdf",
        ]);
    }
}

test('My Venues prompts the owner to upload documents when any are missing', function () {
    $venue = Venue::factory()->pending()->create();

    $this->actingAs($venue->owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee('Upload documents');
});

test('the upload-documents prompt clears once every document is provided', function () {
    $venue = Venue::factory()->pending()->create();
    uploadAllDocs($venue);

    $this->actingAs($venue->owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee('Pending approval')
        ->assertDontSee('Upload documents');
});

test('the owner dashboard tells them to upload verification documents to go live', function () {
    $venue = Venue::factory()->pending()->create();

    $this->actingAs($venue->owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Upload your verification documents to go live');
});

test('a rejected venue shows the reason to the owner on the profile', function () {
    $venue = Venue::factory()->pending()->create([
        'rejected_at' => now(),
        'rejection_reason' => 'Your tenancy agreement is not stamped.',
    ]);

    $this->actingAs($venue->owner)->get(route('owner.venues.profile', $venue))
        ->assertOk()
        ->assertSee('Your venue was not approved')
        ->assertSee('Your tenancy agreement is not stamped.');
});

test('a rejected venue shows a Rejected status on My Venues', function () {
    $venue = Venue::factory()->pending()->create([
        'rejected_at' => now(),
        'rejection_reason' => 'Please re-upload a clearer SSM document.',
    ]);

    $this->actingAs($venue->owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee('Rejected');
});

test('re-uploading a document after rejection puts the venue back to pending and un-verifies the item', function () {
    Storage::fake('local');
    $venue = Venue::factory()->pending()->create([
        'rejected_at' => now(),
        'rejection_reason' => 'SSM expired.',
        'verified_items' => ['ssm', 'right_to_occupy'],
    ]);

    $this->actingAs($venue->owner)->post(route('owner.venues.documents.store', $venue), [
        'type' => 'ssm',
        'document' => UploadedFile::fake()->create('ssm-new.pdf', 40, 'application/pdf'),
    ])->assertRedirect();

    $venue->refresh();
    expect($venue->isRejected())->toBeFalse()                 // back in the review queue
        ->and($venue->rejection_reason)->toBeNull()
        ->and($venue->isItemVerified('ssm'))->toBeFalse()      // the re-uploaded item must be re-checked
        ->and($venue->isItemVerified('right_to_occupy'))->toBeTrue(); // untouched item stays verified
});

test('re-uploading on an approved venue does not change its approval or verification', function () {
    Storage::fake('local');
    $venue = Venue::factory()->verified()->create(); // approved (default) + all items verified

    expect($venue->isApproved())->toBeTrue()
        ->and($venue->isFullyVerified())->toBeTrue();

    $this->actingAs($venue->owner)->post(route('owner.venues.documents.store', $venue), [
        'type' => 'ssm',
        'document' => UploadedFile::fake()->create('ssm.pdf', 20, 'application/pdf'),
    ])->assertRedirect();

    $venue->refresh();
    expect($venue->isApproved())->toBeTrue()        // stays live
        ->and($venue->isFullyVerified())->toBeTrue(); // invariant preserved
});

test('the verification section shows while pending and is hidden once approved', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $pending = Venue::factory()->pending()->for($owner, 'owner')->create();
    $this->actingAs($owner)->get(route('owner.venues.profile', $pending))
        ->assertOk()
        ->assertSee('Verification documents');

    $approved = Venue::factory()->for($owner, 'owner')->create(); // approved by default
    $this->actingAs($owner)->get(route('owner.venues.profile', $approved))
        ->assertOk()
        ->assertDontSee('Verification documents');
});
