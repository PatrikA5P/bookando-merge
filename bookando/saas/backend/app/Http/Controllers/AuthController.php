<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use TransformsResponse;

    /**
     * POST /api/v1/auth/login
     *
     * Authenticate user and return a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'data' => [
                'accessToken' => $token,
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoke the current access token.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * GET /api/v1/auth/me
     *
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Format user data for the API response.
     *
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        $user->loadMissing('tenant');

        return [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'tenantId' => $user->tenant_id,
            'organizationName' => $user->tenant?->name,
        ];
    }
}
