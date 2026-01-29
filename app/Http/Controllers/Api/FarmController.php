<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FarmController extends Controller
{
    /**
     * Get the authenticated farmer's farm.
     * Only farmers can view their own farm.
     */
    public function show(): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        if (!($profile instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Only farmers can view farms'], 403);
        }

        $farmer = $profile;

        $farm = $farmer->farm;

        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }

        return response()->json([
            'farm' => $farm,
        ]);
    }

    /**
     * Create or update the authenticated farmer's farm.
     * Only farmers can create/update farms.
     */
    public function store(Request $request): JsonResponse
    {
        $user = request()->user();
        $profile = $user->profile;

        if (!($profile instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Only farmers can create or update farms'], 403);
        }

        $farmer = $profile;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'state' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $farm = $farmer->farm;

        if ($farm) {
            $farm->update($request->only(['name', 'location', 'state']));
        } else {
            $farm = Farm::create([
                'farmer_id' => $farmer->id,
                'name' => $request->name,
                'location' => $request->location,
                'state' => $request->state,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Farm saved successfully',
            'farm' => $farm,
        ]);
    }

    /**
     * Update the authenticated farmer's farm.
     */
    public function update(Request $request): JsonResponse
    {
        return $this->store($request);
    }
}
