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
            'email' => 'required|email|string|unique:users,email',
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

    public function login(Request $request) {

        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // check user
        $user_obj = User::where('email', $validated['email'])->first();

        if($user_obj && Hash::check($validated['password'], $user_obj->password)) {

            return response()->json([
                'message' => 'User authenticated !',
            ], 201);

        }else {

            return response()->json([
                'message' => 'Bad credentials !!!',
            ], 201);

        }

    }

}
