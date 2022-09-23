<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    
    public function register(Request $request) {

        $validated = $request->validate([
            'name' => 'required|min:3|max:20|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed|min:3'
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Data saved !',
        ], 201);

    }

}
