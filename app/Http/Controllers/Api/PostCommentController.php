<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\PostComment;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostCommentController extends Controller
{
    /**
     * Get comments for a post
     */
    public function getComments($postId)
    {
        try {
            $user = Auth::user();
            
            // Verify post exists and is active
            $post = Post::where('id', $postId)
                        ->where('is_active', true)
                        ->first();
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }

            // Get parent comments with pagination
            $comments = PostComment::with([
                'user',
                'replies.user',
                'replies.replies.user' // For nested replies
            ])
            ->where('post_id', $postId)
            ->whereNull('parent_comment_id')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

            // Format comments
            $formattedComments = $comments->map(function ($comment) use ($user) {
                return $this->formatComment($comment, $user);
            });

            return response()->json([
                'success' => true,
                'message' => 'Comments retrieved successfully',
                'data' => [
                    'comments' => $formattedComments,
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'per_page' => $comments->perPage(),
                        'total' => $comments->total(),
                        'last_page' => $comments->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve comments',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Add a new comment
     */
    public function addComment(Request $request, $postId)
    {
        try {
            $user = Auth::user();
            
            // Verify post exists and is active
            $post = Post::where('id', $postId)
                        ->where('is_active', true)
                        ->first();
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:1|max:1000',
                'parent_comment_id' => 'nullable|exists:post_comments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create comment
            $comment = PostComment::create([
                'post_id' => $postId,
                'user_id' => $user->id,
                'parent_comment_id' => $request->parent_comment_id,
                'content' => $request->content,
                'is_active' => true,
            ]);

            // Load relationships
            $comment->load(['user', 'replies.user']);

            // Update post comments count
            $post->increment('comments_count');

            return response()->json([
                'success' => true,
                'message' => $request->parent_comment_id ? 'Reply added successfully' : 'Comment added successfully',
                'data' => $this->formatComment($comment, $user)
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, $commentId)
    {
        try {
            $user = Auth::user();
            
            // Find comment
            $comment = PostComment::with(['user', 'replies.user'])
                                ->find($commentId);
            
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors' => (object)['comment' => 'Comment not found or deleted']
                ], 404);
            }

            // Check ownership
            if ($comment->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['comment' => 'You can only edit your own comments']
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:1|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update comment
            $comment->update([
                'content' => $request->content,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $this->formatComment($comment, $user)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment($commentId)
    {
        try {
            $user = Auth::user();
            
            // Find comment
            $comment = PostComment::find($commentId);
            
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors' => (object)['comment' => 'Comment not found or deleted']
                ], 404);
            }

            // Check ownership (or admin privileges)
            if ($comment->user_id != $user->id && $user->role != 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['comment' => 'You can only delete your own comments']
                ], 403);
            }

            // Soft delete (set inactive)
            $comment->update(['is_active' => false]);

            // Update post comments count (subtract this comment and its replies)
            $post = Post::find($comment->post_id);
            if ($post) {
                $totalComments = PostComment::where('post_id', $post->id)
                    ->where('is_active', true)
                    ->count();
                $post->comments_count = $totalComments;
                $post->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'data' => null
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Like/Unlike a comment
     */
    public function toggleLike($commentId)
    {
        try {
            $user = Auth::user();
            
            // Find comment
            $comment = PostComment::find($commentId);
            
            if (!$comment || !$comment->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors' => (object)['comment' => 'Comment not found or deleted']
                ], 404);
            }

            // Check if already liked
            $existingLike = CommentLike::where('comment_id', $commentId)
                                        ->where('user_id', $user->id)
                                        ->first();

            if ($existingLike) {
                // Unlike
                $existingLike->delete();
                $comment->decrement('likes_count');
                $isLiked = false;
                $message = 'Comment unliked successfully';
            } else {
                // Like
                CommentLike::create([
                    'comment_id' => $commentId,
                    'user_id' => $user->id,
                ]);
                $comment->increment('likes_count');
                $isLiked = true;
                $message = 'Comment liked successfully';
            }

            // Refresh comment data
            $comment->refresh();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'comment_id' => $commentId,
                    'is_liked' => $isLiked,
                    'likes_count' => $comment->likes_count
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get comment likes
     */
    public function getCommentLikes($commentId)
    {
        try {
            $user = Auth::user();
            
            // Find comment
            $comment = PostComment::find($commentId);
            
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors' => (object)['comment' => 'Comment not found or deleted']
                ], 404);
            }

            // Get likes with user data
            $likes = CommentLike::with('user')
                                ->where('comment_id', $commentId)
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);

            $formattedLikes = $likes->map(function ($like) {
                return [
                    'user_id' => $like->user_id,
                    'name' => $like->user->name,
                    'usertype' => $like->user->usertype,
                    'image' => $like->user->image ? asset('user_images/' . $like->user->image) : null,
                    'liked_at' => $like->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Likes retrieved successfully',
                'data' => [
                    'likes' => $formattedLikes,
                    'pagination' => [
                        'current_page' => $likes->currentPage(),
                        'per_page' => $likes->perPage(),
                        'total' => $likes->total(),
                        'last_page' => $likes->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve likes',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Format comment response
     */
    private function formatComment($comment, $user = null)
    {
        $user = $user ?? Auth::user();
        
        $formatted = [
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'is_edited' => $comment->created_at != $comment->updated_at,
            'parent_comment_id' => $comment->parent_comment_id,
            'stats' => [
                'likes' => $comment->likes_count ?? 0,
                'replies' => $comment->replies_count ?? count($comment->replies),
            ],
            'is_liked' => $user ? $this->checkIfLiked($comment->id, $user->id) : false,
            'is_owner' => $user ? ($comment->user_id == $user->id) : false,
            'author' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'usertype' => $comment->user->usertype,
                'image' => $comment->user->image ? asset('user_images/' . $comment->user->image) : null,
            ] : null,
            'replies' => $comment->replies->map(function ($reply) use ($user) {
                return $this->formatComment($reply, $user);
            }),
        ];

        return $formatted;
    }

    /**
     * Check if user liked the comment
     */
    private function checkIfLiked($commentId, $userId)
    {
        return CommentLike::where('comment_id', $commentId)
                          ->where('user_id', $userId)
                          ->exists();
    }
}