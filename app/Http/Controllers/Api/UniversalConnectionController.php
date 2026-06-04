<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\UserConnection;
use App\BlockedUser;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniversalConnectionController extends Controller
{
    // Status constants
    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED  = 'blocked';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ============================================
    // NOTIFICATION HELPER
    // ============================================

    /**
     * Send + store connection notification via NotificationService
     * FCM body/data payload is untouched — handled inside NotificationService
     */
    private function sendConnectionNotification($currentUser, $targetUser, $action)
    {
        try {
            // Respect user push notification preference
            if (!$targetUser->push_notification) {
                return;
            }

            $title = '';
            $body  = '';

            switch ($action) {
                case 'follow':
                    $title = "New Follow Request";
                    $body  = $currentUser->getName() . " sent you a follow request.";
                    break;

                case 'accept':
                    $title = "Follow Request Accepted";
                    $body  = $currentUser->getName() . " accepted your follow request.";
                    break;

                // case 'reject':
                //     $title = "Follow Request Rejected";
                //     $body  = $currentUser->getName() . " rejected your follow request.";
                //     break;

                default:
                    return; // block/unblock/unfollow — no notification needed
            }

            // Action payload matches original FCM data structure
            $actionPayload = [
                'screen'  => 'profile',
                'user_id' => (string) $currentUser->id,
                'type'    => $action,
            ];

            // Stores in notifications table + sends FCM push
            $this->notificationService->send(
                $targetUser->id,     // recipient user_id
                $action,             // type  (follow | accept | reject)
                $title,
                $body,
                'profile',           // screen
                $actionPayload,      // action_payload / data payload
                $currentUser->id     // from_user_id
            );

        } catch (\Exception $e) {
            Log::error('Connection notification failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ============================================
    // ENRICHED ENTITY DATA HELPER
    // ============================================

    private function getEnrichedEntityData($entity)
    {
        if (!$entity) {
            return null;
        }

        if ($entity->usertype === 'company') {
            return [
                'id'                         => $entity->id,
                'name'                       => $entity->company_name ?? $entity->name ?? 'Unknown Company',
                'email'                      => $entity->email ?? null,
                'usertype'                   => 'company',
                'headline'                   => $entity->company_description ?? $entity->headline,
                'image'                      => $entity->company_logo
                                                    ? asset('company_logos/' . $entity->company_logo)
                                                    : ($entity->image ? asset('user_images/' . $entity->image) : null),
                'slug'                       => $entity->company_slug ?? null,
                'visibility_control'         => $entity->visibility_control ?? 'public',
                'post_visibility_control'    => $entity->post_visibility_control ?? 'public',
                'message_visibility_control' => $entity->message_visibility_control ?? 'public',
                'entity_type'                => 'company',
            ];
        }

        $firstName = $entity->first_name ?? '';
        $lastName  = $entity->last_name ?? '';
        $name      = trim($firstName . ' ' . $lastName) ?: ($entity->name ?? 'Unknown User');

        return [
            'id'                         => $entity->id,
            'name'                       => $name,
            'email'                      => $entity->email ?? null,
            'usertype'                   => $entity->usertype ?? 'user',
            'headline'                   => $entity->headline ?? null,
            'image'                      => $entity->image ? asset('user_images/' . $entity->image) : null,
            'slug'                       => null,
            'visibility_control'         => $entity->visibility_control ?? 'public',
            'post_visibility_control'    => $entity->post_visibility_control ?? 'public',
            'message_visibility_control' => $entity->message_visibility_control ?? 'public',
            'entity_type'                => 'user',
        ];
    }

    // ============================================
    // 1. GET FOLLOW SUGGESTIONS
    // ============================================

    public function getSuggestions(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $perPage     = $request->get('per_page', 20);

            $connectedUserIds = UserConnection::where(function ($q) use ($currentUser) {
                    $q->where('follower_id', $currentUser->id)
                      ->orWhere('following_id', $currentUser->id);
                })
                ->get()
                ->map(fn($conn) => $conn->follower_id == $currentUser->id
                    ? $conn->following_id
                    : $conn->follower_id)
                ->toArray();

            $blockedIds  = $this->getBlockedUserIds($currentUser->id);
            $excludedIds = array_unique(array_merge($connectedUserIds, [$currentUser->id], $blockedIds));

            $suggestions = User::where('is_active', 1)
                ->whereNotIn('id', $excludedIds)
                ->select(
                    'id', 'first_name', 'last_name', 'company_name', 'name',
                    'email', 'usertype', 'headline', 'image', 'company_logo',
                    'company_slug', 'company_description', 'created_at',
                    'visibility_control', 'post_visibility_control', 'message_visibility_control'
                )
                ->inRandomOrder()
                ->limit($perPage)
                ->get()
                ->map(function ($user) use ($currentUser) {
                    $enrichedData = $this->getEnrichedEntityData($user);

                    if ($user->usertype === 'company') {
                        return array_merge($enrichedData, [
                            'slug'              => $user->company_slug,
                            'description'       => $user->company_description,
                            'followers_count'   => UserConnection::where('following_id', $user->id)
                                                    ->where('status', self::STATUS_ACCEPTED)->count(),
                            'mutual_count'      => 0,
                            'connection_status' => 'none',
                        ]);
                    }

                    return array_merge($enrichedData, [
                        'mutual_count'      => $this->getMutualConnectionCount($currentUser->id, $user->id),
                        'connection_status' => 'none',
                    ]);
                });

            return response()->json([
                'success' => true,
                'message' => 'Suggestions retrieved successfully',
                'data'    => [
                    'suggestions' => $suggestions->values(),
                    'total'       => $suggestions->count(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get suggestions failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 2. GET MUTUAL CONNECTIONS
    // ============================================

    public function getMutualConnections(Request $request)
    {
        try {
            $currentUser       = Auth::user();
            $perPage           = $request->get('per_page', 20);
            $page              = $request->get('page', 1);
            $mutualConnections = collect();

            $myConnections = UserConnection::where(function ($q) use ($currentUser) {
                    $q->where('follower_id', $currentUser->id)
                      ->orWhere('following_id', $currentUser->id);
                })
                ->where('status', self::STATUS_ACCEPTED)
                ->get()
                ->map(fn($conn) => $conn->follower_id == $currentUser->id
                    ? $conn->following_id
                    : $conn->follower_id)
                ->toArray();

            foreach ($myConnections as $connectionId) {
                $theirConnections = UserConnection::where(function ($q) use ($connectionId) {
                        $q->where('follower_id', $connectionId)
                          ->orWhere('following_id', $connectionId);
                    })
                    ->where('status', self::STATUS_ACCEPTED)
                    ->get()
                    ->map(fn($conn) => $conn->follower_id == $connectionId
                        ? $conn->following_id
                        : $conn->follower_id)
                    ->toArray();

                $mutualUserIds = array_diff(
                    array_intersect($myConnections, $theirConnections),
                    [$currentUser->id, $connectionId]
                );

                if (!empty($mutualUserIds)) {
                    $mutualUsers = User::whereIn('id', $mutualUserIds)
                        ->where('is_active', 1)
                        ->select(
                            'id', 'first_name', 'last_name', 'company_name', 'name',
                            'usertype', 'headline', 'image', 'company_logo', 'created_at',
                            'visibility_control', 'post_visibility_control', 'message_visibility_control'
                        )
                        ->get()
                        ->map(fn($user) => array_merge(
                            $this->getEnrichedEntityData($user),
                            [
                                'mutual_with'       => $connectionId,
                                'connection_status' => $this->checkIfConnected($currentUser, $user->id),
                            ]
                        ));

                    $mutualConnections = $mutualConnections->merge($mutualUsers);
                }
            }

            $mutualConnections = $mutualConnections->unique('id');
            $total             = $mutualConnections->count();
            $paginated         = $mutualConnections->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'success' => true,
                'message' => 'Mutual connections retrieved successfully',
                'data'    => [
                    'mutual_connections' => $paginated,
                    'pagination'         => [
                        'current_page' => $page,
                        'per_page'     => $perPage,
                        'total'        => $total,
                        'last_page'    => (int) ceil($total / $perPage),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get mutual connections failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get mutual connections',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 3. HANDLE CONNECTION ACTIONS
    // ============================================

    public function handleConnection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'target_id' => 'required|integer',
                'action'    => 'required|in:follow,unfollow,accept,reject,block,unblock',
                'reason'    => 'nullable|string|max:500',
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

            $currentUser = Auth::user();
            $targetId    = $request->target_id;
            $action      = $request->action;
            $reason      = $request->reason;

            $targetUser = User::find($targetId);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target not found',
                    'errors'  => (object) ['target_id' => 'Target not found']
                ], 404);
            }

            if ($currentUser->id == $targetId) {
                $actionMessages = [
                    'follow' => 'follow yourself',
                    'accept' => 'accept your own request',
                    'reject' => 'reject your own request',
                    'block'  => 'block yourself',
                ];
                if (isset($actionMessages[$action])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot ' . $actionMessages[$action],
                        'errors'  => (object) ['target_id' => 'Cannot ' . $actionMessages[$action]]
                    ], 400);
                }
            }

            DB::beginTransaction();

            try {
                $result     = null;
                $message    = '';
                $targetName = $targetUser->usertype === 'company'
                    ? ($targetUser->company_name ?? $targetUser->name ?? 'company')
                    : $targetUser->getName();

                switch ($action) {
                    case 'follow':
                        $result  = $this->followUser($currentUser->id, $targetId);
                        $message = 'Follow request sent to ' . $targetName;
                        break;

                    case 'unfollow':
                        $result  = $this->unfollowUser($currentUser->id, $targetId);
                        $message = 'Unfollowed ' . $targetName;
                        break;

                    case 'accept':
                        $result  = $this->acceptFollowRequest($currentUser->id, $targetId);
                        $message = 'Accepted follow request from ' . $targetName;
                        break;

                    case 'reject':
                        $result  = $this->rejectFollowRequest($currentUser->id, $targetId);
                        $message = 'Rejected follow request from ' . $targetName;
                        break;

                    case 'block':
                        $result  = $this->blockUser($currentUser->id, $targetId, $reason);
                        $message = 'Blocked ' . $targetName;
                        break;

                    case 'unblock':
                        $result  = $this->unblockUser($currentUser->id, $targetId);
                        $message = 'Unblocked ' . $targetName;
                        break;
                }

                $result['target_details'] = $this->getEnrichedEntityData($targetUser);

                DB::commit();

                // Store in notifications table + send FCM (follow / accept / reject only)
                $this->sendConnectionNotification($currentUser, $targetUser, $action);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => array_merge($result, [
                        'action'       => $action,
                        'performed_by' => [
                            'id'   => $currentUser->id,
                            'name' => $currentUser->getName(),
                        ]
                    ])
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Handle connection failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform action: ' . $e->getMessage(),
                'errors'  => (object) ['server' => $e->getMessage()]
            ], 400);
        }
    }

    // ============================================
    // 4. GET ALL CONNECTIONS
    // ============================================

    public function getAllConnections(Request $request)
    {
        
       
        try {
            $currentUser  = Auth::user();
            $connections  = collect();
            $processedIds = [];

            $allAccepted = UserConnection::where(function ($q) use ($currentUser) {
                    $q->where('follower_id', $currentUser->id)
                      ->orWhere('following_id', $currentUser->id);
                })
                ->where('status', self::STATUS_ACCEPTED)
                ->get();

            foreach ($allAccepted as $conn) {
                $otherId = $conn->follower_id == $currentUser->id
                    ? $conn->following_id
                    : $conn->follower_id;

                if (in_array($otherId, $processedIds)) continue;
                $processedIds[] = $otherId;

                $entity = User::find($otherId);
                if (!$entity) continue;

                $connectionType = $conn->follower_id == $currentUser->id ? 'following' : 'follower';
                $enrichedData   = $this->getEnrichedEntityData($entity);

                $connectionData = array_merge($enrichedData, [
                    'connection_type'        => $connectionType,
                    'connected_at'           => $conn->created_at,
                    'connected_at_formatted' => $conn->created_at->diffForHumans(),
                ]);

                if ($entity->usertype === 'company') {
                    $connectionData['slug']        = $entity->company_slug;
                    $connectionData['description'] = $entity->company_description;
                }

                $connections->push($connectionData);
            }

            return response()->json([
                'success' => true,
                'message' => 'All connections retrieved',
                'data'    => [
                    'connections' => $connections->values(),
                    'stats'       => [
                        'following' => $connections->where('connection_type', 'following')->count(),
                        'followers' => $connections->where('connection_type', 'follower')->count(),
                        'total'     => $connections->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get all connections failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get connections',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 5. CHECK CONNECTION STATUS
    // ============================================

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
                    'errors'  => (object) $errors
                ], 422);
            }

            $currentUser = Auth::user();
            $targetId    = $request->target_id;

            $targetUser = User::find($targetId);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target not found',
                    'errors'  => (object) ['target_id' => 'Target not found']
                ], 404);
            }

            $status = [
                'entity_type'              => $targetUser->usertype === 'company' ? 'company' : 'user',
                'is_following'             => false,
                'is_follower'              => false,
                'is_blocked'               => false,
                'is_blocked_by'            => false,
                'has_pending_request'      => false,
                'has_pending_request_from' => false,
                'connection_status'        => null,
            ];

            $myRequest    = UserConnection::where('follower_id', $currentUser->id)->where('following_id', $targetId)->first();
            $theirRequest = UserConnection::where('follower_id', $targetId)->where('following_id', $currentUser->id)->first();

            if ($myRequest) {
                $status['connection_status']   = $myRequest->status;
                $status['is_following']        = $myRequest->status == self::STATUS_ACCEPTED;
                $status['is_blocked']          = $myRequest->status == self::STATUS_BLOCKED;
                $status['has_pending_request'] = $myRequest->status == self::STATUS_PENDING;
            }

            if ($theirRequest) {
                $status['is_follower']              = $theirRequest->status == self::STATUS_ACCEPTED;
                $status['is_blocked_by']            = $theirRequest->status == self::STATUS_BLOCKED;
                $status['has_pending_request_from'] = $theirRequest->status == self::STATUS_PENDING;

                if (!$myRequest) {
                    if ($theirRequest->status == self::STATUS_ACCEPTED) {
                        $status['connection_status'] = 'accepted';
                    } elseif ($theirRequest->status == self::STATUS_PENDING) {
                        $status['connection_status'] = 'pending_from_them';
                    } elseif ($theirRequest->status == self::STATUS_BLOCKED) {
                        $status['connection_status'] = 'blocked_by_them';
                    }
                }
            }

            if (BlockedUser::isBlocked($currentUser->id, $targetId)) {
                $status['is_blocked']        = true;
                $status['connection_status'] = 'blocked';
            }

            if (BlockedUser::isBlocked($targetId, $currentUser->id)) {
                $status['is_blocked_by']     = true;
                $status['connection_status'] = 'blocked_by_them';
            }

            $status['mutual_count'] = $this->getMutualConnectionCount($currentUser->id, $targetId);
            $status['can_accept']   = $status['has_pending_request_from'];
            $status['can_reject']   = $status['has_pending_request_from'];
            $status['can_follow']   = !$status['is_following'] && !$status['is_blocked']
                                   && !$status['is_blocked_by'] && !$status['has_pending_request'];
            $status['can_unfollow'] = $status['is_following'];
            $status['can_block']    = !$status['is_blocked'] && !$status['is_blocked_by'];
            $status['can_unblock']  = $status['is_blocked'] || $status['is_blocked_by'];

            return response()->json([
                'success' => true,
                'message' => 'Connection status retrieved',
                'data'    => $status
            ]);

        } catch (Exception $e) {
            Log::error('Check status failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check status',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 6. GET PENDING REQUESTS
    // ============================================

    public function getPendingRequests(Request $request)
    {
        try {
            $currentUser     = Auth::user();
            $pendingRequests = UserConnection::where('following_id', $currentUser->id)
                ->where('status', self::STATUS_PENDING)
                ->get();

            $formattedRequests = $pendingRequests->map(function ($connection) {
                $follower = User::find($connection->follower_id);
                if (!$follower) return null;

                return [
                    'id'                     => $connection->id,
                    'entity_type'            => $follower->usertype === 'company' ? 'company' : 'user',
                    'request_from'           => $this->getEnrichedEntityData($follower),
                    'requested_at'           => $connection->created_at,
                    'requested_at_formatted' => $connection->created_at->diffForHumans(),
                    'mutual_count'           => $this->getMutualConnectionCount(
                                                    $connection->following_id,
                                                    $connection->follower_id
                                                ),
                    'connection_status'      => $connection->status,
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'message' => 'Pending requests retrieved successfully',
                'data'    => [
                    'pending_requests' => $formattedRequests->values(),
                    'count'            => $formattedRequests->count(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get pending requests failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending requests',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }


    // ============================================
    // 7. GET BLOCK LIST
    // ============================================

    public function getBlockList(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $perPage     = $request->get('per_page', 20);
            $page        = $request->get('page', 1);

            $blockedInConnections = UserConnection::where('follower_id', $currentUser->id)
                ->where('status', self::STATUS_BLOCKED)
                ->orderBy('created_at', 'desc')
                ->get();

            $blockedInBlockedTable = BlockedUser::where('blocker_id', $currentUser->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $blockedItems = [];

            foreach ($blockedInConnections as $connection) {
                $entity = User::find($connection->following_id);
                if (!$entity) continue;
                $entityData     = $this->getEnrichedEntityData($entity);
                $blockedItems[] = array_merge($entityData, [
                    'id'                   => $connection->id,
                    'blocked_id'           => $entityData['id'],
                    'blocked_at'           => $connection->created_at,
                    'blocked_at_formatted' => $connection->created_at->diffForHumans(),
                    'reason'               => $connection->reason ?? null,
                    'source'               => 'connection',
                ]);
            }

            foreach ($blockedInBlockedTable as $block) {
                $entity = User::find($block->blocked_id);
                if (!$entity) continue;
                $entityData     = $this->getEnrichedEntityData($entity);
                $blockedItems[] = array_merge($entityData, [
                    'id'                   => $block->id,
                    'blocked_id'           => $entityData['id'],
                    'blocked_at'           => $block->created_at,
                    'blocked_at_formatted' => $block->created_at->diffForHumans(),
                    'reason'               => $block->reason ?? null,
                    'source'               => 'blocked_users',
                ]);
            }

            $uniqueBlocked   = collect($blockedItems)->unique('blocked_id')->values();
            $total           = $uniqueBlocked->count();
            $paginatedBlocks = $uniqueBlocked->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'success' => true,
                'message' => 'Block list retrieved successfully',
                'data'    => [
                    'blocked_items' => $paginatedBlocks,
                    'pagination'    => [
                        'current_page' => $page,
                        'per_page'     => $perPage,
                        'total'        => $total,
                        'last_page'    => (int) ceil($total / $perPage),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get block list failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get block list',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 8. UNBLOCK FROM LIST
    // ============================================

    public function unblockFromList($id)
    {
        try {
            $currentUser = Auth::user();

            $connection = UserConnection::where('id', $id)
                ->where('follower_id', $currentUser->id)
                ->where('status', self::STATUS_BLOCKED)
                ->first();

            if ($connection) {
                $targetId = $connection->following_id;
                $connection->delete();
                $targetUser = User::find($targetId);

                return response()->json([
                    'success' => true,
                    'message' => 'Unblocked successfully',
                    'data'    => [
                        'unblocked_id' => $targetId,
                        'entity_type'  => $targetUser && $targetUser->usertype === 'company' ? 'company' : 'user',
                    ]
                ]);
            }

            $block = BlockedUser::where('id', $id)->where('blocker_id', $currentUser->id)->first();

            if ($block) {
                $targetId = $block->blocked_id;
                $block->delete();
                $targetUser = User::find($targetId);

                return response()->json([
                    'success' => true,
                    'message' => 'Unblocked successfully',
                    'data'    => [
                        'unblocked_id' => $targetId,
                        'entity_type'  => $targetUser && $targetUser->usertype === 'company' ? 'company' : 'user',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Blocked item not found'
            ], 404);

        } catch (Exception $e) {
            Log::error('Unblock from list failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 9. GET CONNECTION STATS
    // ============================================

    public function getConnectionStats(Request $request)
    {
        try {
            $currentUser = Auth::user();

            $allAccepted = UserConnection::where(function ($q) use ($currentUser) {
                    $q->where('follower_id', $currentUser->id)
                      ->orWhere('following_id', $currentUser->id);
                })
                ->where('status', self::STATUS_ACCEPTED)
                ->get();

            $uniqueConnections = [];
            foreach ($allAccepted as $conn) {
                $otherId = $conn->follower_id == $currentUser->id
                    ? $conn->following_id
                    : $conn->follower_id;
                $uniqueConnections[$otherId] = true;
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection stats retrieved',
                'data'    => [
                    'stats' => [
                        'total_connections' => count($uniqueConnections),
                        'pending_sent'      => UserConnection::where('follower_id', $currentUser->id)
                                                ->where('status', self::STATUS_PENDING)->count(),
                        'pending_received'  => UserConnection::where('following_id', $currentUser->id)
                                                ->where('status', self::STATUS_PENDING)->count(),
                        'blocked'           => UserConnection::where('follower_id', $currentUser->id)
                                                ->where('status', self::STATUS_BLOCKED)->count()
                                              + BlockedUser::where('blocker_id', $currentUser->id)->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get connection stats failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get connection stats',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // 10. GLOBAL USER SEARCH
    // ============================================
    
    public function globalSearch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query'    => 'nullable|string|max:255',
                'usertype' => 'nullable|in:user,company,student,professional',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page'     => 'nullable|integer|min:1',
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
    
            $currentUser = Auth::user();
            $query       = trim($request->get('query', ''));
            $usertype    = $request->get('usertype');
            $perPage     = $request->get('per_page', 20);
            $page        = $request->get('page', 1);
    
            if ($query === '' && !$usertype) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a search query or usertype filter.',
                    'errors'  => (object) ['query' => 'Search query or usertype is required.']
                ], 422);
            }
    
            $blockedByMe   = BlockedUser::where('blocker_id', $currentUser->id)->pluck('blocked_id')->toArray();
            $blockedByThem = BlockedUser::where('blocked_id', $currentUser->id)->pluck('blocker_id')->toArray();
            $excludedIds   = array_unique(array_merge($blockedByMe, $blockedByThem, [$currentUser->id]));
    
            $myConnectionIds = UserConnection::where(function ($q) use ($currentUser) {
                    $q->where('follower_id', $currentUser->id)
                      ->orWhere('following_id', $currentUser->id);
                })
                ->where('status', self::STATUS_ACCEPTED)
                ->get()
                ->map(fn($c) => $c->follower_id == $currentUser->id ? $c->following_id : $c->follower_id)
                ->toArray();
    
            $dbQuery = User::where('is_active', 1)
                ->where('name', '!=', 'New User')
                ->where('name', '!=', 'New Company')
                ->where('is_company_profile_completed', 1)
                ->whereNotIn('id', $excludedIds)
                ->select(
                    'id', 'first_name', 'last_name', 'name',
                    'company_name', 'company_slug', 'company_description', 'company_logo',
                    'email', 'usertype', 'headline', 'image',
                    'visibility_control', 'post_visibility_control', 'message_visibility_control'
                );
    
            if ($usertype) {
                $dbQuery->where('usertype', $usertype);
            }
    
            if ($query !== '') {
                $lowerQuery = strtolower($query);
                $dbQuery->where(function ($q) use ($lowerQuery) {
                    $like = '%' . $lowerQuery . '%';
                    $q->whereRaw('LOWER(first_name)  LIKE ?', [$like])
                      ->orWhereRaw('LOWER(last_name)   LIKE ?', [$like])
                      ->orWhereRaw('LOWER(name)         LIKE ?', [$like])
                      ->orWhereRaw('LOWER(company_name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(email)        LIKE ?', [$like])
                      ->orWhereRaw(
                          "LOWER(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) LIKE ?",
                          [$like]
                      );
                });
            }
    
            $dbQuery->where(function ($q) use ($myConnectionIds) {
                $q->where('visibility_control', 'public')
                  ->orWhere(function ($q2) use ($myConnectionIds) {
                      $q2->where('visibility_control', 'connections')
                         ->whereIn('id', $myConnectionIds);
                  });
            });
    
            $total = (clone $dbQuery)->count();
    
            $results = $dbQuery
                ->when($query !== '', function ($q) use ($query) {
                    $q->orderByRaw("
                        CASE
                            WHEN LOWER(first_name)   LIKE ? THEN 0
                            WHEN LOWER(name)         LIKE ? THEN 0
                            WHEN LOWER(company_name) LIKE ? THEN 0
                            ELSE 1
                        END
                    ", [
                        strtolower($query) . '%',
                        strtolower($query) . '%',
                        strtolower($query) . '%',
                    ]);
                })
                ->orderBy('first_name')
                ->orderBy('name')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();
    
            $formatted = $results->map(function ($user) use ($currentUser) {
                $enriched         = $this->getEnrichedEntityData($user);
                $connectionStatus = $this->checkIfConnected($currentUser, $user->id);
    
                $extra = [
                    'connection_status' => $connectionStatus,
                    'mutual_count'      => $this->getMutualConnectionCount($currentUser->id, $user->id),
                ];
    
                if ($user->usertype === 'company') {
                    $extra['followers_count'] = UserConnection::where('following_id', $user->id)
                        ->where('status', self::STATUS_ACCEPTED)->count();
                }
    
                return array_merge($enriched, $extra);
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved successfully',
                'data'    => [
                    'results'         => $formatted->values(),
                    'pagination'      => [
                        'current_page' => $page,
                        'per_page'     => $perPage,
                        'total'        => $total,
                        'last_page'    => (int) ceil($total / $perPage),
                    ],
                    'filters_applied' => [
                        'query'    => $query ?: null,
                        'usertype' => $usertype ?: null,
                    ],
                ]
            ]);
    
        } catch (Exception $e) {
           
    
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'errors'  => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    // public function globalSearch(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'query'    => 'nullable|string|max:255',
    //             'usertype' => 'nullable|in:user,company,student,professional',
    //             'per_page' => 'nullable|integer|min:1|max:100',
    //             'page'     => 'nullable|integer|min:1',
    //         ]);

    //         if ($validator->fails()) {
    //             $errors = [];
    //             foreach ($validator->errors()->toArray() as $field => $messages) {
    //                 $errors[$field] = $messages[0];
    //             }
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors'  => (object) $errors
    //             ], 422);
    //         }

    //         $currentUser = Auth::user();
    //         $query       = trim($request->get('query', ''));
    //         $usertype    = $request->get('usertype');
    //         $perPage     = $request->get('per_page', 20);
    //         $page        = $request->get('page', 1);

    //         if ($query === '' && !$usertype) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Please provide a search query or usertype filter.',
    //                 'errors'  => (object) ['query' => 'Search query or usertype is required.']
    //             ], 422);
    //         }

    //         $blockedByMe   = BlockedUser::where('blocker_id', $currentUser->id)->pluck('blocked_id')->toArray();
    //         $blockedByThem = BlockedUser::where('blocked_id', $currentUser->id)->pluck('blocker_id')->toArray();
    //         $excludedIds   = array_unique(array_merge($blockedByMe, $blockedByThem, [$currentUser->id]));

    //         $myConnectionIds = UserConnection::where(function ($q) use ($currentUser) {
    //                 $q->where('follower_id', $currentUser->id)
    //                   ->orWhere('following_id', $currentUser->id);
    //             })
    //             ->where('status', self::STATUS_ACCEPTED)
    //             ->get()
    //             ->map(fn($c) => $c->follower_id == $currentUser->id ? $c->following_id : $c->follower_id)
    //             ->toArray();

           
            
    //         $dbQuery = User::where('is_active', 1)
    //             ->where('name', '!=', 'New User')
    //             ->where('name', '!=', 'New Company')
    //             ->whereNotIn('id', $excludedIds)
    //             ->select(
    //                 'id', 'first_name', 'last_name', 'name',
    //                 'company_name', 'company_slug', 'company_description', 'company_logo',
    //                 'email', 'usertype', 'headline', 'image',
    //                 'visibility_control', 'post_visibility_control', 'message_visibility_control'
    //             );

    //         if ($usertype) {
    //             $dbQuery->where('usertype', $usertype);
    //         }

    //         if ($query !== '') {
    //             $dbQuery->where(function ($q) use ($query) {
    //                 $like = '%' . $query . '%';
    //                 $q->where('first_name',     'LIKE', $like)
    //                   ->orWhere('last_name',    'LIKE', $like)
    //                   ->orWhere('name',         'LIKE', $like)
    //                   ->orWhere('company_name', 'LIKE', $like)
    //                   ->orWhere('email',        'LIKE', $like)
    //                   ->orWhereRaw(
    //                       "CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?",
    //                       [$like]
    //                   );
    //             });
    //         }

    //         $dbQuery->where(function ($q) use ($myConnectionIds) {
    //             $q->where('visibility_control', 'public')
    //               ->orWhere(function ($q2) use ($myConnectionIds) {
    //                   $q2->where('visibility_control', 'connections')
    //                      ->whereIn('id', $myConnectionIds);
    //               });
    //         });

    //         $total = (clone $dbQuery)->count();

    //         $results = $dbQuery
    //             ->when($query !== '', function ($q) use ($query) {
    //                 $q->orderByRaw("
    //                     CASE
    //                         WHEN first_name   LIKE ? THEN 0
    //                         WHEN name         LIKE ? THEN 0
    //                         WHEN company_name LIKE ? THEN 0
    //                         ELSE 1
    //                     END
    //                 ", [$query . '%', $query . '%', $query . '%']);
    //             })
    //             ->orderBy('first_name')
    //             ->orderBy('name')
    //             ->offset(($page - 1) * $perPage)
    //             ->limit($perPage)
    //             ->get();

    //         $formatted = $results->map(function ($user) use ($currentUser) {
    //             $enriched         = $this->getEnrichedEntityData($user);
    //             $connectionStatus = $this->checkIfConnected($currentUser, $user->id);

    //             $extra = [
    //                 'connection_status' => $connectionStatus,
    //                 'mutual_count'      => $this->getMutualConnectionCount($currentUser->id, $user->id),
    //             ];

    //             if ($user->usertype === 'company') {
    //                 $extra['followers_count'] = UserConnection::where('following_id', $user->id)
    //                     ->where('status', self::STATUS_ACCEPTED)->count();
    //             }

    //             return array_merge($enriched, $extra);
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Search results retrieved successfully',
    //             'data'    => [
    //                 'results'         => $formatted->values(),
    //                 'pagination'      => [
    //                     'current_page' => $page,
    //                     'per_page'     => $perPage,
    //                     'total'        => $total,
    //                     'last_page'    => (int) ceil($total / $perPage),
    //                 ],
    //                 'filters_applied' => [
    //                     'query'    => $query ?: null,
    //                     'usertype' => $usertype ?: null,
    //                 ],
    //             ]
    //         ]);

    //     } catch (Exception $e) {
    //         Log::error('Global search failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Search failed',
    //             'errors'  => (object) ['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }

    // ============================================
    // PRIVATE ACTION HELPERS
    // ============================================

    private function getBlockedUserIds($userId)
    {
        return BlockedUser::where('blocker_id', $userId)->pluck('blocked_id')->toArray();
    }

    private function getBlockerIds($userId)
    {
        return BlockedUser::where('blocked_id', $userId)->pluck('blocker_id')->toArray();
    }

    private function isUserBlocked($currentUserId, $targetUserId)
    {
        return BlockedUser::isBlocked($currentUserId, $targetUserId)
            || BlockedUser::isBlocked($targetUserId, $currentUserId);
    }

    private function getMutualConnectionCount($userId1, $userId2)
    {
        $user1Connections = UserConnection::where(function ($q) use ($userId1) {
                $q->where('follower_id', $userId1)->orWhere('following_id', $userId1);
            })
            ->where('status', self::STATUS_ACCEPTED)
            ->get()
            ->map(fn($conn) => $conn->follower_id == $userId1 ? $conn->following_id : $conn->follower_id)
            ->toArray();

        $user2Connections = UserConnection::where(function ($q) use ($userId2) {
                $q->where('follower_id', $userId2)->orWhere('following_id', $userId2);
            })
            ->where('status', self::STATUS_ACCEPTED)
            ->get()
            ->map(fn($conn) => $conn->follower_id == $userId2 ? $conn->following_id : $conn->follower_id)
            ->toArray();

        return count(array_intersect($user1Connections, $user2Connections));
    }

    private function findAnyConnection($userId1, $userId2)
    {
        return UserConnection::where(function ($q) use ($userId1, $userId2) {
                $q->where('follower_id', $userId1)->where('following_id', $userId2);
            })
            ->orWhere(function ($q) use ($userId1, $userId2) {
                $q->where('follower_id', $userId2)->where('following_id', $userId1);
            })
            ->first();
    }

    private function getUserConnectionStatus($currentUserId, $targetUserId)
    {
        $connection = UserConnection::where('follower_id', $currentUserId)
            ->where('following_id', $targetUserId)
            ->first();

        return $connection ? $connection->status : 'none';
    }

    private function checkIfConnected($currentUser, $targetId)
    {
        if (!$targetId) return 'none';
        if ($currentUser->id == $targetId) return 'self';

        if (BlockedUser::isBlocked($currentUser->id, $targetId)) return 'blocked';
        if (BlockedUser::isBlocked($targetId, $currentUser->id)) return 'blocked_by_them';

        $connection = UserConnection::where(function ($query) use ($currentUser, $targetId) {
                $query->where('follower_id', $currentUser->id)->where('following_id', $targetId);
            })
            ->orWhere(function ($query) use ($currentUser, $targetId) {
                $query->where('following_id', $currentUser->id)->where('follower_id', $targetId);
            })
            ->first();

        if ($connection) {
            if ($connection->follower_id == $currentUser->id) {
                return $connection->status;
            }
            if ($connection->status == self::STATUS_ACCEPTED) return 'accepted';
            if ($connection->status == self::STATUS_PENDING)  return 'pending_from_them';
            if ($connection->status == self::STATUS_BLOCKED)  return 'blocked_by_them';
        }

        return 'none';
    }

    private function followUser($followerId, $followingId)
    {
        $reversePending = UserConnection::where('follower_id', $followingId)
            ->where('following_id', $followerId)
            ->where('status', self::STATUS_PENDING)
            ->first();

        if ($reversePending) {
            $reversePending->status = self::STATUS_ACCEPTED;
            $reversePending->save();
            return ['connection_id' => $reversePending->id, 'status' => self::STATUS_ACCEPTED, 'action' => 'connection_established'];
        }

        $existing = UserConnection::where(function ($q) use ($followerId, $followingId) {
                $q->where('follower_id', $followerId)->where('following_id', $followingId);
            })
            ->orWhere(function ($q) use ($followerId, $followingId) {
                $q->where('follower_id', $followingId)->where('following_id', $followerId);
            })
            ->first();

        if ($existing) {
            if ($existing->status == self::STATUS_BLOCKED)  throw new Exception('Cannot follow blocked user');
            if ($existing->status == self::STATUS_ACCEPTED) throw new Exception('Already connected');
            if ($existing->status == self::STATUS_PENDING) {
                throw new Exception($existing->follower_id == $followerId
                    ? 'Request already sent'
                    : 'Pending request from target exists');
            }
        }

        $targetUser    = User::find($followingId);
        $followingType = $targetUser && $targetUser->usertype === 'company' ? 'company' : 'user';

        $connection = UserConnection::create([
            'follower_id'    => $followerId,
            'follower_type'  => 'user',
            'following_id'   => $followingId,
            'following_type' => $followingType,
            'status'         => self::STATUS_PENDING,
        ]);

        return ['connection_id' => $connection->id, 'status' => self::STATUS_PENDING, 'action' => 'follow_request_sent'];
    }

    private function unfollowUser($followerId, $followingId)
    {
        UserConnection::where(function ($q) use ($followerId, $followingId) {
                $q->where('follower_id', $followerId)->where('following_id', $followingId);
            })
            ->orWhere(function ($q) use ($followerId, $followingId) {
                $q->where('follower_id', $followingId)->where('following_id', $followerId);
            })
            ->delete();

        return ['unfollowed_id' => $followingId, 'action' => 'unfollowed'];
    }

    private function acceptFollowRequest($userId, $followerId)
    {
        $connection = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->first();

        if (!$connection) throw new Exception('No pending follow request found');

        $connection->status = self::STATUS_ACCEPTED;
        $connection->save();

        // Remove any reverse pending to keep a single accepted record
        UserConnection::where('follower_id', $userId)
            ->where('following_id', $followerId)
            ->where('status', self::STATUS_PENDING)
            ->delete();

        return ['connection_id' => $connection->id, 'status' => self::STATUS_ACCEPTED, 'action' => 'request_accepted'];
    }

    private function rejectFollowRequest($userId, $followerId)
    {
        $connection = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->first();

        if (!$connection) throw new Exception('No pending follow request found');

        $connection->delete();

        return ['rejected_user_id' => $followerId, 'action' => 'request_rejected'];
    }

    private function blockUser($blockerId, $blockedId, $reason = null)
    {
        $connection = UserConnection::where(function ($q) use ($blockerId, $blockedId) {
                $q->where('follower_id', $blockerId)->where('following_id', $blockedId);
            })
            ->orWhere(function ($q) use ($blockerId, $blockedId) {
                $q->where('follower_id', $blockedId)->where('following_id', $blockerId);
            })
            ->first();

        if ($connection) {
            $connection->status = self::STATUS_BLOCKED;
            $connection->save();
        }

        $block = BlockedUser::create([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
            'reason'     => $reason,
        ]);

        return ['blocked_id' => $blockedId, 'reason' => $reason, 'action' => 'blocked', 'block_id' => $block->id];
    }

    private function unblockUser($blockerId, $blockedId)
    {
        $connection = UserConnection::where(function ($q) use ($blockerId, $blockedId) {
                $q->where('follower_id', $blockerId)->where('status', self::STATUS_BLOCKED)->where('following_id', $blockedId);
            })
            ->orWhere(function ($q) use ($blockerId, $blockedId) {
                $q->where('follower_id', $blockedId)->where('status', self::STATUS_BLOCKED)->where('following_id', $blockerId);
            })
            ->first();

        if ($connection) {
            $connection->status = self::STATUS_ACCEPTED;
            $connection->save();
        }

        $deleted = BlockedUser::where('blocker_id', $blockerId)->where('blocked_id', $blockedId)->delete();

        if (!$deleted) throw new Exception('User not blocked');

        return ['unblocked_id' => $blockedId, 'action' => 'unblocked'];
    }

}