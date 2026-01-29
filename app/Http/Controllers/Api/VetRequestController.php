<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Vet;
use App\Models\VetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VetRequestController extends Controller
{
    /**
     * Send a request to join a farm (vet only).
     */
    public function sendRequest(Request $request): JsonResponse
    {
        $user = request()->user();
        $vet = $user->profile;

        if (!$vet || !($vet instanceof Vet)) {
            return response()->json(['message' => 'Vet profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'farm_id' => 'required|exists:farms,id',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $farm = Farm::findOrFail($request->farm_id);

        // Check if vet is already assigned to this farm
        if ($vet->farms()->where('farms.id', $farm->id)->exists()) {
            return response()->json(['message' => 'You are already assigned to this farm'], 400);
        }

        // Check if there's already a pending request
        $existingRequest = VetRequest::where('vet_id', $vet->id)
            ->where('farm_id', $farm->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'You already have a pending request for this farm'], 400);
        }

        // Create the request
        $vetRequest = VetRequest::create([
            'vet_id' => $vet->id,
            'farm_id' => $farm->id,
            'status' => 'pending',
            'message' => $request->message,
            'requested_at' => now(),
        ]);

        $vetRequest->load(['vet', 'farm']);

        return response()->json([
            'message' => 'Request sent successfully',
            'request' => $vetRequest,
        ], 201);
    }

    /**
     * Get all requests for a vet (vet only).
     */
    public function myRequests(): JsonResponse
    {
        $user = request()->user();
        $vet = $user->profile;

        if (!$vet || !($vet instanceof Vet)) {
            return response()->json(['message' => 'Vet profile not found'], 404);
        }

        $requests = VetRequest::where('vet_id', $vet->id)
            ->with(['farm.farmer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    /**
     * Get all pending requests for a farmer's farm (farmer only).
     */
    public function pendingRequests(): JsonResponse
    {
        $user = request()->user();
        $farmer = $user->profile;

        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }

        $farm = $farmer->farm;

        if (!$farm) {
            return response()->json(['requests' => []]);
        }

        $requests = VetRequest::where('farm_id', $farm->id)
            ->where('status', 'pending')
            ->with(['vet'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    /**
     * Approve a vet request (farmer only).
     */
    public function approveRequest(Request $request, string $id): JsonResponse
    {
        $user = request()->user();
        $farmer = $user->profile;

        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }

        $farm = $farmer->farm;

        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }

        $vetRequest = VetRequest::where('id', $id)
            ->where('farm_id', $farm->id)
            ->where('status', 'pending')
            ->first();

        if (!$vetRequest) {
            return response()->json(['message' => 'Request not found or already processed'], 404);
        }

        // Update request status
        $vetRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        // Assign vet to farm
        $vet = Vet::findOrFail($vetRequest->vet_id);
        if (!$farm->vets()->where('vets.id', $vet->id)->exists()) {
            $farm->vets()->attach($vet->id, ['assigned_at' => now()]);
        }

        $vetRequest->load(['vet', 'farm']);

        return response()->json([
            'message' => 'Request approved successfully',
            'request' => $vetRequest,
        ]);
    }

    /**
     * Reject a vet request (farmer only).
     */
    public function rejectRequest(Request $request, string $id): JsonResponse
    {
        $user = request()->user();
        $farmer = $user->profile;

        if (!$farmer || !($farmer instanceof \App\Models\Farmer)) {
            return response()->json(['message' => 'Farmer profile not found'], 404);
        }

        $farm = $farmer->farm;

        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }

        $vetRequest = VetRequest::where('id', $id)
            ->where('farm_id', $farm->id)
            ->where('status', 'pending')
            ->first();

        if (!$vetRequest) {
            return response()->json(['message' => 'Request not found or already processed'], 404);
        }

        // Update request status
        $vetRequest->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        $vetRequest->load(['vet', 'farm']);

        return response()->json([
            'message' => 'Request rejected',
            'request' => $vetRequest,
        ]);
    }

    /**
     * Cancel a pending vet request (vet only).
     */
    public function cancelRequest(Request $request, string $id): JsonResponse
    {
        $user = request()->user();
        $vet = $user->profile;

        if (!$vet || !($vet instanceof Vet)) {
            return response()->json(['message' => 'Vet profile not found'], 404);
        }

        $vetRequest = VetRequest::where('id', $id)
            ->where('vet_id', $vet->id)
            ->where('status', 'pending')
            ->first();

        if (!$vetRequest) {
            return response()->json(['message' => 'Request not found or cannot be cancelled'], 404);
        }

        $vetRequest->delete();

        return response()->json([
            'message' => 'Request cancelled successfully',
        ]);
    }

    /**
     * Get assigned farms for a vet (vet only).
     */
    public function assignedFarms(): JsonResponse
    {
        $user = request()->user();
        $vet = $user->profile;

        if (!$vet || !($vet instanceof Vet)) {
            return response()->json(['message' => 'Vet profile not found'], 404);
        }

        $farms = $vet->farms()->with(['farmer'])->get()->map(function ($farm) {
            return [
                'id' => $farm->id,
                'name' => $farm->name,
                'location' => $farm->location,
                'state' => $farm->state,
                'is_active' => $farm->is_active,
                'farmer' => $farm->farmer ? [
                    'id' => $farm->farmer->id,
                ] : null,
                'assigned_at' => $farm->pivot->assigned_at ?? null,
            ];
        });

        return response()->json([
            'farms' => $farms,
        ]);
    }

    /**
     * Get all farms (for vets to browse and request).
     */
    public function listFarms(): JsonResponse
    {
        $user = request()->user();
        $vet = $user->profile;

        if (!$vet || !($vet instanceof Vet)) {
            return response()->json(['message' => 'Vet profile not found'], 404);
        }

        $farms = Farm::with(['farmer'])
            ->whereHas('farmer') // Only show farms that have a farmer
            ->get()
            ->map(function ($farm) use ($vet) {
                $isAssigned = $vet->farms()->where('farms.id', $farm->id)->exists();
                $hasPendingRequest = VetRequest::where('vet_id', $vet->id)
                    ->where('farm_id', $farm->id)
                    ->where('status', 'pending')
                    ->exists();

                return [
                    'id' => $farm->id,
                    'name' => $farm->name,
                    'location' => $farm->location,
                    'state' => $farm->state,
                    'farmer' => $farm->farmer ? [
                        'id' => $farm->farmer->id,
                    ] : null,
                    'is_assigned' => $isAssigned,
                    'has_pending_request' => $hasPendingRequest,
                ];
            })
            ->filter(); // Remove any null entries

        return response()->json([
            'farms' => $farms->values(), // Re-index array
        ]);
    }
}
