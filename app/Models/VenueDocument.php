<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VenueDocument extends Model
{
    protected $fillable = ['venue_id', 'type', 'path', 'original_name'];

    /** Delete the (private) stored file when the row is removed. */
    protected static function booted(): void
    {
        static::deleting(function (VenueDocument $document) {
            if ($document->path) {
                Storage::disk('local')->delete($document->path);
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** Human label for this document type, from the verification config. */
    public function label(): string
    {
        return config("courtgo.verification.{$this->type}.label", $this->type);
    }

    /** Whether the upload is an image (vs. a PDF) — for choosing a thumbnail vs. a link. */
    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
