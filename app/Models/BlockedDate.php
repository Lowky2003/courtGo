<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDate extends Model
{
    /** @use HasFactory<\Database\Factories\BlockedDateFactory> */
    use HasFactory;

    protected $fillable = [
        'court_id',
        'date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * The court this blocked date belongs to.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
