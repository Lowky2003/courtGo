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
