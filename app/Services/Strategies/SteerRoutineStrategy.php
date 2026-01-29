<?php

namespace App\Services\Strategies;

use App\Models\Animal;

class SteerRoutineStrategy implements DailyRoutineStrategy
{
    public function processDailyRoutine(Animal $animal): array
    {
        $routine = [
            'feeding' => 'High-energy feed for weight gain (2.5-3.5% body weight)',
            'exercise' => 'Moderate exercise to maintain muscle tone',
            'health_check' => 'Monitor weight gain, feed conversion ratio, and overall health',
            'housing' => 'Group housing with adequate space per animal',
            'notes' => 'Focus on efficient weight gain and feed conversion',
        ];

        // Age-based adjustments
        $ageInMonths = $animal->date_of_birth 
            ? now()->diffInMonths($animal->date_of_birth) 
            : 0;

        if ($ageInMonths < 12) {
            $routine['feeding'] = 'Growing feed with higher protein content';
            $routine['growth_stage'] = 'Early growth phase';
        } elseif ($ageInMonths >= 12 && $ageInMonths < 24) {
            $routine['feeding'] = 'Finishing feed for optimal marbling';
            $routine['growth_stage'] = 'Finishing phase';
        }

        return $routine;
    }
}
