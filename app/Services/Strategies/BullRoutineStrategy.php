<?php

namespace App\Services\Strategies;

use App\Models\Animal;

class BullRoutineStrategy implements DailyRoutineStrategy
{
    public function processDailyRoutine(Animal $animal): array
    {
        $routine = [
            'feeding' => 'High-protein feed (2-3% body weight)',
            'exercise' => 'Controlled exercise and breeding activity monitoring',
            'health_check' => 'Check for injuries, aggression levels, and breeding fitness',
            'housing' => 'Secure, spacious pen with adequate ventilation',
            'notes' => 'Monitor semen quality and breeding performance',
        ];

        // Additional logic based on bull's age and condition
        if ($animal->animalable && $animal->animalable->aggression_level === 'High') {
            $routine['safety'] = 'Extra safety precautions required';
        }

        return $routine;
    }
}
