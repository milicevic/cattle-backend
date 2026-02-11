<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Cow extends Model
{
    protected $fillable = [
        'milk_yield',
        'last_calving_date',
        'last_insemination_date',
        'expected_calving_date',
        'actual_calving_date',
        'performed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'milk_yield' => 'decimal:2',
            'last_calving_date' => 'date',
            'last_insemination_date' => 'date',
            'expected_calving_date' => 'date',
            'actual_calving_date' => 'date',
        ];
    }

    /**
     * Get all animals that are cows.
     */
    public function animals(): MorphMany
    {
        return $this->morphMany(Animal::class, 'animalable');
    }

    /**
     * Get all insemination records for this cow.
     */
    public function inseminations(): HasMany
    {
        return $this->hasMany(Insemination::class);
    }

    /**
     * Get all calving records for this cow.
     */
    public function calvings(): HasMany
    {
        return $this->hasMany(Calving::class);
    }

    /**
     * Scope to get cows in their final month of pregnancy (9th month).
     * Cows that will calve today were inseminated 283 days ago.
     * Cows entering their 9th month were inseminated 253 days ago.
     */
    public function scopeInFinalMonth($query)
    {
        $start = now()->subDays(283); // Start of pregnancy for those calving today
        $end = now()->subDays(253);   // Start of pregnancy for those entering 9th month
        
        return $query->whereBetween('last_insemination_date', [$start, $end]);
    }

    /**
     * Get the user who performed the last calving.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
