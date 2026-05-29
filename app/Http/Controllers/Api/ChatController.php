<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\Post;
use App\UserMessage;
use App\Models\ChatType;
use App\Models\WorthDiscussingPoint;
use App\Models\ChatSession;
use App\BlockedUser;
use App\UserConnection;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\PostRepost;
use App\Services\NotificationService;

class ChatController extends Controller
{
   
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';
    
    const ONLINE_THRESHOLD_SECONDS = 30; 

    /**
     * Get all worth discussing points
     */
    public function getWorthDiscussingPoints()
    {
        try {
            $points = WorthDiscussingPoint::active()->ordered()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Worth discussing points retrieved successfully',
                'data' => $points
            ]);
            
        } catch (Exception $e) {
            Log::error('Get worth discussing points failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve worth discussing points',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Check if user is online based on last activity
     */
    private function isUserOnline($lastActivity)
    {
        if (!$lastActivity) {
            return false;
        }
        
        $lastActivityTime = $lastActivity instanceof Carbon 
            ? $lastActivity 
            : Carbon::parse($lastActivity);
        
        return $lastActivityTime->diffInSeconds(now()) < self::ONLINE_THRESHOLD_SECONDS;
    }

    /**
     * Update user's last activity timestamp
     */
    private function updateLastActivity($user)
    {
        if (!$user) {
            return;
        }

        try {
            $user->last_activity = now();
            $user->save();
        } catch (Exception $e) {
            Log::error('Failed to update last activity', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);
        }
    }

    /**
     * Helper function to get entity data from users table
     */
    private function getEntityData($id)
    {
        if (!$id) {
            return null;
        }

        $user = User::find($id);
        
        if ($user) {
            $lastActivity = $user->last_activity ?? null;
            
            if ($user->usertype === 'company') {
                return [
                    'id' => $user->id,
                    'name' => $user->company_name ?? $user->name ?? 'Unknown Company',
                    'usertype' => 'company',
                    'email' => $user->email ?? null,
                    'phone' => $user->phone ?? null,
                    'image' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                               ($user->image ? asset('user_images/' . $user->image) : null),
                    'entity_type' => 'company',
                    'visibility_control' => $user->visibility_control ?? 'public',
                    'post_visibility_control' => $user->post_visibility_control ?? 'public',
                    'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    'last_activity' => $lastActivity,
                    'last_activity_formatted' => $lastActivity ? $this->formatTimeDiff($lastActivity) : null,
                    'is_online' => $this->isUserOnline($lastActivity),
                ];
            }
            
            // Regular user
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $name = $name ?: ($user->name ?? 'Unknown User');
            
            return [
                'id' => $user->id,
                'name' => $name,
                'usertype' => $user->usertype ?? 'user',
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'entity_type' => 'user',
                'visibility_control' => $user->visibility_control ?? 'public',
                'post_visibility_control' => $user->post_visibility_control ?? 'public',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
                'last_activity' => $lastActivity,
                'last_activity_formatted' => $lastActivity ? $this->formatTimeDiff($lastActivity) : null,
                'is_online' => $this->isUserOnline($lastActivity),
            ];
        }

        return [
            'id' => $id,
            'name' => 'Unknown User',
            'usertype' => 'unknown',
            'email' => null,
            'phone' => null,
            'image' => null,
            'entity_type' => 'unknown',
            'visibility_control' => 'public',
            'post_visibility_control' => 'public',
            'message_visibility_control' => 'public',
            'last_activity' => null,
            'last_activity_formatted' => null,
            'is_online' => false,
        ];
    }
    
    
    
    public function deleteConversation($chatSessionId)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $chatSession = ChatSession::find($chatSessionId);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }
    
            if (!$chatSession->hasUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }
    
            DB::beginTransaction();
            
            try {
                // Get current deleted_by array
                $deletedBy = $chatSession->deleted_by ?? [];
                if (is_string($deletedBy)) {
                    $deletedBy = json_decode($deletedBy, true);
                }
                if (!is_array($deletedBy)) {
                    $deletedBy = [];
                }
                
                // Get current user_deleted_at array
                $userDeletedAt = $chatSession->user_deleted_at ?? [];
                if (is_string($userDeletedAt)) {
                    $userDeletedAt = json_decode($userDeletedAt, true);
                }
                if (!is_array($userDeletedAt)) {
                    $userDeletedAt = [];
                }
                
                // Add current user to deleted_by
                if (!in_array($user->id, $deletedBy)) {
                    $deletedBy[] = $user->id;
                    $chatSession->deleted_by = json_encode($deletedBy);
                    
                    // Store the deletion time for this user
                    $userDeletedAt[(string)$user->id] = now()->toDateTimeString();
                    $chatSession->user_deleted_at = json_encode($userDeletedAt);
                    
                    $chatSession->save();
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Conversation deleted successfully',
                    'data' => null
                ]);
                
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Delete conversation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Restore a deleted conversation for current user
     */
    public function restoreConversation($chatSessionId)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $chatSession = ChatSession::find($chatSessionId);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }
    
            if (!$chatSession->hasUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }
    
            DB::beginTransaction();
            
            try {
                $deletedBy = $chatSession->deleted_by ?? [];
                if (is_string($deletedBy)) {
                    $deletedBy = json_decode($deletedBy, true);
                }
                if (!is_array($deletedBy)) {
                    $deletedBy = [];
                }
                
                $deletedBy = array_filter($deletedBy, function($id) use ($user) {
                    return $id != $user->id;
                });
                
                $chatSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                $chatSession->save();
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Conversation restored successfully',
                    'data' => null
                ]);
                
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Restore conversation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore conversation',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get all users with their visibility settings
     */
    private function getVisibilityMap()
    {
        $users = User::select('id', 'usertype', 'visibility_control', 'post_visibility_control', 'message_visibility_control', 'last_activity')->get();
        
        $visibilityMap = [];
        
        foreach ($users as $user) {
            $lastActivity = $user->last_activity ?? null;
            $visibilityMap[$user->id] = [
                'type' => $user->usertype === 'company' ? 'company' : 'user',
                'visibility_control' => $user->visibility_control ?? 'public',
                'post_visibility_control' => $user->post_visibility_control ?? 'public',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
                'last_activity' => $lastActivity,
                'last_activity_formatted' => $lastActivity ? $this->formatTimeDiff($lastActivity) : null,
                'is_online' => $this->isUserOnline($lastActivity),
            ];
        }
        
        return $visibilityMap;
    }

    /**
     * Get target user's message visibility setting
     */
    private function getMessageVisibility($targetId, $visibilityMap = [])
    {
        if (isset($visibilityMap[$targetId])) {
            return $visibilityMap[$targetId]['message_visibility_control'] ?? 'public';
        }
        
        $user = User::find($targetId);
        if ($user) {
            return $user->message_visibility_control ?? 'public';
        }
        
        return 'public';
    }

    /**
     * Check if current user can message the target user
     */
    private function canMessage($currentUserId, $targetId, $connectionIds = [], $visibilityMap = [])
    {
        if ($currentUserId == $targetId) {
            return true;
        }
        
        $messageVisibility = $this->getMessageVisibility($targetId, $visibilityMap);
    
        if ($messageVisibility == 'public') {
            return true;
        }
    
        if ($messageVisibility == 'private') {
            return in_array($targetId, $connectionIds);
        }
        
        return true; 
    }

    /**
     * Get user's connection IDs
     */
    private function getUserConnections($userId)
    {
        $connectionIds = [];
        
        $following = UserConnection::where('follower_id', $userId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $followers = UserConnection::where('following_id', $userId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        $connectionIds = array_unique(array_merge($following, $followers));
        
        return $connectionIds;
    }

    /**
     * Check if users can chat (not blocked)
     */
    private function canChat($user1Id, $user2Id)
    {
        if (!$user1Id || !$user2Id) {
            return false;
        }

        if (BlockedUser::isBlocked($user1Id, $user2Id)) {
            return false;
        }
        
        if (BlockedUser::isBlocked($user2Id, $user1Id)) {
            return false;
        }
        
        $blockedInConnection = UserConnection::where(function($query) use ($user1Id, $user2Id) {
            $query->where('follower_id', $user1Id)
                  ->where('following_id', $user2Id)
                  ->where('status', self::STATUS_BLOCKED);
        })->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('follower_id', $user2Id)
                  ->where('following_id', $user1Id)
                  ->where('status', self::STATUS_BLOCKED);
        })->exists();

        if ($blockedInConnection) {
            return false;
        }
        
        return true;
    }

    /**
     * Get blocked user IDs
     */
    private function getBlockedUserIds($userId)
    {
        $blockedFromBlockedTable = BlockedUser::where('blocker_id', $userId)
            ->pluck('blocked_id')
            ->toArray();
        
        $blockedFromConnection = UserConnection::where('follower_id', $userId)
            ->where('status', self::STATUS_BLOCKED)
            ->pluck('following_id')
            ->toArray();
        
        return array_unique(array_merge($blockedFromBlockedTable, $blockedFromConnection));
    }

    /**
     * Get blocker IDs
     */
    private function getBlockerIds($userId)
    {
        $blockersFromBlockedTable = BlockedUser::where('blocked_id', $userId)
            ->pluck('blocker_id')
            ->toArray();
        
        $blockersFromConnection = UserConnection::where('following_id', $userId)
            ->where('status', self::STATUS_BLOCKED)
            ->pluck('follower_id')
            ->toArray();
        
        return array_unique(array_merge($blockersFromBlockedTable, $blockersFromConnection));
    }

    /**
     * Get all excluded user IDs
     */
    private function getExcludedUserIds($userId)
    {
        $blockedIds = $this->getBlockedUserIds($userId);
        $blockerIds = $this->getBlockerIds($userId);
        return array_unique(array_merge($blockedIds, $blockerIds));
    }

    /**
     * Initialize chat from post
     */
    public function initializeChatFromPost(Request $request, $postId)
    {
        try {
            $user = Auth::user();
 
            if ($request->chat_type == 'general') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type for post-based chat',
                    'errors'  => (object)[
                        'chat_type' => 'General chat cannot be initiated from a post.'
                    ]
                ], 400);
            }
 
            $this->updateLastActivity($user);
 
            $visibilityMap = $this->getVisibilityMap();
            $connectionIds = $this->getUserConnections($user->id);
 
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors'  => (object)['user' => 'User not found']
                ], 404);
            }
 
            $validator = Validator::make($request->all(), [
                'chat_type'                 => 'required|in:job_application,worth_discussing',
                'worth_discussing_point_id' => 'required_if:chat_type,worth_discussing|exists:worth_discussing_points,id',
                'initial_message'           => 'required|string|min:1|max:1000',
            ]);
 
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => (object)$errors
                ], 422);
            }
 
            $post = Post::with('user')->find($postId);
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors'  => (object)['post' => 'Post not found']
                ], 404);
            }
 
            if (!$post->is_active || !$post->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is not available',
                    'errors'  => (object)['post' => 'Post is not published or inactive']
                ], 400);
            }
 
            if ($user->id == $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot initiate chat about your own post',
                    'errors'  => (object)['user' => 'Cannot chat with yourself']
                ], 400);
            }
 
            if (!$this->canChat($user->id, $post->user_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot initiate chat due to block restrictions',
                    'errors'  => (object)['user' => 'Communication not possible']
                ], 403);
            }
 
            if (!$this->canMessage($user->id, $post->user_id, $connectionIds, $visibilityMap)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot send message due to user's privacy settings",
                    'errors'  => (object)['user' => 'This user only accepts messages from connections']
                ], 403);
            }
 
            $chatType = ChatType::where('slug', $request->chat_type)->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type'
                ], 404);
            }
 
            if ($request->chat_type == 'job_application' && !$post->is_job_post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid job post',
                    'errors'  => (object)['post' => 'This post is not a job post']
                ], 400);
            }
 
            if ($request->chat_type == 'worth_discussing') {
                if ($post->is_job_post) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Worth discussing is not available for job posts'
                    ], 400);
                }
 
                $point = WorthDiscussingPoint::find($request->worth_discussing_point_id);
                if (!$point) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid discussion point'
                    ], 404);
                }
            }
 
            // Check for existing session (including deleted ones)
            $existingSessionForThisPost = ChatSession::where('post_id', $post->id)
                ->where('chat_type_id', $chatType->id)
                ->where(function ($query) use ($user, $post) {
                    $query->where(function ($q) use ($user, $post) {
                        $q->where('user1_id', $user->id)
                          ->where('user2_id', $post->user_id);
                    })->orWhere(function ($q) use ($user, $post) {
                        $q->where('user1_id', $post->user_id)
                          ->where('user2_id', $user->id);
                    });
                })
                ->first(); // Include deleted sessions
 
            if ($existingSessionForThisPost) {
                // Check if this session was deleted by current user
                $deletedBy = [];
                if ($existingSessionForThisPost->deleted_by) {
                    if (is_string($existingSessionForThisPost->deleted_by)) {
                        $deletedBy = json_decode($existingSessionForThisPost->deleted_by, true);
                    } else {
                        $deletedBy = $existingSessionForThisPost->deleted_by;
                    }
                    if (!is_array($deletedBy)) {
                        $deletedBy = [];
                    }
                }
                
                $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
                
                if ($isDeletedByCurrentUser) {
                    // Restore the session
                    Log::info('Restoring existing session for user', [
                        'session_id' => $existingSessionForThisPost->id,
                        'user_id' => $user->id
                    ]);
                    
                    $deletedBy = array_filter($deletedBy, function($id) use ($user) {
                        return $id != $user->id;
                    });
                    
                    $existingSessionForThisPost->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                    $existingSessionForThisPost->is_active = true;
                    $existingSessionForThisPost->save();
                    
                    $receiverData = $this->getEntityData($post->user_id);
                    
                    DB::beginTransaction();
                    
                    $subject = $this->generateChatSubject(
                        $request->chat_type,
                        $post,
                        $request->worth_discussing_point_id
                    );
                    
                    $message = UserMessage::create([
                        'listing_id' => $post->id,
                        'listing_title' => $post->title,
                        'from_id' => $user->id,
                        'to_id' => $post->user_id,
                        'to_email' => $receiverData['email'],
                        'to_name' => $receiverData['name'],
                        'from_name' => $currentUserData['name'],
                        'from_email' => $currentUserData['email'],
                        'from_phone' => $currentUserData['phone'],
                        'message_txt' => $request->initial_message,
                        'subject' => $subject,
                        'chat_type_id' => $chatType->id,
                        'chat_session_id' => $existingSessionForThisPost->id,
                        'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                        'status' => 'active',
                        'is_read' => false,
                        'message_type' => 'text',
                    ]);
                    
                    $existingSessionForThisPost->update([
                        'last_message_at' => now(),
                        'last_message' => Str::limit($request->initial_message, 100),
                        'unread_count' => 1
                    ]);
                    
                    DB::commit();
                    
                    $message->load(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint']);
                    
                    // Send push notification
                    $this->sendChatPushNotification($user, $post->user_id, $request->initial_message, $existingSessionForThisPost);
                    
                    return response()->json([
                        'success' => true,
                        'message' => $request->chat_type == 'job_application'
                            ? 'Job application sent successfully'
                            : 'Discussion initiated successfully',
                        'data' => [
                            'message' => $this->formatMessageResponse($message),
                            'session_id' => $existingSessionForThisPost->id,
                            'is_existing_session' => true,
                            'is_restored' => true,
                            'chat_type' => $chatType->slug,
                            'post' => [
                                'id' => $post->id,
                                'title' => $post->title,
                                'is_job_post' => $post->is_job_post,
                            ],
                        ]
                    ], 201);
                } else {
                    // Session exists and not deleted by current user
                    $errorMsg = $request->chat_type === 'worth_discussing'
                        ? 'Worth discussing already initiated for this post'
                        : 'Job application already submitted for this post';
    
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'errors'  => (object)[
                            'post' => $errorMsg,
                            'existing_session_id' => $existingSessionForThisPost->id,
                        ]
                    ], 409);
                }
            }
 
            // No existing session - create new one
            $receiverData = $this->getEntityData($post->user_id);
 
            DB::beginTransaction();
 
            $sessionId = ChatSession::generateId(
                $user->id,
                $post->user_id,
                $post->id,
                $chatType->id
            );
 
            $chatSession = ChatSession::create([
                'id'                        => $sessionId,
                'user1_id'                  => min($user->id, $post->user_id),
                'user2_id'                  => max($user->id, $post->user_id),
                'post_id'                   => $post->id,
                'chat_type_id'              => $chatType->id,
                'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                'last_message_at'           => now(),
                'last_message'              => Str::limit($request->initial_message, 100),
                'unread_count'              => 1,
                'is_active'                 => true,
                'deleted_by'                => null
            ]);
 
            $subject = $this->generateChatSubject(
                $request->chat_type,
                $post,
                $request->worth_discussing_point_id
            );
 
            $message = UserMessage::create([
                'listing_id'                => $post->id,
                'listing_title'             => $post->title,
                'from_id'                   => $user->id,
                'to_id'                     => $post->user_id,
                'to_email'                  => $receiverData['email'],
                'to_name'                   => $receiverData['name'],
                'from_name'                 => $currentUserData['name'],
                'from_email'                => $currentUserData['email'],
                'from_phone'                => $currentUserData['phone'],
                'message_txt'               => $request->initial_message,
                'subject'                   => $subject,
                'chat_type_id'              => $chatType->id,
                'chat_session_id'           => $sessionId,
                'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                'status'                    => 'active',
                'is_read'                   => false,
                'message_type'              => 'text',
            ]);
 
            DB::commit();
 
            $message->load(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint']);
            
            // Send push notification
            $this->sendChatPushNotification($user, $post->user_id, $request->initial_message, $chatSession);
 
            return response()->json([
                'success' => true,
                'message' => $request->chat_type == 'job_application'
                    ? 'Job application sent successfully'
                    : 'Discussion initiated successfully',
                'data'    => [
                    'message'             => $this->formatMessageResponse($message),
                    'session_id'          => $sessionId,
                    'is_existing_session' => false,
                    'chat_type'           => $chatType->slug,
                    'post'                => [
                        'id'          => $post->id,
                        'title'       => $post->title,
                        'is_job_post' => $post->is_job_post,
                    ],
                ]
            ], 201);
 
        } catch (Exception $e) {
            DB::rollBack();
 
            Log::error('Chat initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize chat: ' . $e->getMessage(),
                'errors'  => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
 
    /**
     * Initialize general chat
     */
    public function initializeGeneralChat(Request $request)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $visibilityMap = $this->getVisibilityMap();
            $connectionIds = $this->getUserConnections($user->id);
            
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'to_user_id' => 'required',
                'initial_message' => 'required|string|min:1|max:1000',
                'subject' => 'nullable|string|max:200'
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

            if ($user->id == $request->to_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message to yourself',
                    'errors' => (object)['to_user_id' => 'Cannot send message to yourself']
                ], 400);
            }

            if (!$this->canChat($user->id, $request->to_user_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message due to block restrictions',
                    'errors' => (object)['to_user_id' => 'Communication not possible']
                ], 403);
            }

            if (!$this->canMessage($user->id, $request->to_user_id, $connectionIds, $visibilityMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message due to user\'s privacy settings',
                    'errors' => (object)['to_user_id' => 'This user only accepts messages from connections']
                ], 403);
            }

            $receiverData = $this->getEntityData($request->to_user_id);
            if (!$receiverData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found',
                    'errors' => (object)['to_user_id' => 'User not found']
                ], 404);
            }

            $chatType = ChatType::where('slug', 'general')->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat type not found',
                    'errors' => (object)['chat_type' => 'General chat type not configured']
                ], 404);
            }

            // Check for existing session (including deleted)
            $existingSession = ChatSession::where(function($query) use ($user, $request) {
                    $query->where('user1_id', $user->id)
                          ->where('user2_id', $request->to_user_id);
                })
                ->orWhere(function($query) use ($user, $request) {
                    $query->where('user1_id', $request->to_user_id)
                          ->where('user2_id', $user->id);
                })
                ->where('chat_type_id', $chatType->id)
                ->first();

            $sessionId = null;
            $chatSession = null;

            if ($existingSession) {
                $deletedBy = [];
                if ($existingSession->deleted_by) {
                    if (is_string($existingSession->deleted_by)) {
                        $deletedBy = json_decode($existingSession->deleted_by, true);
                    } else {
                        $deletedBy = $existingSession->deleted_by;
                    }
                    if (!is_array($deletedBy)) {
                        $deletedBy = [];
                    }
                }
                
                $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
                
                if ($isDeletedByCurrentUser) {
                    $deletedBy = array_filter($deletedBy, function($id) use ($user) {
                        return $id != $user->id;
                    });
                    
                    $existingSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                    $existingSession->is_active = true;
                    $existingSession->save();
                    
                    Log::info('Restored general chat session', [
                        'session_id' => $existingSession->id,
                        'user_id' => $user->id
                    ]);
                }
                
                $sessionId = $existingSession->id;
                $chatSession = $existingSession;
            } else {
                $sessionId = ChatSession::generateId(
                    $user->id,
                    $request->to_user_id,
                    null,
                    $chatType->id
                );
                
                $chatSession = ChatSession::create([
                    'id' => $sessionId,
                    'user1_id' => min($user->id, $request->to_user_id),
                    'user2_id' => max($user->id, $request->to_user_id),
                    'post_id' => null,
                    'chat_type_id' => $chatType->id,
                    'worth_discussing_point_id' => null,
                    'last_message_at' => now(),
                    'last_message' => Str::limit($request->initial_message, 100),
                    'is_active' => true,
                    'deleted_by' => null
                ]);
            }

            DB::beginTransaction();

            try {
                $chatSession->increment('unread_count');

                $message = UserMessage::create([
                    'listing_id' => null,
                    'listing_title' => null,
                    'from_id' => $user->id,
                    'to_id' => $request->to_user_id,
                    'to_email' => $receiverData['email'],
                    'to_name' => $receiverData['name'],
                    'from_name' => $currentUserData['name'],
                    'from_email' => $currentUserData['email'],
                    'from_phone' => $currentUserData['phone'],
                    'message_txt' => $request->initial_message,
                    'subject' => $request->subject ?? 'General Chat',
                    'chat_type_id' => $chatType->id,
                    'chat_session_id' => $sessionId,
                    'worth_discussing_point_id' => null,
                    'status' => 'active',
                    'is_read' => false,
                    'message_type' => 'text'
                ]);

                DB::commit();

                $message->load(['sender', 'receiver', 'chatType']);
                
                // Send push notification
                $this->sendChatPushNotification($user, $request->to_user_id, $request->initial_message, $chatSession);

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => [
                        'message' => $this->formatMessageResponse($message),
                        'session_id' => $sessionId,
                        'chat_type' => $chatType->slug
                    ]
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('General chat initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Check or get chat session with a specific user
     */
    public function getOrCheckChatWithUser(Request $request)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            // Get current user entity data
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'chat_type' => 'nullable|string|in:general,job_application,worth_discussing',
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
    
            $targetUserId = $request->user_id;
            $chatType = $request->chat_type ?? 'general';
    
            $visibilityMap = $this->getVisibilityMap();
            $connectionIds = $this->getUserConnections($user->id);
            $canChat = $this->canChat($user->id, $targetUserId);
            $canMessage = $this->canMessage($user->id, $targetUserId, $connectionIds, $visibilityMap);
            
            $targetUserData = $this->getEntityData($targetUserId);
            if (!$targetUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user_id' => 'User not found']
                ], 404);
            }
            
            $targetUserData['message_visibility_control'] = $this->getMessageVisibility($targetUserId, $visibilityMap);
    
            $chatTypeModel = ChatType::where('slug', $chatType)->first();
            if (!$chatTypeModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type',
                    'errors' => (object)['chat_type' => 'Chat type not found']
                ], 404);
            }
    
            $existingSession = ChatSession::where(function($query) use ($user, $targetUserId, $chatTypeModel) {
                    $query->where('user1_id', $user->id)
                          ->where('user2_id', $targetUserId)
                          ->where('chat_type_id', $chatTypeModel->id);
                })
                ->orWhere(function($query) use ($user, $targetUserId, $chatTypeModel) {
                    $query->where('user1_id', $targetUserId)
                          ->where('user2_id', $user->id)
                          ->where('chat_type_id', $chatTypeModel->id);
                })
                ->with(['messages' => function($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }])
                ->first();
    
            if ($existingSession) {
                $deletedBy = [];
                if ($existingSession->deleted_by) {
                    if (is_string($existingSession->deleted_by)) {
                        $deletedBy = json_decode($existingSession->deleted_by, true);
                    } else {
                        $deletedBy = $existingSession->deleted_by;
                    }
                    if (!is_array($deletedBy)) {
                        $deletedBy = [];
                    }
                }
                
                $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
                
                if ($isDeletedByCurrentUser) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No existing chat found',
                        'data' => [
                            'has_chat' => false,
                            'can_chat' => $canChat && $canMessage,
                            'blocked' => !$canChat,
                            'privacy_restricted' => $canChat && !$canMessage,
                            'can_initiate' => $canChat && $canMessage,
                            'other_user' => $targetUserData,
                            'chat_type' => [
                                'id' => $chatTypeModel->id,
                                'name' => $chatTypeModel->name,
                                'slug' => $chatTypeModel->slug,
                            ],
                            'suggestions' => ['You can start a new conversation with this user'],
                        ]
                    ]);
                }
                
                $lastMessage = $existingSession->messages->first();
                $unreadCount = UserMessage::where('chat_session_id', $existingSession->id)
                    ->where('to_id', $user->id)
                    ->where('is_read', false)
                    ->count();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Existing chat found',
                    'data' => [
                        'has_chat' => true,
                        'can_chat' => $canChat && $canMessage,
                        'blocked' => !$canChat,
                        'privacy_restricted' => $canChat && !$canMessage,
                        'chat_session' => [
                            'session_id' => $existingSession->id,
                            'chat_type' => [
                                'id' => $chatTypeModel->id,
                                'name' => $chatTypeModel->name,
                                'slug' => $chatTypeModel->slug,
                            ],
                            'other_user' => $targetUserData,
                            'last_message' => $lastMessage ? [
                                'id' => $lastMessage->id,
                                'message' => Str::limit($lastMessage->message_txt, 100),
                                'created_at' => $lastMessage->created_at,
                                'created_at_formatted' => $this->formatTimeDiff($lastMessage->created_at),
                            ] : null,
                            'unread_count' => $unreadCount,
                            'created_at' => $existingSession->created_at,
                            'created_at_formatted' => $this->formatTimeDiff($existingSession->created_at),
                            'updated_at' => $existingSession->updated_at,
                            'updated_at_formatted' => $this->formatTimeDiff($existingSession->updated_at),
                        ],
                        'can_initiate' => false,
                    ]
                ]);
            }
    
            $canInitiate = $canChat && $canMessage;
            $suggestions = [];
            
            if (!$canChat) {
                $suggestions = ['You cannot communicate with this user due to block restrictions'];
            } elseif (!$canMessage) {
                $suggestions = ['This user only accepts messages from connections. Send a connection request first.'];
            } else {
                $suggestions = [
                    'You can start a new conversation with this user',
                    'Click the chat button to send your first message',
                ];
            }
    
            return response()->json([
                'success' => true,
                'message' => 'No existing chat found',
                'data' => [
                    'has_chat' => false,
                    'can_chat' => $canChat && $canMessage,
                    'blocked' => !$canChat,
                    'privacy_restricted' => $canChat && !$canMessage,
                    'can_initiate' => $canInitiate,
                    'other_user' => $targetUserData,
                    'chat_type' => [
                        'id' => $chatTypeModel->id,
                        'name' => $chatTypeModel->name,
                        'slug' => $chatTypeModel->slug,
                    ],
                    'suggestions' => $suggestions,
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Check chat with user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to check chat status',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

   
    /**
     * Send message in existing chat
     */
    public function sendMessage(Request $request)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $visibilityMap = $this->getVisibilityMap();
            $connectionIds = $this->getUserConnections($user->id);
            
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }
    
            $validator = Validator::make($request->all(), [
                'chat_session_id' => 'required|exists:chat_sessions,id',
                'message' => 'required|string|min:1|max:5000',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240'
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
    
            $chatSession = ChatSession::find($request->chat_session_id);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }
    
            // Get the other user ID (receiver)
            $otherUserId = $chatSession->getOtherUserId($user->id);
            
            // ==============================================
            // RESTORE CONVERSATION FOR BOTH USERS
            // Remove from deleted_by but KEEP user_deleted_at
            // ==============================================
            
            // Parse current deleted_by
            $deletedBy = [];
            if ($chatSession->deleted_by) {
                if (is_string($chatSession->deleted_by)) {
                    $deletedBy = json_decode($chatSession->deleted_by, true);
                } else {
                    $deletedBy = $chatSession->deleted_by;
                }
                if (!is_array($deletedBy)) {
                    $deletedBy = [];
                }
            }
            
            // Remove current user from deleted_by if present
            $isCurrentUserDeleted = in_array($user->id, $deletedBy);
            if ($isCurrentUserDeleted) {
                $deletedBy = array_filter($deletedBy, function($id) use ($user) {
                    return $id != $user->id;
                });
            }
            
            // Remove other user from deleted_by if present (so conversation appears for them)
            $isOtherUserDeleted = in_array($otherUserId, $deletedBy);
            if ($isOtherUserDeleted) {
                $deletedBy = array_filter($deletedBy, function($id) use ($otherUserId) {
                    return $id != $otherUserId;
                });
            }
            
            // Update deleted_by
            $chatSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
            
            // Make sure session is active
            $chatSession->is_active = true;
            $chatSession->save();
            
            // Log restoration
            if ($isOtherUserDeleted) {
                Log::info('Conversation restored for other user (receiver)', [
                    'session_id' => $chatSession->id,
                    'user_id' => $otherUserId,
                    'message_from' => $user->id
                ]);
            }
            
            if ($isCurrentUserDeleted) {
                Log::info('Conversation restored for current user (sender)', [
                    'session_id' => $chatSession->id,
                    'user_id' => $user->id
                ]);
            }
            
            // Check if session is active
            if (!$chatSession->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session is inactive',
                    'errors' => (object)['session' => 'This chat session is no longer active']
                ], 400);
            }
    
            if (!$chatSession->hasUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }
            
            // Check if users can still chat (not blocked)
            if (!$this->canChat($user->id, $otherUserId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message due to block restrictions',
                    'errors' => (object)['session' => 'Communication not possible']
                ], 403);
            }
            
            // Check message visibility permission
            if (!$this->canMessage($user->id, $otherUserId, $connectionIds, $visibilityMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message due to user\'s privacy settings',
                    'errors' => (object)['session' => 'This user has restricted messages to connections only']
                ], 403);
            }
            
            $receiverData = $this->getEntityData($otherUserId);
            if (!$receiverData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found',
                    'errors' => (object)['receiver' => 'Receiver user not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Handle attachments
                $attachments = [];
                if ($request->hasFile('attachments')) {
                    $uploadPath = public_path('chat_attachments');
                    
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    foreach ($request->file('attachments') as $file) {
                        $fileName = 'chat_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                        $file->move($uploadPath, $fileName);
                        
                        $attachments[] = [
                            'filename' => $file->getClientOriginalName(),
                            'path' => $fileName,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];
                    }
                }
                
                // Create message
                $message = UserMessage::create([
                    'listing_id' => $chatSession->post_id,
                    'listing_title' => $chatSession->post ? $chatSession->post->title : null,
                    'from_id' => $user->id,
                    'to_id' => $otherUserId,
                    'to_email' => $receiverData['email'],
                    'to_name' => $receiverData['name'],
                    'from_name' => $currentUserData['name'],
                    'from_email' => $currentUserData['email'],
                    'from_phone' => $currentUserData['phone'],
                    'message_txt' => $request->message,
                    'subject' => $chatSession->post ? $chatSession->post->title : 'General Chat',
                    'chat_type_id' => $chatSession->chat_type_id,
                    'chat_session_id' => $chatSession->id,
                    'worth_discussing_point_id' => $chatSession->worth_discussing_point_id,
                    'status' => 'active',
                    'is_read' => false,
                    'message_type' => !empty($attachments) ? 'file' : 'text',
                    'attachments' => !empty($attachments) ? json_encode($attachments) : null
                ]);
    
                // Update chat session
                $chatSession->update([
                    'last_message_at' => now(),
                    'last_message' => Str::limit($request->message, 100)
                ]);
    
                $chatSession->increment('unread_count');
    
                DB::commit();
    
                $message->load(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint']);
                
                // Send push notification to receiver
                $this->sendChatPushNotification($user, $otherUserId, $request->message, $chatSession);
    
                $responseData = $this->formatMessageResponse($message);
                
                // Add restoration info to response
                if ($isOtherUserDeleted) {
                    $responseData['conversation_restored_for_receiver'] = true;
                    $responseData['message_for_receiver'] = 'Conversation restored for the other user';
                }
                if ($isCurrentUserDeleted) {
                    $responseData['conversation_restored_for_sender'] = true;
                }
    
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $responseData
                ], 201);
    
            } catch (Exception $e) {
                DB::rollBack();
                
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        @unlink(public_path('chat_attachments/' . $attachment['path']));
                    }
                }
                
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Send message failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Restore conversation for a specific user (remove from deleted_by)
     */
    private function restoreConversationForUser($sessionId, $userId)
    {
        try {
            $chatSession = ChatSession::find($sessionId);
            if (!$chatSession) {
                Log::warning('Restore failed: Session not found', [
                    'session_id' => $sessionId,
                    'user_id' => $userId
                ]);
                return false;
            }
            
            // Parse current deleted_by
            $deletedBy = [];
            if ($chatSession->deleted_by) {
                if (is_string($chatSession->deleted_by)) {
                    $deletedBy = json_decode($chatSession->deleted_by, true);
                } else {
                    $deletedBy = $chatSession->deleted_by;
                }
                if (!is_array($deletedBy)) {
                    $deletedBy = [];
                }
            }
            
            // Check if user is in deleted_by
            if (in_array($userId, $deletedBy)) {
                // Remove user from deleted_by
                $deletedBy = array_filter($deletedBy, function($id) use ($userId) {
                    return $id != $userId;
                });
                
                $chatSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                $chatSession->save();
                
                Log::info('Conversation restored for user', [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'updated_deleted_by' => $chatSession->deleted_by
                ]);
                
                return true;
            }
            
            Log::info('User was not in deleted_by, no restore needed', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'current_deleted_by' => $deletedBy
            ]);
            
            return false;
            
        } catch (Exception $e) {
            Log::error('Failed to restore conversation for user', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Check if conversation was restored for a user
     */
    private function isConversationRestoredForUser($sessionId, $userId)
    {
        try {
            $chatSession = ChatSession::find($sessionId);
            if (!$chatSession) {
                return false;
            }
            
            $deletedBy = [];
            if ($chatSession->deleted_by) {
                if (is_string($chatSession->deleted_by)) {
                    $deletedBy = json_decode($chatSession->deleted_by, true);
                } else {
                    $deletedBy = $chatSession->deleted_by;
                }
                if (!is_array($deletedBy)) {
                    $deletedBy = [];
                }
            }
            
            return !in_array($userId, $deletedBy);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Send push notification for new message
     */
    private function sendChatPushNotification($fromUser, $toUserId, $messageText, $chatSession)
    {
        try {
            $receiver = User::find($toUserId);
            
            if (!$receiver) {
                Log::warning('Push notification failed: Receiver not found', ['to_user_id' => $toUserId]);
                return false;
            }
            
            if ($receiver->push_notification != 1 || empty($receiver->firebase_token)) {
                Log::info('Push notification skipped: User has notifications disabled or no token', [
                    'user_id' => $toUserId,
                    'push_notification' => $receiver->push_notification,
                    'has_token' => !empty($receiver->firebase_token)
                ]);
                return false;
            }
            
            $senderName = $fromUser->first_name 
                ? $fromUser->first_name . ' ' . $fromUser->last_name 
                : ($fromUser->name ?? 'Someone');
            
            $title = "New message from " . $senderName;
            $body = Str::limit($messageText, 100);
            
            $actionPayload = [
                'chat_session_id' => (string) $chatSession->id,
                'from_user_id' => (string) $fromUser->id,
                'chat_type' => $chatSession->chatType->slug ?? 'general',
                'action' => 'open_chat'
            ];
            
            if ($chatSession->post_id) {
                $actionPayload['post_id'] = (string) $chatSession->post_id;
                if ($chatSession->post) {
                    $actionPayload['post_title'] = $chatSession->post->title;
                }
            }
            
            if ($chatSession->worth_discussing_point_id) {
                $actionPayload['worth_discussing_point_id'] = (string) $chatSession->worth_discussing_point_id;
            }
            
            Log::info('Sending chat push notification via NotificationService', [
                'to_user_id' => $toUserId,
                'from_user_id' => $fromUser->id,
                'chat_session_id' => $chatSession->id,
                'title' => $title,
                'body' => $body
            ]);
            
            $notificationService = app(NotificationService::class);
            
            $result = $notificationService->send(
                $toUserId,
                'message',
                $title,
                $body,
                'chat',
                $actionPayload,
                $fromUser->id
            );
            
            if ($result) {
                Log::info('Push notification sent successfully', [
                    'to_user_id' => $toUserId,
                    'chat_session_id' => $chatSession->id
                ]);
            } else {
                Log::warning('Push notification returned false', [
                    'to_user_id' => $toUserId,
                    'chat_session_id' => $chatSession->id
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to send chat push notification', [
                'error' => $e->getMessage(),
                'to_user_id' => $toUserId,
                'from_user_id' => $fromUser->id ?? null
            ]);
            return false;
        }
    }
    


   /**
 * Get chat messages
 */
public function getMessages_new(Request $request, $chatSessionId = null)
{
    try {
        $user = Auth::user();
        $this->updateLastActivity($user);
        
        $validator = Validator::make($request->all(), [
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => (object)$errors
            ], 422);
        }

        $sessionId = $chatSessionId ?? $request->chat_session_id;

        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session ID is required',
                'errors'  => (object)['session' => 'Chat session ID is required']
            ], 400);
        }

        $primarySession = ChatSession::with(['chatType', 'worthDiscussingPoint', 'post'])->find($sessionId);
        
        if (!$primarySession) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session not found',
                'errors'  => (object)['session' => 'Chat session not found']
            ], 404);
        }

        if (!$primarySession->hasUser($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors'  => (object)['session' => 'You are not part of this chat session']
            ], 403);
        }

        $otherUserId = $primarySession->getOtherUserId($user->id);
        
        if (!$this->canChat($user->id, $otherUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot view messages due to block restrictions',
                'errors'  => (object)['session' => 'Communication not possible']
            ], 403);
        }

        $perPage      = $request->per_page ?? 50;
        $page         = $request->page     ?? 1;
        $chatTypeId   = $primarySession->chat_type_id;
        $chatTypeSlug = $primarySession->chatType->slug;

        // ==============================================
        // CHECK IF USER EVER DELETED THIS CONVERSATION
        // Get permanent deletion time from user_deleted_at
        // ==============================================
        $userDeletedAt = null;
        $hasUserEverDeleted = false;
        
        if ($primarySession->user_deleted_at) {
            $userDeletedAtArray = [];
            if (is_string($primarySession->user_deleted_at)) {
                $userDeletedAtArray = json_decode($primarySession->user_deleted_at, true);
            } else {
                $userDeletedAtArray = $primarySession->user_deleted_at;
            }
            
            if (is_array($userDeletedAtArray) && isset($userDeletedAtArray[(string)$user->id])) {
                $userDeletedAt = $userDeletedAtArray[(string)$user->id];
                $hasUserEverDeleted = true;
                
                Log::info('User has deletion record', [
                    'user_id' => $user->id,
                    'deleted_at' => $userDeletedAt,
                    'session_id' => $sessionId
                ]);
            }
        }

        // ==============================================
        // BUILD MESSAGE QUERY
        // ==============================================
        $messagesQuery = UserMessage::with([
                'sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint'
            ])
            ->where('status', 'active');

        if ($chatTypeSlug === 'general') {
            // General: only this session's messages
            $messagesQuery->where('chat_session_id', $sessionId);
        } else {
            // Job Application / Worth Discussing:
            // All messages between this user pair with the SAME chat_type_id
            $messagesQuery->where('chat_type_id', $chatTypeId)
                ->where(function ($q) use ($user, $otherUserId) {
                    $q->where(function ($inner) use ($user, $otherUserId) {
                        $inner->where('from_id', $user->id)
                              ->where('to_id', $otherUserId);
                    })->orWhere(function ($inner) use ($user, $otherUserId) {
                        $inner->where('from_id', $otherUserId)
                              ->where('to_id', $user->id);
                    });
                });
        }
        
        // ==============================================
        // CRITICAL: FILTER OUT OLD MESSAGES IF USER EVER DELETED
        // Only show messages AFTER the deletion time
        // ==============================================
        if ($hasUserEverDeleted && $userDeletedAt) {
            $messagesQuery->where('created_at', '>', $userDeletedAt);
            
            Log::info('Filtering messages - showing only messages after deletion', [
                'user_id' => $user->id,
                'deleted_at' => $userDeletedAt,
                'session_id' => $sessionId
            ]);
        }

        $messages = $messagesQuery
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // ==============================================
        // COUNT HIDDEN MESSAGES (for debugging/info)
        // ==============================================
        $hiddenMessagesCount = 0;
        if ($hasUserEverDeleted && $userDeletedAt) {
            $hiddenQuery = UserMessage::where('status', 'active');
            
            if ($chatTypeSlug === 'general') {
                $hiddenQuery->where('chat_session_id', $sessionId);
            } else {
                $hiddenQuery->where('chat_type_id', $chatTypeId)
                    ->where(function ($q) use ($user, $otherUserId) {
                        $q->where(function ($inner) use ($user, $otherUserId) {
                            $inner->where('from_id', $user->id)
                                  ->where('to_id', $otherUserId);
                        })->orWhere(function ($inner) use ($user, $otherUserId) {
                            $inner->where('from_id', $otherUserId)
                                  ->where('to_id', $user->id);
                        });
                    });
            }
            
            $hiddenMessagesCount = $hiddenQuery->where('created_at', '<=', $userDeletedAt)->count();
        }

        // ==============================================
        // MARK MESSAGES AS READ
        // ==============================================
        if ($chatTypeSlug === 'general') {
            // Only mark messages that are visible to user
            $readQuery = UserMessage::where('chat_session_id', $sessionId)
                ->where('to_id', $user->id)
                ->where('is_read', false);
            
            if ($hasUserEverDeleted && $userDeletedAt) {
                $readQuery->where('created_at', '>', $userDeletedAt);
            }
            
            $readQuery->update(['is_read' => true, 'read_at' => now()]);
            $primarySession->update(['unread_count' => 0]);
        } else {
            // Mark all messages sent TO current user
            $readQuery = UserMessage::where('chat_type_id', $chatTypeId)
                ->where('from_id', $otherUserId)
                ->where('to_id', $user->id)
                ->where('is_read', false);
            
            if ($hasUserEverDeleted && $userDeletedAt) {
                $readQuery->where('created_at', '>', $userDeletedAt);
            }
            
            $readQuery->update(['is_read' => true, 'read_at' => now()]);

            // Reset unread_count on ALL sessions of this type
            ChatSession::where('chat_type_id', $chatTypeId)
                ->where(function ($q) use ($user, $otherUserId) {
                    $q->where(function ($inner) use ($user, $otherUserId) {
                        $inner->where('user1_id', $user->id)
                              ->where('user2_id', $otherUserId);
                    })->orWhere(function ($inner) use ($user, $otherUserId) {
                        $inner->where('user1_id', $otherUserId)
                              ->where('user2_id', $user->id);
                    });
                })
                ->update(['unread_count' => 0]);
        }

        // Format messages
        $formattedMessages = $messages->map(function ($message) {
            return $this->formatMessageResponse($message);
        });

        // ==============================================
        // RELATED SESSIONS (for grouped types)
        // ==============================================
        $relatedSessions = [];
        if ($chatTypeSlug !== 'general') {
            $relatedSessions = ChatSession::with(['post', 'worthDiscussingPoint'])
                ->where('chat_type_id', $chatTypeId)
                ->where(function ($q) use ($user, $otherUserId) {
                    $q->where(function ($inner) use ($user, $otherUserId) {
                        $inner->where('user1_id', $user->id)
                              ->where('user2_id', $otherUserId);
                    })->orWhere(function ($inner) use ($user, $otherUserId) {
                        $inner->where('user1_id', $otherUserId)
                              ->where('user2_id', $user->id);
                    });
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($session) {
                    return [
                        'session_id'             => $session->id,
                        'post_id'                => $session->post_id,
                        'post_title'             => $session->post ? $session->post->title : null,
                        'worth_discussing_point' => $session->worthDiscussingPoint ? [
                            'id'    => $session->worthDiscussingPoint->id,
                            'title' => $session->worthDiscussingPoint->title,
                        ] : null,
                        'created_at'             => $session->created_at,
                        'created_at_formatted'   => $this->formatTimeDiff($session->created_at),
                        'message_count'          => UserMessage::where('chat_session_id', $session->id)
                                                        ->where('status', 'active')
                                                        ->count(),
                    ];
                })
                ->toArray();
        }

        // ==============================================
        // CHECK IF CURRENTLY DELETED (for UI)
        // ==============================================
        $currentlyDeletedBy = [];
        if ($primarySession->deleted_by) {
            if (is_string($primarySession->deleted_by)) {
                $currentlyDeletedBy = json_decode($primarySession->deleted_by, true);
            } else {
                $currentlyDeletedBy = $primarySession->deleted_by;
            }
            if (!is_array($currentlyDeletedBy)) {
                $currentlyDeletedBy = [];
            }
        }
        $isCurrentlyDeleted = in_array($user->id, $currentlyDeletedBy);

        return response()->json([
            'success' => true,
            'message' => 'Messages retrieved successfully',
            'data'    => [
                'chat_session'     => $this->formatChatSessionResponse($primarySession, $user),
                'messages'         => $formattedMessages,
                'related_sessions' => $relatedSessions,
                'deletion_info'    => [
                    'has_ever_deleted' => $hasUserEverDeleted,
                    'deleted_at' => $userDeletedAt,
                    'is_currently_deleted' => $isCurrentlyDeleted,
                    'old_messages_hidden' => $hiddenMessagesCount,
                    'only_showing_new_messages' => $hasUserEverDeleted && !$isCurrentlyDeleted
                ],
                'pagination'       => [
                    'current_page' => $messages->currentPage(),
                    'per_page'     => $messages->perPage(),
                    'total'        => $messages->total(),
                    'last_page'    => $messages->lastPage(),
                ],
            ]
        ]);

    } catch (Exception $e) {
        Log::error('Get messages failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve messages',
            'errors'  => (object)['server' => 'An error occurred']
        ], 500);
    }
}
    
    
    
    /**
     * Get conversations with grouping
     */
    public function getConversations_new(Request $request)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'chat_type' => 'nullable|string|in:all,general,job_application,worth_discussing,all_job_woth',
                'search' => 'nullable|string|min:1|max:255',
                'include_deleted' => 'nullable|boolean',
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

            $perPage = $request->per_page ?? 20;
            $page = $request->page ?? 1;
            $chatType = $request->chat_type;
            $searchTerm = $request->search;
            $includeDeleted = $request->include_deleted ?? false;

            $excludedIds = $this->getExcludedUserIds($user->id);

            $allSessions = ChatSession::with([
                'user1',
                'user2',
                'post',
                'chatType',
                'worthDiscussingPoint',
                'messages' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
            ->where('is_active', true)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->orderBy('last_message_at', 'desc')
            ->get();

            $filteredSessions = collect();
            foreach ($allSessions as $session) {
                $otherUserId = $session->getOtherUserId($user->id);
                
                if (in_array($otherUserId, $excludedIds)) {
                    continue;
                }
                
                $deletedBy = [];
                if ($session->deleted_by) {
                    if (is_string($session->deleted_by)) {
                        $deletedBy = json_decode($session->deleted_by, true);
                    } else {
                        $deletedBy = $session->deleted_by;
                    }
                    if (!is_array($deletedBy)) {
                        $deletedBy = [];
                    }
                }
                
                $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
                
                if (!$includeDeleted && $isDeletedByCurrentUser) {
                    continue;
                }
                
                $filteredSessions->push($session);
            }

            $groupedConversations = collect();
            $processedPairs = [];
            
            foreach ($filteredSessions as $session) {
                $otherUserId = $session->getOtherUserId($user->id);
                $chatTypeSlug = $session->chatType->slug;
                $pairKey = min($user->id, $otherUserId) . '_' . max($user->id, $otherUserId) . '_' . $chatTypeSlug;
                
                if ($chatType && $chatType !== 'all') {
                    if ($chatType === 'all_job_woth') {
                        if (!in_array($chatTypeSlug, ['job_application', 'worth_discussing'])) {
                            continue;
                        }
                    } elseif ($chatType !== $chatTypeSlug) {
                        continue;
                    }
                }

                if (in_array($chatTypeSlug, ['job_application', 'worth_discussing'])) {
                    if (isset($processedPairs[$pairKey])) {
                        continue;
                    }
                    $processedPairs[$pairKey] = true;
                }

                if (in_array($chatTypeSlug, ['job_application', 'worth_discussing'])) {
                    $unreadCount = UserMessage::where(function($q) use ($user, $otherUserId) {
                            $q->where('from_id', $user->id)->where('to_id', $otherUserId)
                              ->orWhere('from_id', $otherUserId)->where('to_id', $user->id);
                        })
                        ->whereHas('chatType', function($q) use ($chatTypeSlug) {
                            $q->where('slug', $chatTypeSlug);
                        })
                        ->where('to_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                } else {
                    $unreadCount = UserMessage::where('chat_session_id', $session->id)
                        ->where('to_id', $user->id)
                        ->where('is_read', false)
                        ->count();
                }
                
                $session->unread_count = $unreadCount;
                $groupedConversations->push($session);
            }

            $groupedConversations = $groupedConversations->sortByDesc('last_message_at')->values();

            if ($searchTerm) {
                $groupedConversations = $groupedConversations->filter(function ($session) use ($user, $searchTerm) {
                    $otherUser = $session->getOtherUser($user->id);
                    
                    if (!$otherUser) {
                        return false;
                    }
                    
                    $isCompany = $otherUser->usertype === 'company';
                    
                    if ($isCompany) {
                        $name = $otherUser->company_name ?? $otherUser->name ?? 'Unknown Company';
                    } else {
                        $firstName = $otherUser->first_name ?? '';
                        $lastName = $otherUser->last_name ?? '';
                        $name = trim($firstName . ' ' . $lastName);
                        $name = $name ?: ($otherUser->name ?? 'Unknown User');
                    }
                    
                    $searchTermLower = strtolower($searchTerm);
                    $nameMatch = stripos($name, $searchTermLower) !== false;
                    $emailMatch = isset($otherUser->email) && stripos($otherUser->email, $searchTermLower) !== false;
                    
                    return $nameMatch || $emailMatch;
                });
            }

            $groupedConversations = $groupedConversations->values();

            $total = $groupedConversations->count();
            $offset = ($page - 1) * $perPage;
            $paginatedConversations = $groupedConversations->slice($offset, $perPage)->values();

            $formattedConversations = $paginatedConversations->map(function ($session) use ($user) {
                $formatted = $this->formatChatSessionResponse($session, $user);
                
                $deletedBy = [];
                if ($session->deleted_by) {
                    if (is_string($session->deleted_by)) {
                        $deletedBy = json_decode($session->deleted_by, true);
                    } else {
                        $deletedBy = $session->deleted_by;
                    }
                    if (!is_array($deletedBy)) {
                        $deletedBy = [];
                    }
                }
                
                $formatted['deleted_by'] = $deletedBy;
                $formatted['is_deleted_by_me'] = in_array($user->id, $deletedBy);
                return $formatted;
            });

            $totalUnread = 0;
            $unreadByType = [];
            
            foreach ($groupedConversations as $session) {
                if ($session->unread_count > 0) {
                    $totalUnread += $session->unread_count;
                    $slug = $session->chatType->slug;
                    $unreadByType[$slug] = ($unreadByType[$slug] ?? 0) + $session->unread_count;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversations retrieved successfully',
                'data' => [
                    'conversations' => $formattedConversations,
                    'stats' => [
                        'total_unread' => $totalUnread,
                        'unread_by_type' => (object)$unreadByType
                    ],
                    'pagination' => [
                        'current_page' => (int)$page,
                        'per_page' => (int)$perPage,
                        'total' => (int)$total,
                        'last_page' => (int)ceil($total / $perPage),
                    ],
                    'filters' => [
                        'chat_type' => $chatType ?? 'all',
                        'search' => $searchTerm,
                        'include_deleted' => $includeDeleted,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get conversations failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversations',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Search users for new conversations
     */
    public function searchUsersForChat(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'search' => 'required|string|min:1|max:255',
                'limit' => 'nullable|integer|min:1|max:50',
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

            $searchTerm = $request->search;
            $limit = $request->limit ?? 20;

            $excludedIds = $this->getExcludedUserIds($user->id);

            $existingChatUserIds = ChatSession::where(function($q) use ($user) {
                    $q->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
                })
                ->get()
                ->map(function($session) use ($user) {
                    return $session->getOtherUserId($user->id);
                })
                ->toArray();

            $excludedIds = array_unique(array_merge($excludedIds, [$user->id], $existingChatUserIds));

            $users = User::where('is_active', 1)
                ->whereNotIn('id', $excludedIds)
                ->where(function($q) use ($searchTerm) {
                    $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('company_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('usertype', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('headline', 'LIKE', "%{$searchTerm}%");
                })
                ->limit($limit)
                ->get()
                ->map(function($userResult) {
                    $lastActivity = $userResult->last_activity ?? null;
                    
                    if ($userResult->usertype === 'company') {
                        $name = $userResult->company_name ?? $userResult->name;
                        $image = $userResult->company_logo ? asset('company_logos/' . $userResult->company_logo) : 
                                 ($userResult->image ? asset('user_images/' . $userResult->image) : null);
                    } else {
                        $name = trim(($userResult->first_name ?? '') . ' ' . ($userResult->last_name ?? '')) ?: $userResult->name;
                        $image = $userResult->image ? asset('user_images/' . $userResult->image) : null;
                    }
                    
                    return [
                        'id' => $userResult->id,
                        'name' => $name,
                        'email' => $userResult->email,
                        'usertype' => $userResult->usertype ?? 'user',
                        'image' => $image,
                        'headline' => $userResult->headline,
                        'entity_type' => $userResult->usertype === 'company' ? 'company' : 'user',
                        'can_chat' => true,
                        'message_visibility_control' => $userResult->message_visibility_control ?? 'public',
                        'last_activity' => $lastActivity,
                        'last_activity_formatted' => $lastActivity ? $this->formatTimeDiff($lastActivity) : null,
                        'is_online' => $this->isUserOnline($lastActivity),
                    ];
                });

            $results = $users->sortBy('name')->values();

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'results' => $results->take($limit),
                    'total' => $results->count(),
                    'search_term' => $searchTerm,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('User search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search users',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $validator = Validator::make($request->all(), [
                'message_ids' => 'required|array',
                'message_ids.*' => 'exists:user_messages,id'
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

            $updatedCount = UserMessage::whereIn('id', $request->message_ids)
                ->where('to_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            $affectedSessions = UserMessage::whereIn('id', $request->message_ids)
                ->where('to_id', $user->id)
                ->pluck('chat_session_id')
                ->unique();

            foreach ($affectedSessions as $sessionId) {
                $unreadCount = UserMessage::where('chat_session_id', $sessionId)
                    ->where('to_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                    
                ChatSession::where('id', $sessionId)
                    ->update(['unread_count' => $unreadCount]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Mark messages as read failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    // public function deleteMessage($messageId)
    // {
    //     try {
    //         $user = Auth::user();
    //         $this->updateLastActivity($user);
            
    //         $message = UserMessage::find($messageId);
            
    //         if (!$message) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Message not found',
    //                 'errors' => (object)['message' => 'Message not found']
    //             ], 404);
    //         }

    //         if ($message->from_id != $user->id) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorized',
    //                 'errors' => (object)['message' => 'You can only delete your own messages']
    //             ], 403);
    //         }

    //         DB::beginTransaction();
            
    //         try {
    //             $message->update(['status' => 'deleted']);
                
    //             $chatSession = ChatSession::find($message->chat_session_id);
                
    //             if ($chatSession) {
    //                 $latestMessage = UserMessage::where('chat_session_id', $chatSession->id)
    //                     ->where('status', 'active')
    //                     ->orderBy('created_at', 'desc')
    //                     ->first();
                    
    //                 if ($latestMessage) {
    //                     $chatSession->last_message_at = $latestMessage->created_at;
    //                     $chatSession->last_message = Str::limit($latestMessage->message_txt, 100);
    //                     $chatSession->save();
    //                 } else {
    //                     $hasAnyMessage = UserMessage::where('chat_session_id', $chatSession->id)
    //                         ->whereIn('status', ['active', 'deleted'])
    //                         ->exists();
                        
    //                     if ($hasAnyMessage) {
    //                         $chatSession->last_message_at = $message->created_at;
    //                         $chatSession->last_message = 'Last message deleted';
    //                         $chatSession->save();
    //                     } else {
    //                         $chatSession->last_message_at = null;
    //                         $chatSession->last_message = null;
    //                         $chatSession->save();
    //                     }
    //                 }
    //             }
                
    //             DB::commit();
                
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Message deleted successfully',
    //                 'data' => null
    //             ]);
                
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }

    //     } catch (Exception $e) {
    //         Log::error('Delete message failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete message',
    //             'errors' => (object)['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }
    
    
    /**
     * Delete a message
     */
    public function deleteMessage($messageId)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $message = UserMessage::find($messageId);
            
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                    'errors' => (object)['message' => 'Message not found']
                ], 404);
            }
    
            if ($message->from_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['message' => 'You can only delete your own messages']
                ], 403);
            }
    
            DB::beginTransaction();
            
            try {
                $message->update(['status' => 'deleted']);
                
                $chatSession = ChatSession::find($message->chat_session_id);
                
                if ($chatSession) {
                    // Get the latest ACTIVE message (not deleted)
                    $latestMessage = UserMessage::where('chat_session_id', $chatSession->id)
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestMessage) {
                        // There is at least one active message
                        $chatSession->last_message_at = $latestMessage->created_at;
                        $chatSession->last_message = Str::limit($latestMessage->message_txt, 100);
                        $chatSession->save();
                    } else {
                        // NO active messages left in this conversation
                        // Check if there are ANY messages (including deleted ones)
                        $hasAnyMessage = UserMessage::where('chat_session_id', $chatSession->id)
                            ->exists();
                        
                        if ($hasAnyMessage) {
                            // Messages exist but all are deleted
                            $chatSession->last_message_at = $message->created_at;
                            $chatSession->last_message = 'Please enter message';  // ← CHANGE HERE
                            $chatSession->save();
                        } else {
                            // Completely empty conversation (should not happen normally)
                            $chatSession->last_message_at = null;
                            $chatSession->last_message = null;
                            $chatSession->save();
                        }
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Message deleted successfully',
                    'data' => null
                ]);
                
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Delete message failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Close/end a chat session
     */
    public function closeChatSession($chatSessionId)
    {
        try {
            $user = Auth::user();
            $this->updateLastActivity($user);
            
            $chatSession = ChatSession::find($chatSessionId);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }

            if (!$chatSession->hasUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }

            $chatSession->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Chat session closed successfully',
                'data' => null
            ]);

        } catch (Exception $e) {
            Log::error('Close chat session failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close chat session',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Update user last activity
     */
    public function updateUserActivity(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $this->updateLastActivity($user);

            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'data' => [
                    'last_activity' => now(),
                    'last_activity_formatted' => $this->formatTimeDiff(now()),
                    'is_online' => true
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Update user activity failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Helper: Generate chat subject
     */
    private function generateChatSubject($chatType, $post, $worthDiscussingPointId = null)
    {
        switch ($chatType) {
            case 'job_application':
                return "Job Application: {$post->title}";
                
            case 'worth_discussing':
                $point = WorthDiscussingPoint::find($worthDiscussingPointId);
                return $point ? "{$point->title} - {$post->title}" : "Discussion - {$post->title}";
                
            default:
                return "General Chat";
        }
    }

    /**
     * Helper: Format chat session response
     */
    private function formatChatSessionResponse($session, $currentUser)
    {
        $otherUser = $session->getOtherUser($currentUser->id);
        
        $messageQuery = UserMessage::where('chat_session_id', $session->id);
        
        if ($session->chatType && $session->chatType->slug === 'worth_discussing') {
            $otherUserId = $session->getOtherUserId($currentUser->id);
            $messageQuery = UserMessage::where(function($q) use ($currentUser, $otherUserId) {
                    $q->where('from_id', $currentUser->id)->where('to_id', $otherUserId)
                      ->orWhere('from_id', $otherUserId)->where('to_id', $currentUser->id);
                })
                ->whereHas('chatType', function($q) {
                    $q->where('slug', 'worth_discussing');
                });
        }
        
        $lastMessage = $messageQuery->orderBy('created_at', 'desc')->first();
        $canChat = $this->canChat($currentUser->id, $otherUser ? $otherUser->id : null);
        
        $unreadCount = $session->unread_count;
        if ($session->chatType && $session->chatType->slug === 'worth_discussing') {
            $otherUserId = $session->getOtherUserId($currentUser->id);
            $unreadCount = UserMessage::where(function($q) use ($currentUser, $otherUserId) {
                    $q->where('from_id', $currentUser->id)->where('to_id', $otherUserId)
                      ->orWhere('from_id', $otherUserId)->where('to_id', $currentUser->id);
                })
                ->whereHas('chatType', function($q) {
                    $q->where('slug', 'worth_discussing');
                })
                ->where('to_id', $currentUser->id)
                ->where('is_read', false)
                ->count();
        }
        
        $otherUserData = null;
        if ($otherUser) {
            $isCompany = $otherUser->usertype === 'company';
            
            if ($isCompany) {
                $name = $otherUser->company_name ?? $otherUser->name ?? 'Unknown Company';
                $image = $otherUser->company_logo ? asset('company_logos/' . $otherUser->company_logo) : 
                         ($otherUser->image ? asset('user_images/' . $otherUser->image) : null);
                $usertype = 'company';
                $entityType = 'company';
            } else {
                $firstName = $otherUser->first_name ?? '';
                $lastName = $otherUser->last_name ?? '';
                $name = trim($firstName . ' ' . $lastName);
                $name = $name ?: ($otherUser->name ?? 'Unknown User');
                $image = $otherUser->image ? asset('user_images/' . $otherUser->image) : null;
                $usertype = $otherUser->usertype ?? 'user';
                $entityType = 'user';
            }
            
            $lastActivity = $otherUser->last_activity ?? null;
            $messageVisibility = $otherUser->message_visibility_control ?? 'public';
            
            $otherUserData = [
                'id' => $otherUser->id,
                'name' => $name,
                'email' => $otherUser->email,
                'image' => $image,
                'usertype' => $usertype,
                'entity_type' => $entityType,
                'is_blocked' => !$this->canChat($currentUser->id, $otherUser->id),
                'blocked_by_me' => $this->isBlockedByMe($currentUser->id, $otherUser->id),
                'blocked_by_them' => $this->isBlockedByThem($currentUser->id, $otherUser->id),
                'can_chat' => $canChat,
                'message_visibility_control' => $messageVisibility,
                'last_activity' => $lastActivity,
                'last_activity_formatted' => $lastActivity ? $this->formatTimeDiff($lastActivity) : null,
                'is_online' => $this->isUserOnline($lastActivity),
            ];
        }
        
        $lastMessageAt = $session->last_message_at ? Carbon::parse($session->last_message_at) : null;
        $createdAt = $session->created_at ? Carbon::parse($session->created_at) : null;
        
        $formatted = [
            'session_id' => $session->id,
            'other_user' => $otherUserData,
            'unread_count' => $unreadCount,
            'last_message_at' => $lastMessageAt ? $lastMessageAt->toIso8601String() : null,
            'last_message_at_formatted' => $lastMessageAt ? $lastMessageAt->format('d M Y, h:i A') : null,
            'last_message_at_diff' => $lastMessageAt ? $this->formatTimeDiff($lastMessageAt) : null,
            'last_message' => $session->last_message,
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null,
            'created_at_formatted' => $createdAt ? $createdAt->format('d M Y, h:i A') : null,
            'created_at_diff' => $createdAt ? $this->formatTimeDiff($createdAt) : null,
            'updated_at' => $session->updated_at ? Carbon::parse($session->updated_at)->toIso8601String() : null,
            'updated_at_formatted' => $session->updated_at ? Carbon::parse($session->updated_at)->format('d M Y, h:i A') : null,
            'updated_at_diff' => $session->updated_at ? $this->formatTimeDiff($session->updated_at) : null,
            'is_active' => $session->is_active,
            'can_chat' => $canChat,
        ];
    
        if ($lastMessage) {
            $lastMessageCreatedAt = $lastMessage->created_at ? Carbon::parse($lastMessage->created_at) : null;
            
            $formatted['last_message_details'] = [
                'id' => $lastMessage->id,
                'message' => Str::limit($lastMessage->message_txt, 100),
                'full_message' => $lastMessage->message_txt,
                'is_sender' => $lastMessage->from_id == $currentUser->id,
                'sender_name' => $lastMessage->from_name,
                'created_at' => $lastMessageCreatedAt ? $lastMessageCreatedAt->toIso8601String() : null,
                'created_at_formatted' => $lastMessageCreatedAt ? $lastMessageCreatedAt->format('d M Y, h:i A') : null,
                'created_at_diff' => $lastMessageCreatedAt ? $this->formatTimeDiff($lastMessageCreatedAt) : null,
                'is_read' => $lastMessage->is_read,
                'message_type' => $lastMessage->message_type
            ];
        }
    
        if ($session->chatType) {
            $formatted['chat_type'] = [
                'id' => $session->chatType->id,
                'name' => $session->chatType->name,
                'slug' => $session->chatType->slug
            ];
        }
    
        if ($session->post) {
            $repostRecord = PostRepost::where('reposted_post_id', $session->post->id)->first();
            $isRepost = !is_null($repostRecord);
            $postContent = $session->post->content ? strip_tags($session->post->content) : null;
            
            if ($isRepost && $repostRecord) {
                $postContent = $repostRecord->repost_comment;
            }
            
            $formatted['post'] = [
                'id' => $session->post->id,
                'title' => $session->post->title,
                'content' => $postContent,
                'is_job_post' => $session->post->is_job_post ?? false,
                'is_repost' => $isRepost
            ];
        }
    
        if ($session->worthDiscussingPoint) {
            $formatted['worth_discussing_point'] = [
                'id' => $session->worthDiscussingPoint->id,
                'title' => $session->worthDiscussingPoint->title,
                'icon' => $session->worthDiscussingPoint->icon
            ];
        }
    
        if ($session->chatType && $session->chatType->slug === 'worth_discussing') {
            $otherUserId = $session->getOtherUserId($currentUser->id);
            $totalDiscussions = ChatSession::where(function($q) use ($currentUser, $otherUserId) {
                    $q->where('user1_id', $currentUser->id)->where('user2_id', $otherUserId)
                      ->orWhere('user1_id', $otherUserId)->where('user2_id', $currentUser->id);
                })
                ->whereHas('chatType', function($q) {
                    $q->where('slug', 'worth_discussing');
                })
                ->where('is_active', true)
                ->count();
            
            $formatted['total_discussions'] = $totalDiscussions;
        }
    
        return $formatted;
    }

    /**
     * Helper: Format message response
     */
    private function formatMessageResponse($message)
    {
        $user = Auth::user();
        $attachments = $message->attachments ? json_decode($message->attachments, true) : [];
        
        $senderData = $this->getEntityData($message->from_id);
        $receiverData = $this->getEntityData($message->to_id);
        
        $createdAt = $message->created_at ? Carbon::parse($message->created_at) : null;
        $updatedAt = $message->updated_at ? Carbon::parse($message->updated_at) : null;
        $readAt = $message->read_at ? Carbon::parse($message->read_at) : null;
        
        $isWorthDiscussion = ($message->chatType && $message->chatType->slug === 'worth_discussing');
        
        $isNeedDesign = false;
        $matchingWorthPoint = null;
        
        if ($message->message_txt) {
            static $worthPoints = null;
            if ($worthPoints === null) {
                $worthPoints = WorthDiscussingPoint::where('is_active', true)
                    ->pluck('title')
                    ->map(function($title) {
                        return trim($title);
                    })
                    ->toArray();
            }
            
            $messageText = trim($message->message_txt);
            if (in_array($messageText, $worthPoints, true)) {
                $isNeedDesign = true;
                $matchingWorthPoint = $messageText;
            }
        }
        
        $formatted = [
            'id' => $message->id,
            'message' => $message->message_txt,
            'subject' => $message->subject,
            'is_read' => $message->is_read,
            'read_at' => $readAt ? $readAt->toIso8601String() : null,
            'read_at_formatted' => $readAt ? $readAt->format('d M Y, h:i A') : null,
            'read_at_diff' => $readAt ? $this->formatTimeDiff($readAt) : null,
            'message_type' => $message->message_type,
            'attachments' => array_map(function($attachment) {
                return [
                    'filename' => $attachment['filename'] ?? '',
                    'path' => isset($attachment['path']) ? asset('chat_attachments/' . $attachment['path']) : null,
                    'mime_type' => $attachment['mime_type'] ?? '',
                    'size' => $attachment['size'] ?? 0
                ];
            }, $attachments),
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null,
            'created_at_formatted' => $createdAt ? $createdAt->format('d M Y, h:i A') : null,
            'created_at_diff' => $createdAt ? $this->formatTimeDiff($createdAt) : null,
            'created_at_date' => $createdAt ? $createdAt->format('Y-m-d') : null,
            'created_at_time' => $createdAt ? $createdAt->format('H:i:s') : null,
            'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : null,
            'updated_at_formatted' => $updatedAt ? $updatedAt->format('d M Y, h:i A') : null,
            'is_sender' => $message->from_id == $user->id,
            'is_worth_discussion' => $isWorthDiscussion,
            'is_need_design' => $isNeedDesign,
            'matching_worth_point' => $matchingWorthPoint,
            'sender' => $senderData,
            'receiver' => $receiverData,
        ];
    
        if ($message->listing_id && $message->post) {
            $postCreatedAt = $message->post->created_at ? 
                Carbon::parse($message->post->created_at) : null;
                
            $formatted['post'] = [
                'id' => $message->post->id,
                'title' => $message->post->title,
                'is_job_post' => $message->post->is_job_post ?? false,
                'created_at' => $postCreatedAt ? $postCreatedAt->toIso8601String() : null,
                'created_at_formatted' => $postCreatedAt ? $postCreatedAt->format('d M Y, h:i A') : null,
            ];
        }
    
        if ($message->chatType) {
            $formatted['chat_type'] = [
                'id' => $message->chatType->id,
                'name' => $message->chatType->name,
                'slug' => $message->chatType->slug
            ];
        }
    
        if ($message->worthDiscussingPoint) {
            $formatted['worth_discussing_point'] = [
                'id' => $message->worthDiscussingPoint->id,
                'title' => $message->worthDiscussingPoint->title,
                'description' => $message->worthDiscussingPoint->description,
                'icon' => $message->worthDiscussingPoint->icon
            ];
        }
    
        return $formatted;
    }

    /**
     * Helper: Format time difference
     */
    private function formatTimeDiff($dateTime)
    {
        if (!$dateTime) {
            return null;
        }

        $now = Carbon::now();
        $diffInSeconds = $now->diffInSeconds($dateTime);
        $diffInMinutes = $now->diffInMinutes($dateTime);
        $diffInHours = $now->diffInHours($dateTime);
        $diffInDays = $now->diffInDays($dateTime);

        if ($diffInSeconds < 60) {
            return $diffInSeconds <= 5 ? 'Just now' : $diffInSeconds . ' seconds ago';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' ' . ($diffInMinutes == 1 ? 'minute ago' : 'minutes ago');
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ' . ($diffInHours == 1 ? 'hour ago' : 'hours ago');
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' ' . ($diffInDays == 1 ? 'day ago' : 'days ago');
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' ' . ($weeks == 1 ? 'week ago' : 'weeks ago');
        } elseif ($diffInDays < 365) {
            $months = floor($diffInDays / 30);
            return $months . ' ' . ($months == 1 ? 'month ago' : 'months ago');
        } else {
            $years = floor($diffInDays / 365);
            return $years . ' ' . ($years == 1 ? 'year ago' : 'years ago');
        }
    }

    /**
     * Helper: Check if current user blocked the other user
     */
    private function isBlockedByMe($currentUserId, $otherUserId)
    {
        if (!$currentUserId || !$otherUserId) {
            return false;
        }

        if (BlockedUser::isBlocked($currentUserId, $otherUserId)) {
            return true;
        }

        return UserConnection::where('follower_id', $currentUserId)
            ->where('following_id', $otherUserId)
            ->where('status', self::STATUS_BLOCKED)
            ->exists();
    }

    /**
     * Helper: Check if other user blocked current user
     */
    private function isBlockedByThem($currentUserId, $otherUserId)
    {
        if (!$currentUserId || !$otherUserId) {
            return false;
        }

        if (BlockedUser::isBlocked($otherUserId, $currentUserId)) {
            return true;
        }

        return UserConnection::where('follower_id', $otherUserId)
            ->where('following_id', $currentUserId)
            ->where('status', self::STATUS_BLOCKED)
            ->exists();
    }
}