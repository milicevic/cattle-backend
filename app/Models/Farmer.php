<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Farmer extends Model
{
    protected $fillable = [
        'subscription_plan',
        'address',
    ];

    /**
     * Get the user that owns this farmer profile (one-to-one).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'profile_id')
            ->where('profile_type', self::class);
    }

    /**
     * Get the farm owned by this farmer.
     */
    public function farm(): HasOne
    {
        return $this->hasOne(Farm::class);
    }
}
