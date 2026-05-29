<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\PostComment;
use App\Models\CommentLike;
use App\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostCommentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ============================================
    // 1. GET COMMENTS
    // ============================================

    public function getComments($postId)
    {
        try {
            $user = Auth::user();

            $post = Post::where('id', $postId)->where('is_active', true)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors'  => (object) ['post' => 'Post not found or deleted']
                ], 404);
            }

            $comments = PostComment::where('post_id', $postId)
                ->whereNull('parent_comment_id')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $formattedComments = $comments->map(fn($comment) => $this->formatComment($comment, $user));

            return response()->json([
                'success' => true,
                'message' => 'Comments retrieved successfully',
                'data'    => [
                    'comments'   => $formattedComments,
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'per_page'     => $comments->perPage(),
                        'total'        => $comments->total(),
                        'last_page'    => $comments->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get comments failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve comments',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 2. ADD COMMENT / REPLY
    // ============================================

    public function addComment(Request $request, $postId)
    {
        try {
            $user = Auth::user();

            $post = Post::where('id', $postId)->where('is_active', true)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors'  => (object) ['post' => 'Post not found or deleted']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'content'           => 'required|string|min:1|max:1000',
                'parent_comment_id' => 'nullable|exists:post_comments,id',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => (object) $errors
                ], 422);
            }

            DB::beginTransaction();

            try {
                $comment = PostComment::create([
                    'post_id'           => $postId,
                    'user_id'           => $user->id,
                    'parent_comment_id' => $request->parent_comment_id,
                    'content'           => $request->content,
                    'is_active'         => true,
                    'likes_count'       => 0,
                ]);

                $post->increment('comments_count');

                DB::commit();

                // ── Notifications ────────────────────────────────────────────────────

                if ($request->parent_comment_id) {
                    // REPLY — notify the parent comment owner (if not replying to yourself)
                    $parentComment = PostComment::find($request->parent_comment_id);

                    if ($parentComment && $parentComment->user_id != $user->id) {
                        $this->notificationService->send(
                            $parentComment->user_id,          // recipient
                            'comment_reply',                  // type
                            'New Reply on Your Comment',      // title
                            $user->getName() . ' replied to your comment: "' . $this->truncate($request->content) . '"',
                            'post_detail',                    // screen
                            [
                                'post_id'    => (string) $postId,
                                'comment_id' => (string) $comment->id,
                                'type'       => 'comment_reply',
                            ],
                            $user->id                         // from_user_id
                        );
                    }
                } else {
                    // TOP-LEVEL COMMENT — notify the post owner (if not commenting on own post)
                    if ($post->user_id != $user->id) {
                        $this->notificationService->send(
                            $post->user_id,                   // recipient
                            'post_comment',                   // type
                            'New Comment on Your Post',       // title
                            $user->getName() . ' commented: "' . $this->truncate($request->content) . '"',
                            'post_detail',                    // screen
                            [
                                'post_id'    => (string) $postId,
                                'comment_id' => (string) $comment->id,
                                'type'       => 'post_comment',
                            ],
                            $user->id                         // from_user_id
                        );
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $request->parent_comment_id ? 'Reply added successfully' : 'Comment added successfully',
                    'data'    => $this->formatComment($comment, $user)
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Add comment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 3. UPDATE COMMENT
    // ============================================

    public function updateComment(Request $request, $commentId)
    {
        try {
            $user    = Auth::user();
            $comment = PostComment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors'  => (object) ['comment' => 'Comment not found or deleted']
                ], 404);
            }

            if ($comment->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors'  => (object) ['comment' => 'You can only edit your own comments']
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:1|max:1000',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => (object) $errors
                ], 422);
            }

            $comment->update(['content' => $request->content]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data'    => $this->formatComment($comment, $user)
            ]);

        } catch (Exception $e) {
            Log::error('Update comment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 4. DELETE COMMENT
    // ============================================

    public function deleteComment($commentId)
    {
        try {
            $user    = Auth::user();
            $comment = PostComment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors'  => (object) ['comment' => 'Comment not found or deleted']
                ], 404);
            }

            if ($comment->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors'  => (object) ['comment' => 'You can only delete your own comments']
                ], 403);
            }

            DB::beginTransaction();

            try {
                $post = Post::find($comment->post_id);

                $comment->update(['is_active' => false]);

                if ($post) {
                    $post->comments_count = PostComment::where('post_id', $post->id)
                        ->where('is_active', true)
                        ->count();
                    $post->save();
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Comment deleted successfully',
                    'data'    => null
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Delete comment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 5. TOGGLE LIKE
    // ============================================

    public function toggleLike($commentId)
    {
        try {
            $user    = Auth::user();
            $comment = PostComment::find($commentId);

            if (!$comment || !$comment->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors'  => (object) ['comment' => 'Comment not found or deleted']
                ], 404);
            }

            DB::beginTransaction();

            try {
                $existingLike = CommentLike::where('comment_id', $commentId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingLike) {
                    // Unlike — no notification needed
                    $existingLike->delete();
                    $comment->decrement('likes_count');
                    $isLiked = false;
                    $message = 'Comment unliked successfully';
                } else {
                    // Like
                    CommentLike::create([
                        'comment_id' => $commentId,
                        'user_id'    => $user->id,
                    ]);
                    $comment->increment('likes_count');
                    $isLiked = true;
                    $message = 'Comment liked successfully';
                }

                DB::commit();

                $comment->refresh();

                // ── Notification on like (not on unlike, not on own comment) ─────────
                if ($isLiked && $comment->user_id != $user->id) {
                    $this->notificationService->send(
                        $comment->user_id,                    // recipient
                        'comment_like',                       // type
                        'Someone Liked Your Comment',         // title
                        $user->getName() . ' liked your comment: "' . $this->truncate($comment->content) . '"',
                        'post_detail',                        // screen
                        [
                            'post_id'    => (string) $comment->post_id,
                            'comment_id' => (string) $commentId,
                            'type'       => 'comment_like',
                        ],
                        $user->id                             // from_user_id
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => [
                        'comment_id'  => (int) $commentId,
                        'is_liked'    => $isLiked,
                        'likes_count' => $comment->likes_count,
                    ]
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Toggle like failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 6. GET COMMENT LIKES
    // ============================================

    public function getCommentLikes($commentId)
    {
        try {
            $user    = Auth::user();
            $comment = PostComment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors'  => (object) ['comment' => 'Comment not found or deleted']
                ], 404);
            }

            $likes   = CommentLike::where('comment_id', $commentId)
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            $userIds = $likes->pluck('user_id')->unique()->toArray();
            $users   = User::whereIn('id', $userIds)->get()->keyBy('id');

            $formattedLikes = $likes->map(fn($like) => $this->formatLikeAuthorData($like, $users))->filter();

            return response()->json([
                'success' => true,
                'message' => 'Likes retrieved successfully',
                'data'    => [
                    'comment_id'  => (int) $commentId,
                    'total_likes' => $comment->likes_count,
                    'likes'       => $formattedLikes,
                    'pagination'  => [
                        'current_page' => $likes->currentPage(),
                        'per_page'     => $likes->perPage(),
                        'total'        => $likes->total(),
                        'last_page'    => $likes->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get comment likes failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve likes',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 7. GET REPLIES
    // ============================================

    public function getReplies($commentId)
    {
        try {
            $user          = Auth::user();
            $parentComment = PostComment::find($commentId);

            if (!$parentComment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                    'errors'  => (object) ['comment' => 'Parent comment not found']
                ], 404);
            }

            $replies = PostComment::where('parent_comment_id', $commentId)
                ->where('is_active', true)
                ->orderBy('created_at', 'asc')
                ->paginate(20);

            $formattedReplies = $replies->map(fn($reply) => $this->formatComment($reply, $user));

            return response()->json([
                'success' => true,
                'message' => 'Replies retrieved successfully',
                'data'    => [
                    'comment_id' => (int) $commentId,
                    'replies'    => $formattedReplies,
                    'pagination' => [
                        'current_page' => $replies->currentPage(),
                        'per_page'     => $replies->perPage(),
                        'total'        => $replies->total(),
                        'last_page'    => $replies->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get replies failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve replies',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // PRIVATE HELPERS
    // ============================================

    /**
     * Format comment response - FIXED with direct table checks
     */
    private function formatComment($comment, $user = null)
    {
        $user       = $user ?? Auth::user();
        $authorData = $this->getEntityData($comment->user_id);

        $isLiked = $user
            ? CommentLike::where('comment_id', $comment->id)->where('user_id', $user->id)->exists()
            : false;

        $isOwner = $user ? ($comment->user_id == $user->id) : false;

        $repliesCount = PostComment::where('parent_comment_id', $comment->id)
            ->where('is_active', true)
            ->count();

        $replies = collect([]);

        if ($repliesCount > 0 && !$comment->parent_comment_id) {
            $replyComments = PostComment::where('parent_comment_id', $comment->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            $replies = $replyComments->map(fn($reply) => $this->formatComment($reply, $user));
        }

        return [
            'id'                    => (int) $comment->id,
            'post_id'               => (int) $comment->post_id,
            'content'               => $comment->content,
            'created_at'            => $comment->created_at?->toIso8601String(),
            'created_at_formatted'  => $comment->created_at?->format('d M Y, h:i A'),
            'created_at_diff'       => $comment->created_at ? $this->formatTimeDiff($comment->created_at) : null,
            'updated_at'            => $comment->updated_at?->toIso8601String(),
            'updated_at_formatted'  => $comment->updated_at?->format('d M Y, h:i A'),
            'is_edited'             => $comment->created_at != $comment->updated_at,
            'parent_comment_id'     => $comment->parent_comment_id ? (int) $comment->parent_comment_id : null,
            'stats'                 => [
                'likes'   => $comment->likes_count ?? 0,
                'replies' => $repliesCount,
            ],
            'is_liked'              => $isLiked,
            'is_owner'              => $isOwner,
            'author'                => $authorData,
            'replies'               => $replies,
            'has_more_replies'      => $repliesCount > 5,
        ];
    }

    /**
     * Format like author data
     */
    private function formatLikeAuthorData($like, $users)
    {
        if (!isset($users[$like->user_id])) {
            return null;
        }

        $user = $users[$like->user_id];

        $likedAt = [
            'liked_at'           => $like->created_at?->toIso8601String(),
            'liked_at_formatted' => $like->created_at?->format('d M Y, h:i A'),
            'liked_at_diff'      => $like->created_at ? $this->formatTimeDiff($like->created_at) : null,
        ];

        if ($user->usertype === 'company') {
            return array_merge([
                'user_id'  => (int) $user->id,
                'name'     => $user->company_name ?? $user->name ?? 'Unknown Company',
                'usertype' => 'company',
                'image'    => $user->company_logo
                                ? asset('company_logos/' . $user->company_logo)
                                : ($user->image ? asset('user_images/' . $user->image) : null),
            ], $likedAt);
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: ($user->name ?? 'Unknown User');

        return array_merge([
            'user_id'  => (int) $user->id,
            'name'     => $name,
            'usertype' => $user->usertype ?? 'user',
            'image'    => $user->image ? asset('user_images/' . $user->image) : null,
        ], $likedAt);
    }

    /**
     * Get entity data from users table
     */
    private function getEntityData($id)
    {
        if (!$id) return null;

        $user = User::find($id);

        if (!$user) {
            return ['id' => (int) $id, 'name' => 'Unknown User', 'usertype' => 'unknown', 'image' => null, 'email' => null];
        }

        if ($user->usertype === 'company') {
            return [
                'id'       => (int) $user->id,
                'name'     => $user->company_name ?? $user->name ?? 'Unknown Company',
                'usertype' => 'company',
                'image'    => $user->company_logo
                                ? asset('company_logos/' . $user->company_logo)
                                : ($user->image ? asset('user_images/' . $user->image) : null),
                'email'    => $user->email,
            ];
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: ($user->name ?? 'Unknown User');

        return [
            'id'       => (int) $user->id,
            'name'     => $name,
            'usertype' => $user->usertype ?? 'user',
            'image'    => $user->image ? asset('user_images/' . $user->image) : null,
            'email'    => $user->email,
        ];
    }

    /**
     * Truncate text for notification body preview
     */
    private function truncate($text, $limit = 60)
    {
        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit) . '...'
            : $text;
    }

    /**
     * Format time difference
     */
    private function formatTimeDiff($dateTime)
    {
        if (!$dateTime) return null;

        $now            = Carbon::now();
        $diffInSeconds  = $now->diffInSeconds($dateTime);
        $diffInMinutes  = $now->diffInMinutes($dateTime);
        $diffInHours    = $now->diffInHours($dateTime);
        $diffInDays     = $now->diffInDays($dateTime);

        if ($diffInSeconds < 60) {
            return $diffInSeconds <= 5 ? 'Just now' : $diffInSeconds . ' seconds ago';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' ' . ($diffInMinutes == 1 ? 'minute ago' : 'minutes ago');
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ' . ($diffInHours == 1 ? 'hour ago' : 'hours ago');
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' ' . ($diffInDays == 1 ? 'day ago' : 'days ago');
        } elseif ($diffInDays < 30) {
            $weeks = (int) floor($diffInDays / 7);
            return $weeks . ' ' . ($weeks == 1 ? 'week ago' : 'weeks ago');
        } elseif ($diffInDays < 365) {
            $months = (int) floor($diffInDays / 30);
            return $months . ' ' . ($months == 1 ? 'month ago' : 'months ago');
        } else {
            $years = (int) floor($diffInDays / 365);
            return $years . ' ' . ($years == 1 ? 'year ago' : 'years ago');
        }
    }
}