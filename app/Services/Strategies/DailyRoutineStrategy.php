<?php

namespace App\Services\Strategies;

use App\Models\Animal;

interface DailyRoutineStrategy
{
    /**
     * Process the daily routine for a specific cattle type.
     *
     * @param Animal $animal
     * @return array
     */
    public function processDailyRoutine(Animal $animal): array;
}
