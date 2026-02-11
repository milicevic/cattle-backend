<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Bull;
use App\Models\Cow;
use App\Models\Farm;
use App\Services\CattleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnimalController extends Controller
{
    /**
     * Get the farm that the authenticated user has access to.
     * Returns farm for farmers or approved farms for vets.
     */
    protected function getUserFarm(): ?Farm
    {
        $user = request()->user();
        $profile = $user->profile;

        if ($profile instanceof \App\Models\Farmer) {
            return $profile->farm;
        } elseif ($profile instanceof \App\Models\Vet) {
            // For vets, get the first approved farm (or allow selecting farm in future)
            return $profile->farms()->first();
        }

        return null;
    }

    /**
     * Check if user has access to a specific farm.
     */
    protected function hasAccessToFarm(int $farmId): bool
    {
        $user = request()->user();
        $profile = $user->profile;

        if ($profile instanceof \App\Models\Farmer) {
            return $profile->farm && $profile->farm->id === $farmId;
        } elseif ($profile instanceof \App\Models\Vet) {
            return $profile->farms()->where('farms.id', $farmId)->exists();
        }

        return false;
    }

    /**
     * Get all animals for the authenticated user's accessible farm(s).
     */
    public function index(Request $request): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        $farm = null;
        if ($profile instanceof \App\Models\Farmer) {
            $farm = $profile->farm;
        } elseif ($profile instanceof \App\Models\Vet) {
            // For vets, allow specifying farm_id or use first assigned farm
            if ($request->has('farm_id')) {
                $farmId = (int) $request->farm_id;
                if ($this->hasAccessToFarm($farmId)) {
                    $farm = Farm::find($farmId);
                } else {
                    return response()->json(['message' => 'You do not have access to this farm'], 403);
                }
            } else {
                // Default to first assigned farm
                $farm = $profile->farms()->first();
            }
        }

        if (!$farm) {
            return response()->json(['animals' => [], 'by_species' => []]);
        }

        // Load animals with relationships, but handle animalable carefully
        $query = Animal::where('farm_id', $farm->id)
            ->with(['mother', 'father']);

        // Filter by species if provided
        if ($request->has('species')) {
            $query->where('species', $request->species);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Pagination parameters
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        $perPage = min(max(1, (int)$perPage), 100); // Limit between 1 and 100
        $page = max(1, (int)$page);

        // Get total count before pagination
        $total = $query->count();

        // Paginate the query
        $paginatedAnimals = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Only eager load animalable for cattle species to avoid errors
        $animals = $paginatedAnimals->map(function ($animal) {
            // Only try to load animalable if it's cattle and the class exists
            if ($animal->species === 'cattle' && $animal->animalable_type && class_exists($animal->animalable_type)) {
                try {
                    $animal->load('animalable');
                } catch (\Exception $e) {
                    // If class doesn't exist, set to null
                    $animal->setRelation('animalable', null);
                }
            } else {
                $animal->setRelation('animalable', null);
            }
            return $animal;
        });

        // Group by species (for all animals, not just paginated)
        $allAnimals = Animal::where('farm_id', $farm->id)->get();
        $bySpecies = [
            'cattle' => $allAnimals->where('species', 'cattle')->values(),
            'horse' => $allAnimals->where('species', 'horse')->values(),
            'sheep' => $allAnimals->where('species', 'sheep')->values(),
        ];

        return response()->json([
            'animals' => $animals,
            'by_species' => $bySpecies,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
        ]);
    }

    /**
     * Create a new animal.
     */
    public function store(Request $request): JsonResponse
    {
        $user = request()->user();
        $farmer = $user->profile;

        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }

        $farm = $farmer->farm;

        if (!$farm) {
            return response()->json(['message' => 'Farm not found. Please create a farm first.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255|unique:animals,tag_number,NULL,id,farm_id,' . $farm->id,
            'species' => 'required|in:cattle,horse,sheep',
            'type' => 'required|string',
            'name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female', // Made optional - will be auto-determined
            'date_of_birth' => 'nullable|date',
            'mother_id' => 'nullable|exists:animals,id',
            'father_id' => 'nullable|exists:animals,id',
            // Species-specific fields (only for cattle)
            'milk_yield' => 'nullable|numeric',
            'last_calving_date' => 'nullable|date',
            'semen_quality' => 'nullable|string',
            'aggression_level' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Automatically determine gender from type (gender is always set automatically)
        $gender = Animal::getGenderFromType($request->type, $request->species);

        // Create animalable based on species and type (only for cattle)
        $animalable = null;
        $animalableType = null;

        if ($request->species === 'cattle') {
            if ($request->type === 'Bull') {
                $animalable = Bull::create([
                    'semen_quality' => $request->semen_quality,
                    'aggression_level' => $request->aggression_level,
                ]);
                $animalableType = Bull::class;
            } else {
                // Cow, Steer, Heifer
                $animalable = Cow::create([
                    'milk_yield' => $request->milk_yield,
                    'last_calving_date' => $request->last_calving_date,
                ]);
                $animalableType = Cow::class;
            }
        }
        // For horse and sheep, animalable_id and animalable_type remain null

        // Build animal data array
        $animalData = [
            'tag_number' => $request->tag_number,
            'farm_id' => $farm->id,
            'species' => $request->species,
            'type' => $request->type,
            'name' => $request->name,
            'gender' => $gender, // Use auto-determined gender
            'date_of_birth' => $request->date_of_birth,
            'mother_id' => $request->mother_id,
            'father_id' => $request->father_id,
            'is_active' => true,
        ];

        // Only include animalable fields if they exist (for cattle only)
        if ($animalable && $animalableType) {
            $animalData['animalable_id'] = $animalable->id;
            $animalData['animalable_type'] = $animalableType;
        }

        // Create animal
        $animal = Animal::create($animalData);

        $animal->load(['animalable', 'mother', 'father']);

        return response()->json([
            'message' => 'Animal created successfully',
            'animal' => $animal,
        ], 201);
    }

    /**
     * Get a specific animal.
     */
    public function show(string $id): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        $animal = Animal::findOrFail($id);

        // Check access: farmer owns the farm, or vet is assigned to the farm
        if ($profile instanceof \App\Models\Farmer) {
            $farm = $profile->farm;
            if (!$farm || $animal->farm_id !== $farm->id) {
                return response()->json(['message' => 'Animal not found'], 404);
            }
        } elseif ($profile instanceof \App\Models\Vet) {
            // Check if vet has access to this farm
            if (!$this->hasAccessToFarm($animal->farm_id)) {
                return response()->json(['message' => 'You do not have access to this animal'], 403);
            }
        } else {
            return response()->json(['message' => 'Access denied'], 403);
        }

        // Load animalable only if it's cattle and class exists
        if ($animal->species === 'cattle' && $animal->animalable_type && class_exists($animal->animalable_type)) {
            try {
                $animal->load('animalable');
            } catch (\Exception $e) {
                $animal->setRelation('animalable', null);
            }
        } else {
            $animal->setRelation('animalable', null);
        }

        $animal->load(['mother', 'father', 'healthRecords.vet']);

        $response = ['animal' => $animal];

        // Add pregnancy progress for cows
        if ($animal->species === 'cattle' && in_array($animal->type, ['Cow', 'Heifer']) && $animal->animalable instanceof Cow) {
            $cattleService = new CattleService();
            $pregnancyProgress = $cattleService->getPregnancyProgress($animal->animalable);
            if ($pregnancyProgress) {
                $response['pregnancy_progress'] = $pregnancyProgress;
            }
            
            // Add next insemination period if cow has calved
            if ($animal->animalable->actual_calving_date || $animal->animalable->last_calving_date) {
                $nextInsemination = $cattleService->getNextInseminationPeriod($animal->animalable);
                if ($nextInsemination) {
                    $response['next_insemination_period'] = $nextInsemination;
                }
            }
        }

        // Add calving history for all animals (to see if they were born from a calving)
        // For cows/heifers, this shows their own calving history
        // For calves, this shows the calving record that created them
        $calvings = \App\Models\Calving::where('animal_id', $animal->id)
            ->with('performedBy')
            ->orderBy('calving_date', 'desc')
            ->get();
        
        if ($calvings->isNotEmpty()) {
            $response['calving_history'] = $calvings;
        }

        return response()->json($response);
    }

    /**
     * Get upcoming calvings (cows in final month of pregnancy).
     */
    public function upcomingCalvings(Request $request): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        $farm = null;
        if ($request->has('farm_id')) {
            $farmId = (int) $request->farm_id;
            if ($profile instanceof \App\Models\Farmer) {
                $farmerFarm = $profile->farm;
                if ($farmerFarm && $farmerFarm->id === $farmId) {
                    $farm = $farmerFarm;
                }
            } elseif ($profile instanceof \App\Models\Vet) {
                if ($this->hasAccessToFarm($farmId)) {
                    $farm = Farm::find($farmId);
                }
            }
        } else {
            $farm = $this->getUserFarm();
        }

        if (!$farm) {
            return response()->json(['upcoming_calvings' => [], 'count' => 0]);
        }

        // Pagination parameters
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        $perPage = min(max(1, (int)$perPage), 100);
        $page = max(1, (int)$page);

        $cattleService = new CattleService();
        $allUpcomingCalvings = $cattleService->getUpcomingCalvings($farm->id);
        
        $total = $allUpcomingCalvings->count();
        $upcomingCalvings = $allUpcomingCalvings->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'upcoming_calvings' => $upcomingCalvings,
            'count' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
        ]);
    }

    /**
     * Get cows needing insemination soon.
     */
    public function cowsNeedingInsemination(Request $request): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        $farm = null;
        if ($request->has('farm_id')) {
            $farmId = (int) $request->farm_id;
            if ($profile instanceof \App\Models\Farmer) {
                $farmerFarm = $profile->farm;
                if ($farmerFarm && $farmerFarm->id === $farmId) {
                    $farm = $farmerFarm;
                }
            } elseif ($profile instanceof \App\Models\Vet) {
                if ($this->hasAccessToFarm($farmId)) {
                    $farm = Farm::find($farmId);
                }
            }
        } else {
            if ($profile instanceof \App\Models\Farmer) {
                $farm = $profile->farm;
            } elseif ($profile instanceof \App\Models\Vet) {
                $farm = $profile->farms()->first();
            }
        }

        if (!$farm) {
            return response()->json(['cows' => [], 'count' => 0]);
        }

        // Pagination parameters
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        $perPage = min(max(1, (int)$perPage), 100);
        $page = max(1, (int)$page);

        $cattleService = new CattleService();
        $allCows = $cattleService->getCowsNeedingInsemination($farm->id);
        
        $total = $allCows->count();
        $cows = $allCows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'cows' => $cows,
            'count' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
        ]);
    }

    /**
     * Get notifications for calvings and insemination needs.
     */
    public function notifications(): JsonResponse
    {
        $farm = $this->getUserFarm();

        if (!$farm) {
            return response()->json(['notifications' => [], 'count' => 0]);
        }

        $user = request()->user();
        $cattleService = new CattleService();
        
        // Sync notifications to database
        $cattleService->syncNotificationsForFarm($farm->id);

        // Get unread notifications from database
        $unreadNotifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                return array_merge($data, [
                    'id' => $notification->id,
                    'created_at' => $notification->created_at->toISOString(),
                ]);
            })
            ->values()
            ->toArray();

        return response()->json([
            'notifications' => $unreadNotifications,
            'count' => count($unreadNotifications),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead(string $notificationId): JsonResponse
    {
        $user = request()->user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Update an animal.
     * Both farmers and vets can update animals on their accessible farms.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number' => 'sometimes|string|max:255|unique:animals,tag_number,' . $id . ',id,farm_id,' . $farm->id,
            'species' => 'sometimes|in:cattle,horse,sheep',
            'type' => 'sometimes|string',
            'name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'mother_id' => 'nullable|exists:animals,id',
            'father_id' => 'nullable|exists:animals,id',
            'is_active' => 'sometimes|boolean',
            // Cattle-specific fields
            'milk_yield' => 'nullable|numeric',
            'last_calving_date' => 'nullable|date',
            'last_insemination_date' => 'nullable|date',
            'expected_calving_date' => 'nullable|date',
            'actual_calving_date' => 'nullable|date',
            'semen_quality' => 'nullable|string',
            'aggression_level' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prepare animal update data
        $animalData = [];
        
        if ($request->has('tag_number')) {
            $animalData['tag_number'] = $request->tag_number;
        }
        if ($request->has('species')) {
            $animalData['species'] = $request->species;
        }
        if ($request->has('type')) {
            $animalData['type'] = $request->type;
            // Auto-determine gender if type changes
            $species = $request->has('species') ? $request->species : $animal->species;
            $animalData['gender'] = Animal::getGenderFromType($request->type, $species);
        }
        if ($request->has('name')) {
            $animalData['name'] = $request->name;
        }
        if ($request->has('date_of_birth')) {
            $animalData['date_of_birth'] = $request->date_of_birth;
        }
        if ($request->has('mother_id')) {
            $animalData['mother_id'] = $request->mother_id;
        }
        if ($request->has('father_id')) {
            $animalData['father_id'] = $request->father_id;
        }
        if ($request->has('is_active')) {
            $animalData['is_active'] = $request->is_active;
        }

        // Update animal basic fields
        if (!empty($animalData)) {
            $animal->update($animalData);
        }

        // Update animalable fields for cattle
        if ($animal->species === 'cattle' && $animal->animalable) {
            $animalableData = [];
            
            // Bull-specific fields
            if ($animal->type === 'Bull' && $animal->animalable instanceof Bull) {
                if ($request->has('semen_quality')) {
                    $animalableData['semen_quality'] = $request->semen_quality;
                }
                if ($request->has('aggression_level')) {
                    $animalableData['aggression_level'] = $request->aggression_level;
                }
            }
            
            // Cow-specific fields (Cow, Steer, Heifer)
            if (in_array($animal->type, ['Cow', 'Steer', 'Heifer']) && $animal->animalable instanceof Cow) {
                if ($request->has('milk_yield')) {
                    $animalableData['milk_yield'] = $request->milk_yield;
                }
                if ($request->has('last_calving_date')) {
                    $animalableData['last_calving_date'] = $request->last_calving_date;
                }
                if ($request->has('last_insemination_date')) {
                    $animalableData['last_insemination_date'] = $request->last_insemination_date;
                    // Auto-calculate expected_calving_date when last_insemination_date is updated
                    if ($request->last_insemination_date) {
                        $cattleService = new CattleService();
                        $inseminationDate = \Carbon\Carbon::parse($request->last_insemination_date);
                        $animalableData['expected_calving_date'] = $inseminationDate->copy()->addDays(283);
                    }
                }
                // expected_calving_date should not be manually updated - it's auto-calculated
                // Only allow updating if last_insemination_date is not being updated
                if ($request->has('actual_calving_date')) {
                    $animalableData['actual_calving_date'] = $request->actual_calving_date;
                    // Also update last_calving_date if actual_calving_date is set
                    if ($request->actual_calving_date) {
                        $animalableData['last_calving_date'] = $request->actual_calving_date;
                    }
                }
            }

            if (!empty($animalableData)) {
                $animal->animalable->update($animalableData);
            }
        }

        // Reload relationships
        $animal->load(['animalable', 'mother', 'father']);

        // Add pregnancy progress for cows if applicable
        $response = [
            'message' => 'Animal updated successfully',
            'animal' => $animal,
        ];

        if ($animal->species === 'cattle' && in_array($animal->type, ['Cow', 'Heifer']) && $animal->animalable instanceof Cow) {
            $cattleService = new CattleService();
            $pregnancyProgress = $cattleService->getPregnancyProgress($animal->animalable);
            if ($pregnancyProgress) {
                $response['pregnancy_progress'] = $pregnancyProgress;
            }
        }

        return response()->json($response);
    }

    /**
     * Delete an animal.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = request()->user();
        $farmer = $user->profile;

        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }

        $farm = $farmer->farm;

        $animal = Animal::where('id', $id)
            ->where('farm_id', $farm->id)
            ->first();

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $animal->delete();

        return response()->json([
            'message' => 'Animal deleted successfully',
        ]);
    }

    /**
     * Record insemination for a cow.
     * Both farmers and vets can record insemination.
     */
    public function recordInsemination(Request $request, string $id): JsonResponse
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if animal is a cow
        if ($animal->species !== 'cattle' || !in_array($animal->type, ['Cow', 'Heifer'])) {
            return response()->json(['message' => 'Insemination can only be recorded for cows and heifers'], 400);
        }

        if (!$animal->animalable || !($animal->animalable instanceof Cow)) {
            return response()->json(['message' => 'Cow record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'insemination_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'bull_id' => 'nullable|integer|exists:bulls,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bullId = $request->filled('bull_id') ? (int) $request->bull_id : null;
        if ($bullId !== null) {
            $bullAnimal = Animal::where('animalable_type', Bull::class)
                ->where('animalable_id', $bullId)
                ->first();
            if (! $bullAnimal || $bullAnimal->farm_id !== $animal->farm_id) {
                return response()->json(['message' => 'Bull must belong to the same farm as the cow'], 422);
            }
        }

        $cattleService = new CattleService();
        $user = request()->user();
        $insemination = $cattleService->recordInsemination(
            $animal->animalable,
            $request->insemination_date,
            $request->notes,
            $user,
            $bullId
        );

        $animal->load(['animalable', 'mother', 'father']);

        return response()->json([
            'message' => 'Insemination recorded successfully',
            'insemination' => $insemination,
            'animal' => $animal,
            'pregnancy_progress' => $cattleService->getPregnancyProgress($animal->animalable),
        ]);
    }

    /**
     * Update insemination status.
     * Both farmers and vets can update insemination status.
     */
    public function updateInseminationStatus(Request $request, string $animalId, string $inseminationId): JsonResponse
    {
        $animal = Animal::find($animalId);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $insemination = \App\Models\Insemination::where('id', $inseminationId)
            ->where('animal_id', $animal->id)
            ->first();

        if (!$insemination) {
            return response()->json(['message' => 'Insemination record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,failed,needs_repeat',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $insemination->update([
            'status' => $request->status,
            'notes' => $request->has('notes') ? $request->notes : $insemination->notes,
        ]);

        // If confirmed, update cow's last_insemination_date if this is the latest confirmed insemination
        if ($request->status === 'confirmed') {
            $latestConfirmed = $animal->animalable->inseminations()
                ->where('status', 'confirmed')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestConfirmed && $latestConfirmed->id === $insemination->id) {
                $expectedCalving = \Carbon\Carbon::parse($insemination->insemination_date)->addDays(283);
                $animal->animalable->update([
                    'last_insemination_date' => $insemination->insemination_date,
                    'expected_calving_date' => $expectedCalving,
                ]);
            }
        }

        return response()->json([
            'message' => 'Insemination status updated successfully',
            'insemination' => $insemination->fresh(),
        ]);
    }

    /**
     * Get insemination history for an animal.
     */
    public function getInseminationHistory(string $id): JsonResponse
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if animal is a cow
        if ($animal->species !== 'cattle' || !in_array($animal->type, ['Cow', 'Heifer'])) {
            return response()->json(['message' => 'Insemination history is only available for cows and heifers'], 400);
        }

        $inseminations = \App\Models\Insemination::where('animal_id', $animal->id)
            ->with(['bull.animals'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ins) {
                $bullAnimal = $ins->bull ? $ins->bull->animals->first() : null;
                return [
                    'id' => $ins->id,
                    'cow_id' => $ins->cow_id,
                    'animal_id' => $ins->animal_id,
                    'bull_id' => $ins->bull_id,
                    'bull' => $ins->bull_id && $bullAnimal ? [
                        'id' => $ins->bull->id,
                        'tag_number' => $bullAnimal->tag_number,
                        'name' => $bullAnimal->name,
                    ] : null,
                    'insemination_date' => $ins->insemination_date,
                    'status' => $ins->status,
                    'notes' => $ins->notes,
                    'created_at' => $ins->created_at,
                    'updated_at' => $ins->updated_at,
                ];
            });

        return response()->json([
            'inseminations' => $inseminations,
        ]);
    }

    /**
     * Get calving history for an animal.
     */
    public function getCalvingHistory(string $id): JsonResponse
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        // Calving history is available for all animals (to see if they were born from a calving)
        // But primarily for cows/heifers to see their own calving history
        $calvings = \App\Models\Calving::where('animal_id', $animal->id)
            ->with('performedBy')
            ->orderBy('calving_date', 'desc')
            ->get();

        return response()->json([
            'calvings' => $calvings,
        ]);
    }

    /**
     * Record calving for a cow.
     * Both farmers and vets can record calving.
     */
    public function recordCalving(Request $request, string $id): JsonResponse
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if user has access to this animal's farm
        if (!$this->hasAccessToFarm($animal->farm_id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        // Check if animal is a cow
        if ($animal->species !== 'cattle' || !in_array($animal->type, ['Cow', 'Heifer'])) {
            return response()->json(['message' => 'Calving can only be recorded for cows and heifers'], 400);
        }

        if (!$animal->animalable || !($animal->animalable instanceof Cow)) {
            return response()->json(['message' => 'Cow record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_successful' => 'required|boolean',
            'calving_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'calves' => 'nullable|array',
            'calves.*.tag_number' => 'required_with:calves|string|max:255|unique:animals,tag_number,NULL,id,farm_id,' . $animal->farm_id,
            'calves.*.name' => 'nullable|string|max:255',
            'calves.*.type' => 'required_with:calves|in:Bull,Cow,Heifer,Steer',
            'calves.*.date_of_birth' => 'nullable|date',
            'calves.*.milk_yield' => 'nullable|numeric',
            'calves.*.semen_quality' => 'nullable|string',
            'calves.*.aggression_level' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cattleService = new CattleService();
        $user = request()->user();
        
        // Only pass calves if calving was successful
        $calves = $request->is_successful ? $request->calves : null;
        
        $result = $cattleService->recordCalving(
            $animal->animalable,
            $request->calving_date,
            $calves,
            $request->notes,
            $request->is_successful,
            $user
        );

        // Reload animal to get updated status
        $animal->refresh();
        $animal->load(['animalable', 'mother', 'father']);

        return response()->json([
            'message' => $request->is_successful 
                ? 'Calving recorded successfully' 
                : 'Unsuccessful calving recorded',
            'animal' => $animal,
            'calves' => $result['calves'] ?? [],
            'notes' => $result['notes'],
            'is_successful' => $request->is_successful,
        ]);
    }
}
