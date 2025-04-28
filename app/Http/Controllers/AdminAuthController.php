<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Symfony\Component\HttpFoundation\Cookie;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::guard('admin')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $admin = Auth::guard('admin')->user();
        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        $cookie = cookie(
            'admin_token', // Changed from auth_token for specificity
            $token,
            1440, // 1 day in minutes
            null,
            null,
            config('app.env') === 'production', // Secure in production only
            true, // httpOnly
            false,
            'Lax' // Changed from None for better security
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully', // Fixed typo here
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'name' => $admin->name ?? 'Admin' // Handle null name
            ]
        ])->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        $request->user('admin')->tokens()->delete();

        $cookie = Cookie::forget('admin_token');

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ])->withCookie($cookie);
    }
}
