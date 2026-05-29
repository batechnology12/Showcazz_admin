<?php

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
use App\JobTitle;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    protected $fcmService;

    /**
     * Constructor with admin auth middleware
     */
    public function __construct(FCMService $fcmService)
    {
        $this->middleware('auth:admin');
        $this->fcmService = $fcmService;
    }

    /**
     * Helper function to decode JSON fields
     */
    private function decodeField($field)
    {
        if (empty($field)) {
            return [];
        }
        
        if (is_array($field)) {
            return $field;
        }
        
        $decoded = json_decode($field, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get user display name
     */
    private function getUserDisplayName($user)
    {
        if (!$user) {
            return 'Unknown User';
        }

        if ($user->usertype === 'company') {
            return $user->company_name ?? $user->name ?? 'Unknown Company';
        }

        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Unknown User');
    }

    // ============================================
    // USER MANAGEMENT
    // ============================================

    /**
     * Display a listing of users (excluding companies)
     */
    public function index(Request $request)
    {
        $query = User::with(['country', 'state', 'city', 'industry'])
            ->where('usertype', '!=', 'company'); // Exclude companies for regular user list

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
            ->with(['job'])
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

        // If deactivated, logout user from app (Sanctum)
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        // Send Push Notification
        if (!empty($user->firebase_token) && $user->push_notification) {
            $status = $user->is_active ? 'Activated' : 'Deactivated';
            $title = "Account " . $status;
            $body = $user->is_active 
                ? "Your account has been activated. You can now use all features." 
                : "Your account has been deactivated by admin. Please contact support.";
            
            $this->fcmService->fcmSendNotification($user->firebase_token, $title, $body, [
                'type' => 'account_status',
                'is_active' => (string)$user->is_active
            ]);
        }

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

    public function exportData($id)
    {
        $user = User::with([
            'country', 'state', 'city', 'industry', 'gender', 'careerLevel', 'jobExperience'
        ])->findOrFail($id);

        $analytics = $this->getDetailedAnalytics($user->id);
        
        // Get Interests and Skills
        $interests = \DB::table('job_titles')
            ->whereIn('id', is_array($user->area_of_interest_id) ? $user->area_of_interest_id : [])
            ->pluck('job_title')
            ->toArray();
            
        $skills = \DB::table('profile_skills')
            ->join('job_skills', 'profile_skills.job_skill_id', '=', 'job_skills.job_skill_id')
            ->where('profile_skills.user_id', $user->id)
            ->pluck('job_skills.job_skill')
            ->toArray();

        $filename = 'user_detail_' . $user->id . '_' . Str::slug($this->getUserDisplayName($user)) . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'User ID', 'Unique ID', 'Name', 'Email', 'Phone', 'User Type', 'Joined Date', 
            'College', 'School', 'Headline', 'Location', 'Industry', 'Career Level', 
            'Experience', 'Summary', 'Interests', 'Skills', 
            'Total Posts', 'Job Posts', 'Regular Posts', 
            'Total Likes Received', 'Total Comments Received', 'Total Shares Received', 'Total Views Received',
            'Followers', 'Following', 'Job Applications'
        ];

        $callback = function() use($user, $columns, $analytics, $interests, $skills) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $location = implode(', ', array_filter([
                $user->city?->city,
                $user->state?->state,
                $user->country?->country
            ]));

            fputcsv($file, [
                $user->id,
                $user->unique_id,
                $this->getUserDisplayName($user),
                $user->email,
                $user->phone ?? $user->mobile_num,
                ucfirst($user->usertype ?? 'user'),
                $user->created_at->format('Y-m-d H:i:s'),
                $user->college_name,
                $user->school_name,
                $user->headline,
                $location,
                $user->industry?->industry,
                $user->careerLevel?->career_level,
                $user->jobExperience?->job_experience,
                $user->getProfileSummary('summary'),
                implode(', ', $interests),
                implode(', ', $skills),
                $analytics['posts']['total'],
                $analytics['posts']['job_posts'],
                $analytics['posts']['regular_posts'],
                $analytics['engagement']['likes'],
                $analytics['engagement']['comments'],
                $analytics['engagement']['shares'],
                $analytics['engagement']['views'],
                $analytics['connections']['followers'],
                $analytics['connections']['following'],
                $analytics['applications']['total']
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
                'userName' => $this->getUserDisplayName($user),
                'subject' => $request->subject,
                'messageBody' => $request->message,
            ], function ($message) use ($user, $request) {
                $message->to($user->email, $this->getUserDisplayName($user))
                    ->subject($request->subject);
            });

            Log::info('Admin notification sent to user: ' . $user->email);

            return redirect()->back()->with('success', 'Notification sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user: ' . $e->getMessage());
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
            
            // Delete company logo if user is company
            if ($user->usertype === 'company' && $user->company_logo) {
                $logoPath = public_path('company_logos/' . $user->company_logo);
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
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
                $userName = $like->user ? $this->getUserDisplayName($like->user) : 'Someone';
                $postTitle = $like->post ? $like->post->title : 'Unknown';
                return [
                    'type' => 'like',
                    'description' => $userName . ' liked your post: ' . $postTitle,
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
                $userName = $comment->user ? $this->getUserDisplayName($comment->user) : 'Someone';
                $postTitle = $comment->post ? $comment->post->title : 'Unknown';
                return [
                    'type' => 'comment',
                    'description' => $userName . ' commented on your post: ' . $postTitle,
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
                $followerName = $connection->follower ? $this->getUserDisplayName($connection->follower) : 'Unknown User';
                return [
                    'type' => 'follower',
                    'description' => 'New follower: ' . $followerName,
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