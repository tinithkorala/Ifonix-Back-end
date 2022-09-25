<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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

            try {
                $validated = $validator->validated();

                $user_obj = User::create([
                    'name'      => $validated['name'],  
                    'email'     => $validated['email'],
                    'password'  => Hash::make($validated['password']),
                ]);
    
                $user_token_string = $user_obj->createToken($user_obj->email.'_Token')->plainTextToken;
        
                return response()->json([
                    'status' => 201,
                    'user_id' => $user_obj->id,
                    'user_name' => $user_obj->name,
                    'auth_user_type' => $user_obj->is_admin,
                    'token' => $user_token_string,
                    'message' => 'User Registered Successfully !',
                ]);

            }catch(\Exception $e) {

                Log::error($e);

                return response()->json([
                    'status' => 500,
                    'message' => 'Something went wrong',
                ]);

            }

        }

    }

    public function login(Request $request) {

        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if($validator->fails()) {

            return response()->json([
                'status' => 400,
                'validation_errors' => $validator->messages(),
            ]);

        }else {

            try {
                
                $validated = $validator->validated();

                // check user
                $user_obj = User::where('email', $validated['email'])->first();

                if($user_obj && Hash::check($validated['password'], $user_obj->password)) {

                    $user_token_string = $user_obj->createToken($user_obj->email.'_Token')->plainTextToken;

                    return response()->json([
                        'status' => 200,
                        'user_id' => $user_obj->id,
                        'user_name' => $user_obj->name,
                        'auth_user_type' => $user_obj->is_admin,
                        'token' => $user_token_string,
                        'message' => 'Logged in Successfully !',
                    ]);
        
                }else {
        
                    return response()->json([
                        'status' => 401,
                        'message' => 'Bad credentials !!!',
                    ]);
        
                }

            } catch (\Exception $e) {

                Log::error($e);

                return response()->json([
                    'status' => 500,
                    'message' => 'Something went wrong',
                ]);

            }

        }

    }

    public function logout() {

        auth()->user()->tokens()->delete();
        
        return response()->json([
            'status' => 200,
            'message' => 'Logged Out Successfully'
        ]);

    }

}
