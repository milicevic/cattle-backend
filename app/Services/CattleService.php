<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\CattleVital;
use App\Models\Cow;
use App\Models\Insemination;
use App\Services\Strategies\DailyRoutineStrategy;
use App\Services\Strategies\BullRoutineStrategy;
use App\Services\Strategies\CowRoutineStrategy;
use App\Services\Strategies\SteerRoutineStrategy;
use App\Services\Strategies\HeiferRoutineStrategy;
use Carbon\Carbon;

class CattleService
{
    /**
     * Records vitals for a cattle animal.
     * Shared logic: All cattle have their temperature, weight, heart rate, and respiration tracked similarly.
     *
     * @param Animal $animal
     * @param array $data
     * @return CattleVital
     */
    public function recordVitals(Animal $animal, array $data): CattleVital
    {
        // Validate that this is a cattle animal
        if ($animal->species !== 'cattle') {
            throw new \InvalidArgumentException('This method only works for cattle animals.');
        }

        return CattleVital::create([
            'animal_id' => $animal->id,
            'weight' => $data['weight'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'respiration_rate' => $data['respiration_rate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'checked_at' => $data['checked_at'] ?? now(),
        ]);
    }

    /**
     * Checks if a calf is eligible for weaning.
     * Logic specific to bovine growth cycles - typically weaned at 6-8 months.
     *
     * @param Animal $animal
     * @return array
     */
    public function checkWeaningEligibility(Animal $animal): array
    {
        if ($animal->species !== 'cattle') {
            throw new \InvalidArgumentException('This method only works for cattle animals.');
        }

        if (!$animal->date_of_birth) {
            return [
                'eligible' => false,
                'reason' => 'Date of birth not available',
            ];
        }

        $ageInMonths = now()->diffInMonths($animal->date_of_birth);
        $ageInDays = now()->diffInDays($animal->date_of_birth);

        // Standard weaning age is 6-8 months (180-240 days)
        $minWeaningAge = 6; // months
        $maxWeaningAge = 8; // months
        $minWeaningDays = 180;
        $maxWeaningDays = 240;

        $eligible = $ageInMonths >= $minWeaningAge && $ageInMonths <= $maxWeaningAge;

        return [
            'eligible' => $eligible,
            'age_in_months' => $ageInMonths,
            'age_in_days' => $ageInDays,
            'min_age_months' => $minWeaningAge,
            'max_age_months' => $maxWeaningAge,
            'recommendation' => $eligible 
                ? 'Ready for weaning' 
                : ($ageInMonths < $minWeaningAge 
                    ? "Too young - wait until {$minWeaningAge} months" 
                    : "Past optimal weaning window - should have been weaned by {$maxWeaningAge} months"),
            'has_mother' => $animal->mother_id !== null,
        ];
    }

    /**
     * Process daily routine using strategy pattern based on cattle type.
     *
     * @param Animal $animal
     * @return array
     */
    public function processDailyRoutine(Animal $animal): array
    {
        if ($animal->species !== 'cattle') {
            throw new \InvalidArgumentException('This method only works for cattle animals.');
        }

        $strategy = $this->getStrategyForType($animal->type);
        
        return [
            'animal_id' => $animal->id,
            'tag_number' => $animal->tag_number,
            'type' => $animal->type,
            'routine' => $strategy->processDailyRoutine($animal),
            'processed_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the appropriate strategy for a cattle type.
     *
     * @param string $type
     * @return DailyRoutineStrategy
     */
    protected function getStrategyForType(string $type): DailyRoutineStrategy
    {
        return match (strtolower($type)) {
            'bull' => new BullRoutineStrategy(),
            'cow' => new CowRoutineStrategy(),
            'steer' => new SteerRoutineStrategy(),
            'heifer' => new HeiferRoutineStrategy(),
            default => throw new \InvalidArgumentException("Unknown cattle type: {$type}"),
        };
    }

    /**
     * Get the age of an animal in months.
     *
     * @param Animal $animal
     * @return int|null
     */
    public function getAgeInMonths(Animal $animal): ?int
    {
        if (!$animal->date_of_birth) {
            return null;
        }

        return now()->diffInMonths($animal->date_of_birth);
    }

    /**
     * Get recent vitals for an animal.
     *
     * @param Animal $animal
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentVitals(Animal $animal, int $limit = 10)
    {
        return CattleVital::where('animal_id', $animal->id)
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Record insemination for a cow and calculate expected calving date.
     * Average gestation period for cows is 283 days.
     *
     * @param Cow $cow
     * @param string|Carbon $date
     * @return bool
     */
    public function recordInsemination(Cow $cow, $date, ?string $notes = null): Insemination
    {
        $inseminationDate = Carbon::parse($date);
        $animal = $cow->animals()->first();
        
        if (!$animal) {
            throw new \InvalidArgumentException('Cow must have an associated animal record.');
        }

        // Create insemination record with pending status
        // Don't update last_insemination_date until status is confirmed
        $insemination = Insemination::create([
            'cow_id' => $cow->id,
            'animal_id' => $animal->id,
            'insemination_date' => $inseminationDate,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        // Note: last_insemination_date will be updated when status is confirmed
        // This allows cows with pending/failed inseminations to still appear in "needing insemination" list

        return $insemination;
    }

    /**
     * Record actual calving date for a cow and optionally create calf records.
     *
     * @param Cow $cow
     * @param string|Carbon $date
     * @param array|null $calves Array of calf data (tag_number, name, type, date_of_birth)
     * @param string|null $notes Calving notes/outcome
     * @param bool $isSuccessful Whether the calving was successful
     * @return array Returns array with success status and created calves
     */
    public function recordCalving(Cow $cow, $date, ?array $calves = null, ?string $notes = null, bool $isSuccessful = true): array
    {
        $calvingDate = Carbon::parse($date);
        $animal = $cow->animals->first();
        
        if (!$animal) {
            throw new \Exception('Animal record not found for cow');
        }

        // Update cow calving dates - always update regardless of success
        // This marks the end of the pregnancy period
        $cow->update([
            'actual_calving_date' => $calvingDate,
            'last_calving_date' => $calvingDate,
            // Reset insemination dates to allow for new breeding cycle
            'last_insemination_date' => null,
            'expected_calving_date' => null,
        ]);

        $createdCalves = [];

        // Create calf records only if calving was successful and calves are provided
        if ($isSuccessful && $calves && is_array($calves) && count($calves) > 0) {
            foreach ($calves as $calfData) {
                if (empty($calfData['tag_number']) || empty($calfData['type'])) {
                    continue; // Skip invalid calf data
                }

                // Determine gender from type
                $gender = Animal::getGenderFromType($calfData['type'], 'cattle');

                // Determine animalable type based on calf type
                $animalable = null;
                $animalableType = null;

                if ($calfData['type'] === 'Bull') {
                    $animalable = \App\Models\Bull::create([
                        'semen_quality' => $calfData['semen_quality'] ?? null,
                        'aggression_level' => $calfData['aggression_level'] ?? null,
                    ]);
                    $animalableType = \App\Models\Bull::class;
                } else {
                    // Cow, Steer, Heifer
                    $animalable = Cow::create([
                        'milk_yield' => $calfData['milk_yield'] ?? null,
                    ]);
                    $animalableType = Cow::class;
                }

                // Create the calf animal record
                $calf = Animal::create([
                    'tag_number' => $calfData['tag_number'],
                    'farm_id' => $animal->farm_id,
                    'species' => 'cattle',
                    'type' => $calfData['type'],
                    'name' => $calfData['name'] ?? null,
                    'gender' => $gender,
                    'date_of_birth' => $calfData['date_of_birth'] ?? $calvingDate->toDateString(),
                    'mother_id' => $animal->id,
                    'father_id' => $calfData['father_id'] ?? null,
                    'animalable_id' => $animalable->id,
                    'animalable_type' => $animalableType,
                    'is_active' => true,
                ]);

                $createdCalves[] = $calf;
            }
        }

        return [
            'success' => true,
            'calves' => $createdCalves,
            'notes' => $notes,
        ];
    }

    /**
     * Get pregnancy progress information for a cow.
     *
     * @param Cow $cow
     * @return array|null
     */
    public function getPregnancyProgress(Cow $cow): ?array
    {
        if (!$cow->last_insemination_date || !$cow->expected_calving_date) {
            return null;
        }

        $now = Carbon::now();
        $inseminationDate = Carbon::parse($cow->last_insemination_date);
        $expectedCalving = Carbon::parse($cow->expected_calving_date);
        
        $totalDays = 283; // Total gestation period
        $daysSinceInsemination = $now->diffInDays($inseminationDate);
        $daysUntilCalving = $now->diffInDays($expectedCalving, false); // false = absolute value
        
        $progressPercentage = min(100, max(0, ($daysSinceInsemination / $totalDays) * 100));
        
        $status = 'pregnant';
        if ($cow->actual_calving_date) {
            $status = 'calved';
        } elseif ($daysUntilCalving < 0) {
            $status = 'overdue';
        } elseif ($daysUntilCalving <= 14) {
            $status = 'due_soon';
        }

        return [
            'status' => $status,
            'last_insemination_date' => $cow->last_insemination_date,
            'expected_calving_date' => $cow->expected_calving_date,
            'actual_calving_date' => $cow->actual_calving_date,
            'days_since_insemination' => $daysSinceInsemination,
            'days_until_calving' => $daysUntilCalving,
            'progress_percentage' => round($progressPercentage, 1),
            'total_gestation_days' => $totalDays,
        ];
    }

    /**
     * Get cows that are in their final month of pregnancy (upcoming calvings).
     *
     * @param int|null $farmId Optional farm ID to filter by farm
     * @return \Illuminate\Support\Collection
     */
    public function getUpcomingCalvings($farmId = null)
    {
        $query = Cow::query()->inFinalMonth();

        if ($farmId) {
            $query->whereHas('animals', function($q) use ($farmId) {
                $q->where('farm_id', $farmId);
            });
        }

        return $query->with('animals')->get()->map(function ($cow) {
            $animal = $cow->animals->first(); // Get the first associated animal
            
            if (!$animal || !$cow->last_insemination_date) {
                return null;
            }

            $inseminationDate = Carbon::parse($cow->last_insemination_date);
            $daysSinceInsemination = $inseminationDate->diffInDays(now());
            
            // Calculate days remaining using expected_calving_date if available, otherwise calculate from insemination
            if ($cow->expected_calving_date) {
                $expectedCalving = Carbon::parse($cow->expected_calving_date);
                $daysRemaining = now()->diffInDays($expectedCalving, false); // false = absolute value
            } else {
                // Fallback: calculate from insemination date
                $daysRemaining = 283 - $daysSinceInsemination;
            }
            
            $progress = $this->getPregnancyProgress($cow);

            return [
                'cow_id' => $cow->id,
                'animal_id' => $animal->id,
                'tag_number' => $animal->tag_number,
                'name' => $animal->name,
                'last_insemination_date' => $cow->last_insemination_date,
                'expected_calving_date' => $cow->expected_calving_date,
                'days_remaining' => max(0, $daysRemaining),
                'days_since_insemination' => $daysSinceInsemination,
                'progress' => $progress,
            ];
        })->filter(); // Remove null entries
    }

    /**
     * Get cows that need insemination soon (within 5 days after calving or last calving date).
     * Typically, cows should be inseminated 50-90 days after calving.
     *
     * @param int|null $farmId Optional farm ID to filter by farm
     * @return \Illuminate\Support\Collection
     */
    public function getCowsNeedingInsemination($farmId = null)
    {
        $query = Cow::query()
            ->whereNotNull('last_calving_date')
            ->whereNull('last_insemination_date') // Not currently pregnant
            ->whereNull('actual_calving_date'); // Not currently calving

        if ($farmId) {
            $query->whereHas('animals', function($q) use ($farmId) {
                $q->where('farm_id', $farmId);
            });
        }

        return $query->with(['animals', 'inseminations' => function($q) {
            $q->orderBy('insemination_date', 'desc')->limit(1);
        }])->get()->map(function ($cow) {
            $animal = $cow->animals->first();
            
            if (!$animal || !$cow->last_calving_date) {
                return null;
            }

            $lastCalvingDate = Carbon::parse($cow->last_calving_date);
            $daysSinceCalving = $lastCalvingDate->diffInDays(now());
            
            // Ideal insemination window is 50-90 days after calving
            // Alert if within 5 days of ideal window start (45-50 days) or overdue (>90 days)
            $idealStart = 50;
            $idealEnd = 90;
            $daysUntilIdeal = $idealStart - $daysSinceCalving;
            $isOverdue = $daysSinceCalving > $idealEnd;
            $isClose = $daysSinceCalving >= 45 && $daysSinceCalving <= 95;

            if (!$isClose && !$isOverdue) {
                return null; // Not in the alert window
            }

            // Get latest insemination if exists
            $latestInsemination = $cow->inseminations->first();
            $latestInseminationData = null;
            if ($latestInsemination) {
                $latestInseminationData = [
                    'id' => $latestInsemination->id,
                    'insemination_date' => $latestInsemination->insemination_date,
                    'status' => $latestInsemination->status,
                    'notes' => $latestInsemination->notes,
                ];
            }

            return [
                'cow_id' => $cow->id,
                'animal_id' => $animal->id,
                'tag_number' => $animal->tag_number,
                'name' => $animal->name,
                'last_calving_date' => $cow->last_calving_date,
                'days_since_calving' => $daysSinceCalving,
                'days_until_ideal' => $daysUntilIdeal,
                'is_overdue' => $isOverdue,
                'status' => $isOverdue ? 'overdue' : ($daysSinceCalving >= 45 ? 'ready' : 'approaching'),
                'latest_insemination' => $latestInseminationData,
            ];
        })->filter(); // Remove null entries
    }

    /**
     * Get next insemination period information for a cow that has calved.
     *
     * @param Cow $cow
     * @return array|null
     */
    public function getNextInseminationPeriod(Cow $cow): ?array
    {
        // Only calculate if cow has calved and is not currently pregnant
        if (!$cow->last_calving_date || $cow->last_insemination_date) {
            return null; // Not calved yet or already pregnant
        }

        $lastCalvingDate = Carbon::parse($cow->last_calving_date);
        $daysSinceCalving = $lastCalvingDate->diffInDays(now());
        
        // Ideal insemination window is 50-90 days after calving
        $idealStart = 50;
        $idealEnd = 90;
        
        $daysUntilIdealStart = $idealStart - $daysSinceCalving;
        $daysUntilIdealEnd = $idealEnd - $daysSinceCalving;
        $isInWindow = $daysSinceCalving >= $idealStart && $daysSinceCalving <= $idealEnd;
        $isPastWindow = $daysSinceCalving > $idealEnd;
        $isBeforeWindow = $daysSinceCalving < $idealStart;
        
        $status = 'ready';
        if ($isPastWindow) {
            $status = 'overdue';
        } elseif ($isBeforeWindow) {
            $status = 'approaching';
        }

        $nextInseminationDate = $lastCalvingDate->copy()->addDays($idealStart);
        
        return [
            'last_calving_date' => $cow->last_calving_date,
            'days_since_calving' => $daysSinceCalving,
            'ideal_start_days' => $idealStart,
            'ideal_end_days' => $idealEnd,
            'days_until_ideal_start' => max(0, $daysUntilIdealStart),
            'days_until_ideal_end' => $daysUntilIdealEnd,
            'is_in_window' => $isInWindow,
            'is_past_window' => $isPastWindow,
            'is_before_window' => $isBeforeWindow,
            'next_insemination_date' => $nextInseminationDate->toDateString(),
            'status' => $status,
        ];
    }

    /**
     * Get notifications for upcoming calvings and insemination needs.
     *
     * @param int|null $farmId Optional farm ID to filter by farm
     * @return array
     */
    public function getNotifications($farmId = null)
    {
        $notifications = [];

        // Cows with calving due within 15 days
        $upcomingCalvings = $this->getUpcomingCalvings($farmId);
        $calvingDueSoon = $upcomingCalvings->filter(function ($cow) {
            return $cow['days_remaining'] <= 15 && $cow['days_remaining'] > 0;
        });

        foreach ($calvingDueSoon as $cow) {
            $notifications[] = [
                'type' => 'calving_due_soon',
                'priority' => $cow['days_remaining'] <= 5 ? 'high' : 'medium',
                'message' => "{$cow['tag_number']} is due to calve in {$cow['days_remaining']} days",
                'tag_number' => $cow['tag_number'],
                'name' => $cow['name'],
                'days_remaining' => $cow['days_remaining'],
                'expected_calving_date' => $cow['expected_calving_date'],
            ];
        }

        // Cows needing insemination - comprehensive notifications
        $cowsNeedingInsemination = $this->getCowsNeedingInsemination($farmId);
        
        // Get all cows that have calved and need insemination (not just filtered ones)
        $query = Cow::query()
            ->whereNotNull('last_calving_date')
            ->whereNull('last_insemination_date') // Not currently pregnant
            ->whereNull('actual_calving_date'); // Not currently calving

        if ($farmId) {
            $query->whereHas('animals', function($q) use ($farmId) {
                $q->where('farm_id', $farmId);
            });
        }

        $allCowsNeedingInsemination = $query->with('animals')->get();

        foreach ($allCowsNeedingInsemination as $cow) {
            $animal = $cow->animals->first();
            
            if (!$animal || !$cow->last_calving_date) {
                continue;
            }

            $lastCalvingDate = Carbon::parse($cow->last_calving_date);
            $daysSinceCalving = $lastCalvingDate->diffInDays(now());
            
            // Ideal insemination window is 50-90 days after calving
            $idealStart = 50;
            $idealEnd = 90;
            $daysUntilIdealStart = $idealStart - $daysSinceCalving;
            $isOverdue = $daysSinceCalving > $idealEnd;
            $isInWindow = $daysSinceCalving >= $idealStart && $daysSinceCalving <= $idealEnd;
            $isApproaching = $daysSinceCalving >= 45 && $daysSinceCalving < $idealStart;
            
            // Create notification for:
            // 1. Cows approaching window (45-49 days) - medium priority
            // 2. Cows in ideal window (50-90 days) - high priority
            // 3. Cows overdue (>90 days) - high priority
            if ($isApproaching || $isInWindow || $isOverdue) {
                $priority = ($isOverdue || $isInWindow) ? 'high' : 'medium';
                
                $message = '';
                if ($isOverdue) {
                    $daysOverdue = $daysSinceCalving - $idealEnd;
                    $message = "{$animal->tag_number} is {$daysOverdue} days overdue for insemination";
                } elseif ($isInWindow) {
                    $daysInWindow = $daysSinceCalving - $idealStart;
                    $message = "{$animal->tag_number} is in ideal insemination window ({$daysInWindow} days into window)";
                } else {
                    $message = "{$animal->tag_number} is approaching insemination window ({$daysUntilIdealStart} days until ideal start)";
                }
                
                $notifications[] = [
                    'type' => 'insemination_due',
                    'priority' => $priority,
                    'message' => $message,
                    'tag_number' => $animal->tag_number,
                    'name' => $animal->name,
                    'days_since_calving' => $daysSinceCalving,
                    'days_until_ideal' => max(0, $daysUntilIdealStart),
                    'is_overdue' => $isOverdue,
                    'is_in_window' => $isInWindow,
                    'is_approaching' => $isApproaching,
                    'last_calving_date' => $cow->last_calving_date,
                ];
            }
        }

        // Sort by priority (high first) and then by days
        usort($notifications, function ($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            if ($priorityOrder[$a['priority']] !== $priorityOrder[$b['priority']]) {
                return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
            }
            $daysA = $a['days_remaining'] ?? $a['days_until_ideal'] ?? 999;
            $daysB = $b['days_remaining'] ?? $b['days_until_ideal'] ?? 999;
            return $daysA - $daysB;
        });

        return $notifications;
    }
}
