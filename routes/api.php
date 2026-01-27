<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register/farmer', [RegistrationController::class, 'registerFarmer']);
Route::post('/register/vet', [RegistrationController::class, 'registerVet']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
