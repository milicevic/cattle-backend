<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calving extends Model
{
    protected $fillable = [
        'cow_id',
        'animal_id',
        'calving_date',
        'is_successful',
        'notes',
        'performed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'calving_date' => 'date',
            'is_successful' => 'boolean',
        ];
    }

    /**
     * Get the cow that this calving belongs to.
     */
    public function cow(): BelongsTo
    {
        return $this->belongsTo(Cow::class);
    }

    /**
     * Get the animal that this calving belongs to.
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /**
     * Get the user who performed this calving.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
