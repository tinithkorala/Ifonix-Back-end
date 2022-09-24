<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{   

    public function index() {

        $posts = Post::where('is_approved', true)->get();

        if($posts) {

            return response()->json($posts, 200);

        }

    }
    
    public function store(Request $request) {

        $user_id = Auth::id();
        $is_admin = Auth::user()->is_admin;
        
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if($validator->fails()) {

            return response()->json([
                'status' => 400,
                'validation_errors' => $validator->messages(),
            ]);

        }else {

            $validated = $validator->validate();

            $post_obj = Post::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'is_approved' => $is_admin ? true : false,
                'user_id' => $user_id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Post Created'
            ]);

        }
        
    }

}
