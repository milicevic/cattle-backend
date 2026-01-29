<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Cow;
use App\Models\Farmer;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CowsInDifferentStagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create farmer
        $farmer = Farmer::first();
        
        if (!$farmer) {
            $farmer = Farmer::create([
                'subscription_plan' => 'premium',
                'address' => '123 Farm Road, Countryside',
            ]);

            // Create user for farmer
            User::create([
                'email' => 'farmer@example.com',
                'password' => Hash::make('password'),
                'profile_id' => $farmer->id,
                'profile_type' => Farmer::class,
            ]);
        }

        // Get or create farm
        $farm = Farm::where('farmer_id', $farmer->id)->first();
        
        if (!$farm) {
            $farm = Farm::create([
                'name' => 'Large Cattle Farm',
                'farmer_id' => $farmer->id,
                'location' => '123 Farm Road',
                'state' => 'Countryside',
                'is_active' => true,
                'approved_at' => now(),
            ]);
        }

        $now = Carbon::now();
        $gestationPeriod = 283; // days

        // Create 100 cows with different stages
        for ($i = 1; $i <= 100; $i++) {
            $stage = $i % 10; // Distribute across 10 different stages
            
            $lastCalvingDate = null;
            $lastInseminationDate = null;
            $expectedCalvingDate = null;
            $actualCalvingDate = null;
            $milkYield = null;

            switch ($stage) {
                case 0: // Early pregnancy (0-50 days)
                    $daysSinceInsemination = rand(10, 50);
                    $lastInseminationDate = $now->copy()->subDays($daysSinceInsemination);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(15, 25);
                    break;

                case 1: // Mid pregnancy (51-150 days)
                    $daysSinceInsemination = rand(51, 150);
                    $lastInseminationDate = $now->copy()->subDays($daysSinceInsemination);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(20, 30);
                    break;

                case 2: // Late pregnancy (151-250 days)
                    $daysSinceInsemination = rand(151, 250);
                    $lastInseminationDate = $now->copy()->subDays($daysSinceInsemination);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(18, 28);
                    break;

                case 3: // Due soon (251-280 days)
                    $daysSinceInsemination = rand(251, 280);
                    $lastInseminationDate = $now->copy()->subDays($daysSinceInsemination);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(15, 25);
                    break;

                case 4: // Overdue (281+ days, not calved yet)
                    $daysSinceInsemination = rand(281, 300);
                    $lastInseminationDate = $now->copy()->subDays($daysSinceInsemination);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(12, 22);
                    break;

                case 5: // Recently calved (1-30 days ago)
                    $daysSinceCalving = rand(1, 30);
                    $actualCalvingDate = $now->copy()->subDays($daysSinceCalving);
                    $lastCalvingDate = $actualCalvingDate;
                    $lastInseminationDate = $actualCalvingDate->copy()->subDays($gestationPeriod);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(25, 35);
                    break;

                case 6: // Calved 31-60 days ago (approaching insemination window)
                    $daysSinceCalving = rand(31, 60);
                    $actualCalvingDate = $now->copy()->subDays($daysSinceCalving);
                    $lastCalvingDate = $actualCalvingDate;
                    $lastInseminationDate = $actualCalvingDate->copy()->subDays($gestationPeriod);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(28, 38);
                    break;

                case 7: // Calved 61-90 days ago (in ideal insemination window)
                    $daysSinceCalving = rand(61, 90);
                    $actualCalvingDate = $now->copy()->subDays($daysSinceCalving);
                    $lastCalvingDate = $actualCalvingDate;
                    $lastInseminationDate = $actualCalvingDate->copy()->subDays($gestationPeriod);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(30, 40);
                    break;

                case 8: // Calved 91-120 days ago (past ideal window, not inseminated)
                    $daysSinceCalving = rand(91, 120);
                    $actualCalvingDate = $now->copy()->subDays($daysSinceCalving);
                    $lastCalvingDate = $actualCalvingDate;
                    $lastInseminationDate = $actualCalvingDate->copy()->subDays($gestationPeriod);
                    $expectedCalvingDate = $lastInseminationDate->copy()->addDays($gestationPeriod);
                    $milkYield = rand(25, 35);
                    break;

                case 9: // Not pregnant, ready for insemination (calved 120+ days ago)
                    $daysSinceCalving = rand(120, 200);
                    $lastCalvingDate = $now->copy()->subDays($daysSinceCalving);
                    $milkYield = rand(20, 30);
                    break;
            }

            // Create cow record
            $cow = Cow::create([
                'milk_yield' => $milkYield,
                'last_calving_date' => $lastCalvingDate,
                'last_insemination_date' => $lastInseminationDate,
                'expected_calving_date' => $expectedCalvingDate,
                'actual_calving_date' => $actualCalvingDate,
            ]);

            // Determine type based on age and calving history
            $type = 'Cow';
            if ($i <= 10) {
                // First 10 are heifers (young females that haven't calved or just calved once)
                $type = $actualCalvingDate ? 'Cow' : 'Heifer';
            }

            // Create animal record
            Animal::create([
                'tag_number' => 'COW-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'animalable_id' => $cow->id,
                'animalable_type' => Cow::class,
                'farm_id' => $farm->id,
                'species' => 'cattle',
                'type' => $type,
                'name' => 'Cow ' . $i,
                'gender' => 'female',
                'date_of_birth' => $now->copy()->subYears(rand(2, 8))->subDays(rand(0, 365)),
                'is_active' => true,
            ]);
        }

        $this->command->info('100 cows created successfully with different insemination/calving stages!');
        $this->command->info('Stages distributed:');
        $this->command->info('- Early pregnancy (0-50 days): 10 cows');
        $this->command->info('- Mid pregnancy (51-150 days): 10 cows');
        $this->command->info('- Late pregnancy (151-250 days): 10 cows');
        $this->command->info('- Due soon (251-280 days): 10 cows');
        $this->command->info('- Overdue (281+ days): 10 cows');
        $this->command->info('- Recently calved (1-30 days): 10 cows');
        $this->command->info('- Calved 31-60 days ago: 10 cows');
        $this->command->info('- Calved 61-90 days ago (ideal window): 10 cows');
        $this->command->info('- Calved 91-120 days ago: 10 cows');
        $this->command->info('- Ready for insemination (120+ days): 10 cows');
    }
}
