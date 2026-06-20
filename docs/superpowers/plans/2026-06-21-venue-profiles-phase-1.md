# Venue Profiles — Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Owners can add a photo gallery and tick amenities for a venue; customers see the gallery, amenities, and one-tap **Open in Google Maps** / **Open in Waze** buttons on the venue page.

**Architecture:** Per-venue data. Amenities are a fixed list in `config/courtgo.php`; the venue stores the ticked keys in a new `amenities` JSON column. The gallery is a new `venue_photos` table (one venue → many photos). Image uploads use plain HTTP form POST controllers (the existing `VenuePhotoController` pattern), NOT Livewire file uploads — that is what works on the single-threaded dev server. A new owner **Venue Profile** Livewire page hosts the amenities form and embeds the cover + gallery upload forms. Directions are a reusable Blade component.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI (free), Tailwind 4, Pest (SQLite + RefreshDatabase), Storage `public` disk.

**Conventions:**
- PHP binary for commands: `C:\Users\lowky\.config\herd\bin\php84\php.exe`.
- Run one test file: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/<path>`.
- Owner pages use `#[Layout('layouts.app')]`; customer pages use `layouts.customer`.
- The venue's existing single photo (`venues.image_path`) is the **cover** and already works via `VenuePhotoController` — Phase 1 does not change it, only reuses it on the new page.

---

## File structure (Phase 1)

| File | Responsibility |
|---|---|
| `config/courtgo.php` | Add the `amenities` list (key → label + icon). |
| `database/migrations/2026_06_21_000001_add_amenities_to_venues_table.php` | Add `amenities` JSON column. |
| `database/migrations/2026_06_21_000002_create_venue_photos_table.php` | Gallery table. |
| `app/Models/VenuePhoto.php` | Gallery photo model: belongs to venue, deletes its file, `imageUrl()`. |
| `database/factories/VenuePhotoFactory.php` | Test factory for gallery photos. |
| `app/Models/Venue.php` | Add `amenities` to fillable+cast, `photos()` relation, `amenityLabels()`, gallery file cleanup on delete. |
| `app/Http/Controllers/Owner/VenueMediaController.php` | Plain-HTTP gallery add/remove. |
| `app/Livewire/Owner/Venues/Profile.php` | Owner profile page; amenities form; hosts cover + gallery forms. |
| `resources/views/livewire/owner/venues/profile.blade.php` | The profile page view. |
| `resources/views/components/venue-directions.blade.php` | Google Maps + Waze buttons. |
| `resources/views/livewire/venue-show.blade.php` | Customer page: add gallery, amenities chips, directions. |
| `app/Livewire/VenueShow.php` | Eager-load `photos` for the customer page. |
| `routes/web.php` | Profile + media routes (owner group). |
| `resources/views/livewire/owner/venues/index.blade.php` | "Edit profile" link. |
| Tests (see tasks) | `tests/Feature/...` |

---

## Task 1: Amenities config list

**Files:**
- Modify: `config/courtgo.php` (append a new `'amenities'` key to the returned array)
- Test: `tests/Feature/AmenitiesConfigTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AmenitiesConfigTest.php`:

```php
<?php

test('the amenities config is a non-empty keyed list with labels and icons', function () {
    $amenities = config('courtgo.amenities');

    expect($amenities)->toBeArray()->not->toBeEmpty()
        ->and($amenities)->toHaveKey('parking')
        ->and($amenities['parking'])->toHaveKeys(['label', 'icon'])
        ->and($amenities['parking']['label'])->toBe('Parking');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/AmenitiesConfigTest.php`
Expected: FAIL (config key `courtgo.amenities` is null).

- [ ] **Step 3: Add the config**

In `config/courtgo.php`, add this entry inside the returned array (next to `'support_email'`):

