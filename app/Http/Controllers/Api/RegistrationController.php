<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFarmerRequest;
use App\Http\Requests\RegisterVetRequest;
use App\Models\Farmer;
use App\Models\User;
use App\Models\Vet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    /**
     * Register a new farmer.
     */
    public function registerFarmer(RegisterFarmerRequest $request): JsonResponse
    {
        // Create farmer profile
        $farmer = Farmer::create([
            'subscription_plan' => $request->subscription_plan,
            'address' => $request->address,
        ]);

        // Create user with farmer profile
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_id' => $farmer->id,
            'profile_type' => Farmer::class,
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Farmer registered successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'profile_type' => 'farmer',
                'profile' => $farmer,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Register a new vet.
     */
    public function registerVet(RegisterVetRequest $request): JsonResponse
    {
        // Create vet profile
        $vet = Vet::create([
            'license_number' => $request->license_number,
            'specialization' => $request->specialization,
            'clinic_name' => $request->clinic_name,
        ]);

        // Create user with vet profile
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_id' => $vet->id,
            'profile_type' => Vet::class,
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Vet registered successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'profile_type' => 'vet',
                'profile' => $vet,
            ],
            'token' => $token,
        ], 201);
    }
}
