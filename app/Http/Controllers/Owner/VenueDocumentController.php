<?php

namespace App\Http\Controllers\Owner;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VenueDocumentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Upload rules: a single PDF or image, size-capped. Stored on the PRIVATE
     * disk — these are legal/business documents, not public gallery photos.
     *
     * @var array<int, string>
     */
    private const FILE_RULES = ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'];

    /** Owner uploads a verification document of a given type. */
    public function store(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $validated = $request->validate([
            'type' => ['required', Rule::in(Venue::verificationKeys())],
            'document' => self::FILE_RULES,
        ]);

        $file = $request->file('document');
        $type = $validated['type'];

        $venue->documents()->create([
            'type' => $type,
            'path' => $file->store('venue-documents/'.$venue->id, 'local'),
            'original_name' => $file->getClientOriginalName(),
        ]);

        // Only touch verification/approval state while the venue is NOT approved —
        // never silently de-verify a live venue (keeps "approved ⇒ fully verified").
        if (! $venue->isApproved()) {
            $updates = [];

            // A new/replacement document means the admin should re-check that item.
            $verified = $venue->verified_items ?? [];
            if (in_array($type, $verified, true)) {
                $updates['verified_items'] = array_values(array_diff($verified, [$type]));
            }

            // Re-uploading after a rejection puts the venue back in the review queue.
            if ($venue->rejected_at) {
                $updates['rejected_at'] = null;
                $updates['rejection_reason'] = null;
            }

            if ($updates) {
                $venue->update($updates);
            }
        }

        return back()->with('status', config("courtgo.verification.{$type}.label").' uploaded.');
    }

    /** Owner removes one of their uploaded documents. */
    public function destroy(Venue $venue, VenueDocument $document)
    {
        $this->authorize('update', $venue);

        abort_unless($document->venue_id === $venue->id, 404);

        $document->delete();

        return back()->with('status', 'Document removed.');
    }

    /**
     * Stream a document to the venue owner or an admin only. Documents live on
     * the private disk, so this is the only way to view them.
     */
    public function show(Request $request, VenueDocument $document)
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->id === $document->venue->owner_id || $user->role === UserRole::Admin),
            403
        );

        return Storage::disk('local')->response($document->path, $document->original_name);
    }
}
