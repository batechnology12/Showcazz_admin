<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Post;
use App\Job;
use App\UserConnection;
use App\UserMessage;
use App\Models\ChatSession;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\Models\PostRepost;
use App\PostView;
use App\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    /**
     * Get dashboard data with filters - RANDOM POSTS ON EVERY REQUEST
     * With visibility control: public posts visible to all, private posts only to connections
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
            
            // Get user's connections (accepted connections)
            $connectionIds = $this->getUserConnections($user);
            
            // Get blocked user IDs
            $blockedIds = $this->getBlockedUserIds($user->id); // Users I blocked
            $blockedByIds = $this->getBlockerIds($user->id); // Users who blocked me
            
            // Merge all excluded user IDs
            $excludedUserIds = array_unique(array_merge($blockedIds, $blockedByIds));
            
            // Create a map of user visibility settings
            $visibilityMap = $this->getVisibilityMap($excludedUserIds);
            
            // Get main feed posts
            $posts = $this->getFilteredPosts($user, $request, $connectionIds, $excludedUserIds, $visibilityMap);
            
            // Get IDs of posts already in main feed to exclude from other sections
            $excludedPostIds = collect($posts['posts'])->pluck('id')->toArray();
            
            // Get trending posts
            $trendingPosts = $this->getTrendingPosts($user, $connectionIds, $excludedPostIds, $excludedUserIds, $visibilityMap);
            
            // Get featured posts
            $allExcludedIds = array_merge($excludedPostIds, collect($trendingPosts)->pluck('id')->toArray());
            $featuredPosts = $this->getFeaturedPosts($user, $connectionIds, $allExcludedIds, $excludedUserIds, $visibilityMap);
            
            // Get user stats
            $userStats = $this->getUserStats($user);
            
            // Get network updates
            $networkUpdates = $this->getNetworkUpdates($user, $excludedUserIds, $visibilityMap);
            
            // Get job opportunities
            $jobOpportunities = $this->getJobOpportunities($user);
            
            // Get message stats
            $messageStats = $this->getMessageStats($user, $excludedUserIds, $visibilityMap);
            
            // Get connection suggestions
            $connectionSuggestions = $this->getConnectionSuggestions($user, $excludedUserIds, $visibilityMap);
            
            // Get pending requests
            $pendingRequests = $this->getPendingRequests($user, $excludedUserIds, $visibilityMap);
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
                        'shuffled' => true,
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
     * Get visibility map for all users
     */
    private function getVisibilityMap($excludedUserIds = [])
    {
        $users = User::select('id', 'visibility_control', 'post_visibility_control', 'message_visibility_control', 'usertype')
            ->whereNotIn('id', $excludedUserIds)
            ->get();
        
        $visibilityMap = [];
        foreach ($users as $u) {
            $visibilityMap[$u->id] = [
                'type' => $u->usertype,
                'profile' => $u->visibility_control ?? 'public',
                'post' => $u->post_visibility_control ?? 'public',
                'message' => $u->message_visibility_control ?? 'public',
            ];
        }
        
        return $visibilityMap;
    }

    /**
     * Get blocked user IDs from BlockedUser table
     */
    private function getBlockedUserIds($userId)
    {
        return BlockedUser::where('blocker_id', $userId)
            ->pluck('blocked_id')
            ->toArray();
    }

    /**
     * Get users who have blocked current user from BlockedUser table
     */
    private function getBlockerIds($userId)
    {
        return BlockedUser::where('blocked_id', $userId)
            ->pluck('blocker_id')
            ->toArray();
    }

    /**
     * Check if user is blocked by either side
     */
    private function isUserBlocked($currentUserId, $targetUserId)
    {
        if (BlockedUser::isBlocked($currentUserId, $targetUserId)) {
            return true;
        }
        
        if (BlockedUser::isBlocked($targetUserId, $currentUserId)) {
            return true;
        }
        
        return false;
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
        
        $displayName = $user->usertype === 'company' 
            ? ($user->company_name ?? $user->name) 
            : $user->getName();
        
        return [
            'message' => $timeOfDay . ', ' . $displayName,
            'time_of_day' => strtolower(str_replace('Good ', '', $timeOfDay)),
            'display_name' => $displayName,
            'headline' => $user->usertype === 'company' ? ($user->company_description ?? $user->headline) : $user->headline,
        ];
    }

    /**
     * Get user's connection IDs (accepted connections) - USING ONLY user_connections
     */
    private function getUserConnections($user)
    {
        $connectionIds = [];
        
        // Get accepted followers and following
        $following = UserConnection::where('follower_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $followers = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        $connectionIds = array_unique(array_merge($following, $followers));
        
        return array_unique($connectionIds);
    }

    /**
     * Check connection status - USING ONLY user_connections
     */
    private function checkIfConnected($currentUser, $targetId)
    {
        if (!$targetId) {
            return 'none';
        }
        
        if ($currentUser->id == $targetId) {
            return 'self';
        }
    
        // Check if blocked in BlockedUser table
        if (BlockedUser::isBlocked($currentUser->id, $targetId)) {
            return 'blocked';
        }
        
        if (BlockedUser::isBlocked($targetId, $currentUser->id)) {
            return 'blocked_by_them';
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
                if ($connection->status == self::STATUS_ACCEPTED) {
                    return 'accepted';
                } elseif ($connection->status == self::STATUS_PENDING) {
                    return 'pending';
                }
            }
        }
    
        return 'none';
    }

    /**
     * Check if current user can view a post based on author's post visibility and connection status
     */
    private function canViewPost($currentUser, $authorId, $connectionIds, $excludedUserIds, $visibilityMap)
    {
        if ($currentUser->id == $authorId) {
            return true;
        }
        
        if (in_array($authorId, $excludedUserIds)) {
            return false;
        }
        
        $postVisibility = $visibilityMap[$authorId]['post'] ?? 'public';
        
        if ($postVisibility == 'public') {
            return true;
        }
        
        if ($postVisibility == 'private') {
            return in_array($authorId, $connectionIds);
        }
        
        return true;
    }

    /**
     * Check if current user can send message to author based on message visibility
     */
    private function canSendMessage($currentUser, $authorId, $connectionIds, $excludedUserIds, $visibilityMap)
    {
        if ($currentUser->id == $authorId) {
            return true;
        }
        
        if (in_array($authorId, $excludedUserIds)) {
            return false;
        }
        
        $messageVisibility = $visibilityMap[$authorId]['message'] ?? 'public';
        
        if ($messageVisibility == 'public') {
            return true;
        }
        
        if ($messageVisibility == 'private') {
            return in_array($authorId, $connectionIds);
        }
        
        return true;
    }

    /**
     * Check if current user can view profile based on profile visibility
     */
    private function canViewProfile($currentUser, $authorId, $connectionIds, $excludedUserIds, $visibilityMap)
    {
        if ($currentUser->id == $authorId) {
            return true;
        }
        
        if (in_array($authorId, $excludedUserIds)) {
            return false;
        }
        
        $profileVisibility = $visibilityMap[$authorId]['profile'] ?? 'public';
        
        if ($profileVisibility == 'public') {
            return true;
        }
        
        if ($profileVisibility == 'private') {
            return in_array($authorId, $connectionIds);
        }
        
        return true;
    }

    /**
     * Get enriched entity data - Works with User model only
     */
    private function getEnrichedEntityData($entity, $visibilityMap = [])
    {
        if (!$entity) {
            return null;
        }

        if ($entity->usertype === 'company') {
            return [
                'id' => $entity->id,
                'name' => $entity->company_name ?? $entity->name ?? 'Unknown Company',
                'email' => $entity->email ?? null,
                'usertype' => 'company',
                'headline' => $entity->company_description ?? $entity->headline,
                'image' => $entity->company_logo ? asset('company_logos/' . $entity->company_logo) : 
                         ($entity->image ? asset('user_images/' . $entity->image) : null),
                'slug' => $entity->company_slug ?? null,
                'visibility_control' => $visibilityMap[$entity->id]['profile'] ?? $entity->visibility_control ?? 'public',
                'post_visibility_control' => $visibilityMap[$entity->id]['post'] ?? $entity->post_visibility_control ?? 'public',
                'message_visibility_control' => $visibilityMap[$entity->id]['message'] ?? $entity->message_visibility_control ?? 'public',
                'entity_type' => 'company',
            ];
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
            'visibility_control' => $visibilityMap[$entity->id]['profile'] ?? $entity->visibility_control ?? 'public',
            'post_visibility_control' => $visibilityMap[$entity->id]['post'] ?? $entity->post_visibility_control ?? 'public',
            'message_visibility_control' => $visibilityMap[$entity->id]['message'] ?? $entity->message_visibility_control ?? 'public',
            'entity_type' => 'user',
        ];
    }

    /**
     * Get pending connection requests
     */
    private function getPendingRequests($user, $excludedUserIds = [], $visibilityMap = [])
    {
        $pendingRequests = collect();
        
        $pendingRequests = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_PENDING)
            ->whereNotIn('follower_id', $excludedUserIds)
            ->get()
            ->map(function($connection) use ($excludedUserIds, $visibilityMap) {
                $requester = User::find($connection->follower_id);
                
                if (!$requester) {
                    return null;
                }
                
                $enrichedData = $this->getEnrichedEntityData($requester, $visibilityMap);
                
                if (!$enrichedData) {
                    return null;
                }
                
                $mutualCount = $this->getMutualConnectionCount(
                    $connection->following_id,
                    $connection->follower_id
                );
                
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
        
        return $pendingRequests;
    }


    
    private function getFilteredPosts($user, $request, $connectionIds, $excludedUserIds = [], $visibilityMap = [])
    {
        $perPage = $request->per_page ?? 20;
        $page = $request->page ?? 1;
        $shuffle = $request->shuffle ?? false;
        
        // Filter connection IDs to exclude blocked users
        $filteredConnectionIds = array_diff($connectionIds, $excludedUserIds);
        
        $query = Post::with([
            'postType',
            'category',
            'subcategory',
            'taggedUsers',
        ])
        ->withCount(['likes', 'comments', 'shares', 'views'])
        ->where('is_active', true)
        ->where('post_type_id', 1)
        ->where('is_published', true);
    
        // Exclude posts from blocked users
        if (!empty($excludedUserIds)) {
            $query->whereNotIn('user_id', $excludedUserIds);
        }
    
        // Apply post visibility control
        $query->where(function($q) use ($user, $filteredConnectionIds) {
            // Include user's own posts
            $q->where('user_id', $user->id);
            
            // Include posts where author's post visibility is public
            $q->orWhereIn('user_id', function($userQ) {
                $userQ->select('id')
                    ->from('users')
                    ->where('post_visibility_control', 'public');
            });
            
            // Include posts from connections (users with private visibility but connected)
            if (!empty($filteredConnectionIds)) {
                $q->orWhereIn('user_id', $filteredConnectionIds);
            }
        });
    
        // Apply filters
        if ($request->filter == 'featured') {
            $query->where('category_id', 5);
        } elseif ($request->filter == 'trending') {
            $sevenDaysAgo = Carbon::now()->subDays(7);
            $query->where('created_at', '>=', $sevenDaysAgo)
                ->orderByRaw('(likes_count * 1 + comments_count * 2 + shares_count * 3 + views_count * 0.1) DESC');
        } elseif ($request->filter == 'random') {
            $query->inRandomOrder();
        }
    
        if ($request->search) {
            $search = $request->search;
        
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
        
                $q->orWhereHas('category', function ($cat) use ($search) {
                    $cat->where('name', 'like', "%{$search}%");
                });
        
                $q->orWhereHas('subcategory', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
        
                $q->orWhereIn('user_id', function ($subQuery) use ($search) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where(function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                        });
                });
            });
        }
    
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
    
        if ($request->post_type_id) {
            $query->where('post_type_id', $request->post_type_id);
        }
    
        if ($request->show_connections_only && !empty($filteredConnectionIds)) {
            $query->whereIn('user_id', $filteredConnectionIds);
        }
    
        if ($request->filter == 'trending' || $request->filter == 'random') {
            // Already ordered
        } else {
            if ($shuffle) {
                $query->inRandomOrder();
            } else {
                $query->orderBy('created_at', 'desc');
            }
        }
    
        $posts = $query->paginate($perPage, ['*'], 'page', $page);
    
        $formattedPosts = $posts->map(function($post) use ($user, $excludedUserIds, $visibilityMap, $filteredConnectionIds) {
            return $this->formatPostForDashboard($post, $user, $excludedUserIds, $visibilityMap, $filteredConnectionIds);
        });
    
        return [
            'posts' => $formattedPosts->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'shuffled' => true,
            ]
        ];
    }

    /**
     * Get trending posts
     */
    private function getTrendingPosts($user, $connectionIds, $excludedPostIds = [], $excludedUserIds = [], $visibilityMap = [])
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        $filteredConnectionIds = array_diff($connectionIds, $excludedUserIds);
        
        $query = Post::with(['user', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('created_at', '>=', $sevenDaysAgo);
        
        if (!empty($excludedUserIds)) {
            $query->whereNotIn('user_id', $excludedUserIds);
        }
        
        $query->where(function($q) use ($user, $filteredConnectionIds) {
            $q->where('user_id', $user->id)
              ->orWhereIn('user_id', function($userQ) {
                  $userQ->select('id')
                      ->from('users')
                      ->where('post_visibility_control', 'public');
              });
            
            if (!empty($filteredConnectionIds)) {
                $q->orWhereIn('user_id', $filteredConnectionIds);
            }
        });
        
        if (!empty($excludedPostIds)) {
            $query->whereNotIn('id', $excludedPostIds);
        }
        
        $trending = $query->orderByRaw('(likes_count * 1 + comments_count * 2 + shares_count * 3 + views_count * 0.1) DESC')
            ->limit(5)
            ->get()
            ->map(function($post) use ($user, $excludedUserIds, $visibilityMap, $filteredConnectionIds) {
                return $this->formatPostForDashboard($post, $user, $excludedUserIds, $visibilityMap, $filteredConnectionIds);
            });
        
        return $trending->values();
    }

    /**
     * Get featured/job posts
     */
    private function getFeaturedPosts($user, $connectionIds, $excludedPostIds = [], $excludedUserIds = [], $visibilityMap = [])
    {
        $filteredConnectionIds = array_diff($connectionIds, $excludedUserIds);
        
        $query = Post::with(['user', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('category_id', 5);
        
        if (!empty($excludedUserIds)) {
            $query->whereNotIn('user_id', $excludedUserIds);
        }
        
        $query->where(function($q) use ($user, $filteredConnectionIds) {
            $q->where('user_id', $user->id)
              ->orWhereIn('user_id', function($userQ) {
                  $userQ->select('id')
                      ->from('users')
                      ->where('post_visibility_control', 'public');
              });
            
            if (!empty($filteredConnectionIds)) {
                $q->orWhereIn('user_id', $filteredConnectionIds);
            }
        });
        
        if (!empty($excludedPostIds)) {
            $query->whereNotIn('id', $excludedPostIds);
        }
        
        $featured = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($post) use ($user, $excludedUserIds, $visibilityMap, $filteredConnectionIds) {
                $formattedPost = $this->formatPostForDashboard($post, $user, $excludedUserIds, $visibilityMap, $filteredConnectionIds);
                $formattedPost['is_job_post'] = true;
                return $formattedPost;
            });
        
        return $featured->values();
    }

    /**
     * Get user stats - FIXED: worth_discussing counts unique users per post
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
        
        $userPostIds = Post::where('user_id', $user->id)->pluck('id');
        $repostsReceived = PostRepost::whereIn('original_post_id', $userPostIds)->count();
        $repostsMade = PostRepost::where('user_id', $user->id)->count();
        
        // FIXED: Count unique users per post for worth discussing
        $worthDiscussing = 0;
        
        if ($userPostIds->isNotEmpty()) {
            foreach ($userPostIds as $postId) {
                $uniqueUsersForPost = DB::table('user_messages')
                    ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
                    ->where('chat_types.slug', 'worth_discussing')
                    ->where('user_messages.listing_id', $postId)
                    ->where(function($q) use ($user) {
                        $q->where('user_messages.from_id', $user->id)
                          ->orWhere('user_messages.to_id', $user->id);
                    })
                    ->select(DB::raw('CASE 
                        WHEN user_messages.from_id = ' . $user->id . ' THEN user_messages.to_id 
                        ELSE user_messages.from_id 
                    END as other_user_id'))
                    ->distinct()
                    ->get()
                    ->pluck('other_user_id')
                    ->unique()
                    ->count();
                
                $worthDiscussing += $uniqueUsersForPost;
            }
        }

        $followingCount = UserConnection::where('follower_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
        
        $followerCount = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
        
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
            'worth_discussing' => $this->formatNumber($worthDiscussing), // Now counts unique users per post
        ];
    }

    /**
     * Get network updates
     */
    private function getNetworkUpdates($user, $excludedUserIds = [], $visibilityMap = [])
    {
        $connectionIds = $this->getUserConnections($user);
        $filteredConnectionIds = array_diff($connectionIds, $excludedUserIds);
        $updates = [];
        
        $newPosts = Post::with('user')
            ->whereIn('user_id', $filteredConnectionIds)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($post) use ($excludedUserIds, $visibilityMap) {
                $authorEntity = User::find($post->user_id);
                $authorData = $this->getEnrichedEntityData($authorEntity, $visibilityMap);
                
                if (!$authorData) {
                    return null;
                }
                
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
        if ($user->usertype === 'company') {
            $jobs = Job::where('company_id', $user->id)
                ->where('is_active', true)
                ->with('company')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($job) use ($user) {
                    $companyData = $this->getEnrichedEntityData($user);
                    
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $companyData['name'],
                        'company_logo' => $companyData['image'],
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
                    $company = User::find($job->company_id);
                    $companyData = $company ? $this->getEnrichedEntityData($company) : null;
                    
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $companyData['name'] ?? 'N/A',
                        'company_logo' => $companyData['image'] ?? null,
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
    private function getMessageStats($user, $excludedUserIds = [], $visibilityMap = [])
    {
        $unreadCount = UserMessage::where('to_id', $user->id)
            ->where('is_read', false)
            ->where('status', 'active')
            ->whereNotIn('from_id', $excludedUserIds)
            ->count();
        
        $unreadByType = UserMessage::where('to_id', $user->id)
            ->where('is_read', false)
            ->where('status', 'active')
            ->whereNotIn('from_id', $excludedUserIds)
            ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
            ->select('chat_types.slug', DB::raw('COUNT(*) as count'))
            ->groupBy('chat_types.slug')
            ->pluck('count', 'slug');
        
        $activeConversations = ChatSession::where(function($q) use ($user, $excludedUserIds) {
                $q->where('user1_id', $user->id)
                  ->orWhere('user2_id', $user->id);
            })
            ->whereNotIn('user1_id', $excludedUserIds)
            ->whereNotIn('user2_id', $excludedUserIds)
            ->where('is_active', true)
            ->count();
        
        $pendingConnections = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_PENDING)
            ->whereNotIn('follower_id', $excludedUserIds)
            ->count();
        
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
    private function getConnectionSuggestions($user, $excludedUserIds = [], $visibilityMap = [])
    {
        $suggestions = collect();
        
        $followingIds = UserConnection::where('follower_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $followerIds = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        $allConnections = array_unique(array_merge($followingIds, $followerIds));
        $allConnections[] = $user->id;
        
        $allExclusions = array_unique(array_merge($allConnections, $excludedUserIds));
        
        $userInterests = is_array($user->area_of_interest_id)
            ? $user->area_of_interest_id
            : (json_decode($user->area_of_interest_id ?? '[]', true) ?: []);

        $userSkills = $user->specialization ? explode(',', $user->specialization) : [];
        
        $suggestedUsers = User::where('is_active', 1)
            ->where('visibility_control', 'public')
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', $allExclusions)
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
            ->select('id', 'first_name', 'last_name', 'company_name', 'usertype', 'headline', 'image', 'company_logo', 'location', 'industry', 'visibility_control', 'post_visibility_control', 'message_visibility_control')
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function($suggestedUser) use ($user, $excludedUserIds, $visibilityMap) {
                $fullName = $suggestedUser->usertype === 'company' 
                    ? ($suggestedUser->company_name ?? $suggestedUser->name)
                    : trim(($suggestedUser->first_name ?? '') . ' ' . ($suggestedUser->last_name ?? ''));
                
                $image = $suggestedUser->usertype === 'company'
                    ? ($suggestedUser->company_logo ? asset('company_logos/' . $suggestedUser->company_logo) : null)
                    : ($suggestedUser->image ? asset('user_images/' . $suggestedUser->image) : null);
                
                return [
                    'id' => $suggestedUser->id,
                    'name' => $fullName,
                    'usertype' => $suggestedUser->usertype,
                    'headline' => $suggestedUser->headline,
                    'image' => $image,
                    'location' => $suggestedUser->location,
                    'industry' => $suggestedUser->industry,
                    'visibility_control' => $suggestedUser->visibility_control,
                    'post_visibility_control' => $suggestedUser->post_visibility_control,
                    'message_visibility_control' => $suggestedUser->message_visibility_control,
                    'mutual_connections' => $this->getMutualConnectionCount($user->id, $suggestedUser->id),
                    'connection_status' => $this->checkIfConnected($user, $suggestedUser->id),
                    'entity_type' => $suggestedUser->usertype === 'company' ? 'company' : 'user',
                    'is_blocked' => in_array($suggestedUser->id, $excludedUserIds),
                ];
            });
        
        $suggestions = $suggestions->merge($suggestedUsers);
        
        return $suggestions->shuffle()->take(5)->values();
    }

   
 
    /**
     * Format post for dashboard - FIXED: worth_discussing counts unique users, includes files
     */
    private function formatPostForDashboard($post, $currentUser, $excludedUserIds = [], $visibilityMap = [], $connectionIds = [])
    {
        $isLiked = PostLike::where('post_id', $post->id)
            ->where('user_id', $currentUser->id)
            ->exists();
        
        $images = $post->images ? json_decode($post->images, true) : [];
        $formattedImages = array_map(function($image) {
            return env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image);
        }, $images);
        
        // ✅ NEW: Format files
        $files = $post->files ? json_decode($post->files, true) : [];
        $formattedFiles = array_map(function($file) {
            $filePath = public_path('post_files/' . $file);
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            
            return [
                'name' => $file,
                'url' => env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_files/' . $file) : asset('post_files/' . $file),
                'size' => $this->formatFileSize($fileSize),
                'size_bytes' => $fileSize,
                'extension' => $fileExtension,
                'icon' => $this->getFileIcon($fileExtension),
            ];
        }, $files);
        
        $thumbnail = !empty($formattedImages) ? $formattedImages[0] : null;
        
        // If no images but files exist, use file icon as thumbnail
        if (!$thumbnail && !empty($formattedFiles)) {
            $thumbnail = $formattedFiles[0]['icon'] ?? asset('images/file-icon.png');
        }
        
        preg_match_all('/#(\w+)/', $post->content, $hashtags);
        $tags = $hashtags[1] ?? [];
        
        // FIXED: Count unique users who have discussed this post with current user
        $worthDiscussing = DB::table('user_messages')
            ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
            ->where('chat_types.slug', 'worth_discussing')
            ->where('user_messages.listing_id', $post->id)
            ->where(function ($q) use ($currentUser) {
                $q->where('user_messages.from_id', $currentUser->id)
                  ->orWhere('user_messages.to_id', $currentUser->id);
            })
            ->select(DB::raw('CASE 
                WHEN user_messages.from_id = ' . $currentUser->id . ' THEN user_messages.to_id 
                ELSE user_messages.from_id 
            END as other_user_id'))
            ->distinct()
            ->get()
            ->pluck('other_user_id')
            ->unique()
            ->count();
        
        $stats = [
            'likes' => $post->likes_count ?? 0,
            'comments' => $post->comments_count ?? 0,
            'shares' => $post->shares_count ?? 0,
            'reposts' => $post->repost_count ?? 0,
            'views' => $post->views_count ?? 0,
            'worth_discussing' => $worthDiscussing ?? 0, // Now counts unique users
        ];
        
        $isJobPost = $post->category_id == 5;
        
        $authorEntity = User::find($post->user_id);
        $authorData = $this->getEnrichedEntityData($authorEntity, $visibilityMap);
        
        if (!$authorData) {
            $authorData = [
                'id' => $post->user_id,
                'name' => 'Unknown User',
                'email' => null,
                'usertype' => 'unknown',
                'headline' => null,
                'image' => null,
                'slug' => null,
                'visibility_control' => 'public',
                'post_visibility_control' => 'public',
                'message_visibility_control' => 'public',
                'entity_type' => 'unknown',
            ];
        }
        
        $isConnected = $this->checkIfConnected($currentUser, $authorData['id']);
        $isBlocked = in_array($authorData['id'], $excludedUserIds);
    
        $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
        $isRepost = !is_null($repostRecord);
        
        $canView = $this->canViewPost($currentUser, $authorData['id'], $connectionIds, $excludedUserIds, $visibilityMap);
        
        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'thumbnail' => $thumbnail,
            'images' => $formattedImages,
            // ✅ NEW: Add files to response
            'files' => $formattedFiles,
            'has_files' => !empty($formattedFiles),
            'has_images' => !empty($formattedImages),
            'is_liked' => $isLiked,
            'is_connected' => $isConnected,
            'is_blocked' => $isBlocked,
            'is_job_post' => $isJobPost,
            'is_repost' => $isRepost,
            'created_at' => $post->created_at->diffForHumans(),
            'created_at_raw' => $post->created_at,
            'category' => $post->category ? $post->category->name : null,
            'subcategory' => $post->subcategory ? $post->subcategory->name : null,
            'tags' => $tags,
            'stats' => $stats,
            'author' => $authorData,
            'tagged_users' => $post->taggedUsers->map(function ($taggedUser) use ($currentUser, $excludedUserIds, $visibilityMap) {
                    $enriched = $this->getEnrichedEntityData($taggedUser, $visibilityMap);
                
                    if (!$enriched) {
                        return null;
                    }
                
                    return [
                        'id'                 => $enriched['id'],
                        'name'               => $enriched['name'],
                        'usertype'           => $enriched['usertype'],
                        'entity_type'        => $enriched['entity_type'],
                        'image'              => $enriched['image'],
                        'headline'           => $enriched['headline'],
                        'slug'               => $enriched['slug'] ?? null,
                        'is_blocked'         => in_array($taggedUser->id, $excludedUserIds),
                        'connection_status'  => $this->checkIfConnected($currentUser, $taggedUser->id),
                    ];
                })->filter()->values(),
            'visibility_info' => [
                'author_visibility' => $authorData['post_visibility_control'],
                'is_visible' => $canView,
            ],
            'recent_likes' => [],
            'recent_comments' => [],
        ];
        
        if ($isRepost && $repostRecord) {
            $originalPost = Post::find($repostRecord->original_post_id);
            
            if ($originalPost) {
                $originalAuthorEntity = User::find($originalPost->user_id);
                $originalAuthorData = $this->getEnrichedEntityData($originalAuthorEntity, $visibilityMap);
                
                if (!$originalAuthorData) {
                    $originalAuthorData = [
                        'id' => $originalPost->user_id,
                        'name' => 'Unknown User',
                        'usertype' => 'unknown',
                        'image' => null,
                        'visibility_control' => 'public',
                        'post_visibility_control' => 'public',
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
                
                $formatted['display_type'] = 'repost';
                $formatted['repost_comment'] = $repostRecord->repost_comment;
            }
        } else {
            $reposts = PostRepost::where('original_post_id', $post->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
            
            $repostCount = $post->repost_count ?? $reposts->count();
            
            $repostedBy = $reposts->map(function ($repost) use ($excludedUserIds, $visibilityMap) {
                if (!$repost->user) {
                    return null;
                }
            
                $reposterData = $this->getEnrichedEntityData($repost->user, $visibilityMap);
            
                if (!$reposterData) {
                    return null;
                }
            
                return [
                    'id' => $reposterData['id'] ?? null,
                    'name' => $reposterData['name'] ?? null,
                    'usertype' => $reposterData['usertype'] ?? null,
                    'image' => $reposterData['image'] ?? null,
                    'reposted_at' => optional($repost->created_at)->diffForHumans(),
                    'repost_comment' => $repost->repost_comment,
                    'is_blocked' => in_array($reposterData['id'], $excludedUserIds),
                ];
            })->filter()->values();
            
            $formatted['repost_stats'] = [
                'total_reposts' => $repostCount,
                'reposted_by' => $repostedBy,
            ];
        }
    
        return $formatted;
    }
    
    /**
     * Format file size to human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Get file icon based on extension
     */
    private function getFileIcon($extension)
    {
        $extension = strtolower($extension);
        
        $icons = [
            'pdf' => asset('icons/pdf-icon.png'),
            'doc' => asset('icons/doc-icon.png'),
            'docx' => asset('icons/doc-icon.png'),
            'xls' => asset('icons/excel-icon.png'),
            'xlsx' => asset('icons/excel-icon.png'),
            'ppt' => asset('icons/ppt-icon.png'),
            'pptx' => asset('icons/ppt-icon.png'),
            'txt' => asset('icons/txt-icon.png'),
            'zip' => asset('icons/zip-icon.png'),
            'rar' => asset('icons/zip-icon.png'),
            'jpg' => asset('icons/image-icon.png'),
            'jpeg' => asset('icons/image-icon.png'),
            'png' => asset('icons/image-icon.png'),
            'gif' => asset('icons/image-icon.png'),
            'mp4' => asset('icons/video-icon.png'),
            'mp3' => asset('icons/audio-icon.png'),
        ];
        
        return $icons[$extension] ?? asset('icons/file-icon.png');
    }

    /**
     * Format user data
     */
    private function formatUserData($user)
    {
        $displayName = $user->usertype === 'company' 
            ? ($user->company_name ?? $user->name) 
            : $user->getName();
        
        $image = null;
        if ($user->usertype === 'company') {
            $image = $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                     ($user->image ? asset('user_images/' . $user->image) : null);
        } else {
            $image = $user->image ? asset('user_images/' . $user->image) : null;
        }
        
        $data = [
            'id' => $user->id,
            'name' => $displayName,
            'email' => $user->email,
            'usertype' => $user->usertype,
            'image' => $image,
            'headline' => $user->usertype === 'company' ? ($user->company_description ?? $user->headline) : $user->headline,
            'location' => $user->usertype === 'company' ? ($user->company_location ?? $user->location) : $user->location,
            'industry' => $user->usertype === 'company' ? $user->company_industry_id : $user->industry,
            'profile_completion' => $this->calculateProfileCompletion($user),
            'visibility_control' => $user->visibility_control ?? 'public',
            'post_visibility_control' => $user->post_visibility_control ?? 'public',
            'message_visibility_control' => $user->message_visibility_control ?? 'public',
        ];
        
        if ($user->usertype !== 'company') {
            $data['college_name'] = $user->college_name;
            $data['degree'] = $user->degree;
            $data['portfolio_website'] = $user->portfolio_website;
        } else {
            $data['website'] = $user->company_website;
            $data['description'] = $user->company_description;
            $data['slug'] = $user->company_slug;
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
        
        if ($user->usertype === 'company') {
            $fields = ['company_name', 'email', 'company_logo', 'company_description', 'company_location', 'company_website', 'phone', 'company_industry_id'];
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
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $user2Connections = UserConnection::where('follower_id', $userId2)
            ->where('status', self::STATUS_ACCEPTED)
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
            
            $blockedIds = $this->getBlockedUserIds($user->id);
            $blockedByIds = $this->getBlockerIds($user->id);
            $excludedUserIds = array_unique(array_merge($blockedIds, $blockedByIds));
            
            $visibilityMap = $this->getVisibilityMap($excludedUserIds);
            
            $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
            
            if ($repostRecord) {
                $originalPost = Post::find($repostRecord->original_post_id);
                $reposterData = $this->getEnrichedEntityData($user, $visibilityMap);
                
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
                            'author' => $this->getEnrichedEntityData(User::find($originalPost->user_id), $visibilityMap),
                            'stats' => [
                                'likes' => $originalPost->likes_count,
                                'comments' => $originalPost->comments_count,
                                'reposts' => $originalPost->repost_count,
                            ],
                        ] : null,
                    ]
                ]);
            } else {
                $reposts = PostRepost::where('original_post_id', $post->id)
                    ->with(['user'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                    
                $formattedReposts = $reposts->map(function($repost) use ($excludedUserIds, $visibilityMap) {
                    $reposterData = $this->getEnrichedEntityData($repost->user, $visibilityMap);
                    
                    return [
                        'id' => $repost->id,
                        'repost_comment' => $repost->repost_comment,
                        'reposted_by' => $reposterData,
                        'reposted_at' => $repost->created_at,
                        'reposted_at_formatted' => $repost->created_at->diffForHumans(),
                        'is_blocked' => in_array($reposterData['id'] ?? null, $excludedUserIds),
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