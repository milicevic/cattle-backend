<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Vet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
