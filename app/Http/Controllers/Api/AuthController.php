<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends BaseApiController
{
    public function login(Request $request)
    {
        $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = 'api-login:' . strtolower($request->email) . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error(
                "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                429
            );
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            return $this->error('The provided credentials are incorrect.', 401);
        }

        if (!$user->is_active) {
            return $this->error('Your account has been deactivated.', 403);
        }

        RateLimiter::clear($throttleKey);

        $deviceName = $request->device_name ?? 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->currentRoleName(),
            ],
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => $user->currentRoleName(),
            'is_active'     => $user->is_active,
            'permissions'   => $user->getAllPermissions()->pluck('name'),
            'last_login_at' => $user->last_login_at?->toISOString(),
        ]);
    }
}
