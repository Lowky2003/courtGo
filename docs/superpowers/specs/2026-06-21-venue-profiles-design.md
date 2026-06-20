# Rich Venue Profiles — Design

**Date:** 2026-06-21
**Status:** Approved design (pending implementation plan)

## Goal

Let owners build a richer public profile for each venue — cover + photo gallery,
announcement, opening hours, pricing, venue-layout image, policy, contact details,
and amenities — and show all of it to customers on the venue page, including
one-tap **Open in Google Maps** and **Open in Waze** directions.

## Scope

**In scope (all per-venue):**

- Cover photo (the existing single venue photo) + a multi-image **gallery**
- **Venue layout** image (floor plan)
- **Announcement** — one short message with an on/off switch and an optional "hide after" date
- **Opening hours** — per weekday open/close (or "Closed")
- **Pricing** — an auto-computed price range from the venue's court slots, plus an optional owner note
- **Policy** — free text
- **Contact** — phone, WhatsApp, email, website, Instagram, Facebook
- **Amenities** — owner ticks from a fixed list; customers see them
- **Directions** — Google Maps + Waze buttons (address-based)

**Out of scope (YAGNI / future):**

- Per-court photos (photos are per-venue only)
- Drag-to-reorder gallery, image cropping, full lightbox (gallery is add/remove + "set as cover"; images open in a new tab)
- Real lat/long geocoding for pin-perfect map/Waze links (we use the typed address; accurate-pin via saved coordinates is a possible future improvement)
- Reviews / ratings

## Architecture (chosen approach)

**Approach A — fields on the venue + one small gallery table + amenities in config.**
Chosen over (B) full normalization (overkill for a 7-day table and a fixed list) and
(C) a single JSON blob (hard to validate/query). Image uploads reuse the existing
**plain HTTP form** upload pattern (`VenuePhotoController`), not Livewire file uploads,
because that is what works on the single-threaded dev server.

### Data model

**`venues` — new columns** (existing `image_path` becomes the **cover**):

| Column | Type | Notes |
|---|---|---|
| `announcement` | text, nullable | message body |
| `announcement_active` | boolean, default false | on/off switch |
| `announcement_until` | date, nullable | hide on/after this date (null = no expiry) |
| `opening_hours` | json, nullable | see shape below |
| `pricing_note` | string, nullable | short note shown next to the auto range |
| `policy` | text, nullable | rules / cancellation etc. |
| `contact_phone` | string, nullable | |
| `contact_whatsapp` | string, nullable | digits; used to build a wa.me link |
| `contact_email` | string, nullable | |
| `contact_website` | string, nullable | url |
| `contact_instagram` | string, nullable | handle or url |
| `contact_facebook` | string, nullable | handle or url |
| `amenities` | json, nullable | array of amenity keys, e.g. `["parking","wifi"]` |
| `layout_image_path` | string, nullable | uploaded floor-plan image |

`opening_hours` shape — keyed by Carbon day-of-week (0=Sun … 6=Sat):

```json
{ "1": { "closed": false, "open": "08:00", "close": "23:00" },
  "0": { "closed": true } }
```

Days display Monday-first (reuse `config('courtgo.weekdays')`).

**`venue_photos` — new table (the gallery):**

| Column | Type | Notes |
|---|---|---|
| `id` | id | |
| `venue_id` | FK → venues, cascade on delete | |
| `path` | string | stored on the `public` disk under `venues/gallery` |
| `position` | unsigned int, default 0 | display order |
| timestamps | | |

`Venue::photos()` hasMany ordered by `position`, `id`. On photo/venue delete, the
stored file is removed (same `booted()`/`deleting` pattern as the cover).

**`config/courtgo.php` — amenities list** (key → label + icon):

