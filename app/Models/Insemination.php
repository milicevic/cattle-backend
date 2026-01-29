<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insemination extends Model
{
    protected $fillable = [
        'cow_id',
        'animal_id',
        'insemination_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'insemination_date' => 'date',
        ];
    }

    /**
     * Get the cow that this insemination belongs to.
     */
    public function cow(): BelongsTo
    {
        return $this->belongsTo(Cow::class);
    }

    /**
     * Get the animal that this insemination belongs to.
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
