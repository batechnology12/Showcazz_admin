<?php
// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function getNotifications(Request $request)
    {
        
       
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'type' => 'nullable|string',
                'status' => 'nullable|in:active,archived,all',
                'is_read' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object) $validator->errors()->toArray()
                ], 422);
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            $type = $request->get('type');
            $status = $request->get('status', 'active');
            $isRead = $request->get('is_read');

            // Build query
            $query = Notification::with('fromUser')
                ->where('user_id', $user->id)
                ->recent();

            // Apply filters
            if ($type) {
                $query->byType($type);
            }

            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'archived') {
                $query->where('status', 'archived');
            }

            if ($isRead !== null) {
                $query->where('is_read', $isRead);
            }

            // Get paginated results
            $notifications = $query->paginate($perPage, ['*'], 'page', $page);

            // Format notifications
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return $notification->formatForApi();
            });

            // Get unread count
            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->active()
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => [
                    'notifications' => $formattedNotifications,
                    'unread_count' => $unreadCount,
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get notifications failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'exists:notifications,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object) $validator->errors()->toArray()
                ], 422);
            }

            $notificationIds = $request->notification_ids;

            // Update notifications
            $updated = Notification::whereIn('id', $notificationIds)
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read',
                'data' => [
                    'updated_count' => $updated
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Mark notifications as read failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Mark single notification as read
     */
    public function markSingleAsRead($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification->formatForApi()
            ]);

        } catch (Exception $e) {
            Log::error('Mark notification as read failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Mark notification as clicked
     */
    public function markAsClicked($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsClicked();

            // Also mark as read if not already
            if (!$notification->is_read) {
                $notification->markAsRead();
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as clicked',
                'data' => [
                    'screen' => $notification->screen,
                    'action_payload' => $notification->action_payload
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Mark notification as clicked failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as clicked',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Archive notification
     */
    public function archiveNotification($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->archive();

            return response()->json([
                'success' => true,
                'message' => 'Notification archived successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Archive notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive notification',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Archive multiple notifications
     */
    public function archiveMultiple(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'exists:notifications,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object) $validator->errors()->toArray()
                ], 422);
            }

            $updated = Notification::whereIn('id', $request->notification_ids)
                ->where('user_id', $user->id)
                ->update(['status' => 'archived']);

            return response()->json([
                'success' => true,
                'message' => 'Notifications archived successfully',
                'data' => [
                    'archived_count' => $updated
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Archive multiple notifications failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive notifications',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function deleteNotification($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Delete notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        try {
            $user = Auth::user();

            Notification::where('user_id', $user->id)
                ->active()
                ->update(['status' => 'archived']);

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Clear all notifications failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get unread count
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();

            $count = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->active()
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Unread count retrieved',
                'data' => [
                    'unread_count' => $count
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get unread count failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get notification by ID
     */
    public function getNotification($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::with('fromUser')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification retrieved successfully',
                'data' => $notification->formatForApi()
            ]);

        } catch (Exception $e) {
            Log::error('Get notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification',
                'errors' => (object) ['server' => 'An error occurred']
            ], 500);
        }
    }
}