```php
'amenities' => [
    'parking'           => ['label' => 'Parking',             'icon' => 'truck'],
    'wifi'              => ['label' => 'Free WiFi',           'icon' => 'wifi'],
    'showers'          => ['label' => 'Showers',             'icon' => 'sparkles'],
    'changing_rooms'   => ['label' => 'Changing rooms',      'icon' => 'user-group'],
    'lockers'          => ['label' => 'Lockers',             'icon' => 'lock-closed'],
    'surau'            => ['label' => 'Surau',               'icon' => 'moon'],
    'cafe'             => ['label' => 'Café / drinks',       'icon' => 'cake'],
    'equipment_rental' => ['label' => 'Equipment rental',    'icon' => 'shopping-bag'],
    'air_conditioned'  => ['label' => 'Air-conditioned',     'icon' => 'cloud'],
    'spectator_seating'=> ['label' => 'Spectator seating',   'icon' => 'eye'],
    'toilets'          => ['label' => 'Toilets',             'icon' => 'home'],
    'wheelchair'       => ['label' => 'Wheelchair accessible','icon' => 'heart'],
    'cctv'             => ['label' => 'CCTV',                'icon' => 'video-camera'],
],
```

(Icons are best-effort Heroicon names and can be swapped during implementation;
the list is editable later.)

### Derived data

- **Price range:** `Venue::priceRange()` returns the min/max of the venue's active
  session-template prices across its courts. Display `RM{min}–RM{max}` (or a single
  value when min == max), followed by `pricing_note` if set. Empty when no slots.
- **Announcement visible** when `announcement_active` is true, `announcement` is
  non-empty, and (`announcement_until` is null OR today ≤ `announcement_until`).

## Owner experience — "Venue profile" page

A new page per venue (`/owner/venues/{venue}/profile`, "Edit profile" button on the
venues list and the manage-courts page), split by upload type:

- **Structured/text fields** (announcement, opening hours, pricing note, policy,
  contact, amenities) → a Livewire form component (`Owner\Venues\Profile`). No file
  inputs here, so Livewire is safe.
- **Images** — three independent uploads (kept separate, as requested): **cover**
  (replace), **gallery** (add / remove), **layout** (replace) → plain HTTP `POST`
  endpoints on a `VenueMediaController` (extends the existing photo pattern),
  embedded as small standalone forms on the same page.

Validation: images `image|max:20480`; gallery capped at 12 photos; `opening_hours.*`
times `H:i` with open < close unless closed; `contact_email` email; `contact_website`
url; `announcement_until` date `after_or_equal:today`; amenity keys must exist in config.

## Customer experience — venue page

The two-column venue page (details left, booking right) gains:

- A full-width **announcement banner** at the very top when visible.
- Left/info column sections: **cover** (already there) → **gallery** thumbnails
  (open full image in a new tab) → **amenities** (icon + label chips) →
  **opening hours** (Mon-first table) → **price range + note** → **venue layout**
  image (opens full) → **policy** → **contact** (phone/WhatsApp/email/website/IG/FB
  as links) → **Get directions** with **Open in Google Maps** and **Open in Waze**.
- The booking calendar stays prominent on the right; on mobile everything stacks.

**Directions deep links** (a reusable `x-venue-directions` component):

- Google Maps: `https://www.google.com/maps/search/?api=1&query={urlencoded address}`
- Waze: `https://www.waze.com/ul?q={urlencoded address}&navigate=yes`

(The existing `x-venue-map-link` keeps the address-as-link; the new component adds the
two explicit buttons.)

## Phasing

Each phase is independently shippable (migration → model/config → owner UI →
customer display → tests).

- **Phase 1 — visible quick wins:** Google Maps + Waze buttons; amenities (config +
  column + owner ticks + customer chips); cover + gallery (`venue_photos` table,
  upload/remove/set-as-cover, customer thumbnails).
- **Phase 2 — info & messaging:** opening hours (per-day table); price range + note;
  announcement (on/off + optional expiry + customer banner).
- **Phase 3 — the rest:** policy text; full contact block; venue layout image.

## Testing

- **Owner:** save each field group; validation rejects bad times/emails/urls/amenity
  keys/expired-date; only the venue's owner may edit (authorization 403 otherwise);
  cover replace; gallery add/remove; layout upload; deleting a venue/photo removes
  the stored file (`Storage::fake`).
- **Customer:** venue page renders each section; announcement shows only when active
  and not expired; price range computed correctly (incl. single-price and no-slots
  cases); Google Maps and Waze links present with the correctly-encoded address;
  amenities and opening hours render.

## Open follow-ups (not blocking)

- Saved lat/long for exact map/Waze pins.
- Simple in-page lightbox for the gallery instead of opening a new tab.
