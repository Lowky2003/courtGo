<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\SessionTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'court_id',
        'day_of_week',
        'start_time',
        'end_time',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The court this recurring session belongs to.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
