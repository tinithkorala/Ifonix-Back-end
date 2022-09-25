<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

    public function postApproveRejected(Request $request) {

        $post_id = $request->input('post_id'); 

        // return response()->json([
        //     'status' => 200,
        //     'post_id' => $post_id
        // ]);

        $user_id = Auth::id();
        $is_admin = Auth::user()->is_admin;

        if($is_admin) {

            $post_id = $request->input('post_id'); 
            $post_approve_reject_status = $request->input('post_approve_reject_status'); 

            $tbl_column = $post_approve_reject_status ? "approved_at" : "rejected_at";

            $post = Post::find($post_id);
            $post->is_approved = $post_approve_reject_status;
            $post->$tbl_column = Carbon::now()->toDateTimeString(); 
            $post->update();

            return response()->json([
                'status' => 200,
                'message' => 'Post Updated'
            ]);

        }else {

            return response()->json([
                'status' => 400,
                'message' => 'Bad method'
            ]); 

        }

    }



}
