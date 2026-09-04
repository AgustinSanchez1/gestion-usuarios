<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    private const SIARHU_TOKEN_CACHE_KEY = 'siarhu_api_token';

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'name_user' => ['required', 'string'],
            'password'  => ['required', 'string'],
        ]);

        if (Auth::attempt($request->only('name_user', 'password')) && count(Auth::user()->getRoleNames()) > 0) {
            $user = Auth::user();

            $user->tokens()->delete();

            $token = $user->createToken('api-token')->plainTextToken;

            $this->fetchAndCacheSiarhuToken();

            return response()->json([
                'user'        => $user,
                'token'       => $token,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]);
        }

        return response()->json([
            'message' => __('auth.failed'),
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /* public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    } */

    private function fetchAndCacheSiarhuToken(): void
    {
        $credentials = [
            'email'         => config('services.siarhu.email'),
            'nombreusuario' => config('services.siarhu.username'),
            'password'      => config('services.siarhu.password'),
        ];

        $token = $this->authenticate($credentials);

        Cache::put(self::SIARHU_TOKEN_CACHE_KEY, $token);
    }

    private function authenticate(array $credentials): string
    {
        $url = config('services.siarhu.url') . '/user/login';

        $response = Http::post($url, $credentials);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Error al autenticar con SIARHU: ' . $response->status() . ' - ' . $response->body()
            );
        }

        $data = $response->json();

        if (!isset($data['data']['api_token'])) {
            throw new \RuntimeException('La respuesta de login de SIARHU no contiene api_token.');
        }

        return $data['data']['api_token'];
    }
}
