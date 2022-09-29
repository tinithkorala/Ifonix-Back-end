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
    
    /**
    * @OA\Post(
    *   path="/api/register",
    *   operationId="Register",
    *   tags={"Authentication"},
    *   summary="User Register",
    *   description="User Register here",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"name","email", "password", "password_confirmation"},
    *               @OA\Property(property="name", type="text"),
    *               @OA\Property(property="email", type="text"),
    *               @OA\Property(property="password", type="password"),
    *               @OA\Property(property="password_confirmation", type="password")
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="User Registered Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=200,
    *          description="Register Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=422,
    *          description="Unprocessable Entity",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=500,
    *          description="Something went wrong",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=400, description="Bad request"),
    *      @OA\Response(response=404, description="Resource Not Found"),
    * )
    */
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

    /**
    * @OA\Post(
    * path="/api/login",
    * operationId="login",
    * tags={"Authentication"},
    * summary="User Login",
    * description="Login User Here",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"email", "password"},
    *               @OA\Property(property="email", type="email"),
    *               @OA\Property(property="password", type="password")
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="Login Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=200,
    *          description="Login Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=422,
    *          description="Unprocessable Entity",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=400, description="Bad request"),
    *      @OA\Response(response=404, description="Resource Not Found"),
    * )
    */
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
