<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Bull;
use App\Models\Cow;
use App\Models\Farmer;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FarmerWithAnimalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create farmer profile
        $farmer = Farmer::create([
            'subscription_plan' => 'premium',
            'address' => '123 Farm Road, Countryside',
        ]);

        // Create user for farmer
        $user = User::create([
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
            'profile_id' => $farmer->id,
            'profile_type' => Farmer::class,
        ]);

        // Create farm for farmer
        $farm = Farm::create([
            'name' => 'Test Farm',
            'farmer_id' => $farmer->id,
            'location' => '123 Farm Road',
            'state' => 'Countryside',
            'is_active' => true,
            'approved_at' => now(),
        ]);

        // Create 10 Cattle (mix of Cows and Bulls)
        $cattleTypes = ['Bull', 'Cow', 'Steer', 'Heifer'];
        for ($i = 1; $i <= 10; $i++) {
            $type = $cattleTypes[array_rand($cattleTypes)];
            $gender = in_array($type, ['Bull', 'Steer']) ? 'male' : 'female';
            
            // Create animalable based on type
            if ($type === 'Bull') {
                $qualities = ['Excellent', 'Good', 'Fair'];
                $levels = ['Low', 'Medium', 'High'];
                $animalable = Bull::create([
                    'semen_quality' => $qualities[array_rand($qualities)],
                    'aggression_level' => $levels[array_rand($levels)],
                ]);
            } else {
                // For Cow, Steer, Heifer - use Cow model
                $milkYield = $type === 'Cow' ? rand(15, 35) : null;
                
                // Create realistic pregnancy scenarios for Cows and Heifers
                $lastCalvingDate = null;
                $lastInseminationDate = null;
                $expectedCalvingDate = null;
                $actualCalvingDate = null;
                
                if ($type === 'Cow') {
                    // Randomly assign pregnancy status
                    $pregnancyStatus = rand(1, 4); // 1=not pregnant, 2=pregnant, 3=due soon, 4=already calved
                    
                    if ($pregnancyStatus === 1) {
                        // Not pregnant - just has a previous calving
                        $lastCalvingDate = Carbon::now()->subDays(rand(60, 300));
                    } elseif ($pregnancyStatus === 2) {
                        // Currently pregnant - inseminated 50-200 days ago
                        $daysSinceInsemination = rand(50, 200);
                        $lastInseminationDate = Carbon::now()->subDays($daysSinceInsemination);
                        $expectedCalvingDate = $lastInseminationDate->copy()->addDays(283);
                    } elseif ($pregnancyStatus === 3) {
                        // Due soon - inseminated 260-280 days ago
                        $daysSinceInsemination = rand(260, 280);
                        $lastInseminationDate = Carbon::now()->subDays($daysSinceInsemination);
                        $expectedCalvingDate = $lastInseminationDate->copy()->addDays(283);
                    } else {
                        // Already calved - inseminated and calved
                        $daysSinceCalving = rand(10, 60);
                        $actualCalvingDate = Carbon::now()->subDays($daysSinceCalving);
                        $lastCalvingDate = $actualCalvingDate;
                        $lastInseminationDate = $actualCalvingDate->copy()->subDays(283);
                        $expectedCalvingDate = $lastInseminationDate->copy()->addDays(283);
                    }
                } elseif ($type === 'Heifer') {
                    // Heifers might be pregnant but haven't calved yet
                    $isPregnant = rand(1, 3) === 1; // 33% chance of being pregnant
                    
                    if ($isPregnant) {
                        $daysSinceInsemination = rand(50, 250);
                        $lastInseminationDate = Carbon::now()->subDays($daysSinceInsemination);
                        $expectedCalvingDate = $lastInseminationDate->copy()->addDays(283);
                    }
                }
                
                $animalable = Cow::create([
                    'milk_yield' => $milkYield,
                    'last_calving_date' => $lastCalvingDate,
                    'last_insemination_date' => $lastInseminationDate,
                    'expected_calving_date' => $expectedCalvingDate,
                    'actual_calving_date' => $actualCalvingDate,
                ]);
            }

            Animal::create([
                'tag_number' => 'CATTLE-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'animalable_id' => $animalable->id,
                'animalable_type' => get_class($animalable),
                'farm_id' => $farm->id,
                'species' => 'cattle',
                'type' => $type,
                'name' => 'Cattle ' . $i,
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(1, 10))->subDays(rand(0, 365)),
                'is_active' => true,
            ]);
        }

        // Create 10 Horses (no animalable needed)
        $horseTypes = ['Stallion', 'Gelding', 'Mare', 'Filly'];
        for ($i = 1; $i <= 10; $i++) {
            $type = $horseTypes[array_rand($horseTypes)];
            $gender = in_array($type, ['Stallion', 'Gelding']) ? 'male' : 'female';

            Animal::create([
                'tag_number' => 'HORSE-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'animalable_id' => null,
                'animalable_type' => null,
                'farm_id' => $farm->id,
                'species' => 'horse',
                'type' => $type,
                'name' => 'Horse ' . $i,
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(1, 15))->subDays(rand(0, 365)),
                'is_active' => true,
            ]);
        }

        // Create 10 Sheep (no animalable needed)
        $sheepTypes = ['ram', 'wether', 'ewe', 'ewe_lamb'];
        for ($i = 1; $i <= 10; $i++) {
            $type = $sheepTypes[array_rand($sheepTypes)];
            $gender = in_array($type, ['ram', 'wether']) ? 'male' : 'female';

            Animal::create([
                'tag_number' => 'SHEEP-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'animalable_id' => null,
                'animalable_type' => null,
                'farm_id' => $farm->id,
                'species' => 'sheep',
                'type' => $type,
                'name' => 'Sheep ' . $i,
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(1, 8))->subDays(rand(0, 365)),
                'is_active' => true,
            ]);
        }

        $this->command->info('Farmer with farm and 30 animals (10 cattle, 10 horses, 10 sheep) created successfully!');
        $this->command->info('Email: test2@example.com');
        $this->command->info('Password: password');
    }
}
