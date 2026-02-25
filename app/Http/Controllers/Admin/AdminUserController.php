<?php
// app/Http/Controllers/Admin/AdminUserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Post;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\UserConnection;
use App\Job;
use App\JobApply;
use App\FavouriteCompany;
use App\JobTitle;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Constructor with admin auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // ============================================
    // USER MANAGEMENT
    // ============================================

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['country', 'state', 'city', 'industry']);

        // Apply filters
        if ($request->filled('name')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->name . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $request->name . '%')
                  ->orWhere('name', 'LIKE', '%' . $request->name . '%');
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'LIKE', '%' . $request->email . '%');
        }

        if ($request->filled('usertype') && $request->usertype != '-1') {
            $query->where('usertype', $request->usertype);
        }

        if ($request->filled('is_active') && $request->is_active != '-1') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('is_featured') && $request->is_featured != '-1') {
            $query->where('is_featured', $request->is_featured);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get counts for each user
        $users = $query->orderBy('id', 'DESC')->paginate(20);
        
        // Add counts to each user
        foreach ($users as $user) {
            $user->posts_count = Post::where('user_id', $user->id)->count();
            $user->job_posts_count = Post::where('user_id', $user->id)->where('is_job_post', true)->count();
            $user->regular_posts_count = Post::where('user_id', $user->id)->where('is_job_post', false)->count();
            $user->followers_count = UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            $user->following_count = UserConnection::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            $user->total_engagement = $this->getUserEngagementTotal($user->id);
        }

        return view('admin.user_new.index', compact('users'));
    }

    /**
     * Show complete user details with all posts and analytics
     */
    public function show($id)
    {
        $user = User::with([
            'country',
            'state',
            'city',
            'industry',
            'gender',
            'maritalStatus',
            'careerLevel',
            'jobExperience',
            'functionalArea'
        ])->findOrFail($id);

        // ============================================
        // POSTS DATA - All posts by this user
        // ============================================
        
        // Get all posts with pagination
        $posts = Post::where('user_id', $user->id)
            ->with([
                'postType', 
                'category', 
                'subcategory',
                'taggedUsers',
                'taggedCompanies'
            ])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'posts_page');

        // Get job posts separately
        $jobPosts = Post::where('user_id', $user->id)
            ->where('is_job_post', true)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'job_posts_page');

        // Get regular posts separately
        $regularPosts = Post::where('user_id', $user->id)
            ->where('is_job_post', false)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'regular_posts_page');

        // ============================================
        // ENGAGEMENT DATA
        // ============================================
        
        // Get all likes on user's posts
        $likes = PostLike::whereIn('post_id', function($query) use ($user) {
                $query->select('id')->from('posts')->where('user_id', $user->id);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'likes_page');

        // Get all comments on user's posts
        $comments = PostComment::whereIn('post_id', function($query) use ($user) {
                $query->select('id')->from('posts')->where('user_id', $user->id);
            })
            ->with(['user', 'post'])
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'comments_page');

        // Get all shares of user's posts
        $shares = PostShare::whereIn('post_id', function($query) use ($user) {
                $query->select('id')->from('posts')->where('user_id', $user->id);
            })
            ->with(['user', 'post'])
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'shares_page');

        // Get all views of user's posts
        $views = PostView::whereIn('post_id', function($query) use ($user) {
                $query->select('id')->from('posts')->where('user_id', $user->id);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'views_page');

        // ============================================
        // CONNECTIONS DATA
        // ============================================
        
        // Get followers
        $followers = UserConnection::where('following_id', $user->id)
            ->where('status', 'accepted')
            ->with('follower')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'followers_page');

        // Get following
        $following = UserConnection::where('follower_id', $user->id)
            ->where('status', 'accepted')
            ->with('following')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'following_page');

        // Get pending follow requests
        $pendingRequests = UserConnection::where('following_id', $user->id)
            ->where('status', 'pending')
            ->with('follower')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'pending_page');

        // ============================================
        // JOB APPLICATIONS
        // ============================================
        
        $jobApplications = JobApply::where('user_id', $user->id)
            ->with(['job.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'applications_page');

        // ============================================
        // AREA OF INTERESTS & SKILLS
        // ============================================
        
        // Parse area of interests
        $areaOfInterestIds = [];
        if (!empty($user->area_of_interest_id)) {
            if (is_array($user->area_of_interest_id)) {
                $areaOfInterestIds = $user->area_of_interest_id;
            } elseif (is_string($user->area_of_interest_id)) {
                $areaOfInterestIds = json_decode($user->area_of_interest_id, true) ?? [];
            }
        }
        
        $areaOfInterests = JobTitle::whereIn('id', $areaOfInterestIds)
            ->where('is_active', 1)
            ->get(['id', 'job_title']);

        // Parse skills/specialization
        $skillIds = [];
        if (!empty($user->specialization)) {
            $skillIds = array_map('intval', explode(',', $user->specialization));
        }
        
        $skills = JobSkill::whereIn('id', $skillIds)
            ->where('is_active', 1)
            ->get(['id', 'job_skill']);

        // ============================================
        // ANALYTICS DATA
        // ============================================
        
        $analytics = $this->getDetailedAnalytics($user->id);

        // ============================================
        // RECENT ACTIVITY
        // ============================================
        
        $recentActivity = $this->getRecentActivity($user->id);

        return view('admin.user_new.show', compact(
            'user',
            'posts',
            'jobPosts',
            'regularPosts',
            'likes',
            'comments',
            'shares',
            'views',
            'followers',
            'following',
            'pendingRequests',
            'jobApplications',
            'areaOfInterests',
            'skills',
            'analytics',
            'recentActivity'
        ));
    }

    // ============================================
    // USER POST METHODS
    // ============================================

    /**
     * Get all posts of a user (API endpoint for AJAX)
     */
    public function getUserPosts($userId, Request $request)
    {
        $posts = Post::where('user_id', $userId)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    // ============================================
    // USER CONNECTION METHODS
    // ============================================

    /**
     * Get followers of a user
     */
    public function getFollowers($userId)
    {
        $followers = UserConnection::where('following_id', $userId)
            ->where('status', 'accepted')
            ->with('follower')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $followers
        ]);
    }

    /**
     * Get users that a user is following
     */
    public function getFollowing($userId)
    {
        $following = UserConnection::where('follower_id', $userId)
            ->where('status', 'accepted')
            ->with('following')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $following
        ]);
    }

    // ============================================
    // USER STATUS MANAGEMENT
    // ============================================

    /**
     * Update user status (active/inactive)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'new_status' => $user->is_active
        ]);
    }

    /**
     * Update featured status
     */
    public function updateFeatured(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'is_featured' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->is_featured = $request->is_featured;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User featured status updated successfully',
            'new_status' => $user->is_featured
        ]);
    }

    // ============================================
    // EXPORT & NOTIFICATIONS
    // ============================================

    /**
     * Export user data as JSON
     */
    public function exportData($id)
    {
        $user = User::with([
            'country',
            'state',
            'city',
            'industry',
            'gender',
            'careerLevel',
            'jobExperience'
        ])->findOrFail($id);

        $posts = Post::where('user_id', $user->id)
            ->with(['postType', 'category', 'subcategory', 'likes', 'comments', 'shares', 'views'])
            ->get();

        $connections = [
            'followers' => UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->with('follower')
                ->get(),
            'following' => UserConnection::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->with('following')
                ->get()
        ];

        $data = [
            'user' => $user,
            'posts' => $posts,
            'connections' => $connections,
            'statistics' => $this->getDetailedAnalytics($user->id),
            'exported_at' => now()->toDateTimeString(),
        ];

        $filename = 'user_' . $user->id . '_' . Str::slug($user->getName()) . '_' . date('Y-m-d') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Send notification to user
     */
    public function sendNotification(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Send email notification
        try {
            \Mail::send('emails.admin_user_notification', [
                'user' => $user,
                'subject' => $request->subject,
                'messageBody' => $request->message,
            ], function ($message) use ($user, $request) {
                $message->to($user->email, $user->getName())
                    ->subject($request->subject);
            });

            \Log::info('Admin notification sent to user: ' . $user->email);

            return redirect()->back()->with('success', 'Notification sent successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to send notification to user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $user = User::findOrFail($id);
            
            // Delete all posts by this user
            $posts = Post::where('user_id', $user->id)->get();
            foreach ($posts as $post) {
                // Delete files
                if ($post->images) {
                    $images = json_decode($post->images, true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            $path = public_path('post_images/' . $image);
                            if (file_exists($path)) {
                                @unlink($path);
                            }
                        }
                    }
                }
                
                // Delete related records
                PostLike::where('post_id', $post->id)->delete();
                PostComment::where('post_id', $post->id)->delete();
                PostShare::where('post_id', $post->id)->delete();
                PostView::where('post_id', $post->id)->delete();
                
                $post->delete();
            }
            
            // Delete user image
            if ($user->image) {
                $imagePath = public_path('user_images/' . $user->image);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            if ($user->cover_image) {
                $coverPath = public_path('user_images/' . $user->cover_image);
                if (file_exists($coverPath)) {
                    @unlink($coverPath);
                }
            }
            
            // Delete connection records
            UserConnection::where('follower_id', $user->id)->delete();
            UserConnection::where('following_id', $user->id)->delete();
            
            // Delete user
            $user->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // ANALYTICS METHODS
    // ============================================

    private function getDetailedAnalytics($userId)
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        $lastWeek = Carbon::now()->subWeek();
        $lastYear = Carbon::now()->subYear();

        // Posts statistics
        $totalPosts = Post::where('user_id', $userId)->count();
        $totalJobPosts = Post::where('user_id', $userId)->where('is_job_post', true)->count();
        $totalRegularPosts = Post::where('user_id', $userId)->where('is_job_post', false)->count();
        
        $postsToday = Post::where('user_id', $userId)->whereDate('created_at', today())->count();
        $postsThisWeek = Post::where('user_id', $userId)->where('created_at', '>=', $lastWeek)->count();
        $postsThisMonth = Post::where('user_id', $userId)->where('created_at', '>=', $lastMonth)->count();
        $postsThisYear = Post::where('user_id', $userId)->where('created_at', '>=', $lastYear)->count();

        // Engagement metrics
        $totalLikes = PostLike::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })->count();

        $totalComments = PostComment::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })->count();

        $totalShares = PostShare::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })->count();

        $totalViews = PostView::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })->count();

        // Connection statistics
        $totalFollowers = UserConnection::where('following_id', $userId)
            ->where('status', 'accepted')
            ->count();
            
        $totalFollowing = UserConnection::where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();
            
        $pendingRequests = UserConnection::where('following_id', $userId)
            ->where('status', 'pending')
            ->count();

        // Job applications
        $totalApplications = JobApply::where('user_id', $userId)->count();
        $recentApplications = JobApply::where('user_id', $userId)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        // Top performing posts
        $topLikedPosts = DB::select("
            SELECT 
                p.id, 
                p.title,
                COUNT(pl.id) as likes_count
            FROM posts p
            LEFT JOIN post_likes pl ON p.id = pl.post_id
            WHERE p.user_id = ?
            GROUP BY p.id, p.title
            ORDER BY likes_count DESC
            LIMIT 5
        ", [$userId]);

        $topCommentedPosts = DB::select("
            SELECT 
                p.id, 
                p.title,
                COUNT(pc.id) as comments_count
            FROM posts p
            LEFT JOIN post_comments pc ON p.id = pc.post_id
            WHERE p.user_id = ?
            GROUP BY p.id, p.title
            ORDER BY comments_count DESC
            LIMIT 5
        ", [$userId]);

        $topViewedPosts = DB::select("
            SELECT 
                id, 
                title,
                views_count
            FROM posts
            WHERE user_id = ?
            ORDER BY views_count DESC NULLS LAST
            LIMIT 5
        ", [$userId]);

        return [
            'posts' => [
                'total' => $totalPosts,
                'job_posts' => $totalJobPosts,
                'regular_posts' => $totalRegularPosts,
                'today' => $postsToday,
                'this_week' => $postsThisWeek,
                'this_month' => $postsThisMonth,
                'this_year' => $postsThisYear,
            ],
            'engagement' => [
                'likes' => $totalLikes,
                'comments' => $totalComments,
                'shares' => $totalShares,
                'views' => $totalViews,
            ],
            'connections' => [
                'followers' => $totalFollowers,
                'following' => $totalFollowing,
                'pending_requests' => $pendingRequests,
            ],
            'applications' => [
                'total' => $totalApplications,
                'recent' => $recentApplications,
            ],
            'top_posts' => [
                'liked' => $topLikedPosts,
                'commented' => $topCommentedPosts,
                'viewed' => $topViewedPosts,
            ],
        ];
    }

    /**
     * Get recent activity for a user
     */
    private function getRecentActivity($userId)
    {
        $activities = collect();

        // Recent posts
        $recentPosts = Post::where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($post) {
                return [
                    'type' => 'post',
                    'description' => 'Created a new post: ' . $post->title,
                    'created_at' => $post->created_at,
                    'url' => '',
                    'icon' => 'fa-file-text',
                    'color' => 'blue',
                ];
            });

        // Recent likes received
        $recentLikes = PostLike::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })
            ->with('user', 'post')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($like) {
                return [
                    'type' => 'like',
                    'description' => ($like->user ? $like->user->getName() : 'Someone') . ' liked your post: ' . ($like->post ? $like->post->title : 'Unknown'),
                    'created_at' => $like->created_at,
                    'url' => '',
                    'icon' => 'fa-heart',
                    'color' => 'red',
                ];
            });

        // Recent comments received
        $recentComments = PostComment::whereIn('post_id', function($query) use ($userId) {
                $query->select('id')->from('posts')->where('user_id', $userId);
            })
            ->with('user', 'post')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($comment) {
                return [
                    'type' => 'comment',
                    'description' => ($comment->user ? $comment->user->getName() : 'Someone') . ' commented on your post: ' . ($comment->post ? $comment->post->title : 'Unknown'),
                    'created_at' => $comment->created_at,
                    'url' => '',
                    'icon' => 'fa-comment',
                    'color' => 'green',
                ];
            });

        // Recent followers
        $recentFollowers = UserConnection::where('following_id', $userId)
            ->where('status', 'accepted')
            ->with('follower')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($connection) {
                return [
                    'type' => 'follower',
                    'description' => 'New follower: ' . ($connection->follower ? $connection->follower->getName() : 'Unknown User'),
                    'created_at' => $connection->created_at,
                    'url' => route('users.show', $connection->follower_id),
                    'icon' => 'fa-user-plus',
                    'color' => 'purple',
                ];
            });

        // Recent job applications
        $recentApplications = JobApply::where('user_id', $userId)
            ->with('job')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($application) {
                return [
                    'type' => 'application',
                    'description' => 'Applied for job: ' . ($application->job ? $application->job->title : 'Unknown'),
                    'created_at' => $application->created_at,
                    'url' => '#',
                    'icon' => 'fa-briefcase',
                    'color' => 'orange',
                ];
            });

        $activities = $activities->merge($recentPosts)
            ->merge($recentLikes)
            ->merge($recentComments)
            ->merge($recentFollowers)
            ->merge($recentApplications)
            ->sortByDesc('created_at')
            ->take(50);

        return $activities;
    }

    /**
     * Get total engagement for a user
     */
    private function getUserEngagementTotal($userId)
    {
        $likes = PostLike::whereIn('post_id', function($query) use ($userId) {
            $query->select('id')->from('posts')->where('user_id', $userId);
        })->count();

        $comments = PostComment::whereIn('post_id', function($query) use ($userId) {
            $query->select('id')->from('posts')->where('user_id', $userId);
        })->count();

        $shares = PostShare::whereIn('post_id', function($query) use ($userId) {
            $query->select('id')->from('posts')->where('user_id', $userId);
        })->count();

        return $likes + $comments + $shares;
    }
}