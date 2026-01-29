<?php

namespace App\Services\Strategies;

use App\Models\Animal;
use Carbon\Carbon;

class HeiferRoutineStrategy implements DailyRoutineStrategy
{
    public function processDailyRoutine(Animal $animal): array
    {
        $routine = [
            'feeding' => 'Balanced feed for growth and development (2-2.5% body weight)',
            'exercise' => 'Regular exercise to promote healthy development',
            'health_check' => 'Monitor growth rate, reproductive development, and overall health',
            'housing' => 'Group housing with other heifers',
            'notes' => 'Preparing for first breeding and future milk production',
        ];

        // Age-based adjustments
        $ageInMonths = $animal->date_of_birth 
            ? now()->diffInMonths($animal->date_of_birth) 
            : 0;

        if ($ageInMonths < 6) {
            $routine['stage'] = 'Pre-weaning - still with mother';
            $routine['feeding'] = 'Milk-based diet supplemented with starter feed';
        } elseif ($ageInMonths >= 6 && $ageInMonths < 12) {
            $routine['stage'] = 'Post-weaning - growing phase';
            $routine['feeding'] = 'High-quality growing feed';
        } elseif ($ageInMonths >= 12 && $ageInMonths < 15) {
            $routine['stage'] = 'Pre-breeding phase';
            $routine['breeding_prep'] = 'Monitor for breeding readiness (target: 13-15 months)';
        } elseif ($ageInMonths >= 15) {
            $routine['stage'] = 'Breeding age';
            $routine['breeding_prep'] = 'Ready for first breeding';
        }

        return $routine;
    }
}
