<?php

namespace App\Services\Strategies;

use App\Models\Animal;
use Carbon\Carbon;

class CowRoutineStrategy implements DailyRoutineStrategy
{
    public function processDailyRoutine(Animal $animal): array
    {
        $routine = [
            'feeding' => 'Balanced feed with minerals (2-3% body weight)',
            'milking' => 'Twice daily milking schedule',
            'health_check' => 'Check udder health, body condition, and overall wellness',
            'housing' => 'Clean, comfortable milking parlor and resting area',
            'notes' => 'Monitor milk production and calving cycle',
        ];

        // Check if cow is lactating
        if ($animal->animalable && $animal->animalable->milk_yield > 0) {
            $routine['milking_details'] = [
                'frequency' => 'Twice daily',
                'expected_yield' => $animal->animalable->milk_yield . ' liters/day',
            ];
        }

        // Check if cow is close to calving
        if ($animal->animalable && $animal->animalable->last_calving_date) {
            $daysSinceCalving = Carbon::parse($animal->animalable->last_calving_date)->diffInDays(now());
            if ($daysSinceCalving < 60) {
                $routine['post_calving_care'] = 'Post-calving recovery period - monitor closely';
            }
        }

        return $routine;
    }
}
