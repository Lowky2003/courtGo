<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VenueMediaController extends Controller
{
    use AuthorizesRequests;

    /** Max photos in a venue gallery. */
    public const GALLERY_LIMIT = 12;

    /**
     * Upload rules: raster image only (no SVG → no stored-XSS), size + pixel caps.
     *
     * @var array<int, string>
     */
    private const PHOTO_RULES = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=5000,max_height=5000'];

    /** Replace the venue's cover photo and return to the page it was uploaded from. */
    public function storeCover(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $request->validate(['photo' => self::PHOTO_RULES]);

        if ($venue->image_path) {
            Storage::disk('public')->delete($venue->image_path);
        }

        $venue->update(['image_path' => $request->file('photo')->store('venues', 'public')]);

        return back()->with('status', 'Cover photo updated.');
    }

    /** Replace the venue's layout (floor-plan) image. */
    public function storeLayout(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $request->validate(['photo' => self::PHOTO_RULES]);

        if ($venue->layout_image_path) {
            Storage::disk('public')->delete($venue->layout_image_path);
        }

        $venue->update(['layout_image_path' => $request->file('photo')->store('venues/layout', 'public')]);

        return back()->with('status', 'Venue layout updated.');
    }

    public function storePhoto(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => self::PHOTO_RULES,
        ]);

        $incoming = $request->file('photos');
        $current = $venue->photos()->count();

        if ($current + count($incoming) > self::GALLERY_LIMIT) {
            throw ValidationException::withMessages([
                'photos' => 'You can upload up to '.self::GALLERY_LIMIT.' gallery photos (you already have '.$current.').',
            ]);
        }

        $position = (int) $venue->photos()->max('position');

        foreach ($incoming as $file) {
            $venue->photos()->create([
                'path' => $file->store('venues/gallery', 'public'),
                'position' => ++$position,
            ]);
        }

        return back()->with('status', count($incoming).' photo(s) added.');
    }

    public function destroyPhoto(Venue $venue, VenuePhoto $photo)
    {
        $this->authorize('update', $venue);

        abort_unless($photo->venue_id === $venue->id, 404);

        $photo->delete();

        return back()->with('status', 'Gallery photo removed.');
    }
}
