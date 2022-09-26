<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{   

    public function index() {

        $posts = Post::where('is_approved', true)->get();
        if($posts) {
            return response()->json($posts, 200);
        }

    }

    public function postsForApproveReject() {

        $posts = Post::where('is_approved', false)->where('rejected_at', NULL)->get();
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
                'status' => 204,
                'validation_errors' => $validator->messages(),
            ]);

        }else {

            $validated = $validator->validate();

            try {

                $post_obj = Post::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'is_approved' => $is_admin ? true : false,
                    'user_id' => $user_id
                ]);

                return response()->json(
                    [
                        'status' => 201,
                        'message' => 'Post Created'
                    ],
                );
          
            } catch (\Exception $e) {

                Log::error($e);

                return response()->json(
                    [
                        'status' => 503,
                        'message' => '503 Service Unavailable'
                    ],
                );
             
            }

        }
        
    }

    public function update(Request $request, $id) {

        $is_admin = Auth::user()->is_admin;

        if($is_admin) {

            $post_approve_reject_status = $request->input('post_approve_reject_status'); 

            $tbl_column = $post_approve_reject_status ? "approved_at" : "rejected_at";

            $post = Post::find($id);
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

    public function search() {

        // $posts = Post::latest()->where('is_approved', true)->filter(request(['search']))->get();

        $searchString = request('search');

        $posts = Post::whereHas('user', function (Builder $query) use ($searchString) {
            $query->where('title', 'like', '%'.$searchString.'%');
            $query->orWhere('description', 'like', '%'.$searchString.'%');
            $query->orWhere('name', 'like', '%'.$searchString.'%');
        })->get();

        if($posts) {

            return response()->json($posts); 

        }else {

            return response()->json([
                'status' => 400,
                'message' => 'Bad method'
            ]); 

        }

    }

    public function show($id) {

        $post = Post::find($id);
        // $post = Post::find($id)->user()->get();

        if($post) {
            return response()->json($post);
        }

    }

    public function destroy($id) {

        try {

            $post_delete = Post::find($id)->delete();

            return response()->json([
                'status' => 201,
                'message' => 'Post Deleted'
            ]); 
       
        } catch (\Exception $e) {
            
            Log::error($e);

            return response()->json(
                [
                    'status' => 503,
                    'message' => '503 Service Unavailable'
                ],
            );

        }

    }



}
