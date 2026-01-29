<?php

namespace App\Observers;

use App\Models\Cow;
use Illuminate\Support\Facades\Log;

class CowObserver
{
    /**
     * Handle the Cow "updated" event.
     * 
     * When a Heifer has her first calving (actual_calving_date is set),
     * automatically update her type from 'Heifer' to 'Cow'.
     */
    public function updated(Cow $cow): void
    {
        // Check if actual_calving_date was just set (changed from null/empty to a date)
        if ($cow->isDirty('actual_calving_date') && $cow->actual_calving_date) {
            // Get the original value before update
            $originalValue = $cow->getOriginal('actual_calving_date');
            
            // Only proceed if it was previously null/empty (first calving)
            if (!$originalValue) {
                // Get the associated animal
                $animal = $cow->animals()->first();
                
                if ($animal && $animal->type === 'Heifer') {
                    // Update the animal type from Heifer to Cow
                    $animal->update(['type' => 'Cow']);
                    
                    // Log the change (optional)
                    Log::info("Heifer {$animal->tag_number} has become a Cow after first calving", [
                        'animal_id' => $animal->id,
                        'tag_number' => $animal->tag_number,
                        'calving_date' => $cow->actual_calving_date,
                    ]);
                    
                    // Note: You can add notification logic here if needed
                    // Example: Send notification "Congratulations! Heifer {$animal->tag_number} has become a Cow!"
                }
            }
        }
    }
}
