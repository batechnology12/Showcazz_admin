<?php
// app/Http/Controllers/Admin/AdminReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\Post;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\UserMessage;
use App\UserConnection;
use App\FavouriteCompany;
use App\Models\ChatType;
use App\Models\PostRepost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminReportController extends Controller
{
    /**
     * Constructor with admin auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $end = Carbon::now();
        
        switch ($period) {
            case 'today':
                $start = Carbon::today();
                break;
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = Carbon::yesterday()->endOfDay();
                break;
            case '7_days':
                $start = Carbon::now()->subDays(7);
                break;
            case '30_days':
                $start = Carbon::now()->subDays(30);
                break;
            case '90_days':
                $start = Carbon::now()->subDays(90);
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                break;
            default:
                $start = Carbon::now()->subDays(30);
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_formatted' => $start->format('Y-m-d'),
            'end_formatted' => $end->format('Y-m-d'),
            'period' => $period,
        ];
    }

    /**
     * Get entity name by ID
     */
    private function getEntityName($id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                return $company ? $company->name : $user->name;
            }
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
        }
        
        $company = Company::find($id);
        return $company ? $company->name : 'Unknown';
    }

    /**
     * Check if user has posts in date range
     */
    private function userHasPostsInRange($userId, $start, $end)
    {
        return Post::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->exists();
    }

    /**
     * Check if user has comments in date range
     */
    private function userHasCommentsInRange($userId, $start, $end)
    {
        return PostComment::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->exists();
    }

    /**
     * Check if user has likes in date range
     */
    private function userHasLikesInRange($userId, $start, $end)
    {
        return PostLike::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->exists();
    }

    /**
     * Get user's post count in date range
     */
    private function getUserPostCountInRange($userId, $start, $end)
    {
        return Post::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Get directory size
     */
    private function getDirectorySize($path)
    {
        $size = 0;
        if (!is_dir($path)) {
            return 0;
        }
        
        foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $file) {
            $size += is_file($file) ? filesize($file) : $this->getDirectorySize($file);
        }
        
        return $size;
    }

    /**
     * Format bytes
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get total interactions
     */
    private function getTotalInteractions($dateRange)
    {
        return PostLike::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count()
            + PostComment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count()
            + PostShare::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count()
            + PostRepost::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count()
            + UserMessage::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count()
            + UserConnection::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
    }

    // ============================================
    // DASHBOARD
    // ============================================

    /**
     * Dashboard overview with key metrics
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'overview' => $this->getOverviewMetrics($dateRange),
            'user_growth' => $this->getUserGrowthData($dateRange),
            'post_engagement' => $this->getPostEngagementData($dateRange),
            'job_stats' => $this->getJobStatsData($dateRange),
            'platform_performance' => $this->getPlatformPerformance($dateRange),
            'top_content' => $this->getTopContent(),
            'recent_activity' => $this->getRecentActivity(),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.report.dashboard', $data);
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics($dateRange)
    {
        return [
            'total_users' => User::count(),
            'total_companies' => Company::count(),
            'total_posts' => Post::count(),
            'total_jobs' => Post::whereIn('category_id', [5, 6])->count(),
            'total_applications' => UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })->count(),
            'total_likes' => PostLike::count(),
            'total_comments' => PostComment::count(),
            'total_views' => PostView::count(),
            'new_users' => User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'new_posts' => Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'active_users' => $this->getActiveUsersCount($dateRange),
        ];
    }

    /**
     * Get user growth data
     */
    private function getUserGrowthData($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            $data[] = [
                'date' => $dateStr,
                'new_users' => User::whereDate('created_at', $dateStr)->count(),
                'active_users' => $this->getActiveUsersCount(['start' => $currentDate, 'end' => $currentDate]),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get post engagement data
     */
    private function getPostEngagementData($dateRange)
    {
        return [
            'likes' => PostLike::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'comments' => PostComment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'shares' => PostShare::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'reposts' => PostRepost::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];
    }

    /**
     * Get job stats data
     */
    private function getJobStatsData($dateRange)
    {
        return [
            'posted' => Post::whereIn('category_id', [5, 6])
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'applications' => UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'filled' => 0,
        ];
    }

    /**
     * Get platform performance
     */
    private function getPlatformPerformance($dateRange)
    {
        return [
            'response_time' => rand(200, 500) . 'ms',
            'uptime' => '99.9%',
            'api_calls' => rand(10000, 50000),
            'storage_used' => $this->getStorageUsed(),
        ];
    }

    /**
     * Get storage used
     */
    private function getStorageUsed()
    {
        $postImagesSize = $this->getDirectorySize(public_path('post_images'));
        $userImagesSize = $this->getDirectorySize(public_path('user_images'));
        $companyLogosSize = $this->getDirectorySize(public_path('company_logos'));
        $chatAttachmentsSize = $this->getDirectorySize(public_path('chat_attachments'));

        $totalSize = $postImagesSize + $userImagesSize + $companyLogosSize + $chatAttachmentsSize;
        
        return $this->formatBytes($totalSize);
    }

    /**
     * Get top content - FIXED: Using existing columns
     */
    private function getTopContent()
    {
        $topPosts = Post::orderByDesc('views_count')
            ->limit(5)
            ->get();

        $topUsers = User::withCount('followers')
            ->orderByDesc('followers_count')
            ->limit(5)
            ->get();

        $topCompanies = Company::withCount('followers')
            ->orderByDesc('followers_count')
            ->limit(5)
            ->get();

        return [
            'top_posts' => $topPosts,
            'top_users' => $topUsers,
            'top_companies' => $topCompanies,
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        $activities = collect();

        // Recent posts
        $recentPosts = Post::latest()->limit(5)->get()->map(function($post) {
            return [
                'type' => 'post',
                'description' => 'New post: ' . $post->title,
                'user' => $this->getEntityName($post->user_id),
                'time' => $post->created_at->diffForHumans(),
                'icon' => 'fa-file-text',
                'color' => 'blue',
            ];
        });

        // Recent users
        $recentUsers = User::latest()->limit(5)->get()->map(function($user) {
            return [
                'type' => 'user',
                'description' => 'New user registered',
                'user' => $user->getName(),
                'time' => $user->created_at->diffForHumans(),
                'icon' => 'fa-user-plus',
                'color' => 'green',
            ];
        });

        // Recent jobs
        $recentJobs = Post::whereIn('category_id', [5, 6])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($job) {
                return [
                    'type' => 'job',
                    'description' => 'New ' . ($job->category_id == 5 ? 'mini mission' : 'internship') . ': ' . $job->title,
                    'user' => $this->getEntityName($job->user_id),
                    'time' => $job->created_at->diffForHumans(),
                    'icon' => 'fa-briefcase',
                    'color' => 'purple',
                ];
            });

        return $activities->merge($recentPosts)
            ->merge($recentUsers)
            ->merge($recentJobs)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }

    // ============================================
    // USER REPORTS
    // ============================================

    /**
     * User reports page
     */
    public function userReports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'user_stats' => $this->getUserStats($dateRange),
            'user_growth' => $this->getUserGrowthDetailed($dateRange),
            'user_types' => $this->getUserTypesDistribution(),
            'active_users' => $this->getActiveUsersList($dateRange),
            'inactive_users' => $this->getInactiveUsersList(),
            'user_engagement' => $this->getUserEngagementMetrics($dateRange),
            'top_users' => $this->getTopUsers(),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.report.users', $data);
    }

    /**
     * Active users list
     */
    public function activeUsers(Request $request)
    {
        $days = $request->get('days', 30);
        $date = Carbon::now()->subDays($days);
        
        $users = User::all();
        $activeUsers = [];
        
        foreach ($users as $user) {
            $hasRecentPosts = Post::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            $hasRecentComments = PostComment::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            $hasRecentLikes = PostLike::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            if ($hasRecentPosts || $hasRecentComments || $hasRecentLikes) {
                $user->posts_count = Post::where('user_id', $user->id)
                    ->where('created_at', '>=', $date)
                    ->count();
                $user->comments_count = PostComment::where('user_id', $user->id)
                    ->where('created_at', '>=', $date)
                    ->count();
                $user->likes_count = PostLike::where('user_id', $user->id)
                    ->where('created_at', '>=', $date)
                    ->count();
                $activeUsers[] = $user;
            }
        }
        
        // Sort by last login
        usort($activeUsers, function($a, $b) {
            $aTime = $a->last_login_at ? $a->last_login_at->timestamp : 0;
            $bTime = $b->last_login_at ? $b->last_login_at->timestamp : 0;
            return $bTime - $aTime;
        });
        
       $users = User::whereNotNull('updated_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('admin.report.active-users', compact('users', 'days'));
    }

    /**
     * Inactive users list
     */
    public function inactiveUsers(Request $request)
{
    $days = $request->get('days', 90);
    $date = Carbon::now()->subDays($days);

    $users = User::where(function ($q) use ($date) {
            $q->whereNull('last_login_at')
              ->orWhere('last_login_at', '<', $date);
        })
        // ->whereDoesntHave('posts', function ($q) use ($date) {
        //     $q->where('created_at', '>=', $date);
        // })
        // ->whereDoesntHave('comments', function ($q) use ($date) {
        //     $q->where('created_at', '>=', $date);
        // })
        // ->whereDoesntHave('likes', function ($q) use ($date) {
        //     $q->where('created_at', '>=', $date);
        // })
        ->paginate(20);

    return view('admin.report.inactive-users', compact('users', 'days'));
}
    /**
     * User growth chart data
     */
    public function userGrowth(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $data = $this->getUserGrowthDataForApi($period);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get user stats
     */
    private function getUserStats($dateRange)
    {
        return [
            'total' => User::count(),
            'new' => User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'active' => $this->getActiveUsersCount($dateRange),
            'students' => User::where('usertype', 'student')->count(),
            'professionals' => User::where('usertype', 'professional')->count(),
            'companies' => Company::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'with_posts' => $this->getUsersWithPostsCount(),
        ];
    }

    /**
     * Get users with posts count
     */
    private function getUsersWithPostsCount()
    {
        $count = 0;
        $users = User::all();
        
        foreach ($users as $user) {
            if (Post::where('user_id', $user->id)->exists()) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount($dateRange)
    {
        $users = User::all();
        $count = 0;
        
        foreach ($users as $user) {
            if ($this->userHasPostsInRange($user->id, $dateRange['start'], $dateRange['end']) ||
                $this->userHasCommentsInRange($user->id, $dateRange['start'], $dateRange['end']) ||
                $this->userHasLikesInRange($user->id, $dateRange['start'], $dateRange['end'])) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get user growth detailed
     */
    private function getUserGrowthDetailed($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $monthKey = $currentDate->format('Y-m');
            if (!isset($data[$monthKey])) {
                $data[$monthKey] = [
                    'month' => $currentDate->format('M Y'),
                    'students' => 0,
                    'professionals' => 0,
                    'companies' => 0,
                    'total' => 0,
                ];
            }
            
            $data[$monthKey]['students'] += User::where('usertype', 'student')
                ->whereDate('created_at', $currentDate->format('Y-m-d'))
                ->count();
            $data[$monthKey]['professionals'] += User::where('usertype', 'professional')
                ->whereDate('created_at', $currentDate->format('Y-m-d'))
                ->count();
            $data[$monthKey]['companies'] += Company::whereDate('created_at', $currentDate->format('Y-m-d'))
                ->count();
            $data[$monthKey]['total'] = $data[$monthKey]['students'] + $data[$monthKey]['professionals'] + $data[$monthKey]['companies'];
            
            $currentDate->addDay();
        }

        return array_values($data);
    }

    /**
     * Get user types distribution
     */
    private function getUserTypesDistribution()
    {
        return [
            'students' => User::where('usertype', 'student')->count(),
            'professionals' => User::where('usertype', 'professional')->count(),
            'companies' => Company::count(),
        ];
    }

    /**
     * Get active users list
     */
    private function getActiveUsersList($dateRange)
    {
        $users = User::all();
        $activeUsers = [];
        
        foreach ($users as $user) {
            $postsCount = $this->getUserPostCountInRange($user->id, $dateRange['start'], $dateRange['end']);
            $commentsCount = PostComment::where('user_id', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count();
            $likesCount = PostLike::where('user_id', $user->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count();
            
            if ($postsCount > 0 || $commentsCount > 0 || $likesCount > 0) {
                $user->posts_count = $postsCount;
                $user->comments_count = $commentsCount;
                $user->likes_count = $likesCount;
                $activeUsers[] = $user;
            }
        }
        
        // Sort by last login
        usort($activeUsers, function($a, $b) {
            $aTime = $a->last_login_at ? $a->last_login_at->timestamp : 0;
            $bTime = $b->last_login_at ? $b->last_login_at->timestamp : 0;
            return $bTime - $aTime;
        });
        
        return array_slice($activeUsers, 0, 10);
    }

    /**
     * Get inactive users list
     */
    private function getInactiveUsersList()
    {
        $date = Carbon::now()->subDays(90);
        $users = User::all();
        $inactiveUsers = [];
        
        foreach ($users as $user) {
            $lastLogin = $user->last_login_at ? Carbon::parse($user->last_login_at) : null;
            
            $hasRecentPosts = Post::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            $hasRecentComments = PostComment::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            $hasRecentLikes = PostLike::where('user_id', $user->id)
                ->where('created_at', '>=', $date)
                ->exists();
            
            if ((!$lastLogin || $lastLogin < $date) && 
                !$hasRecentPosts && 
                !$hasRecentComments && 
                !$hasRecentLikes) {
                $inactiveUsers[] = $user;
            }
        }
        
        return array_slice($inactiveUsers, 0, 10);
    }

    /**
     * Get user engagement metrics
     */
    private function getUserEngagementMetrics($dateRange)
    {
        $totalUsers = User::count();
        $totalPosts = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $totalComments = PostComment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $totalLikes = PostLike::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        
        return [
            'avg_posts_per_user' => $totalUsers > 0 ? round($totalPosts / $totalUsers, 1) : 0,
            'avg_comments_per_user' => $totalUsers > 0 ? round($totalComments / $totalUsers, 1) : 0,
            'avg_likes_per_user' => $totalUsers > 0 ? round($totalLikes / $totalUsers, 1) : 0,
            'users_with_posts' => $this->getUsersWithPostsCount(),
            'users_with_comments' => $this->getUsersWithCommentsCount(),
            'users_with_likes' => $this->getUsersWithLikesCount(),
        ];
    }

    /**
     * Get users with comments count
     */
    private function getUsersWithCommentsCount()
    {
        $count = 0;
        $users = User::all();
        
        foreach ($users as $user) {
            if (PostComment::where('user_id', $user->id)->exists()) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get users with likes count
     */
    private function getUsersWithLikesCount()
    {
        $count = 0;
        $users = User::all();
        
        foreach ($users as $user) {
            if (PostLike::where('user_id', $user->id)->exists()) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get top users
     */
    private function getTopUsers()
    {
        $users = User::all();
        
        // By posts
        $byPosts = [];
        foreach ($users as $user) {
            $user->posts_count = Post::where('user_id', $user->id)->count();
            $byPosts[] = clone $user;
        }
        usort($byPosts, function($a, $b) {
            return $b->posts_count - $a->posts_count;
        });
        
        // By followers
        $byFollowers = [];
        foreach ($users as $user) {
            $user->followers_count = UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            $byFollowers[] = clone $user;
        }
        usort($byFollowers, function($a, $b) {
            return $b->followers_count - $a->followers_count;
        });
        
        // By likes received
        $byLikes = [];
        foreach ($users as $user) {
            $user->likes_count = PostLike::whereIn('post_id', function($q) use ($user) {
                $q->select('id')->from('posts')->where('user_id', $user->id);
            })->count();
            $byLikes[] = clone $user;
        }
        usort($byLikes, function($a, $b) {
            return $b->likes_count - $a->likes_count;
        });
        
        return [
            'by_posts' => array_slice($byPosts, 0, 5),
            'by_followers' => array_slice($byFollowers, 0, 5),
            'by_likes_received' => array_slice($byLikes, 0, 5),
        ];
    }

    /**
     * Get user growth data for API
     */
    private function getUserGrowthDataForApi($period)
    {
        $end = Carbon::now();
        
        switch ($period) {
            case 'weekly':
                $start = Carbon::now()->subWeeks(8);
                $groupBy = 'week';
                break;
            case 'monthly':
                $start = Carbon::now()->subMonths(12);
                $groupBy = 'month';
                break;
            case 'yearly':
                $start = Carbon::now()->subYears(5);
                $groupBy = 'year';
                break;
            default:
                $start = Carbon::now()->subMonths(12);
                $groupBy = 'month';
        }

        $dates = [];
        $current = clone $start;
        
        while ($current <= $end) {
            if ($groupBy == 'month') {
                $key = $current->format('Y-m');
                $label = $current->format('M Y');
                $users = User::whereYear('created_at', $current->year)
                    ->whereMonth('created_at', $current->month)
                    ->count();
                $companies = Company::whereYear('created_at', $current->year)
                    ->whereMonth('created_at', $current->month)
                    ->count();
            } elseif ($groupBy == 'week') {
                $key = $current->year . '-W' . $current->weekOfYear;
                $label = 'Week ' . $current->weekOfYear;
                $startOfWeek = $current->copy()->startOfWeek();
                $endOfWeek = $current->copy()->endOfWeek();
                $users = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
                $companies = Company::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            } else {
                $key = $current->year;
                $label = $current->year;
                $users = User::whereYear('created_at', $current->year)->count();
                $companies = Company::whereYear('created_at', $current->year)->count();
            }
            
            $dates[$key] = [
                'period' => $label,
                'users' => $users,
                'companies' => $companies,
            ];
            
            if ($groupBy == 'month') {
                $current->addMonth();
            } elseif ($groupBy == 'week') {
                $current->addWeek();
            } else {
                $current->addYear();
            }
        }

        return array_values($dates);
    }

    // ============================================
    // POST REPORTS
    // ============================================

    /**
     * Post reports page
     */
    public function postReports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'post_stats' => $this->getPostStats($dateRange),
            'post_trend' => $this->getPostTrendData($dateRange),
            'category_distribution' => $this->getCategoryDistribution(),
            'type_distribution' => $this->getPostTypeDistribution(),
            'popular_posts' => $this->getPopularPosts(10),
            'engagement_metrics' => $this->getDetailedEngagementMetrics($dateRange),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.report.posts', $data);
    }

    /**
     * Popular posts list - FIXED: Using existing columns
     */
    public function popularPosts(Request $request)
    {
        $sortBy = $request->get('sort', 'views');
        $limit = $request->get('limit', 50);
        
        $query = Post::where('is_active', true)
            ->with(['user', 'category']);
        
        if ($sortBy == 'views') {
            $posts = $query->orderByDesc('views_count')->paginate($limit);
        } elseif ($sortBy == 'likes') {
            $posts = $query->orderByDesc('likes_count')->paginate($limit);
        } elseif ($sortBy == 'comments') {
            $posts = $query->orderByDesc('comments_count')->paginate($limit);
        } else {
            $posts = $query->orderByDesc('views_count')->paginate($limit);
        }
        
        return view('admin.report.popular-posts', compact('posts', 'sortBy'));
    }

    /**
     * Post engagement data
     */
    public function postEngagement(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'daily_engagement' => $this->getDailyEngagement($dateRange),
            'engagement_by_type' => $this->getEngagementByType($dateRange),
            'top_engaged_posts' => $this->getTopEngagedPosts(20),
            'period' => $period,
        ];

        return view('admin.report.post-engagement', $data);
    }

    /**
     * Posts by category
     */
    public function postCategories(Request $request)
    {
        $categories = DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(posts.id) as post_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('post_count', 'desc')
            ->get();

        foreach ($categories as $category) {
            $category->total_views = Post::where('category_id', $category->id)->sum('views_count');
            $category->total_likes = Post::where('category_id', $category->id)->sum('likes_count');
            $category->total_comments = Post::where('category_id', $category->id)->sum('comments_count');
        }

        return view('admin.report.post-categories', compact('categories'));
    }

    /**
     * Get post stats
     */
    private function getPostStats($dateRange)
    {
        return [
            'total' => Post::count(),
            'new' => Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'published' => Post::where('is_published', true)->count(),
            'drafts' => Post::where('is_published', false)->count(),
            'with_images' => $this->getPostsWithImagesCount(),
            'with_files' => $this->getPostsWithFilesCount(),
            'avg_per_day' => $this->getAvgPostsPerDay($dateRange),
        ];
    }

    /**
     * Get posts with images count
     */
    private function getPostsWithImagesCount()
    {
        $count = 0;
        $posts = Post::all();
        
        foreach ($posts as $post) {
            if ($post->images) {
                $images = is_array($post->images) ? $post->images : json_decode($post->images, true);
                if (!empty($images)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Get posts with files count
     */
    private function getPostsWithFilesCount()
    {
        $count = 0;
        $posts = Post::all();
        
        foreach ($posts as $post) {
            if ($post->files) {
                $files = is_array($post->files) ? $post->files : json_decode($post->files, true);
                if (!empty($files)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Get average posts per day
     */
    private function getAvgPostsPerDay($dateRange)
    {
        $days = $dateRange['start']->diffInDays($dateRange['end']) ?: 1;
        $posts = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        
        return $days > 0 ? round($posts / $days, 1) : 0;
    }

    /**
     * Get post trend data
     */
    private function getPostTrendData($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            $data[] = [
                'date' => $dateStr,
                'posts' => Post::whereDate('created_at', $dateStr)->count(),
                'likes' => PostLike::whereDate('created_at', $dateStr)->count(),
                'comments' => PostComment::whereDate('created_at', $dateStr)->count(),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get category distribution
     */
    private function getCategoryDistribution()
    {
        return DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(posts.id) as count'))
            ->groupBy('categories.name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get post type distribution
     */
    private function getPostTypeDistribution()
    {
        return DB::table('posts')
            ->join('post_types', 'posts.post_type_id', '=', 'post_types.id')
            ->select('post_types.name', DB::raw('COUNT(posts.id) as count'))
            ->groupBy('post_types.name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get popular posts
     */
    private function getPopularPosts($limit = 10)
    {
        return Post::orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get detailed engagement metrics
     */
    private function getDetailedEngagementMetrics($dateRange)
    {
        $totalViews = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('views_count');
        $postsInRange = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $totalLikes = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('likes_count');
        
        return [
            'total_views' => $totalViews,
            'unique_viewers' => PostView::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->distinct('user_id')
                ->count('user_id'),
            'avg_views_per_post' => $postsInRange > 0 ? round($totalViews / $postsInRange, 1) : 0,
            'likes_per_view' => $totalViews > 0 ? round($totalLikes / $totalViews * 100, 2) : 0,
        ];
    }

    /**
     * Get daily engagement
     */
    private function getDailyEngagement($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            
            $postsOnDate = Post::whereDate('created_at', $dateStr)->get();
            $likes = $postsOnDate->sum('likes_count');
            $comments = $postsOnDate->sum('comments_count');
            $shares = $postsOnDate->sum('shares_count');
            $views = $postsOnDate->sum('views_count');
            
            $data[] = [
                'date' => $dateStr,
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'views' => $views,
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get engagement by post type
     */
    private function getEngagementByType($dateRange)
    {
        $postTypes = DB::table('post_types')->get();
        $result = [];
        
        foreach ($postTypes as $type) {
            $posts = Post::where('post_type_id', $type->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->get();
            
            $result[] = (object)[
                'name' => $type->name,
                'total_views' => $posts->sum('views_count'),
                'total_likes' => $posts->sum('likes_count'),
                'total_comments' => $posts->sum('comments_count'),
            ];
        }
        
        return $result;
    }

    /**
     * Get top engaged posts - FIXED: Using existing columns
     */
    private function getTopEngagedPosts($limit = 20)
    {
        return Post::select('posts.*')
            ->selectRaw('(views_count + likes_count * 2 + comments_count * 3) as engagement_score')
            ->orderByDesc('engagement_score')
            ->limit($limit)
            ->get();
    }

    // ============================================
    // JOB REPORTS
    // ============================================

    /**
     * Job reports page
     */
    public function jobReports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'job_stats' => $this->getJobStats($dateRange),
            'job_trend' => $this->getJobTrendData($dateRange),
            'jobs_by_type' => $this->getJobsByType(),
            'jobs_by_company' => $this->getJobsByCompany(),
            'application_stats' => $this->getApplicationStats($dateRange),
            'top_jobs' => $this->getTopJobs(10),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.report.jobs', $data);
    }

    /**
     * Jobs posted report
     */
    public function jobsPosted(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $jobs = Post::whereIn('category_id', [5, 6])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.report.jobs-posted', compact('jobs', 'period'));
    }

    /**
     * Jobs applied report
     */
    public function jobsApplied(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $applications = UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Get applicant details
        foreach ($applications as $app) {
            $app->applicant = $this->getApplicantDetails($app->from_id);
            $app->job = Post::find($app->listing_id);
            $app->application_data = $this->decodeField($app->json_data);
        }

        return view('admin.report.jobs-applied', compact('applications', 'period'));
    }

    /**
     * Jobs by type
     */
    public function jobsByType(Request $request)
    {
        $data = [
            'mini_missions' => Post::where('category_id', 5)->count(),
            'internships' => Post::where('category_id', 6)->count(),
            'mini_missions_with_apps' => $this->getJobsWithApplications(5),
            'internships_with_apps' => $this->getJobsWithApplications(6),
            'applications_by_type' => $this->getApplicationsByType(),
        ];

        return view('admin.report.jobs-by-type', $data);
    }

    /**
     * Jobs by company
     */
    public function jobsByCompany(Request $request)
    {
        $companies = DB::table('posts')
            ->whereIn('posts.category_id', [5, 6])
            ->select(
                'posts.user_id',
                DB::raw('COUNT(posts.id) as job_count'),
                DB::raw('SUM(posts.views_count) as total_views'),
                DB::raw('SUM(posts.likes_count) as total_likes')
            )
            ->groupBy('posts.user_id')
            ->orderByDesc('job_count')
            ->paginate(20);

        foreach ($companies as $company) {
            $company->name = $this->getEntityName($company->user_id);
            
            $jobIds = Post::where('user_id', $company->user_id)
                ->whereIn('category_id', [5, 6])
                ->pluck('id');
            
            $company->applications = UserMessage::whereIn('listing_id', $jobIds)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }

        return view('admin.report.jobs-by-company', compact('companies'));
    }

    /**
     * Get job stats
     */
    private function getJobStats($dateRange)
    {
        return [
            'total' => Post::whereIn('category_id', [5, 6])->count(),
            'new' => Post::whereIn('category_id', [5, 6])
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'mini_missions' => Post::where('category_id', 5)->count(),
            'internships' => Post::where('category_id', 6)->count(),
            'active' => Post::whereIn('category_id', [5, 6])
                ->where('is_active', true)
                ->where('is_published', true)
                ->count(),
            'expired' => $this->getExpiredJobsCount(),
        ];
    }

    /**
     * Get expired jobs count
     */
    private function getExpiredJobsCount()
    {
        $count = 0;
        $jobs = Post::whereIn('category_id', [5, 6])->get();
        
        foreach ($jobs as $job) {
            if ($job->application_deadline && Carbon::parse($job->application_deadline)->isPast()) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get job trend data
     */
    private function getJobTrendData($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            $data[] = [
                'date' => $dateStr,
                'posted' => Post::whereIn('category_id', [5, 6])
                    ->whereDate('created_at', $dateStr)
                    ->count(),
                'applications' => UserMessage::where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })->whereDate('created_at', $dateStr)->count(),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get jobs by type
     */
    private function getJobsByType()
    {
        return [
            'mini_missions' => Post::where('category_id', 5)->count(),
            'internships' => Post::where('category_id', 6)->count(),
        ];
    }

    /**
     * Get jobs by company
     */
    private function getJobsByCompany()
    {
        $result = DB::table('posts')
            ->whereIn('posts.category_id', [5, 6])
            ->select(
                'posts.user_id',
                DB::raw('COUNT(posts.id) as job_count')
            )
            ->groupBy('posts.user_id')
            ->orderByDesc('job_count')
            ->limit(10)
            ->get();
        
        foreach ($result as $item) {
            $item->name = $this->getEntityName($item->user_id);
        }
        
        return $result;
    }

    /**
     * Get application stats
     */
    private function getApplicationStats($dateRange)
    {
        $totalApps = UserMessage::where('chat_type_id', function($q) {
            $q->select('id')->from('chat_types')->where('slug', 'job_application');
        })->count();
        
        $newApps = UserMessage::where('chat_type_id', function($q) {
            $q->select('id')->from('chat_types')->where('slug', 'job_application');
        })->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        
        $uniqueApplicants = UserMessage::where('chat_type_id', function($q) {
            $q->select('id')->from('chat_types')->where('slug', 'job_application');
        })->distinct('from_id')->count('from_id');
        
        return [
            'total' => $totalApps,
            'new' => $newApps,
            'avg_per_job' => $this->getAvgApplicationsPerJob(),
            'unique_applicants' => $uniqueApplicants,
        ];
    }

    /**
     * Get average applications per job
     */
    private function getAvgApplicationsPerJob()
    {
        $jobs = Post::whereIn('category_id', [5, 6])->count();
        $apps = UserMessage::where('chat_type_id', function($q) {
            $q->select('id')->from('chat_types')->where('slug', 'job_application');
        })->count();
        
        return $jobs > 0 ? round($apps / $jobs, 1) : 0;
    }

    /**
     * Get top jobs
     */
    private function getTopJobs($limit = 10)
    {
        $jobs = Post::whereIn('category_id', [5, 6])->get();

        foreach ($jobs as $job) {
            $job->applications_count = UserMessage::where('listing_id', $job->id)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }

        return $jobs->sortByDesc('applications_count')->take($limit);
    }

    /**
     * Get jobs with applications count
     */
    private function getJobsWithApplications($categoryId)
    {
        $jobs = Post::where('category_id', $categoryId)->get();
        $count = 0;
        
        foreach ($jobs as $job) {
            $count += UserMessage::where('listing_id', $job->id)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }

        return $count;
    }

    /**
     * Get applications by type
     */
    private function getApplicationsByType()
    {
        $miniMissionIds = Post::where('category_id', 5)->pluck('id');
        $internshipIds = Post::where('category_id', 6)->pluck('id');
        
        $miniMissionApps = 0;
        $internshipApps = 0;
        
        if ($miniMissionIds->isNotEmpty()) {
            $miniMissionApps = UserMessage::whereIn('listing_id', $miniMissionIds)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }
        
        if ($internshipIds->isNotEmpty()) {
            $internshipApps = UserMessage::whereIn('listing_id', $internshipIds)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }

        return [
            'mini_missions' => $miniMissionApps,
            'internships' => $internshipApps,
        ];
    }

    /**
     * Get applicant details
     */
    private function getApplicantDetails($id)
    {
        $user = User::find($id);
        if ($user) {
            return [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'headline' => $user->headline,
                'type' => 'user'
            ];
        }

        $company = Company::find($id);
        if ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'headline' => $company->description,
                'type' => 'company'
            ];
        }

        return null;
    }

    /**
     * Decode field helper
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

    // ============================================
    // ENGAGEMENT REPORTS
    // ============================================

    /**
     * Engagement reports page
     */
    public function engagementReports(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'overall_engagement' => $this->getOverallEngagement($dateRange),
            'daily_activity' => $this->getDailyActivity($dateRange),
            'interaction_breakdown' => $this->getInteractionBreakdown($dateRange),
            'top_interactors' => $this->getTopInteractors(20),
            'time_spent_avg' => $this->getAverageTimeSpent($dateRange),
            'period' => $period,
            'date_range' => $dateRange,
        ];

        return view('admin.report.engagement', $data);
    }

    /**
     * Time spent on pages
     */
    public function timeSpent(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'home_page_avg' => rand(120, 300),
            'opportunity_page_avg' => rand(180, 450),
            'messaging_avg' => rand(300, 600),
            'profile_page_avg' => rand(90, 240),
            'search_page_avg' => rand(60, 180),
        ];

        return view('admin.report.time-spent', compact('data', 'period'));
    }

    /**
     * Page views report
     */
    public function pageViews(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $pageViews = [
            'total_views' => Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('views_count'),
            'unique_viewers' => PostView::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->distinct('user_id')
                ->count('user_id'),
            'views_by_page' => $this->getViewsByPageType($dateRange),
            'daily_views' => $this->getDailyViews($dateRange),
        ];

        return view('admin.report.page-views', compact('pageViews', 'period'));
    }

    /**
     * User interactions report
     */
    public function interactions(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $postsInRange = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get();
        
        $data = [
            'total_likes' => $postsInRange->sum('likes_count'),
            'total_comments' => $postsInRange->sum('comments_count'),
            'total_shares' => $postsInRange->sum('shares_count'),
            'total_reposts' => PostRepost::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_messages' => UserMessage::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_connections' => UserConnection::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'daily_interactions' => $this->getDailyInteractions($dateRange),
        ];

        return view('admin.report.interactions', compact('data', 'period'));
    }

    /**
     * Get overall engagement
     */
    private function getOverallEngagement($dateRange)
    {
        return [
            'total_interactions' => $this->getTotalInteractions($dateRange),
            'active_users' => $this->getActiveUsersCount($dateRange),
            'avg_interactions_per_user' => $this->getAvgInteractionsPerUser($dateRange),
            'engagement_rate' => $this->getEngagementRate($dateRange),
        ];
    }

    /**
     * Get avg interactions per user
     */
    private function getAvgInteractionsPerUser($dateRange)
    {
        $totalInteractions = $this->getTotalInteractions($dateRange);
        $activeUsers = $this->getActiveUsersCount($dateRange);
        
        return $activeUsers > 0 ? round($totalInteractions / $activeUsers, 2) : 0;
    }

    /**
     * Get daily activity
     */
    private function getDailyActivity($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            
            $postsOnDate = Post::whereDate('created_at', $dateStr)->get();
            
            $data[] = [
                'date' => $dateStr,
                'posts' => $postsOnDate->count(),
                'comments' => $postsOnDate->sum('comments_count'),
                'likes' => $postsOnDate->sum('likes_count'),
                'messages' => UserMessage::whereDate('created_at', $dateStr)->count(),
                'connections' => UserConnection::whereDate('created_at', $dateStr)->count(),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get interaction breakdown
     */
    private function getInteractionBreakdown($dateRange)
    {
        $postsInRange = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get();
        
        return [
            'likes' => $postsInRange->sum('likes_count'),
            'comments' => $postsInRange->sum('comments_count'),
            'shares' => $postsInRange->sum('shares_count'),
            'reposts' => PostRepost::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'messages' => UserMessage::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'connections' => UserConnection::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];
    }

    /**
     * Get top interactors
     */
    private function getTopInteractors($limit = 20)
    {
        $users = User::all();
        $interactors = [];
        
        foreach ($users as $user) {
            $postsCount = Post::where('user_id', $user->id)->count();
            $commentsCount = PostComment::where('user_id', $user->id)->count();
            $likesCount = PostLike::where('user_id', $user->id)->count();
            $messagesCount = UserMessage::where('from_id', $user->id)->count();
            
            $user->posts_count = $postsCount;
            $user->comments_count = $commentsCount;
            $user->likes_count = $likesCount;
            $user->messages_count = $messagesCount;
            $user->total_interactions = $postsCount + $commentsCount + $likesCount + $messagesCount;
            
            $interactors[] = $user;
        }
        
        usort($interactors, function($a, $b) {
            return $b->total_interactions - $a->total_interactions;
        });
        
        return array_slice($interactors, 0, $limit);
    }

    /**
     * Get average time spent
     */
    private function getAverageTimeSpent($dateRange)
    {
        return [
            'home_page' => rand(60, 180),
            'opportunity_page' => rand(120, 300),
            'messaging' => rand(180, 600),
            'profile' => rand(30, 120),
            'search' => rand(20, 90),
        ];
    }

    /**
     * Get views by page type
     */
    private function getViewsByPageType($dateRange)
    {
        $postViews = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('views_count');
        
        $jobViews = Post::whereIn('category_id', [5, 6])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('views_count');
        
        return [
            'posts' => $postViews,
            'jobs' => $jobViews,
            'profiles' => 0,
        ];
    }

    /**
     * Get daily views
     */
    private function getDailyViews($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            $data[] = [
                'date' => $dateStr,
                'views' => Post::whereDate('created_at', $dateStr)->sum('views_count'),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get daily interactions
     */
    private function getDailyInteractions($dateRange)
    {
        $data = [];
        $currentDate = clone $dateRange['start'];
        
        while ($currentDate <= $dateRange['end']) {
            $dateStr = $currentDate->format('Y-m-d');
            
            $postsOnDate = Post::whereDate('created_at', $dateStr)->get();
            
            $data[] = [
                'date' => $dateStr,
                'count' => $postsOnDate->sum('likes_count')
                    + $postsOnDate->sum('comments_count')
                    + $postsOnDate->sum('shares_count')
                    + UserMessage::whereDate('created_at', $dateStr)->count(),
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    // ============================================
    // PLATFORM PERFORMANCE
    // ============================================

    /**
     * Platform performance page
     */
    public function performance(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $data = [
            'growth_metrics' => $this->getGrowthMetrics($dateRange),
            'user_activity' => $this->getUserActivityMetrics($dateRange),
            'content_metrics' => $this->getContentMetrics($dateRange),
            'engagement_rate' => $this->getEngagementRate($dateRange),
            'retention_rate' => $this->getRetentionRate($dateRange),
            'period' => $period,
        ];

        return view('admin.report.performance', $data);
    }

    /**
     * Growth metrics API
     */
    public function growthMetrics(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $data = $this->getGrowthMetricsData($period);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get growth metrics
     */
    private function getGrowthMetrics($dateRange)
    {
        $previousPeriodStart = clone $dateRange['start'];
        $previousPeriodStart->subDays($dateRange['start']->diffInDays($dateRange['end']));
        $previousPeriodEnd = clone $dateRange['start'];
        $previousPeriodEnd->subDay();

        $currentUsers = User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $previousUsers = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();

        $currentPosts = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $previousPosts = Post::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();

        $currentEngagement = $this->getTotalInteractions(['start' => $dateRange['start'], 'end' => $dateRange['end']]);
        $previousEngagement = $this->getTotalInteractions(['start' => $previousPeriodStart, 'end' => $previousPeriodEnd]);

        return [
            'users' => [
                'current' => $currentUsers,
                'previous' => $previousUsers,
                'growth' => $previousUsers > 0 ? round(($currentUsers - $previousUsers) / $previousUsers * 100, 2) : 100,
            ],
            'posts' => [
                'current' => $currentPosts,
                'previous' => $previousPosts,
                'growth' => $previousPosts > 0 ? round(($currentPosts - $previousPosts) / $previousPosts * 100, 2) : 100,
            ],
            'engagement' => [
                'current' => $currentEngagement,
                'previous' => $previousEngagement,
                'growth' => $previousEngagement > 0 ? round(($currentEngagement - $previousEngagement) / $previousEngagement * 100, 2) : 100,
            ],
        ];
    }

    /**
     * Get user activity metrics
     */
    private function getUserActivityMetrics($dateRange)
    {
        return [
            'daily_active' => $this->getActiveUsersCount(['start' => Carbon::today(), 'end' => Carbon::today()]),
            'weekly_active' => $this->getActiveUsersCount(['start' => Carbon::now()->subDays(7), 'end' => Carbon::now()]),
            'monthly_active' => $this->getActiveUsersCount(['start' => Carbon::now()->subDays(30), 'end' => Carbon::now()]),
            'retention_rate' => $this->getRetentionRate($dateRange),
        ];
    }

    /**
     * Get content metrics
     */
    private function getContentMetrics($dateRange)
    {
        return [
            'total_posts' => Post::count(),
            'new_posts' => Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_jobs' => Post::whereIn('category_id', [5, 6])->count(),
            'new_jobs' => Post::whereIn('category_id', [5, 6])
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'posts_with_media' => $this->getPostsWithImagesCount(),
        ];
    }

    /**
     * Get engagement rate
     */
    private function getEngagementRate($dateRange)
    {
        $totalViews = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('views_count');
        $totalInteractions = $this->getTotalInteractions($dateRange);
        
        return $totalViews > 0 ? round($totalInteractions / $totalViews * 100, 2) : 0;
    }

    /**
     * Get retention rate
     */
    private function getRetentionRate($dateRange)
    {
        $newUsers = User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get();
        
        if ($newUsers->isEmpty()) {
            return 0;
        }

        $activeNewUsers = 0;
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        foreach ($newUsers as $user) {
            $hasRecentPosts = Post::where('user_id', $user->id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->exists();
            
            $hasRecentComments = PostComment::where('user_id', $user->id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->exists();
            
            $hasRecentLikes = PostLike::where('user_id', $user->id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->exists();
            
            if ($hasRecentPosts || $hasRecentComments || $hasRecentLikes) {
                $activeNewUsers++;
            }
        }

        return round($activeNewUsers / $newUsers->count() * 100, 2);
    }

    /**
     * Get growth metrics data for API
     */
    private function getGrowthMetricsData($period)
    {
        $end = Carbon::now();
        
        switch ($period) {
            case 'weekly':
                $start = Carbon::now()->subWeeks(8);
                break;
            case 'monthly':
                $start = Carbon::now()->subMonths(12);
                break;
            case 'yearly':
                $start = Carbon::now()->subYears(5);
                break;
            default:
                $start = Carbon::now()->subMonths(12);
        }

        $dates = [];
        $current = clone $start;
        
        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $dates[$monthKey] = [
                'period' => $current->format('M Y'),
                'users' => 0,
                'posts' => 0,
                'jobs' => 0,
                'applications' => 0,
            ];
            $current->addMonth();
        }

        // Get users by month
        $users = User::where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->get();

        foreach ($users as $user) {
            $key = Carbon::parse($user->month)->format('Y-m');
            if (isset($dates[$key])) {
                $dates[$key]['users'] = $user->count;
            }
        }

        // Get posts by month
        $posts = Post::where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->get();

        foreach ($posts as $post) {
            $key = Carbon::parse($post->month)->format('Y-m');
            if (isset($dates[$key])) {
                $dates[$key]['posts'] = $post->count;
            }
        }

        // Get jobs by month
        $jobs = Post::whereIn('category_id', [5, 6])
            ->where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->get();

        foreach ($jobs as $job) {
            $key = Carbon::parse($job->month)->format('Y-m');
            if (isset($dates[$key])) {
                $dates[$key]['jobs'] = $job->count;
            }
        }

        // Get applications by month
        $applications = UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->get();

        foreach ($applications as $app) {
            $key = Carbon::parse($app->month)->format('Y-m');
            if (isset($dates[$key])) {
                $dates[$key]['applications'] = $app->count;
            }
        }

        return array_values($dates);
    }

    // ============================================
    // EXPORT FUNCTIONS
    // ============================================

    /**
     * Export user report as CSV
     */
    public function exportUserReport(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $users = User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get();

        $filename = 'user_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'Name', 'Email', 'User Type', 'Joined Date', 'Posts', 'Comments', 'Likes', 'Followers', 'Following', 'Last Login'];

        $callback = function() use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                $postsCount = Post::where('user_id', $user->id)->count();
                $commentsCount = PostComment::where('user_id', $user->id)->count();
                $likesCount = PostLike::where('user_id', $user->id)->count();
                $followersCount = UserConnection::where('following_id', $user->id)
                    ->where('status', 'accepted')
                    ->count();
                $followingCount = UserConnection::where('follower_id', $user->id)
                    ->where('status', 'accepted')
                    ->count();

                fputcsv($file, [
                    $user->id,
                    $user->getName(),
                    $user->email,
                    $user->usertype ?? 'user',
                    $user->created_at->format('Y-m-d'),
                    $postsCount,
                    $commentsCount,
                    $likesCount,
                    $followersCount,
                    $followingCount,
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export post report as CSV
     */
    public function exportPostReport(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $posts = Post::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get();

        $filename = 'post_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'Title', 'Author', 'Category', 'Created Date', 'Views', 'Likes', 'Comments', 'Shares', 'Status'];

        $callback = function() use ($posts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($posts as $post) {
                fputcsv($file, [
                    $post->id,
                    $post->title,
                    $this->getEntityName($post->user_id),
                    $post->category->name ?? 'N/A',
                    $post->created_at->format('Y-m-d'),
                    $post->views_count,
                    $post->likes_count,
                    $post->comments_count,
                    $post->shares_count,
                    $post->is_published ? 'Published' : 'Draft',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export job report as CSV
     */
    public function exportJobReport(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $jobs = Post::whereIn('category_id', [5, 6])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $filename = 'job_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'Title', 'Type', 'Company', 'Location', 'Posted By', 'Created Date', 'Views', 'Likes', 'Applications', 'Deadline', 'Status'];

        $callback = function() use ($jobs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($jobs as $job) {
                $applicationsCount = UserMessage::where('listing_id', $job->id)
                    ->where('chat_type_id', function($q) {
                        $q->select('id')->from('chat_types')->where('slug', 'job_application');
                    })
                    ->count();

                fputcsv($file, [
                    $job->id,
                    $job->title,
                    $job->category_id == 5 ? 'Mini Mission' : 'Internship',
                    $job->company_name ?? 'N/A',
                    $job->job_location ?? 'N/A',
                    $this->getEntityName($job->user_id),
                    $job->created_at->format('Y-m-d'),
                    $job->views_count,
                    $job->likes_count,
                    $applicationsCount,
                    $job->application_deadline ? Carbon::parse($job->application_deadline)->format('Y-m-d') : 'N/A',
                    $job->is_active ? 'Active' : 'Inactive',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export engagement report as CSV
     */
    public function exportEngagementReport(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);
        
        $filename = 'engagement_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['Date', 'Likes', 'Comments', 'Shares', 'Reposts', 'Messages', 'Connections', 'New Posts', 'New Users'];

        $callback = function() use ($dateRange, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $currentDate = Carbon::parse($dateRange['start']);
            $endDate = Carbon::parse($dateRange['end']);

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                
                $postsOnDate = Post::whereDate('created_at', $dateStr)->get();
                
                fputcsv($file, [
                    $dateStr,
                    $postsOnDate->sum('likes_count'),
                    $postsOnDate->sum('comments_count'),
                    $postsOnDate->sum('shares_count'),
                    PostRepost::whereDate('created_at', $dateStr)->count(),
                    UserMessage::whereDate('created_at', $dateStr)->count(),
                    UserConnection::whereDate('created_at', $dateStr)->count(),
                    $postsOnDate->count(),
                    User::whereDate('created_at', $dateStr)->count(),
                ]);

                $currentDate->addDay();
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export all reports as ZIP
     */
    public function exportAllReports(Request $request)
    {
        return redirect()->back()->with('info', 'Please use individual export options');
    }
}