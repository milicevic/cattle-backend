<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete existing tokens (optional - for single device login)
        // $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load profile relationship
        $user->load('profile');

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'profile_type' => $user->profile_type ? class_basename($user->profile_type) : null,
                'profile' => $user->profile,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user and revoke token.
     */
    public function logout(): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(): JsonResponse
    {
        $user = request()->user();
        $user->load('profile');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'profile_type' => $user->profile_type ? class_basename($user->profile_type) : null,
                'profile' => $user->profile,
            ],
        ]);
    }
}
