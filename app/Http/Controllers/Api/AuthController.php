<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Authenticate using name_user + password (SPA, cookie-based).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'name_user' => ['required', 'string'],
            'password'  => ['required', 'string'],
        ]);

        if (Auth::attempt($request->only('name_user', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'user' => Auth::user(),
            ]);
        }

        return response()->json([
            'message' => __('auth.failed'),
        ], 401);
    }

    /**
     * Logout the current user.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Return the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}