<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Bull extends Model
{
    protected $fillable = [
        'semen_quality',
        'aggression_level',
    ];

    /**
     * Get all animals that are bulls.
     */
    public function animals(): MorphMany
    {
        return $this->morphMany(Animal::class, 'animalable');
    }
}
