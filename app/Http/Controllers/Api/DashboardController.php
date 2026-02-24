<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\Post;
use App\Job;
use App\UserConnection;
use App\FavouriteCompany;
use App\UserMessage;
use App\ChatSession;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\Models\PostRepost;
use App\PostView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard data with filters - RANDOM POSTS ON EVERY REQUEST
     */
    public function getDashboard(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // Validate filters
            $validator = Validator::make($request->all(), [
                'filter' => 'nullable|in:all,featured,trending,random',
                'search' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'post_type_id' => 'nullable|exists:post_types,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'show_connections_only' => 'nullable|boolean',
                'shuffle' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }

            // Get user info for greeting
            $greeting = $this->getGreeting($user);
            
            // Get user's connections
            $connectionIds = $this->getUserConnections($user);
            
            // Get main feed posts (will be randomized)
            $posts = $this->getFilteredPosts($user, $request, $connectionIds);
            
            // Get IDs of posts already in main feed to exclude from other sections
            $excludedPostIds = collect($posts['posts'])->pluck('id')->toArray();
            
            // Get trending posts (exclude posts from main feed)
            $trendingPosts = $this->getTrendingPosts($user, $connectionIds, $excludedPostIds);
            
            // Get featured posts (exclude posts from main feed and trending)
            $allExcludedIds = array_merge($excludedPostIds, collect($trendingPosts)->pluck('id')->toArray());
            $featuredPosts = $this->getFeaturedPosts($user, $connectionIds, $allExcludedIds);
            
            // Get user stats
            $userStats = $this->getUserStats($user);
            
            // Get network updates
            $networkUpdates = $this->getNetworkUpdates($user);
            
            // Get job opportunities
            $jobOpportunities = $this->getJobOpportunities($user);
            
            // Get message counts
            $messageStats = $this->getMessageStats($user);
            
            // Get connection suggestions
            $connectionSuggestions = $this->getConnectionSuggestions($user);
            
            // Get pending requests
            $pendingRequests = $this->getPendingRequests($user);
            $pendingRequestsCount = $pendingRequests->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'user' => $this->formatUserData($user),
                    'greeting' => $greeting,
                    'stats' => $userStats,
                    'messages' => $messageStats,
                    'posts' => [
                        'all' => $posts['posts'],
                        'trending' => $trendingPosts,
                        'featured' => $featuredPosts,
                        'pagination' => $posts['pagination'] ?? null,
                    ],
                    'network_updates' => $networkUpdates,
                    'job_opportunities' => $jobOpportunities,
                    'connection_suggestions' => $connectionSuggestions,
                    'pending_requests' => [
                        'count' => $pendingRequestsCount,
                        'list' => $pendingRequests->take(5)->values(),
                        'total' => $pendingRequestsCount,
                    ],
                    'filters' => [
                        'active_filter' => $request->filter ?? 'all',
                        'search_term' => $request->search,
                        'show_connections_only' => $request->show_connections_only ?? false,
                        'shuffled' => true, // Indicate that posts are shuffled
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard: ' . $e->getMessage(),
                'errors' => (object)[
                    'server' => 'An error occurred',
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]
            ], 500);
        }
    }

    /**
     * Get greeting message
     */
    private function getGreeting($user)
    {
        $hour = date('H');
        $timeOfDay = '';
        
        if ($hour < 12) {
            $timeOfDay = 'Good Morning';
        } elseif ($hour < 17) {
            $timeOfDay = 'Good Afternoon';
        } else {
            $timeOfDay = 'Good Evening';
        }
        
        $displayName = $user instanceof Company 
            ? $user->name 
            : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        
        return [
            'message' => $timeOfDay . ', ' . $displayName,
            'time_of_day' => strtolower(str_replace('Good ', '', $timeOfDay)),
            'display_name' => $displayName,
            'headline' => $user->headline ?? ($user instanceof Company ? $user->description : null),
        ];
    }
    
    /**
     * Get entity by ID - Auto detects table
     */
    private function getEntityById($id)
    {
        if (!$id) {
            return null;
        }
        
        $user = User::find($id);
        if ($user) {
            return $user;
        }
        
        $company = Company::find($id);
        if ($company) {
            return $company;
        }
        
        return null;
    }

    /**
     * Get enriched entity data - Works for both User & Company
     */
    private function getEnrichedEntityData($entity)
    {
        if (!$entity) {
            return null;
        }

        if ($entity instanceof Company) {
            return [
                'id' => $entity->id,
                'name' => $entity->name ?? 'Unknown Company',
                'email' => $entity->email ?? null,
                'usertype' => 'company',
                'headline' => $entity->description ?? null,
                'image' => $entity->logo ? asset('company_logos/' . $entity->logo) : null,
                'slug' => $entity->slug ?? null,
                'entity_type' => 'company',
            ];
        }

        if ($entity instanceof User) {
            if ($entity->usertype === 'company') {
                $company = Company::where('user_id', $entity->id)->first();
                if ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email ?? $entity->email,
                        'usertype' => 'company',
                        'headline' => $company->description,
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'slug' => $company->slug,
                        'entity_type' => 'company',
                    ];
                }
            }

            $firstName = $entity->first_name ?? '';
            $lastName = $entity->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $name = $name ?: ($entity->name ?? 'Unknown User');

            return [
                'id' => $entity->id,
                'name' => $name,
                'email' => $entity->email ?? null,
                'usertype' => $entity->usertype ?? 'user',
                'headline' => $entity->headline ?? null,
                'image' => $entity->image ? asset('user_images/' . $entity->image) : null,
                'slug' => null,
                'entity_type' => 'user',
            ];
        }

        return null;
    }

    /**
     * Get pending connection requests
     */
    private function getPendingRequests($user)
    {
        $pendingRequests = collect();
        
        if ($user instanceof User) {
            $pendingRequests = UserConnection::where('following_id', $user->id)
                ->where('status', 'pending')
                ->get()
                ->map(function($connection) {
                    $requester = $this->getEntityById($connection->follower_id);
                    
                    if (!$requester) {
                        return null;
                    }
                    
                    $enrichedData = $this->getEnrichedEntityData($requester);
                    
                    if (!$enrichedData) {
                        return null;
                    }
                    
                    $mutualCount = 0;
                    if ($enrichedData['entity_type'] === 'user') {
                        $mutualCount = $this->getMutualConnectionCount(
                            $connection->following_id,
                            $connection->follower_id
                        );
                    }
                    
                    return [
                        'id' => $connection->id,
                        'requester_id' => $enrichedData['id'],
                        'name' => $enrichedData['name'],
                        'usertype' => $enrichedData['usertype'],
                        'image' => $enrichedData['image'],
                        'headline' => $enrichedData['headline'],
                        'entity_type' => $enrichedData['entity_type'],
                        'mutual_connections' => $mutualCount,
                        'requested_at' => $connection->created_at,
                        'time_ago' => $connection->created_at->diffForHumans(),
                        'type' => 'follow_request',
                        'status' => $connection->status,
                    ];
                })
                ->filter()
                ->values();
        }
        
        return $pendingRequests;
    }

    /**
     * Get user's connection IDs
     */
    private function getUserConnections($user)
    {
        $connectionIds = [];
        
        if ($user instanceof User) {
            $following = UserConnection::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->pluck('following_id')
                ->toArray();
            
            $followers = UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->pluck('follower_id')
                ->toArray();
            
            $connectionIds = array_unique(array_merge($following, $followers));
            
            $followedCompanies = FavouriteCompany::where('user_id', $user->id)
                ->pluck('company_id')
                ->toArray();
            
            $connectionIds = array_merge($connectionIds, $followedCompanies);
        } else {
            $followers = FavouriteCompany::where('company_id', $user->id)
                ->pluck('user_id')
                ->toArray();
            
            $connectionIds = $followers;
        }
        
        return array_unique($connectionIds);
    }

    /**
     * Check connection status
     */
    private function checkIfConnected($currentUser, $targetId)
    {
        if (!$targetId) {
            return 'none';
        }
        
        if ($currentUser->id == $targetId) {
            return 'self';
        }
    
        if ($currentUser instanceof User) {
            $isCompany = Company::where('id', $targetId)->exists();
            if ($isCompany) {
                $favouriteCompany = FavouriteCompany::where('user_id', $currentUser->id)
                    ->where('company_id', $targetId)
                    ->exists();
                
                if ($favouriteCompany) {
                    return 'following';
                }
                
                $blockedCompany = UserConnection::where('follower_id', $currentUser->id)
                    ->where('following_id', $targetId)
                    ->where('status', 'blocked')
                    ->exists();
                
                if ($blockedCompany) {
                    return 'blocked';
                }
                
                return 'none';
            }
    
            $connection = UserConnection::where(function ($query) use ($currentUser, $targetId) {
                    $query->where('follower_id', $currentUser->id)
                          ->where('following_id', $targetId);
                })
                ->orWhere(function ($query) use ($currentUser, $targetId) {
                    $query->where('following_id', $currentUser->id)
                          ->where('follower_id', $targetId);
                })
                ->first();
    
            if ($connection) {
                if ($connection->follower_id == $currentUser->id) {
                    return $connection->status;
                } else {
                    if ($connection->status == 'accepted') {
                        return 'accepted';
                    } elseif ($connection->status == 'pending') {
                        return 'pending_from_them';
                    } elseif ($connection->status == 'blocked') {
                        return 'blocked_by_them';
                    }
                }
            }
    
            return 'none';
    
        } else {
            $favouriteCompany = FavouriteCompany::where('company_id', $targetId)
                ->where('user_id', $currentUser->id)
                ->exists();
            
            return $favouriteCompany ? 'accepted' : 'none';
        }
    }

    /**
     * Get filtered posts - MAIN FEED WITH RANDOM ORDERING
     * Posts are shuffled on every request for a fresh feed experience
     */
    private function getFilteredPosts($user, $request, $connectionIds)
    {
        $perPage = $request->per_page ?? 20;
        $page = $request->page ?? 1;
        $shuffle = $request->shuffle ?? true; // Default to true for random feed
        
        $query = Post::with([
            'user',
            'postType',
            'category',
            'subcategory',
        ])
        ->withCount(['likes', 'comments', 'shares', 'views'])
        ->where('is_active', true)
        ->where('is_published', true);

        // Apply filters
        if ($request->filter == 'featured') {
            $query->where('category_id', 5);
        } elseif ($request->filter == 'trending') {
            $sevenDaysAgo = Carbon::now()->subDays(7);
            $query->where('created_at', '>=', $sevenDaysAgo)
                ->orderByRaw('(likes_count * 1 + comments_count * 2 + shares_count * 3 + views_count * 0.1) DESC');
        } elseif ($request->filter == 'random') {
            // Explicit random filter - always random
            $query->inRandomOrder();
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->post_type_id) {
            $query->where('post_type_id', $request->post_type_id);
        }

        // Show only posts from connections if requested
        if ($request->show_connections_only && !empty($connectionIds)) {
            $query->whereIn('user_id', $connectionIds);
        }

        // Apply ordering - RANDOM for non-trending filters (for fresh feed every time)
        if ($request->filter == 'trending') {
            // Trending already has orderByRaw applied above
            // No additional ordering needed
        } elseif ($request->filter == 'random') {
            // Random already has inRandomOrder applied above
            // No additional ordering needed
        } else {
            // For all other filters (all, featured, or no filter), use random ordering
            // This ensures posts are shuffled on every request
            if ($shuffle) {
                $query->inRandomOrder(); // MySQL RAND() for random ordering
            } else {
                $query->orderBy('created_at', 'desc'); // Fallback to chronological
            }
        }

        // Get paginated results
        $posts = $query->paginate($perPage, ['*'], 'page', $page);

        // Format posts for response
        $formattedPosts = $posts->map(function($post) use ($user) {
            return $this->formatPostForDashboard($post, $user);
        });

        return [
            'posts' => $formattedPosts->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'shuffled' => true, // Indicate that results are shuffled
            ]
        ];
    }

    /**
     * Get trending posts - EXCLUDES posts from main feed
     */
    private function getTrendingPosts($user, $connectionIds, $excludedPostIds = [])
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        $query = Post::with(['user', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('created_at', '>=', $sevenDaysAgo);
        
        if (!empty($excludedPostIds)) {
            $query->whereNotIn('id', $excludedPostIds);
        }
        
        $trending = $query->orderByRaw('(likes_count * 1 + comments_count * 2 + shares_count * 3 + views_count * 0.1) DESC')
            ->limit(5)
            ->get()
            ->map(function($post) use ($user) {
                return $this->formatPostForDashboard($post, $user);
            });
        
        return $trending->values();
    }

    /**
     * Get featured/job posts - EXCLUDES posts from main feed and trending
     */
    private function getFeaturedPosts($user, $connectionIds, $excludedPostIds = [])
    {
        $query = Post::with(['user', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('category_id', 5);
        
        if (!empty($excludedPostIds)) {
            $query->whereNotIn('id', $excludedPostIds);
        }
        
        $featured = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($post) use ($user) {
                $formattedPost = $this->formatPostForDashboard($post, $user);
                $formattedPost['is_job_post'] = true;
                return $formattedPost;
            });
        
        return $featured->values();
    }

    /**
     * Get user stats
     */
    private function getUserStats($user)
    {
        $postCount = Post::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        
        $postLikes = PostLike::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        $postComments = PostComment::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        $postShares = PostShare::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        // Count how many times user's posts have been reposted
        $userPostIds = Post::where('user_id', $user->id)->pluck('id');
        $repostsReceived = PostRepost::whereIn('original_post_id', $userPostIds)->count();
        
        // Count how many reposts the user has made
        $repostsMade = PostRepost::where('user_id', $user->id)->count();
        
        if ($user instanceof User) {
            $followingCount = UserConnection::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            
            $followerCount = UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            
            $companyFollowCount = FavouriteCompany::where('user_id', $user->id)->count();
        } else {
            $followingCount = 0;
            $followerCount = FavouriteCompany::where('company_id', $user->id)->count();
            $companyFollowCount = 0;
        }
        
        $viewCount = PostView::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        return [
            'posts' => $this->formatNumber($postCount),
            'likes' => $this->formatNumber($postLikes),
            'comments' => $this->formatNumber($postComments),
            'shares' => $this->formatNumber($postShares),
            'reposts_received' => $this->formatNumber($repostsReceived),
            'reposts_made' => $this->formatNumber($repostsMade),
            'views' => $this->formatNumber($viewCount),
            'following' => $this->formatNumber($followingCount),
            'followers' => $this->formatNumber($followerCount),
            'following_companies' => $this->formatNumber($companyFollowCount),
        ];
    }

    /**
     * Get network updates - FIXED no "Unknown User"
     */
    private function getNetworkUpdates($user)
    {
        $connectionIds = $this->getUserConnections($user);
        $updates = [];
        
        $newPosts = Post::with('user')
            ->whereIn('user_id', $connectionIds)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($post) {
                $authorEntity = $this->getEntityById($post->user_id);
                $authorData = $this->getEnrichedEntityData($authorEntity);
                
                if (!$authorData) {
                    return null;
                }
                
                // Check if this post is a repost
                $isRepost = PostRepost::where('reposted_post_id', $post->id)->exists();
                
                return [
                    'type' => 'new_post',
                    'user_name' => $authorData['name'],
                    'user_type' => $authorData['usertype'],
                    'post_title' => $post->title,
                    'post_id' => $post->id,
                    'time_ago' => $post->created_at->diffForHumans(),
                    'user_image' => $authorData['image'],
                    'is_repost' => $isRepost,
                ];
            })
            ->filter()
            ->values();
        
        $updates = array_merge($updates, $newPosts->toArray());
        
        if ($user instanceof User) {
            $followedCompanyIds = FavouriteCompany::where('user_id', $user->id)
                ->pluck('company_id')
                ->toArray();
            
            $newJobs = Post::with('user')
                ->whereIn('user_id', $followedCompanyIds)
                ->where('is_active', true)
                ->where('is_published', true)
                ->where('category_id', 5)
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($post) {
                    $authorEntity = $this->getEntityById($post->user_id);
                    $authorData = $this->getEnrichedEntityData($authorEntity);
                    
                    if (!$authorData) {
                        return null;
                    }
                    
                    return [
                        'type' => 'new_job',
                        'company_name' => $authorData['name'],
                        'job_title' => $post->title,
                        'post_id' => $post->id,
                        'time_ago' => $post->created_at->diffForHumans(),
                        'company_logo' => $authorData['image'],
                    ];
                })
                ->filter()
                ->values();
            
            $updates = array_merge($updates, $newJobs->toArray());
        }
        
        usort($updates, function($a, $b) {
            return strtotime($b['time_ago']) - strtotime($a['time_ago']);
        });
        
        return array_slice($updates, 0, 10);
    }

    /**
     * Get job opportunities
     */
    private function getJobOpportunities($user)
    {
        if ($user instanceof Company) {
            $jobs = Job::where('company_id', $user->id)
                ->where('is_active', true)
                ->with('company')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $job->company->name,
                        'company_logo' => $job->company->logo ? asset('company_logos/' . $job->company->logo) : null,
                        'location' => $job->location,
                        'type' => $job->type,
                        'created_at' => $job->created_at->diffForHumans(),
                        'salary' => $job->hide_salary ? 'Confidential' : 
                            ($job->salary_from ? $job->salary_currency . ' ' . $job->salary_from . ' - ' . $job->salary_to : 'Negotiable'),
                        'is_active' => $job->is_active,
                    ];
                });
        } else {
            $userIndustry = $user->industry ?? null;
            $userSkills = $user->specialization ? explode(',', $user->specialization) : [];
            
            $jobs = Job::with('company')
                ->where('is_active', true)
                ->where(function($q) use ($userIndustry, $userSkills) {
                    if ($userIndustry) {
                        $q->where('title', 'like', "%{$userIndustry}%")
                          ->orWhere('description', 'like', "%{$userIndustry}%");
                    }
                    
                    if (!empty($userSkills)) {
                        $q->orWhereHas('jobSkills', function($skillQuery) use ($userSkills) {
                            $skillQuery->whereIn('job_skill_id', $userSkills);
                        });
                    }
                })
                ->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $job->company->name ?? 'N/A',
                        'company_logo' => optional($job->company)->logo
                            ? asset('company_logos/' . $job->company->logo)
                            : null,
                        'location' => $job->location,
                        'type' => $job->type,
                        'created_at' => $job->created_at->diffForHumans(),
                        'salary' => $job->hide_salary ? 'Confidential' : 
                            ($job->salary_from ? $job->salary_currency . ' ' . $job->salary_from . ' - ' . $job->salary_to : 'Negotiable'),
                        'is_featured' => $job->is_featured,
                        'application_deadline' => $job->expiry_date,
                        'days_left' => $job->expiry_date ? Carbon::parse($job->expiry_date)->diffInDays(Carbon::now()) : null,
                    ];
                });
        }
        
        return $jobs->values();
    }

    /**
     * Get message stats
     */
    private function getMessageStats($user)
    {
        $unreadCount = UserMessage::where('to_id', $user->id)
            ->where('is_read', false)
            ->where('status', 'active')
            ->count();
        
        $unreadByType = UserMessage::where('to_id', $user->id)
            ->where('is_read', false)
            ->where('status', 'active')
            ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
            ->select('chat_types.slug', DB::raw('COUNT(*) as count'))
            ->groupBy('chat_types.slug')
            ->pluck('count', 'slug');
        
        $activeConversations = 0;
        
        $pendingConnections = 0;
        if ($user instanceof User) {
            $pendingConnections = UserConnection::where('following_id', $user->id)
                ->where('status', 'pending')
                ->count();
        }
        
        return [
            'unread_total' => $unreadCount,
            'unread_by_type' => $unreadByType,
            'active_conversations' => $activeConversations,
            'pending_connections' => $pendingConnections,
        ];
    }

    /**
     * Get connection suggestions
     */
    private function getConnectionSuggestions($user)
    {
        $suggestions = collect();
        
        if ($user instanceof User) {
            $followingIds = UserConnection::where('follower_id', $user->id)
                ->where('status', 'accepted')
                ->pluck('following_id')
                ->toArray();
            
            $followerIds = UserConnection::where('following_id', $user->id)
                ->where('status', 'accepted')
                ->pluck('follower_id')
                ->toArray();
            
            $allConnections = array_unique(array_merge($followingIds, $followerIds));
            $allConnections[] = $user->id;
            
            $userInterests = is_array($user->area_of_interest_id)
                ? $user->area_of_interest_id
                : (json_decode($user->area_of_interest_id ?? '[]', true) ?: []);

            $userSkills = $user->specialization ? explode(',', $user->specialization) : [];
            
            $suggestedUsers = User::where('is_active', 1)
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $allConnections)
                ->where(function($q) use ($userInterests, $userSkills, $user) {
                    if ($user->industry) {
                        $q->where('industry', 'like', "%{$user->industry}%");
                    }
                    
                    if (!empty($userInterests)) {
                        $q->orWhere(function($interestQ) use ($userInterests) {
                            foreach ($userInterests as $interestId) {
                                $interestQ->orWhere('area_of_interest_id', 'like', "%{$interestId}%");
                            }
                        });
                    }
                    
                    if (!empty($userSkills)) {
                        $q->orWhere(function($skillQ) use ($userSkills) {
                            foreach ($userSkills as $skillId) {
                                $skillQ->orWhere('specialization', 'like', "%{$skillId}%");
                            }
                        });
                    }
                    
                    if ($user->location) {
                        $q->orWhere('location', 'like', "%{$user->location}%");
                    }
                })
                ->select('id', 'first_name', 'last_name', 'usertype', 'headline', 'image', 'location', 'industry')
                ->inRandomOrder() // Randomize suggestions
                ->limit(5)
                ->get()
                ->map(function($suggestedUser) use ($user) {
                    $fullName = trim(($suggestedUser->first_name ?? '') . ' ' . ($suggestedUser->last_name ?? ''));
                    
                    return [
                        'id' => $suggestedUser->id,
                        'name' => $fullName,
                        'usertype' => $suggestedUser->usertype,
                        'headline' => $suggestedUser->headline,
                        'image' => $suggestedUser->image ? asset('user_images/' . $suggestedUser->image) : null,
                        'location' => $suggestedUser->location,
                        'industry' => $suggestedUser->industry,
                        'mutual_connections' => $this->getMutualConnectionCount($user->id, $suggestedUser->id),
                        'connection_status' => $this->checkIfConnected($user, $suggestedUser->id),
                        'entity_type' => 'user',
                    ];
                });
            
            $suggestions = $suggestions->merge($suggestedUsers);
            
            $followedCompanyIds = FavouriteCompany::where('user_id', $user->id)
                ->pluck('company_id')
                ->toArray();
            
            $blockedCompanyIds = UserConnection::where('follower_id', $user->id)
                ->where('status', 'blocked')
                ->pluck('following_id')
                ->toArray();
            
            $suggestedCompanies = Company::where('is_active', 1)
                ->whereNotIn('id', $followedCompanyIds)
                ->whereNotIn('id', $blockedCompanyIds)
                ->whereNotIn('id', $allConnections)
                ->inRandomOrder()
                ->limit(3)
                ->get()
                ->map(function($company) use ($user) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'usertype' => 'company',
                        'headline' => $company->description,
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'location' => $company->location,
                        'industry' => $company->industry,
                        'mutual_connections' => 0,
                        'connection_status' => $this->checkIfConnected($user, $company->id),
                        'entity_type' => 'company',
                    ];
                });
            
            $suggestions = $suggestions->merge($suggestedCompanies);
        }
        
        return $suggestions->shuffle()->take(5)->values();
    }

    /**
     * Format post for dashboard - USING POST_REPOSTS TABLE
     */
    private function formatPostForDashboard($post, $currentUser)
    {
        $isLiked = PostLike::where('post_id', $post->id)
            ->where('user_id', $currentUser->id)
            ->exists();
        
        $images = $post->images ? json_decode($post->images, true) : [];
        $formattedImages = array_map(function($image) {
            return asset('post_images/' . $image);
        }, $images);
        
        $thumbnail = !empty($formattedImages) ? $formattedImages[0] : null;
        
        preg_match_all('/#(\w+)/', $post->content, $hashtags);
        $tags = $hashtags[1] ?? [];
        
        $stats = [
            'likes' => $post->likes_count ?? 0,
            'comments' => $post->comments_count ?? 0,
            'shares' => $post->shares_count ?? 0,
            'reposts' => $post->repost_count ?? 0,
            'views' => $post->views_count ?? 0,
        ];
        
        $isJobPost = $post->category_id == 5;
        
        $authorEntity = $this->getEntityById($post->user_id);
        $authorData = $this->getEnrichedEntityData($authorEntity);
        
        if (!$authorData) {
            $authorData = [
                'id' => $post->user_id,
                'name' => 'Unknown User',
                'email' => null,
                'usertype' => 'unknown',
                'headline' => null,
                'image' => null,
                'slug' => null,
                'entity_type' => 'unknown',
            ];
        }
        
        $isConnected = $this->checkIfConnected($currentUser, $authorData['id']);

        // Check if this post is a repost by looking in post_reposts table
        $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
        $isRepost = !is_null($repostRecord);
        
        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'thumbnail' => $thumbnail,
            'images' => $formattedImages,
            'is_liked' => $isLiked,
            'is_connected' => $isConnected,
            'is_job_post' => $isJobPost,
            'is_repost' => $isRepost,
            'created_at' => $post->created_at->diffForHumans(),
            'created_at_raw' => $post->created_at,
            'category' => $post->category ? $post->category->name : null,
            'subcategory' => $post->subcategory ? $post->subcategory->name : null,
            'tags' => $tags,
            'stats' => $stats,
            'author' => $authorData,
            'recent_likes' => [],
            'recent_comments' => [],
        ];
        
        // Add repost information if this is a repost
        if ($isRepost && $repostRecord) {
            // Get the original post
            $originalPost = Post::find($repostRecord->original_post_id);
            
            if ($originalPost) {
                // Get original author data
                $originalAuthorEntity = $this->getEntityById($originalPost->user_id);
                $originalAuthorData = $this->getEnrichedEntityData($originalAuthorEntity);
                
                if (!$originalAuthorData) {
                    $originalAuthorData = [
                        'id' => $originalPost->user_id,
                        'name' => 'Unknown User',
                        'usertype' => 'unknown',
                        'image' => null,
                    ];
                }
                
                $formatted['repost_info'] = [
                    'repost_record_id' => $repostRecord->id,
                    'original_post_id' => $originalPost->id,
                    'original_post_title' => $originalPost->title,
                    'original_post_content' => $originalPost->content,
                    'original_post_created_at' => $originalPost->created_at->diffForHumans(),
                    'original_author' => $originalAuthorData,
                    'repost_comment' => $repostRecord->repost_comment,
                    'reposted_at' => $post->created_at,
                    'reposted_at_formatted' => $post->created_at->diffForHumans(),
                    'reposted_by' => [
                        'id' => $authorData['id'],
                        'name' => $authorData['name'],
                        'usertype' => $authorData['usertype'],
                        'image' => $authorData['image'],
                    ],
                ];
                
                // Also add a flag to show this in UI
                $formatted['display_type'] = 'repost';
                $formatted['repost_comment'] = $repostRecord->repost_comment;
            }
        } else {
            // Check if this post has been reposted by others
            $reposts = PostRepost::where('original_post_id', $post->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
            
            $repostCount = $post->repost_count ?? $reposts->count();
            
            $repostedBy = $reposts->map(function($repost) {
                $reposterData = $this->getEnrichedEntityData($repost->user);
                return [
                    'id' => $reposterData['id'],
                    'name' => $reposterData['name'],
                    'usertype' => $reposterData['usertype'],
                    'image' => $reposterData['image'],
                    'reposted_at' => $repost->created_at->diffForHumans(),
                    'repost_comment' => $repost->repost_comment,
                ];
            });
            
            $formatted['repost_stats'] = [
                'total_reposts' => $repostCount,
                'reposted_by' => $repostedBy,
            ];
        }

        return $formatted;
    }

    /**
     * Format user data
     */
    private function formatUserData($user)
    {
        $displayName = $user instanceof Company 
            ? $user->name 
            : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        
        $image = null;
        if ($user instanceof Company) {
            $image = $user->logo ? asset('company_logos/' . $user->logo) : null;
        } else {
            $image = $user->image ? asset('user_images/' . $user->image) : null;
        }
        
        $data = [
            'id' => $user->id,
            'name' => $displayName,
            'email' => $user->email,
            'usertype' => $user instanceof Company ? 'company' : $user->usertype,
            'image' => $image,
            'headline' => $user->headline ?? ($user instanceof Company ? $user->description : null),
            'location' => $user->location ?? null,
            'industry' => $user->industry ?? null,
            'profile_completion' => $this->calculateProfileCompletion($user),
            'visibility_control' => $user->visibility_control ?? 'public',
        ];
        
        if ($user instanceof User) {
            $data['college_name'] = $user->college_name;
            $data['degree'] = $user->degree;
            $data['portfolio_website'] = $user->portfolio_website;
        } else {
            $data['website'] = $user->website;
            $data['description'] = $user->description;
        }
        
        return $data;
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user)
    {
        $fields = [];
        $completed = 0;
        
        if ($user instanceof Company) {
            $fields = ['name', 'email', 'logo', 'description', 'location', 'website', 'phone', 'industry_id'];
        } else {
            $fields = ['first_name', 'last_name', 'email', 'image', 'headline', 'location', 'industry', 'college_name', 'degree'];
        }
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }
        
        $totalFields = count($fields);
        return $totalFields > 0 ? round(($completed / $totalFields) * 100) : 0;
    }

    /**
     * Get mutual connection count
     */
    private function getMutualConnectionCount($userId1, $userId2)
    {
        $user1Connections = UserConnection::where('follower_id', $userId1)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        $user2Connections = UserConnection::where('follower_id', $userId2)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        $mutualConnections = array_intersect($user1Connections, $user2Connections);
        
        return count($mutualConnections);
    }

    /**
     * Format numbers with K, M suffixes
     */
    private function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        
        return (string) $number;
    }
    
    /**
     * Get detailed repost information for a specific post
     */
    public function getPostRepostDetails($postId)
    {
        try {
            $user = Auth::user();
            
            $post = Post::where('id', $postId)
                ->where('is_active', true)
                ->first();
                
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }
            
            // Check if this post is a repost
            $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
            
            if ($repostRecord) {
                // This post is a repost
                $originalPost = Post::find($repostRecord->original_post_id);
                $reposterData = $this->getEnrichedEntityData($user);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Repost details retrieved',
                    'data' => [
                        'is_repost' => true,
                        'repost_info' => [
                            'repost_record_id' => $repostRecord->id,
                            'reposted_by' => $reposterData,
                            'repost_comment' => $repostRecord->repost_comment,
                            'reposted_at' => $post->created_at,
                            'reposted_at_formatted' => $post->created_at->diffForHumans(),
                        ],
                        'original_post' => $originalPost ? [
                            'id' => $originalPost->id,
                            'title' => $originalPost->title,
                            'content' => $originalPost->content,
                            'created_at' => $originalPost->created_at->diffForHumans(),
                            'author' => $this->getEnrichedEntityData($this->getEntityById($originalPost->user_id)),
                            'stats' => [
                                'likes' => $originalPost->likes_count,
                                'comments' => $originalPost->comments_count,
                                'reposts' => $originalPost->repost_count,
                            ],
                        ] : null,
                    ]
                ]);
            } else {
                // This is an original post, get all its reposts
                $reposts = PostRepost::where('original_post_id', $post->id)
                    ->with(['user', 'repostedPost'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                    
                $formattedReposts = $reposts->map(function($repost) {
                    $reposterData = $this->getEnrichedEntityData($repost->user);
                    $repostedPost = $repost->repostedPost;
                    
                    return [
                        'id' => $repost->id,
                        'repost_comment' => $repost->repost_comment,
                        'reposted_by' => $reposterData,
                        'reposted_post' => [
                            'id' => $repostedPost->id,
                            'title' => $repostedPost->title,
                            'content' => $repostedPost->content,
                            'created_at' => $repostedPost->created_at->diffForHumans(),
                        ],
                        'reposted_at' => $repost->created_at,
                        'reposted_at_formatted' => $repost->created_at->diffForHumans(),
                    ];
                });
                
                return response()->json([
                    'success' => true,
                    'message' => 'Post reposts retrieved',
                    'data' => [
                        'is_repost' => false,
                        'post_id' => $post->id,
                        'total_reposts' => $post->repost_count,
                        'reposts' => $formattedReposts,
                        'pagination' => [
                            'current_page' => $reposts->currentPage(),
                            'per_page' => $reposts->perPage(),
                            'total' => $reposts->total(),
                            'last_page' => $reposts->lastPage(),
                        ]
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get repost details',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }
}