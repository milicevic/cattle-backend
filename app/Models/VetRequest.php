<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VetRequest extends Model
{
    protected $fillable = [
        'vet_id',
        'farm_id',
        'status',
        'message',
        'requested_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Get the vet that made this request.
     */
    public function vet(): BelongsTo
    {
        return $this->belongsTo(Vet::class);
    }

    /**
     * Get the farm this request is for.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
