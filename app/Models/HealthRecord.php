<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    protected $fillable = [
        'animal_id',
        'diagnosis',
        'vet_id',
        'record_date',
        'treatment',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
        ];
    }

    /**
     * Get the animal this health record belongs to.
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /**
     * Get the vet who created this health record.
     */
    public function vet(): BelongsTo
    {
        return $this->belongsTo(Vet::class);
    }
}
