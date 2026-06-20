<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address',
        'city',
        'state',
        'image_path',
        'approved_at',
        'amenities',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'amenities' => 'array',
        ];
    }

    /**
     * Clean up uploaded images when a venue is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Venue $venue) {
            if ($venue->image_path) {
                Storage::disk('public')->delete($venue->image_path);
            }

            // Remove gallery files (the DB cascade deletes the rows, not the files).
            $venue->photos->each->delete();
        });
    }

    /**
     * Public URL of the venue's image, or null if it has none.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    /**
     * The owner (a user) who runs this venue.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The courts inside this venue.
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    /**
     * Gallery photos shown on the venue page, in display order.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VenuePhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Dates the whole venue is closed (holidays, maintenance) — every court is
     * unbookable on these dates.
     */
    public function closedDates(): HasMany
    {
        return $this->hasMany(VenueClosedDate::class);
    }

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

    /**
     * Whether an admin has approved this venue to be visible to customers.
     */
    public function isApproved(): bool
    {
        return ! is_null($this->approved_at);
    }

    /**
     * Limit to venues an admin has approved.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Limit to venues customers can book: the venue itself is approved AND it
     * has at least one bookable court (active + live owner).
     */
    public function scopeBookable($query)
    {
        return $query->approved()
            ->whereHas('courts', fn ($court) => $court->bookable());
    }
}
