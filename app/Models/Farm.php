<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'name',
        'farmer_id',
        'location',
        'state',
        'is_active',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the farmer that owns this farm.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * Get the vets assigned to this farm.
     */
    public function vets(): BelongsToMany
    {
        return $this->belongsToMany(Vet::class, 'farm_vet')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Get the vet requests for this farm.
     */
    public function vetRequests(): HasMany
    {
        return $this->hasMany(VetRequest::class);
    }

    /**
     * Get the animals on this farm.
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
