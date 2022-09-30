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
    
    /**
    *  @OA\Get(
    *      path="/api/posts",
    *      summary="Get All posts",
    *      operationId="index",
    *      tags={"Post Manage"},
    *      security={{"sanctum":{}}},
    *      @OA\Response(
    *          response=200,
    *          description="All Posts"
    *      ),
    *      @OA\Response(
    *          response=500,
    *          description="Server Error",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=401,
    *          description="Unauthenticated",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=404, description="Resource Not Found"),
    *  )
    */
    public function index() {
       
        try {

            $posts = Post::where('is_approved', true)->get();

            return response()->json([
                'message' => 'All Posts',
                'data_set' => $posts
            ], 200); 
        
        } catch (\Exception $e) {
            
            Log::error($e);
        
        }

    }

    /**
    *  @OA\Get(
    *      path="/api/posts-approve-reject",
    *      summary="Get All Post Data That Needed To Approve/Reject",
    *      operationId="postsForApproveReject",
    *      tags={"Post Manage"},
    *      security={{"sanctum":{}}},
    *      @OA\Response(
    *          response=200,
    *          description="Posts that need permission Approved/Rejected"
    *      ),
    *      @OA\Response(
    *          response=500,
    *          description="Server Error",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=401,
    *          description="Unauthenticated",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=404, description="Resource Not Found"),
    *  )
    */
    public function postsForApproveReject() {

        try {

            $posts = Post::where('is_approved', false)->where('rejected_at', NULL)->get();

            return response()->json([
                'message' => 'Posts that need permission Approved/Rejected',
                'data_set' => $posts
            ], 200); 
        
        } catch (\Exception $e) {
            
            Log::error($e);
        
        }

    }
    
    /**
    * @OA\Post(
    *   path="/api/posts",
    *   operationId="store",
    *   tags={"Post Manage"},
    *   security={{"sanctum":{}}},
    *   summary="Create New Post",
    *   description="Create New Post here",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"title", "description"},
    *               @OA\Property(property="title", type="text"),
    *               @OA\Property(property="description", type="text"),
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="Post Created Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=500,
    *          description="Server Error",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=400,
    *          description="Form Validation",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=401,
    *          description="Unauthenticated",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=404, description="Resource Not Found"),
    * )
    */
    public function store(Request $request) {

        $user_id = Auth::id();
        $is_admin = Auth::user()->is_admin;
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:10',
            'description' => 'required|min:10',
        ]);

        if($validator->fails()) {

            return response()->json([
                'validation_errors' => $validator->messages(),
            ], 400);

        }else {

            $validated = $validator->validate();

            try {

                $post_obj = Post::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'is_approved' => $is_admin ? true : false,
                    'user_id' => $user_id
                ]);

                return response()->json([
                    'message' => 'Post Created Successfully'
                ], 201);
          
            } catch (\Exception $e) {

                Log::error($e);

                // return response()->json(
                //     [
                //         'status' => 503,
                //         'message' => '503 Service Unavailable'
                //     ],
                // );
             
            }

        }
        
    }

    /**
    * @OA\Put(
    *     path="/api/posts/{id}",
    *     operationId="update",
    *     tags={"Post Manage"},
    *     security={{"sanctum":{}}},
    *     summary="Update Post",
    *     description="Update Post",
    *     @OA\Parameter(name="id", in="path", description="Id of Article", required=true,
    *         @OA\Schema(type="integer")
    *     ),
    *     @OA\RequestBody(
    *        required=true,
    *        @OA\JsonContent(
    *           required={"post_approve_reject_status"},
    *           @OA\Property(property="post_approve_reject_status", type="string", format="string", example="True/False")
    *        ),
    *     ),
    *      @OA\Response(
    *          response=201,
    *          description="Post Updated Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=500,
    *          description="Server Error",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=503,
    *          description="Service Unavailable",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=401,
    *          description="Unauthenticated",
    *          @OA\JsonContent()
    *       ),
    *  )
    */
    public function update(Request $request, $id) {

        $is_admin = Auth::user()->is_admin;

        if($is_admin) {

            $post_approve_reject_status = $request->input('post_approve_reject_status'); 
            $tbl_column = $post_approve_reject_status ? "approved_at" : "rejected_at";

            try {

                $post = Post::find($id);
                $post->is_approved = $post_approve_reject_status;
                $post->$tbl_column = Carbon::now()->toDateTimeString(); 
                $post->update();

                return response()->json([
                    'message' => $post_approve_reject_status ? "Post Approved" : "Post Rejected"
                ], 201);

            }catch (\Exception $e) {

                Log::error($e);
             
            }

        }else {

            return response()->json([
                'message' => 'Service Unavailable'
            ], 503);

        }

    }

    /**
    *  @OA\Get(
    *      path="/api/posts/search/?search={search-text}",
    *      summary="Search Post",
    *      operationId="search",
    *      tags={"Post Manage"},
    *      security={{"sanctum":{}}},
    *      @OA\Parameter(
    *           description="ID of post",
    *           in="path",
    *           name="search-text",
    *           required=true,
    *           example="1"
    *      ),
    *      @OA\Response(
    *          response=200,
    *          description="Post Founded"
    *      ),
    *      @OA\Response(
    *          response="default",
    *          description="An error has occurred."
    *      )
    *  )
    */
    public function search() {

        try {

            // $posts = Post::latest()->where('is_approved', true)->filter(request(['search']))->get();

            $searchString = request('search');
            $posts = Post::whereHas('user', function (Builder $query) use ($searchString) {
                $query->where('title', 'like', '%'.$searchString.'%');
                $query->orWhere('description', 'like', '%'.$searchString.'%');
                $query->orWhere('name', 'like', '%'.$searchString.'%');
            })->get();

            if($posts) {

                return response()->json(
                [
                    'status' => 200,
                    'message' => 'Posts Found',
                    'data_set' => $posts
                ]); 

            }

        }catch (\Exception $e) {

            Log::error($e);

            return response()->json(
                [
                    'status' => 503,
                    'message' => '503 Service Unavailable'
                ],
            );
         
        }

    }

    /**
    *  @OA\Get(
    *      path="/api/posts/{id}",
    *      summary="Find Post",
    *      operationId="show",
    *      tags={"Post Manage"},
    *      security={{"sanctum":{}}},
    *      @OA\Parameter(
    *      description="ID of post",
    *      in="path",
    *      name="id",
    *      required=true,
    *      example="1",
    *      @OA\Schema(
    *           type="integer",
    *           format="int64"
    *     ),
    *     ),
    *      @OA\Response(
    *          response=200,
    *          description="Post founded"
    *      ),
    *      @OA\Response(
    *          response=500,
    *          description="Server Error",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=404,
    *          description="Post Not Found",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=401,
    *          description="Unauthenticated",
    *          @OA\JsonContent()
    *       ),
    *  )
    */
    public function show($id) {
        
        try {

            $post = Post::find($id);
            // $post = Post::find($id)->user()->get();

            if($post) {
                return response()->json( [
                    'message' => 'Post Found',
                    'data_set' => $post
                ], 200); 
            }else {
                return response()->json( [
                    'message' => 'Post Not Found',
                ], 404); 
            }
        
        } catch (\Exception $e) {
            
            Log::error($e);
        
        }

    }

    /**
    * @OA\Delete(
    *    path="/api/posts/{id}",
    *    summary="Delete Posts",
    *    operationId="destroy",
    *    security={{"sanctum":{}}},
    *    tags={"Post Manage"},
    *    description="Delete Post",
    *    @OA\Parameter(name="id", in="path", description="Id of Article", required=true,
    *        @OA\Schema(type="integer")
    *    ),
    *    @OA\Response(
    *         response=200,
    *         description="Success",
    *         @OA\JsonContent(
    *         @OA\Property(property="status_code", type="integer", example="200"),
    *         @OA\Property(property="data",type="object")
    *          ),
    *       )
    *      )
    *  )
    */
    public function destroy($id) {

        try {

            $post_delete = Post::find($id)->delete();

            return response()->json([
                'message' => 'Post Deleted'
            ], 201); 
       
        } catch (\Exception $e) {
            
            Log::error($e);

        }

    }

}
