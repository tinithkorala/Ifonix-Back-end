<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    
    public function register(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:20|string',
            'email' => 'required|email|string|unique:users,email',
            'password' => 'required|string|confirmed|min:3'
        ]);

        if($validator->fails()) {

            return response()->json([
                'status' => 400,
                'validation_errors' => $validator->messages(),
            ]);

        }else {

            $validated = $validator->validated();

            $user_obj = User::create([
                'name'      => $validated['name'],  
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
            ]);

            $user_token_string = $user_obj->createToken($user_obj->email.'_Token')->plainTextToken;
    
            return response()->json([
                'status' => 201,
                'user_name' => $user_obj->name,
                'token' => $user_token_string,
                'message' => 'User Registered Successfully !',
            ]);

        }

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
