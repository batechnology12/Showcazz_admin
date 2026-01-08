<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\UserConnection;
use App\FavouriteCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;

class UniversalConnectionController extends Controller
{
    /**
     * 1. Get Follow Suggestions (Auto-detect both Users & Companies)
     */
    public function getSuggestions(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $suggestions = collect();
            
            // Get user's current connections for exclusion
            $followingUserIds = UserConnection::where('follower_id', $currentUser->id)
                ->where('status', 'accepted')
                ->pluck('following_id')
                ->toArray();
            
            $followedCompanyIds = FavouriteCompany::where('user_id', $currentUser->id)
                ->pluck('company_id')
                ->toArray();

            // Get blocked connections
            $blockedUserIds = UserConnection::where('follower_id', $currentUser->id)
                ->where('status', 'blocked')
                ->pluck('following_id')
                ->toArray();

            // Get user suggestions (50% of suggestions)
            $userLimit = ceil($perPage / 2);
            $userSuggestions = User::where('is_active', 1)
                ->where('id', '!=', $currentUser->id)
                ->whereNotIn('id', $followingUserIds)
                ->whereNotIn('id', $blockedUserIds)
                ->select('id', 'name', 'email', 'usertype', 'headline', 'image', 'created_at')
                ->inRandomOrder()
                ->limit($userLimit)
                ->get()
                ->map(function ($user) use ($currentUser) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'usertype' => $user->usertype,
                        'headline' => $user->headline,
                        'image' => $user->image ? asset('user_images/' . $user->image) : null,
                        'entity_type' => 'user',
                        'mutual_count' => $this->getMutualUserCount($currentUser->id, $user->id),
                        'connection_status' => $this->getUserConnectionStatus($currentUser->id, $user->id),
                    ];
                });
            
            $suggestions = $suggestions->merge($userSuggestions);

            // Get company suggestions (remaining 50%)
            $companyLimit = $perPage - $userSuggestions->count();
            if ($companyLimit > 0) {
                $companySuggestions = Company::where('is_active', 1)
                    ->whereNotIn('id', $followedCompanyIds)
                    ->select('id', 'name', 'email', 'slug', 'logo', 'description', 'created_at')
                    ->inRandomOrder()
                    ->limit($companyLimit)
                    ->get()
                    ->map(function ($company) use ($currentUser) {
                        return [
                            'id' => $company->id,
                            'name' => $company->name,
                            'email' => $company->email,
                            'slug' => $company->slug,
                            'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                            'description' => $company->description,
                            'entity_type' => 'company',
                            'followers_count' => FavouriteCompany::where('company_id', $company->id)->count(),
                            'is_following' => FavouriteCompany::where('user_id', $currentUser->id)
                                ->where('company_id', $company->id)
                                ->exists(),
                        ];
                    });
                
                $suggestions = $suggestions->merge($companySuggestions);
            }

            // Shuffle suggestions for better mix
            $suggestions = $suggestions->shuffle();

            return response()->json([
                'success' => true,
                'message' => 'Suggestions retrieved successfully',
                'data' => [
                    'suggestions' => $suggestions,
                    'total' => $suggestions->count(),
                    'user_count' => $userSuggestions->count(),
                    'company_count' => $suggestions->count() - $userSuggestions->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * 2. Get Mutual Connections (Auto-detect both Users & Companies)
     */
    public function getMutualConnections(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $perPage = $request->get('per_page', 20);

            $mutualConnections = collect();
            
            // A. Get mutual connections with other users
            // Get users I follow
            $userFollowingIds = UserConnection::where('follower_id', $currentUser->id)
                ->where('status', 'accepted')
                ->pluck('following_id')
                ->toArray();
            
            // For each user I follow, find our mutual connections
            foreach ($userFollowingIds as $userId) {
                $targetFollowingIds = UserConnection::where('follower_id', $userId)
                    ->where('status', 'accepted')
                    ->pluck('following_id')
                    ->toArray();
                
                $mutualUserIds = array_intersect($userFollowingIds, $targetFollowingIds);
                
                // Remove current user and target user from mutual list
                $mutualUserIds = array_diff($mutualUserIds, [$currentUser->id, $userId]);
                
                if (!empty($mutualUserIds)) {
                    $mutualUsers = User::whereIn('id', $mutualUserIds)
                        ->where('is_active', 1)
                        ->select('id', 'name', 'usertype', 'headline', 'image')
                        ->get()
                        ->map(function ($user) use ($userId) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'usertype' => $user->usertype,
                                'headline' => $user->headline,
                                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                                'entity_type' => 'user',
                                'mutual_with' => $userId,
                            ];
                        });
                    
                    $mutualConnections = $mutualConnections->merge($mutualUsers);
                }
            }

            // B. Get mutual company follows
            // Get companies I follow
            $followedCompanyIds = FavouriteCompany::where('user_id', $currentUser->id)
                ->pluck('company_id')
                ->toArray();
            
            // Find other users who follow same companies
            foreach ($followedCompanyIds as $companyId) {
                $otherFollowers = FavouriteCompany::where('company_id', $companyId)
                    ->where('user_id', '!=', $currentUser->id)
                    ->pluck('user_id')
                    ->toArray();
                
                if (!empty($otherFollowers)) {
                    $company = Company::find($companyId);
                    $mutualUsers = User::whereIn('id', $otherFollowers)
                        ->where('is_active', 1)
                        ->select('id', 'name', 'usertype', 'headline', 'image')
                        ->get()
                        ->map(function ($user) use ($company) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'usertype' => $user->usertype,
                                'headline' => $user->headline,
                                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                                'entity_type' => 'user',
                                'mutual_through' => [
                                    'type' => 'company',
                                    'company_id' => $company->id,
                                    'company_name' => $company->name,
                                ],
                            ];
                        });
                    
                    $mutualConnections = $mutualConnections->merge($mutualUsers);
                }
            }

            // Remove duplicates and limit
            $mutualConnections = $mutualConnections->unique('id')->take($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Mutual connections retrieved successfully',
                'data' => [
                    'mutual_connections' => $mutualConnections->values(),
                    'total' => $mutualConnections->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get mutual connections',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * 3. Universal Connection Actions (Follow, Unfollow, Accept, Reject, Block, Unblock)
     */
    public function handleConnection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'target_id' => 'required',
                'action' => 'required|in:follow,unfollow,accept,reject,block,unblock',
                'reason' => 'nullable|string|max:500',
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

            $currentUser = Auth::user();
            $targetId = $request->target_id;
            $action = $request->action;
            $reason = $request->reason;

            // Auto-detect entity type
            $entityType = $this->detectEntityType($targetId);
            
            if (!$entityType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target not found',
                    'errors' => (object)['target_id' => 'Target not found']
                ], 404);
            }

            // Check if trying to perform action on self (for users)
            if ($entityType == 'user' && $currentUser->id == $targetId) {
                $actionMessages = [
                    'follow' => 'follow yourself',
                    'accept' => 'accept your own request',
                    'reject' => 'reject your own request',
                    'block' => 'block yourself',
                ];
                
                if (isset($actionMessages[$action])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot ' . $actionMessages[$action],
                        'errors' => (object)['target_id' => 'Cannot ' . $actionMessages[$action]]
                    ], 400);
                }
            }

            DB::beginTransaction();

            try {
                $result = null;
                $message = '';

                if ($entityType == 'user') {
                    // Handle user connections
                    $targetUser = User::find($targetId);
                    
                    switch ($action) {
                        case 'follow':
                            $result = $this->followUser($currentUser->id, $targetId);
                            $message = 'Follow request sent to ' . $targetUser->name;
                            break;
                            
                        case 'unfollow':
                            $result = $this->unfollowUser($currentUser->id, $targetId);
                            $message = 'Unfollowed ' . $targetUser->name;
                            break;
                            
                        case 'accept':
                            $result = $this->acceptFollowRequest($currentUser->id, $targetId);
                            $message = 'Accepted follow request from ' . $targetUser->name;
                            break;
                            
                        case 'reject':
                            $result = $this->rejectFollowRequest($currentUser->id, $targetId);
                            $message = 'Rejected follow request from ' . $targetUser->name;
                            break;
                            
                        case 'block':
                            $result = $this->blockUser($currentUser->id, $targetId, $reason);
                            $message = 'Blocked ' . $targetUser->name;
                            break;
                            
                        case 'unblock':
                            $result = $this->unblockUser($currentUser->id, $targetId);
                            $message = 'Unblocked ' . $targetUser->name;
                            break;
                    }
                    
                    $result['target_details'] = [
                        'id' => $targetUser->id,
                        'name' => $targetUser->name,
                        'usertype' => $targetUser->usertype,
                        'image' => $targetUser->image ? asset('user_images/' . $targetUser->image) : null,
                    ];
                    
                } else if ($entityType == 'company') {
                    // Handle company connections
                    $company = Company::find($targetId);
                    
                    switch ($action) {
                        case 'follow':
                            $result = $this->followCompany($currentUser->id, $targetId);
                            $message = 'Started following ' . $company->name;
                            break;
                            
                        case 'unfollow':
                            $result = $this->unfollowCompany($currentUser->id, $targetId);
                            $message = 'Unfollowed ' . $company->name;
                            break;
                            
                        case 'accept':
                            // Companies don't have accept/reject for follows
                            throw new Exception('Companies cannot accept/reject follow requests');
                            
                        case 'reject':
                            // Companies don't have accept/reject for follows
                            throw new Exception('Companies cannot accept/reject follow requests');
                            
                        case 'block':
                            $result = $this->blockCompany($currentUser->id, $targetId, $reason);
                            $message = 'Blocked ' . $company->name;
                            break;
                            
                        case 'unblock':
                            $result = $this->unblockCompany($currentUser->id, $targetId);
                            $message = 'Unblocked ' . $company->name;
                            break;
                    }
                    
                    $result['target_details'] = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                    ];
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => array_merge($result, [
                        'entity_type' => $entityType,
                        'action' => $action,
                        'performed_by' => [
                            'id' => $currentUser->id,
                            'name' => $currentUser->name,
                        ]
                    ])
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform action: ' . $e->getMessage(),
                'errors' => (object)['server' => $e->getMessage()]
            ], 400);
        }
    }

    /**
     * 4. Get All Connections (Following + Followed Companies)
     */
    public function getAllConnections(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            $connections = collect();

            // Get following users
            $following = UserConnection::where('follower_id', $currentUser->id)
                ->where('status', 'accepted')
                ->with(['following:id,name,usertype,headline,image'])
                ->get()
                ->map(function ($conn) {
                    return [
                        'entity_type' => 'user',
                        'connection_type' => 'following',
                        'id' => $conn->following->id,
                        'name' => $conn->following->name,
                        'usertype' => $conn->following->usertype,
                        'headline' => $conn->following->headline,
                        'image' => $conn->following->image ? asset('user_images/' . $conn->following->image) : null,
                        'connected_at' => $conn->created_at,
                    ];
                });

            // Get follower users
            $followers = UserConnection::where('following_id', $currentUser->id)
                ->where('status', 'accepted')
                ->with(['follower:id,name,usertype,headline,image'])
                ->get()
                ->map(function ($conn) {
                    return [
                        'entity_type' => 'user',
                        'connection_type' => 'follower',
                        'id' => $conn->follower->id,
                        'name' => $conn->follower->name,
                        'usertype' => $conn->follower->usertype,
                        'headline' => $conn->follower->headline,
                        'image' => $conn->follower->image ? asset('user_images/' . $conn->follower->image) : null,
                        'connected_at' => $conn->created_at,
                    ];
                });

            // Get followed companies
            $companies = FavouriteCompany::where('user_id', $currentUser->id)
                ->with(['company:id,name,slug,logo,description'])
                ->get()
                ->map(function ($fav) {
                    return [
                        'entity_type' => 'company',
                        'connection_type' => 'following',
                        'id' => $fav->company->id,
                        'name' => $fav->company->name,
                        'slug' => $fav->company->slug,
                        'logo' => $fav->company->logo ? asset('company_logos/' . $fav->company->logo) : null,
                        'followed_at' => $fav->created_at,
                    ];
                });

            $connections = $connections->merge($followers)->merge($following)->merge($companies);

            return response()->json([
                'success' => true,
                'message' => 'All connections retrieved',
                'data' => [
                    'connections' => $connections->values(),
                    'stats' => [
                        'following_users' => $following->count(),
                        'follower_users' => $followers->count(),
                        'following_companies' => $companies->count(),
                        'total' => $connections->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get connections',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * 5. Check Connection Status
     */
    public function checkStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'target_id' => 'required|integer',
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

            $currentUser = Auth::user();
            $targetId = $request->target_id;

            // Detect entity type
            $targetType = $this->detectEntityType($targetId);
            
            if (!$targetType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target not found',
                    'errors' => (object)['target_id' => 'Target not found']
                ], 404);
            }

            $status = [
                'entity_type' => $targetType,
                'is_following' => false,
                'is_follower' => false,
                'is_blocked' => false,
                'is_blocked_by' => false,
                'has_pending_request' => false,
                'has_pending_request_from' => false,
                'connection_status' => null,
            ];

            if ($targetType === 'user') {
                $connection = UserConnection::where(function($q) use ($currentUser, $targetId) {
                    $q->where('follower_id', $currentUser->id)
                      ->where('following_id', $targetId);
                })->orWhere(function($q) use ($currentUser, $targetId) {
                    $q->where('follower_id', $targetId)
                      ->where('following_id', $currentUser->id);
                })->first();

                if ($connection) {
                    $status['connection_status'] = $connection->status;
                    $status['is_following'] = $connection->follower_id == $currentUser->id && 
                                             $connection->status == 'accepted';
                    $status['is_follower'] = $connection->following_id == $currentUser->id && 
                                            $connection->status == 'accepted';
                    $status['is_blocked'] = $connection->follower_id == $currentUser->id && 
                                           $connection->status == 'blocked';
                    $status['is_blocked_by'] = $connection->following_id == $currentUser->id && 
                                              $connection->status == 'blocked';
                    $status['has_pending_request'] = $connection->follower_id == $currentUser->id && 
                                                   $connection->status == 'pending';
                    $status['has_pending_request_from'] = $connection->following_id == $currentUser->id && 
                                                        $connection->status == 'pending';
                }

                $status['mutual_count'] = $this->getMutualUserCount($currentUser->id, $targetId);
                $status['can_accept'] = $status['has_pending_request_from'];
                $status['can_reject'] = $status['has_pending_request_from'];
                $status['can_follow'] = !$status['is_following'] && !$status['is_blocked'] && 
                                      !$status['is_blocked_by'] && !$status['has_pending_request'];
                $status['can_unfollow'] = $status['is_following'];
                $status['can_block'] = !$status['is_blocked'];
                $status['can_unblock'] = $status['is_blocked'];

            } else {
                // Company
                $status['is_following'] = FavouriteCompany::where('user_id', $currentUser->id)
                    ->where('company_id', $targetId)
                    ->exists();
                
                $status['is_blocked'] = UserConnection::where('follower_id', $currentUser->id)
                    ->where('following_id', $targetId)
                    ->where('status', 'blocked')
                    ->exists();
                
                $status['can_follow'] = !$status['is_following'] && !$status['is_blocked'];
                $status['can_unfollow'] = $status['is_following'];
                $status['can_block'] = !$status['is_blocked'];
                $status['can_unblock'] = $status['is_blocked'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection status retrieved',
                'data' => $status
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * 6. Get Pending Follow Requests
     */
    public function getPendingRequests(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            $pendingRequests = UserConnection::where('following_id', $currentUser->id)
                ->where('status', 'pending')
                ->with(['follower:id,name,usertype,headline,image'])
                ->get()
                ->map(function ($connection) {
                    return [
                        'id' => $connection->id,
                        'entity_type' => 'user',
                        'request_from' => [
                            'id' => $connection->follower->id,
                            'name' => $connection->follower->name,
                            'usertype' => $connection->follower->usertype,
                            'headline' => $connection->follower->headline,
                            'image' => $connection->follower->image ? asset('user_images/' . $connection->follower->image) : null,
                        ],
                        'requested_at' => $connection->created_at,
                        'mutual_count' => $this->getMutualUserCount($connection->following_id, $connection->follower_id),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Pending requests retrieved',
                'data' => [
                    'pending_requests' => $pendingRequests,
                    'count' => $pendingRequests->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending requests',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Helper: Auto-detect entity type (user or company)
     */
    private function detectEntityType($id)
    {
        // Check if it's a user
        if (User::where('id', $id)->where('is_active', 1)->exists()) {
            return 'user';
        }
        
        // Check if it's a company
        if (Company::where('id', $id)->where('is_active', 1)->exists()) {
            return 'company';
        }
        
        return null;
    }

    /**
     * Helper: Get mutual user count
     */
    private function getMutualUserCount($userId1, $userId2)
    {
        $user1Following = UserConnection::where('follower_id', $userId1)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        $user2Following = UserConnection::where('follower_id', $userId2)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        return count(array_intersect($user1Following, $user2Following));
    }

    /**
     * Helper: Get user connection status
     */
    private function getUserConnectionStatus($currentUserId, $targetUserId)
    {
        $connection = UserConnection::where('follower_id', $currentUserId)
            ->where('following_id', $targetUserId)
            ->first();
        
        return $connection ? $connection->status : 'none';
    }

    /**
     * Helper: Follow User
     */
    private function followUser($followerId, $followingId)
    {
        $existing = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->first();

        if ($existing) {
            if ($existing->status == 'blocked') {
                throw new Exception('Cannot follow blocked user');
            }
            if ($existing->status == 'accepted') {
                throw new Exception('Already following');
            }
            if ($existing->status == 'pending') {
                throw new Exception('Request already sent');
            }
        }

        $connection = UserConnection::updateOrCreate(
            ['follower_id' => $followerId, 'following_id' => $followingId],
            ['status' => 'pending']
        );

        return [
            'connection_id' => $connection->id,
            'status' => $connection->status,
            'action' => 'follow_request_sent'
        ];
    }

    /**
     * Helper: Unfollow User
     */
    private function unfollowUser($followerId, $followingId)
    {
        $connection = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->first();

        if (!$connection) {
            throw new Exception('Not following this user');
        }

        $connection->delete();

        return [
            'unfollowed_id' => $followingId,
            'action' => 'unfollowed'
        ];
    }

    /**
     * Helper: Accept Follow Request
     */
    private function acceptFollowRequest($userId, $followerId)
    {
        $connection = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            throw new Exception('No pending follow request found');
        }

        $connection->status = 'accepted';
        $connection->save();

        return [
            'connection_id' => $connection->id,
            'status' => $connection->status,
            'action' => 'request_accepted'
        ];
    }

    /**
     * Helper: Reject Follow Request
     */
    private function rejectFollowRequest($userId, $followerId)
    {
        $connection = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            throw new Exception('No pending follow request found');
        }

        $connection->delete();

        return [
            'rejected_user_id' => $followerId,
            'action' => 'request_rejected'
        ];
    }

    /**
     * Helper: Block User
     */
    private function blockUser($blockerId, $blockedId, $reason = null)
    {
        $connection = UserConnection::updateOrCreate(
            ['follower_id' => $blockerId, 'following_id' => $blockedId],
            ['status' => 'blocked']
        );

        return [
            'blocked_id' => $blockedId,
            'reason' => $reason,
            'action' => 'blocked'
        ];
    }

    /**
     * Helper: Unblock User
     */
    private function unblockUser($blockerId, $blockedId)
    {
        $connection = UserConnection::where('follower_id', $blockerId)
            ->where('following_id', $blockedId)
            ->where('status', 'blocked')
            ->first();

        if (!$connection) {
            throw new Exception('User not blocked');
        }

        $connection->delete();

        return [
            'unblocked_id' => $blockedId,
            'action' => 'unblocked'
        ];
    }

    /**
     * Helper: Follow Company
     */
    private function followCompany($userId, $companyId)
    {
        $existing = FavouriteCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if ($existing) {
            throw new Exception('Already following this company');
        }

        $company = Company::find($companyId);
        $favourite = FavouriteCompany::create([
            'user_id' => $userId,
            'company_slug' => $company->slug,
            'company_id' => $companyId,
        ]);

        return [
            'follow_id' => $favourite->id,
            'action' => 'company_followed'
        ];
    }

    /**
     * Helper: Unfollow Company
     */
    private function unfollowCompany($userId, $companyId)
    {
        $favourite = FavouriteCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if (!$favourite) {
            throw new Exception('Not following this company');
        }

        $favourite->delete();

        return [
            'unfollowed_company_id' => $companyId,
            'action' => 'company_unfollowed'
        ];
    }

    /**
     * Helper: Block Company
     */
    private function blockCompany($userId, $companyId, $reason = null)
    {
        // Store company blocks in user_connections table
        $connection = UserConnection::updateOrCreate(
            ['follower_id' => $userId, 'following_id' => $companyId],
            ['status' => 'blocked']
        );

        // Also unfollow if currently following
        FavouriteCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->delete();

        return [
            'blocked_company_id' => $companyId,
            'reason' => $reason,
            'action' => 'company_blocked'
        ];
    }

    /**
     * Helper: Unblock Company
     */
    private function unblockCompany($userId, $companyId)
    {
        $connection = UserConnection::where('follower_id', $userId)
            ->where('following_id', $companyId)
            ->where('status', 'blocked')
            ->first();

        if (!$connection) {
            throw new Exception('Company not blocked');
        }

        $connection->delete();

        return [
            'unblocked_company_id' => $companyId,
            'action' => 'company_unblocked'
        ];
    }
}