<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VenueMediaController extends Controller
{
    use AuthorizesRequests;

    /** Max photos in a venue gallery. */
    public const GALLERY_LIMIT = 12;

    public function storePhoto(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $request->validate(['photo' => 'required|image|max:20480']);

        if ($venue->photos()->count() >= self::GALLERY_LIMIT) {
            throw ValidationException::withMessages([
                'photo' => 'You can upload up to '.self::GALLERY_LIMIT.' gallery photos.',
            ]);
        }

        $venue->photos()->create([
            'path' => $request->file('photo')->store('venues/gallery', 'public'),
            'position' => (int) $venue->photos()->max('position') + 1,
        ]);

        return back()->with('status', 'Gallery photo added.');
    }

    public function destroyPhoto(Venue $venue, VenuePhoto $photo)
    {
        $this->authorize('update', $venue);

        abort_unless($photo->venue_id === $venue->id, 404);

        $photo->delete();

        return back()->with('status', 'Gallery photo removed.');
    }
}
