<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Create access token with short expiry (15 minutes)
        $accessToken = $user->createToken('api', ['*'], Carbon::now()->addMinutes(15));
        
        // Create refresh token with longer expiry (30 days)
        $refreshToken = RefreshToken::generateToken($user, $accessToken->accessToken->id);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 60, // 15 minutes in seconds
            ]
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create access token with short expiry (15 minutes)
        $accessToken = $user->createToken('api', ['*'], Carbon::now()->addMinutes(15));
        
        // Create refresh token with longer expiry (30 days)
        $refreshToken = RefreshToken::generateToken($user, $accessToken->accessToken->id);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 60, // 15 minutes in seconds
            ]
        ], 200);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = RefreshToken::findValidToken($request->refresh_token);

        if (!$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired refresh token'
            ], 401);
        }

        $user = $refreshToken->user;

        // Revoke the old access token if it exists
        if ($refreshToken->access_token_id) {
            $user->tokens()->where('id', $refreshToken->access_token_id)->delete();
        }

        // Create new access token
        $accessToken = $user->createToken('api', ['*'], Carbon::now()->addMinutes(15));
        
        // Update refresh token with new access token ID
        $refreshToken->update([
            'access_token_id' => $accessToken->accessToken->id,
            'expires_at' => Carbon::now()->addDays(30), // Extend refresh token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 60, // 15 minutes in seconds
            ]
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get the current access token ID
        $currentTokenId = $user->currentAccessToken()->id;
        
        // Revoke the current access token
        $user->currentAccessToken()->delete();
        
        // Revoke associated refresh token
        RefreshToken::where('access_token_id', $currentTokenId)
            ->update(['is_revoked' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke all tokens for the user
        $user->revokeAllTokens();
        
        return response()->json([
            'success' => true,
            'message' => 'All sessions logged out successfully'
        ], 200);
    }
}
