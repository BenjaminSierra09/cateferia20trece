<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $login = str($validated['login'])->trim()->lower()->toString();

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query
                    ->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 422);
        }

        $token = $user->createToken($validated['device_name'] ?? 'api-client');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('branch')->loadCount(['workSessions', 'sales'])),
        ]);
    }

    public function show(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user->load('branch')->loadCount(['workSessions', 'sales']));
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión de API cerrada correctamente.',
        ]);
    }
}