```php
    'amenities' => [
        'parking' => ['label' => 'Parking', 'icon' => 'truck'],
        'wifi' => ['label' => 'Free WiFi', 'icon' => 'wifi'],
        'showers' => ['label' => 'Showers', 'icon' => 'sparkles'],
        'changing_rooms' => ['label' => 'Changing rooms', 'icon' => 'user-group'],
        'lockers' => ['label' => 'Lockers', 'icon' => 'lock-closed'],
        'surau' => ['label' => 'Surau', 'icon' => 'moon'],
        'cafe' => ['label' => 'Café / drinks', 'icon' => 'cake'],
        'equipment_rental' => ['label' => 'Equipment rental', 'icon' => 'shopping-bag'],
        'air_conditioned' => ['label' => 'Air-conditioned', 'icon' => 'cloud'],
        'spectator_seating' => ['label' => 'Spectator seating', 'icon' => 'eye'],
        'toilets' => ['label' => 'Toilets', 'icon' => 'home'],
        'wheelchair' => ['label' => 'Wheelchair accessible', 'icon' => 'heart'],
        'cctv' => ['label' => 'CCTV', 'icon' => 'video-camera'],
    ],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan config:clear; & "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/AmenitiesConfigTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add config/courtgo.php tests/Feature/AmenitiesConfigTest.php
git commit -m "feat(venue): add amenities config list"
```

---

## Task 2: Venue amenities column + model helpers

**Files:**
- Create: `database/migrations/2026_06_21_000001_add_amenities_to_venues_table.php`
- Modify: `app/Models/Venue.php` (fillable, casts, `amenityLabels()`)
- Test: `tests/Feature/VenueAmenitiesTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/VenueAmenitiesTest.php`:

```php
<?php

use App\Models\Venue;

test('a venue stores amenity keys and returns their config labels in config order', function () {
    $venue = Venue::factory()->create(['amenities' => ['cafe', 'parking', 'nonsense']]);

    $labels = collect($venue->fresh()->amenityLabels());

    // Invalid keys dropped; valid ones returned in config order (parking before cafe).
    expect($labels->pluck('key')->all())->toBe(['parking', 'cafe'])
        ->and($labels->firstWhere('key', 'parking')['label'])->toBe('Parking')
        ->and($labels->firstWhere('key', 'parking')['icon'])->toBe('truck');
});

test('a venue with no amenities returns an empty list', function () {
    $venue = Venue::factory()->create(['amenities' => null]);

    expect($venue->amenityLabels())->toBe([]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueAmenitiesTest.php`
Expected: FAIL (`amenities` not fillable / column missing / `amenityLabels` undefined).

- [ ] **Step 3a: Create the migration**

Create `database/migrations/2026_06_21_000001_add_amenities_to_venues_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->json('amenities')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('amenities');
        });
    }
};
```

- [ ] **Step 3b: Update the Venue model**

In `app/Models/Venue.php`:

1. Add `'amenities'` to the `$fillable` array.
2. Change the `casts()` method to include amenities:

```php
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'amenities' => 'array',
        ];
    }
```

3. Add this method (after `isApproved()`):

```php
    /**
     * The ticked amenities resolved to their config entries, in config order.
     * Unknown/removed keys are dropped.
     *
     * @return array<int, array{key: string, label: string, icon: string}>
     */
    public function amenityLabels(): array
    {
        $chosen = $this->amenities ?? [];

        return collect(config('courtgo.amenities'))
            ->filter(fn ($meta, $key) => in_array($key, $chosen, true))
            ->map(fn ($meta, $key) => ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon']])
            ->values()
            ->all();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueAmenitiesTest.php`
Expected: PASS (RefreshDatabase runs the new migration).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_21_000001_add_amenities_to_venues_table.php app/Models/Venue.php tests/Feature/VenueAmenitiesTest.php
git commit -m "feat(venue): store amenities + amenityLabels() helper"
```

---

## Task 3: venue_photos table + VenuePhoto model + gallery relation

**Files:**
- Create: `database/migrations/2026_06_21_000002_create_venue_photos_table.php`
- Create: `app/Models/VenuePhoto.php`
- Create: `database/factories/VenuePhotoFactory.php`
- Modify: `app/Models/Venue.php` (`photos()` relation + delete-cleanup)
- Test: `tests/Feature/VenuePhotoTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/VenuePhotoTest.php`:

```php
<?php

