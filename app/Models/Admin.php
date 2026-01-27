<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admin extends Model
{
    protected $fillable = [
        'access_level',
    ];

    /**
     * Get the user that owns this admin profile (one-to-one).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'profile_id')
            ->where('profile_type', self::class);
    }
}
