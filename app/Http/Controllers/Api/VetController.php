<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Vet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VetController extends Controller
{
    /**
     * Get all available veterinarians.
     */
    public function index(): JsonResponse
    {
        $vets = Vet::all()->map(function ($vet) {
            $user = \App\Models\User::where('profile_id', $vet->id)
                ->where('profile_type', Vet::class)
                ->first();
            
            return [
                'id' => $vet->id,
                'license_number' => $vet->license_number,
                'specialization' => $vet->specialization,
                'clinic_name' => $vet->clinic_name,
                'user' => $user ? ['email' => $user->email] : null,
            ];
        });

        return response()->json([
            'vets' => $vets,
        ]);
    }

    /**
     * Update the authenticated vet's own profile.
     * Only vets can update their own profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        if (!($profile instanceof Vet)) {
            return response()->json(['message' => 'Only veterinarians can update their profile'], 403);
        }

        $vet = $profile;

        $validator = Validator::make($request->all(), [
            'license_number' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'clinic_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vet->update($request->only(['license_number', 'specialization', 'clinic_name']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'vet' => $vet->fresh(),
        ]);
    }
}
