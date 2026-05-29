<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\Notification;
use App\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send notification to user
     */
    public function send(
        $userId,
        $type,
        $title,
        $body,
        $screen = null,
        $actionPayload = [],
        $fromUserId = null
    ) {
        try {
            Log::info('🔵 NotificationService: Starting send process', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title
            ]);

            $user = User::find($userId);
            
            if (!$user) {
                Log::error('❌ NotificationService: User not found', ['user_id' => $userId]);
                return false;
            }

            Log::info('✅ NotificationService: User found', [
                'user_id' => $user->id,
                'user_name' => $user->getName(),
                'push_notification' => $user->push_notification,
                'has_firebase_token' => !empty($user->firebase_token) ? 'yes' : 'no'
            ]);

            // Prepare data payload for routing - CONVERT ALL VALUES TO STRINGS
            $dataPayload = [
                'screen' => (string) $screen,
                'type' => (string) $type,
                'timestamp' => (string) now()->toIso8601String()
            ];

            // Merge action payload - CONVERT ALL VALUES TO STRINGS
            if (!empty($actionPayload)) {
                foreach ($actionPayload as $key => $value) {
                    // Convert each value to string, handle arrays by json_encode
                    if (is_array($value)) {
                        $dataPayload[$key] = json_encode($value);
                    } else {
                        $dataPayload[$key] = (string) $value;
                    }
                }
            }

            // Store in database
            $notification = Notification::create([
                'user_id' => $userId,
                'from_user_id' => $fromUserId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $dataPayload,
                'screen' => $screen,
                'action_payload' => $actionPayload,
                'is_read' => false,
                'status' => 'active'
            ]);

            Log::info('✅ NotificationService: Database record created', [
                'notification_id' => $notification->id
            ]);

            // Send push notification if user has push enabled
            if ($user->push_notification && $user->firebase_token) {
                Log::info('🔵 NotificationService: Attempting to send FCM push', [
                    'token_exists' => !empty($user->firebase_token),
                    'push_enabled' => $user->push_notification
                ]);

                $fcmResult = $this->fcmService->fcmSendNotification(
                    $user->firebase_token,
                    $title,
                    $body,
                    $dataPayload  // Now all values are strings
                );

                Log::info('✅ NotificationService: FCM push completed', [
                    'result' => $fcmResult
                ]);
            } else {
                Log::warning('⚠️ NotificationService: Skipping FCM push - conditions not met', [
                    'push_notification' => $user->push_notification,
                    'firebase_token' => !empty($user->firebase_token) ? 'present' : 'missing'
                ]);
            }

            return $notification;

        } catch (\Exception $e) {
            Log::error('❌ NotificationService: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'type' => $type
            ]);
            return false;
        }
    }

    /**
     * Send multiple notifications (bulk)
     */
    public function sendBulk($userIds, $type, $title, $body, $screen = null, $actionPayload = [], $fromUserId = null)
    {
        Log::info('🔵 NotificationService: Starting bulk send', [
            'user_count' => count($userIds),
            'type' => $type
        ]);

        $results = [];
        foreach ($userIds as $userId) {
            $result = $this->send($userId, $type, $title, $body, $screen, $actionPayload, $fromUserId);
            $results[$userId] = $result ? true : false;
        }

        Log::info('✅ NotificationService: Bulk send completed', [
            'results' => $results
        ]);

        return $results;
    }

    /**
     * Send follow request notification
     */
    public function sendFollowRequest($fromUser, $toUserId)
    {
        $title = "New Follow Request";
        $body = $fromUser->getName() . " sent you a follow request.";
        
        return $this->send(
            $toUserId,
            'follow_request',
            $title,
            $body,
            'profile',
            [
                'from_user_id' => (string) $fromUser->id,
                'action' => 'follow_request'
            ],
            $fromUser->id
        );
    }

    /**
     * Send follow request accepted notification
     */
    public function sendFollowAccepted($fromUser, $toUserId)
    {
        $title = "Follow Request Accepted";
        $body = $fromUser->getName() . " accepted your follow request.";
        
        return $this->send(
            $toUserId,
            'follow_accepted',
            $title,
            $body,
            'profile',
            [
                'from_user_id' => (string) $fromUser->id,
                'action' => 'follow_accepted'
            ],
            $fromUser->id
        );
    }

    /**
     * Send message notification
     */
    public function sendMessage($fromUser, $toUserId, $message, $chatSessionId)
    {
        $title = "New Message";
        $body = $fromUser->getName() . ": " . $message;
        
        return $this->send(
            $toUserId,
            'message',
            $title,
            $body,
            'chat',
            [
                'from_user_id' => (string) $fromUser->id,
                'chat_session_id' => (string) $chatSessionId,
                'action' => 'open_chat'
            ],
            $fromUser->id
        );
    }

    /**
     * Send post comment notification
     */
    public function sendPostComment($fromUser, $toUserId, $postId, $postTitle)
    {
        $title = "New Comment";
        $body = $fromUser->getName() . " commented on your post: " . $postTitle;
        
        return $this->send(
            $toUserId,
            'post_comment',
            $title,
            $body,
            'post_detail',
            [
                'from_user_id' => (string) $fromUser->id,
                'post_id' => (string) $postId,
                'action' => 'view_post'
            ],
            $fromUser->id
        );
    }

    /**
     * Send job application notification
     */
    public function sendJobApplication($fromUser, $toUserId, $postId, $postTitle)
    {
        $title = "New Job Application";
        $body = $fromUser->getName() . " applied for: " . $postTitle;
        
        return $this->send(
            $toUserId,
            'job_application',
            $title,
            $body,
            'job_applications',
            [
                'from_user_id' => (string) $fromUser->id,
                'post_id' => (string) $postId,
                'action' => 'view_applications'
            ],
            $fromUser->id
        );
    }

    /**
     * Send worth discussing notification
     */
    public function sendWorthDiscussing($fromUser, $toUserId, $postId, $postTitle, $pointTitle)
    {
        $title = "New Discussion";
        $body = $fromUser->getName() . " wants to discuss: " . $pointTitle . " on " . $postTitle;
        
        return $this->send(
            $toUserId,
            'worth_discussing',
            $title,
            $body,
            'chat',
            [
                'from_user_id' => (string) $fromUser->id,
                'post_id' => (string) $postId,
                'point_title' => (string) $pointTitle,
                'action' => 'open_chat'
            ],
            $fromUser->id
        );
    }
}