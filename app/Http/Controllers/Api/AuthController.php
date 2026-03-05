<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //

    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        $user = User::create([
             'full_name' => $request->full_name,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'phone' => $request->phone,
             'role' => $request->role ?? 'customer',
             'status' => $request->status ?? true,
         ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => auth('api')->user(),
            'token' => $token,
          
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    // add all user allow only for admin
    public function index(){
        $users = User::all();
        return response()->json([
            'message' => 'All users retrieved successfully',
            'data' => $users
        ]);
    }
}

