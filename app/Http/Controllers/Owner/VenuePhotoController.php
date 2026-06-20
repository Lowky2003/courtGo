<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VenuePhotoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the form to upload/change a venue's photo.
     */
    public function edit(Venue $venue)
    {
        $this->authorize('update', $venue);

        return view('owner.venue-photo', ['venue' => $venue]);
    }

    /**
     * Store the uploaded photo as a single, plain request (no Livewire AJAX),
     * so it works on the single-threaded dev server.
     */
    public function update(Request $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $request->validate([
            // Raster image only (no SVG → no stored-XSS), with size + pixel caps.
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        if ($venue->image_path) {
            Storage::disk('public')->delete($venue->image_path);
        }

        $venue->update([
            'image_path' => $request->file('photo')->store('venues', 'public'),
        ]);

        return redirect()->route('owner.venues.index')->with('status', 'Venue photo updated.');
    }
}
