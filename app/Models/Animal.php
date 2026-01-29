<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Animal extends Model
{
    protected $fillable = [
        'tag_number',
        'animalable_id',
        'animalable_type',
        'farm_id',
        'species',
        'type',
        'name',
        'gender',
        'date_of_birth',
        'mother_id',
        'father_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent animalable model (Cow, Bull, etc.).
     * Only cattle have animalable relationships.
     */
    public function animalable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Override to handle missing polymorphic classes gracefully.
     */
    public function getAttribute($key)
    {
        if ($key === 'animalable' && $this->animalable_type) {
            // Check if the class exists before trying to load it
            if (!class_exists($this->animalable_type)) {
                return null;
            }
        }
        
        return parent::getAttribute($key);
    }

    /**
     * Get the farm that owns this animal.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the health records for this animal.
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    /**
     * Get the cattle vitals for this animal (only for cattle species).
     */
    public function cattleVitals(): HasMany
    {
        return $this->hasMany(CattleVital::class);
    }

    /**
     * Get the mother (dam) of this animal.
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }

    /**
     * Get the father (sire) of this animal.
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'father_id');
    }

    /**
     * Get all offspring where this animal is the mother.
     */
    public function offspringAsMother(): HasMany
    {
        return $this->hasMany(Animal::class, 'mother_id');
    }

    /**
     * Get all offspring where this animal is the father.
     */
    public function offspringAsFather(): HasMany
    {
        return $this->hasMany(Animal::class, 'father_id');
    }

    /**
     * Get all offspring (children) of this animal.
     * Returns a merged collection of all children.
     */
    public function getOffspringAttribute()
    {
        return $this->offspringAsMother->merge($this->offspringAsFather);
    }

    /**
     * Get the age of the animal in months.
     */
    public function getAgeInMonthsAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return now()->diffInMonths($this->date_of_birth);
    }

    /**
     * Determine gender based on animal type.
     * 
     * @param string $type The animal type (e.g., 'Bull', 'Cow', 'Stallion', etc.)
     * @param string $species The animal species (cattle, horse, sheep)
     * @return string 'male' or 'female'
     */
    public static function getGenderFromType(string $type, string $species): string
    {
        $type = strtolower(trim($type));
        $species = strtolower(trim($species));
        
        // Define male types for each species (case-insensitive comparison)
        $maleTypes = [
            'cattle' => ['bull', 'steer'],
            'horse' => ['stallion', 'gelding'],
            'sheep' => ['ram', 'wether'],
        ];

        // Check if type is in male types for the given species
        if (isset($maleTypes[$species]) && in_array($type, $maleTypes[$species])) {
            return 'male';
        }

        // Default to female for all other types
        return 'female';
    }
}
