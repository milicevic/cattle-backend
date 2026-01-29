<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CattleVital extends Model
{
    protected $fillable = [
        'animal_id',
        'weight',
        'heart_rate',
        'temperature',
        'respiration_rate',
        'notes',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'heart_rate' => 'integer',
            'temperature' => 'decimal:2',
            'respiration_rate' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * Get the animal this vital record belongs to.
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
