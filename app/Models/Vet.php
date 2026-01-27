<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vet extends Model
{
    protected $fillable = [
        'license_number',
        'specialization',
        'clinic_name',
    ];

    /**
     * Get the user that owns this vet profile (one-to-one).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'profile_id')
            ->where('profile_type', self::class);
    }

    /**
     * Get the farms assigned to this vet.
     */
    public function farms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class, 'farm_vet')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }
}