use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a venue has many gallery photos ordered by position', function () {
    $venue = Venue::factory()->create();
    VenuePhoto::factory()->for($venue)->create(['position' => 2, 'path' => 'b.jpg']);
    VenuePhoto::factory()->for($venue)->create(['position' => 1, 'path' => 'a.jpg']);

    expect($venue->photos->pluck('path')->all())->toBe(['a.jpg', 'b.jpg']);
});

test('deleting a gallery photo removes its stored file', function () {
    Storage::fake('public');
    $venue = Venue::factory()->create();
    $path = UploadedFile::fake()->image('g.jpg')->store('venues/gallery', 'public');
    $photo = VenuePhoto::factory()->for($venue)->create(['path' => $path]);
    Storage::disk('public')->assertExists($path);

    $photo->delete();

    Storage::disk('public')->assertMissing($path);
});

test('deleting a venue removes its gallery files', function () {
    Storage::fake('public');
    $venue = Venue::factory()->create();
    $path = UploadedFile::fake()->image('g.jpg')->store('venues/gallery', 'public');
    VenuePhoto::factory()->for($venue)->create(['path' => $path]);

    $venue->delete();

    Storage::disk('public')->assertMissing($path);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenuePhotoTest.php`
Expected: FAIL (`VenuePhoto` class / table / relation missing).

- [ ] **Step 3a: Create the migration**

Create `database/migrations/2026_06_21_000002_create_venue_photos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_photos');
    }
};
```

- [ ] **Step 3b: Create the model**

Create `app/Models/VenuePhoto.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VenuePhoto extends Model
{
    /** @use HasFactory<\Database\Factories\VenuePhotoFactory> */
    use HasFactory;

    protected $fillable = ['venue_id', 'path', 'position'];

    protected static function booted(): void
    {
        static::deleting(function (VenuePhoto $photo) {
            if ($photo->path) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
```

- [ ] **Step 3c: Create the factory**

Create `database/factories/VenuePhotoFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenuePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenuePhoto>
 */
class VenuePhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'path' => 'venues/gallery/'.fake()->uuid().'.jpg',
            'position' => 0,
        ];
    }
}
```

- [ ] **Step 3d: Add the relation + delete cleanup to Venue**

In `app/Models/Venue.php`:

1. Add `use` for nothing new (HasMany already imported). Add this relation after `courts()`:

```php
    /**
     * Gallery photos shown on the venue page, in display order.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VenuePhoto::class)->orderBy('position')->orderBy('id');
    }
```

2. Extend the existing `booted()` `deleting` closure so gallery files are removed too (DB cascade deletes the rows but not the files). The closure currently deletes `image_path`; make it:

```php
    protected static function booted(): void
    {
        static::deleting(function (Venue $venue) {
            if ($venue->image_path) {
                Storage::disk('public')->delete($venue->image_path);
            }

            // Remove gallery files (cascade deletes the rows, not the files).
            $venue->photos->each->delete();
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenuePhotoTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_21_000002_create_venue_photos_table.php app/Models/VenuePhoto.php database/factories/VenuePhotoFactory.php app/Models/Venue.php tests/Feature/VenuePhotoTest.php
git commit -m "feat(venue): venue_photos gallery table + model"
```

---

## Task 4: Gallery upload/remove controller + routes

**Files:**
- Create: `app/Http/Controllers/Owner/VenueMediaController.php`
- Modify: `routes/web.php` (owner group)
- Test: `tests/Feature/Owner/VenueMediaTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Owner/VenueMediaTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenueMediaTest.php`
Expected: FAIL (route/controller missing).

- [ ] **Step 3a: Create the controller**

Create `app/Http/Controllers/Owner/VenueMediaController.php`:

```php
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
```

- [ ] **Step 3b: Add the routes**

In `routes/web.php`, inside the existing owner group
(`Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(...)`),
after the `venues.photo.update` route, add:

```php
    Route::post('/venues/{venue}/media/photos', [\App\Http\Controllers\Owner\VenueMediaController::class, 'storePhoto'])->name('venues.media.photos.store');
    Route::delete('/venues/{venue}/media/photos/{photo}', [\App\Http\Controllers\Owner\VenueMediaController::class, 'destroyPhoto'])->name('venues.media.photos.destroy');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenueMediaTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Owner/VenueMediaController.php routes/web.php tests/Feature/Owner/VenueMediaTest.php
git commit -m "feat(venue): gallery upload/remove endpoints"
```

---

## Task 5: Owner Venue Profile page (amenities) + cover/gallery forms

**Files:**
- Create: `app/Livewire/Owner/Venues/Profile.php`
- Create: `resources/views/livewire/owner/venues/profile.blade.php`
- Modify: `routes/web.php` (owner group)
- Test: `tests/Feature/Owner/VenueProfileTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Owner/VenueProfileTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Profile;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an owner can save amenities for their venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['amenities' => null]);

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('amenities', ['parking', 'wifi'])
        ->call('save')
        ->assertHasNoErrors();

    expect($venue->fresh()->amenities)->toBe(['parking', 'wifi']);
});

test('saving rejects amenity keys that are not in the config list', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('amenities', ['parking', 'not_a_real_amenity'])
        ->call('save')
        ->assertHasErrors('amenities.1');

    expect($venue->fresh()->amenities)->toBeNull();
});

test('a stranger cannot open another owners venue profile', function () {
    $stranger = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create();

    $this->actingAs($stranger)
        ->get(route('owner.venues.profile', $venue))
        ->assertForbidden();
});

test('the profile page renders for the owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('owner.venues.profile', $venue))
        ->assertOk()
        ->assertSeeLivewire(Profile::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenueProfileTest.php`
Expected: FAIL (component/route/view missing).

- [ ] **Step 3a: Create the Livewire component**

Create `app/Livewire/Owner/Venues/Profile.php`:

```php
<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Venue Profile')]
class Profile extends Component
{
    use AuthorizesRequests;

    public Venue $venue;

    /** @var array<int, string> */
    public array $amenities = [];

    public function mount(Venue $venue): void
    {
        $this->authorize('update', $venue);

        $this->venue = $venue;
        $this->amenities = $venue->amenities ?? [];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'amenities' => 'array',
            'amenities.*' => ['string', Rule::in(array_keys(config('courtgo.amenities')))],
        ]);

        $this->venue->update(['amenities' => $validated['amenities']]);

        session()->flash('status', 'Venue profile updated.');
    }

    public function render()
    {
        return view('livewire.owner.venues.profile', [
            'allAmenities' => config('courtgo.amenities'),
            'photos' => $this->venue->photos()->get(),
        ]);
    }
}
```

- [ ] **Step 3b: Create the view**

Create `resources/views/livewire/owner/venues/profile.blade.php`:

```blade
<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.courts', $venue)" wire:navigate icon="arrow-left">
            Back to courts
        </flux:button>
        <flux:heading size="xl">{{ $venue->name }} — Profile</flux:heading>
        <flux:text>{{ $venue->city }}, {{ $venue->state }}</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Amenities (Livewire) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Amenities</flux:heading>
        <flux:text class="text-sm text-zinc-500">Tick everything your venue offers.</flux:text>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($allAmenities as $key => $meta)
                <flux:checkbox wire:model="amenities" value="{{ $key }}" label="{{ $meta['label'] }}" />
            @endforeach
        </div>
        @error('amenities.*') <flux:text class="text-sm text-red-600">{{ $message }}</flux:text> @enderror

        <flux:button variant="primary" wire:click="save">Save amenities</flux:button>
    </div>

    {{-- Cover photo (plain HTTP form → existing controller) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Cover photo</flux:heading>
        @if ($venue->imageUrl())
            <img src="{{ $venue->imageUrl() }}" alt="" class="h-40 w-full rounded-lg object-cover" />
        @endif
        <form method="POST" action="{{ route('owner.venues.photo.update', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="photo" accept="image/*" required class="text-sm" />
            <flux:button type="submit" variant="primary" size="sm">Upload cover</flux:button>
        </form>
    </div>

    {{-- Gallery (plain HTTP forms → VenueMediaController) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Photo gallery</flux:heading>
        <flux:text class="text-sm text-zinc-500">Up to 12 photos of your courts and facility.</flux:text>

        @if ($photos->isNotEmpty())
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                @foreach ($photos as $photo)
                    <div class="relative" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ $photo->imageUrl() }}" alt="" class="h-24 w-full rounded-lg object-cover" />
                        <form method="POST" action="{{ route('owner.venues.media.photos.destroy', [$venue, $photo]) }}" class="absolute right-1 top-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full bg-black/60 px-2 text-xs text-white hover:bg-black/80">&times;</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($photos->count() < 12)
            <form method="POST" action="{{ route('owner.venues.media.photos.store', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="photo" accept="image/*" required class="text-sm" />
                <flux:button type="submit" variant="primary" size="sm">Add photo</flux:button>
            </form>
        @else
            <flux:text class="text-sm text-zinc-400">Gallery is full (12 photos).</flux:text>
        @endif
    </div>
</div>
```

- [ ] **Step 3c: Add the route**

In `routes/web.php`, inside the owner group, after the media routes from Task 4, add:

```php
    Route::get('/venues/{venue}/profile', \App\Livewire\Owner\Venues\Profile::class)->name('venues.profile');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenueProfileTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Owner/Venues/Profile.php resources/views/livewire/owner/venues/profile.blade.php routes/web.php tests/Feature/Owner/VenueProfileTest.php
git commit -m "feat(venue): owner venue profile page (amenities + cover + gallery)"
```

---

## Task 6: "Edit profile" link on the owner venues list

**Files:**
- Modify: `resources/views/livewire/owner/venues/index.blade.php`
- Test: `tests/Feature/Owner/VenuesIndexTest.php` (add one test)

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Owner/VenuesIndexTest.php`:

```php
test('the venues list links to each venue profile', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee(route('owner.venues.profile', $venue), escape: false);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenuesIndexTest.php`
Expected: FAIL (the profile link is not on the page).

- [ ] **Step 3: Add the link**

In `resources/views/livewire/owner/venues/index.blade.php`, in the venue row Actions cell (the `<div class="flex justify-end gap-2">` next to "Manage courts" / "Photo"), add a Profile button before the Delete button:

```blade
                                        <flux:button size="sm" variant="ghost" icon="identification" :href="route('owner.venues.profile', $venue)" wire:navigate>
                                            Profile
                                        </flux:button>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/Owner/VenuesIndexTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/owner/venues/index.blade.php tests/Feature/Owner/VenuesIndexTest.php
git commit -m "feat(venue): link to venue profile from the venues list"
```

---

## Task 7: Directions component (Google Maps + Waze)

**Files:**
- Create: `resources/views/components/venue-directions.blade.php`
- Test: `tests/Feature/VenueDirectionsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/VenueDirectionsTest.php`:

```php
<?php

use App\Models\Venue;
use Illuminate\Support\Facades\Blade;

test('the directions component renders google maps and waze links for the address', function () {
    $venue = Venue::factory()->create([
        'address' => 'Jalan PJS 11', 'city' => 'Subang Jaya', 'state' => 'Selangor',
    ]);

    $html = Blade::render('<x-venue-directions :venue="$venue" />', ['venue' => $venue]);
    $query = urlencode('Jalan PJS 11, Subang Jaya, Selangor');

    expect($html)->toContain('https://www.google.com/maps/search/?api=1&query='.$query)
        ->and($html)->toContain('https://www.waze.com/ul?q='.$query.'&navigate=yes')
        ->and($html)->toContain('Google Maps')
        ->and($html)->toContain('Waze');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueDirectionsTest.php`
Expected: FAIL (component missing).

- [ ] **Step 3: Create the component**

Create `resources/views/components/venue-directions.blade.php`:

```blade
@props(['venue'])

@php
    $address = trim(implode(', ', array_filter([$venue->address, $venue->city, $venue->state])));
    $query = urlencode($address);
    $googleUrl = 'https://www.google.com/maps/search/?api=1&query='.$query;
    $wazeUrl = 'https://www.waze.com/ul?q='.$query.'&navigate=yes';
    $base = 'inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-medium hover:border-blue-400 dark:border-zinc-700';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    <a href="{{ $googleUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $base }}">
        <flux:icon name="map-pin" class="size-4" /> Open in Google Maps
    </a>
    <a href="{{ $wazeUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $base }}">
        <flux:icon name="map" class="size-4" /> Open in Waze
    </a>
</div>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueDirectionsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/venue-directions.blade.php tests/Feature/VenueDirectionsTest.php
git commit -m "feat(venue): Google Maps + Waze directions component"
```

---

## Task 8: Show gallery, amenities, and directions on the customer venue page

**Files:**
- Modify: `app/Livewire/VenueShow.php` (eager-load photos)
- Modify: `resources/views/livewire/venue-show.blade.php` (left/info column additions)
- Test: `tests/Feature/VenueProfileDisplayTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/VenueProfileDisplayTest.php`:

```php
<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\VenuePhoto;

test('the venue page shows amenities, gallery, and directions', function () {
    $venue = Venue::factory()->create([
        'amenities' => ['parking', 'wifi'],
        'address' => 'Jalan PJS 11', 'city' => 'Subang Jaya', 'state' => 'Selangor',
    ]);
    VenuePhoto::factory()->for($venue)->create(['path' => 'venues/gallery/x.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', $venue))
        ->assertOk()
        ->assertSee('Parking')                                    // amenity label
        ->assertSee('Free WiFi')
        ->assertSee('venues/gallery/x.jpg')                       // gallery image path in <img src>
        ->assertSee('https://www.waze.com/ul', escape: false);    // directions present
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueProfileDisplayTest.php`
Expected: FAIL (amenities/gallery/waze not on the page).

- [ ] **Step 3a: Eager-load photos in VenueShow**

In `app/Livewire/VenueShow.php`, in `mount()`, after `$this->venue = $venue;` add:

```php
        $this->venue->loadMissing('photos');
```

- [ ] **Step 3b: Add the sections to the venue page**

In `resources/views/livewire/venue-show.blade.php`, in the LEFT details column (the
`<div class="space-y-3 lg:sticky lg:top-6">`), after the description `@if` block and
before the closing `</div>` of that column, add:

```blade
            {{-- Photo gallery --}}
            @if ($venue->photos->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($venue->photos as $photo)
                        <a href="{{ $photo->imageUrl() }}" target="_blank" rel="noopener noreferrer" wire:key="vp-{{ $photo->id }}">
                            <img src="{{ $photo->imageUrl() }}" alt="" class="h-20 w-full rounded-lg object-cover" />
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Amenities --}}
            @if (! empty($venue->amenityLabels()))
                <div class="flex flex-wrap gap-2">
                    @foreach ($venue->amenityLabels() as $amenity)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <flux:icon :name="$amenity['icon']" class="size-3.5" />
                            {{ $amenity['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Directions --}}
            <x-venue-directions :venue="$venue" />
```

- [ ] **Step 4: Run test to verify it passes**

Run: `& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test tests/Feature/VenueProfileDisplayTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full suite + build, then commit**

```bash
& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan view:clear
& "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan test
npm run build
git add app/Livewire/VenueShow.php resources/views/livewire/venue-show.blade.php tests/Feature/VenueProfileDisplayTest.php
git commit -m "feat(venue): show gallery, amenities, and directions on the venue page"
```

Expected: full suite green; build succeeds.

---

## Done-ness check (Phase 1)

- [ ] Owner can tick amenities and they persist.
- [ ] Owner can upload a cover and add/remove gallery photos (≤ 12).
- [ ] Customer venue page shows the gallery, amenity chips, and **Open in Google Maps** / **Open in Waze** buttons.
- [ ] All migrations applied on the dev DB (`php artisan migrate`).
- [ ] Full test suite green; `npm run build` clean.

Phases 2 (opening hours, pricing, announcement) and 3 (policy, contact, layout image) get their own plans.
