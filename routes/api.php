<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegistrationController;
use App\Models\Vet;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register/farmer', [RegistrationController::class, 'registerFarmer']);
Route::post('/register/vet', [RegistrationController::class, 'registerVet']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Farm routes
    Route::get('/farm', [\App\Http\Controllers\Api\FarmController::class, 'show']);
    Route::post('/farm', [\App\Http\Controllers\Api\FarmController::class, 'store']);
    Route::put('/farm', [\App\Http\Controllers\Api\FarmController::class, 'update']);
    
    // Animal routes
    Route::get('/animals', [\App\Http\Controllers\Api\AnimalController::class, 'index']);
    Route::post('/animals', [\App\Http\Controllers\Api\AnimalController::class, 'store']);
    // Specific routes must come before parameterized routes
    Route::get('/animals/upcoming-calvings', [\App\Http\Controllers\Api\AnimalController::class, 'upcomingCalvings']);
    Route::get('/animals/needing-insemination', [\App\Http\Controllers\Api\AnimalController::class, 'cowsNeedingInsemination']);
    Route::get('/animals/notifications', [\App\Http\Controllers\Api\AnimalController::class, 'notifications']);
    Route::get('/animals/{id}', [\App\Http\Controllers\Api\AnimalController::class, 'show']);
    Route::put('/animals/{id}', [\App\Http\Controllers\Api\AnimalController::class, 'update']);
    Route::delete('/animals/{id}', [\App\Http\Controllers\Api\AnimalController::class, 'destroy']);
    Route::post('/animals/{id}/insemination', [\App\Http\Controllers\Api\AnimalController::class, 'recordInsemination']);
    Route::get('/animals/{id}/insemination-history', [\App\Http\Controllers\Api\AnimalController::class, 'getInseminationHistory']);
    Route::put('/animals/{animalId}/insemination/{inseminationId}/status', [\App\Http\Controllers\Api\AnimalController::class, 'updateInseminationStatus']);
    Route::post('/animals/{id}/calving', [\App\Http\Controllers\Api\AnimalController::class, 'recordCalving']);
    
    // Vet routes
    Route::get('/vets', [\App\Http\Controllers\Api\VetController::class, 'index']);
    
    // Vet request routes
    Route::get('/vet-requests/my-requests', [\App\Http\Controllers\Api\VetRequestController::class, 'myRequests']);
    Route::get('/vet-requests/pending', [\App\Http\Controllers\Api\VetRequestController::class, 'pendingRequests']);
    Route::get('/vet-requests/farms', [\App\Http\Controllers\Api\VetRequestController::class, 'listFarms']);
    Route::get('/vet-requests/assigned-farms', [\App\Http\Controllers\Api\VetRequestController::class, 'assignedFarms']);
    Route::post('/vet-requests', [\App\Http\Controllers\Api\VetRequestController::class, 'sendRequest']);
    Route::post('/vet-requests/{id}/approve', [\App\Http\Controllers\Api\VetRequestController::class, 'approveRequest']);
    Route::post('/vet-requests/{id}/reject', [\App\Http\Controllers\Api\VetRequestController::class, 'rejectRequest']);
    Route::delete('/vet-requests/{id}', [\App\Http\Controllers\Api\VetRequestController::class, 'cancelRequest']);
    
    // Farm-Vet assignment routes (deprecated - use vet requests instead)
    Route::get('/farm/vets', function () {
        $user = request()->user();
        $farmer = $user->profile;
        
        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }
        
        $farm = $farmer->farm;
        
        if (!$farm) {
            return response()->json(['vets' => []]);
        }
        
        $vets = $farm->vets()->get()->map(function ($vet) {
            $vetUser = \App\Models\User::where('profile_id', $vet->id)
                ->where('profile_type', Vet::class)
                ->first();
            
            return [
                'id' => $vet->id,
                'license_number' => $vet->license_number,
                'specialization' => $vet->specialization,
                'clinic_name' => $vet->clinic_name,
                'user' => $vetUser ? ['email' => $vetUser->email] : null,
            ];
        });
        
        return response()->json(['vets' => $vets]);
    });
    
    Route::post('/farm/vets/{vetId}', function ($vetId) {
        $user = request()->user();
        $farmer = $user->profile;
        
        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }
        
        $farm = $farmer->farm;
        
        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }
        
        $vet = Vet::findOrFail($vetId);
        
        if (!$farm->vets()->where('vets.id', $vetId)->exists()) {
            $farm->vets()->attach($vetId, ['assigned_at' => now()]);
        }
        
        return response()->json(['message' => 'Vet assigned successfully']);
    });
    
    Route::delete('/farm/vets/{vetId}', function ($vetId) {
        $user = request()->user();
        $farmer = $user->profile;
        
        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }
        
        $farm = $farmer->farm;
        
        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }
        
        $farm->vets()->detach($vetId);
        
        return response()->json(['message' => 'Vet removed successfully']);
    });
});
