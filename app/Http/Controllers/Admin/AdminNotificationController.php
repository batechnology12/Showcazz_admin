<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Models\AdminNotification;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminNotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->middleware('auth:admin');
        $this->fcmService = $fcmService;
    }

    /**
     * Notification Dashboard
     */
    public function dashboard(Request $request)
    {
        $stats = [
            'total_sent' => AdminNotification::count(),
            'total_recipients' => AdminNotification::sum('total_recipients'),
            'success_rate' => $this->calculateSuccessRate(),
            'notifications_today' => AdminNotification::whereDate('created_at', today())->count(),
        ];

        $recentNotifications = AdminNotification::with('sentBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Update user stats to include push_notification counts
        $userStats = [
            'total_users' => User::count(),
            'with_fcm_token' => User::whereNotNull('firebase_token')->count(),
            'push_enabled' => User::where('push_notification', true)->count(),
            'push_disabled' => User::where('push_notification', false)->count(),
            'eligible_for_notifications' => User::whereNotNull('firebase_token')
                ->where('push_notification', true)
                ->count(),
            'companies' => User::where('usertype', 'company')->count(),
            'companies_eligible' => User::where('usertype', 'company')
                ->whereNotNull('firebase_token')
                ->where('push_notification', true)
                ->count(),
            'students' => User::where('usertype', 'student')->count(),
            'students_eligible' => User::where('usertype', 'student')
                ->whereNotNull('firebase_token')
                ->where('push_notification', true)
                ->count(),
            'professionals' => User::where('usertype', 'professional')->count(),
            'professionals_eligible' => User::where('usertype', 'professional')
                ->whereNotNull('firebase_token')
                ->where('push_notification', true)
                ->count(),
            'subscribed' => User::where('usertype', 'company')->whereNotNull('package_id')->count(),
            'subscribed_eligible' => User::where('usertype', 'company')
                ->whereNotNull('package_id')
                ->whereNotNull('firebase_token')
                ->where('push_notification', true)
                ->count(),
        ];

        $userStats['fcm_coverage'] = $userStats['total_users'] > 0 
            ? round(($userStats['with_fcm_token'] / $userStats['total_users']) * 100, 2) 
            : 0;
            
        $userStats['eligibility_rate'] = $userStats['total_users'] > 0 
            ? round(($userStats['eligible_for_notifications'] / $userStats['total_users']) * 100, 2) 
            : 0;

        return view('admin.notification.dashboard', compact('stats', 'recentNotifications', 'userStats'));
    }

    /**
     * Show send notification form
     */
    public function showSendForm()
    {
        // Get counts for display in the form
        $counts = [
            'all' => User::whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'companies' => User::where('usertype', 'company')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'students' => User::where('usertype', 'student')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'professionals' => User::where('usertype', 'professional')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'subscribed' => User::where('usertype', 'company')->whereNotNull('package_id')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
        ];
        
        return view('admin.notification.send', compact('counts'));
    }

    /**
     * Show advanced send notification form
     */
    public function showAdvancedSendForm()
    {
        // Get counts for display in the form
        $counts = [
            'all' => User::whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'companies' => User::where('usertype', 'company')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'students' => User::where('usertype', 'student')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'professionals' => User::where('usertype', 'professional')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
            'subscribed' => User::where('usertype', 'company')->whereNotNull('package_id')->whereNotNull('firebase_token')->where('push_notification', true)->count(),
        ];
        
        // Additional data for advanced targeting
        $data = [
            'categories' => DB::table('categories')->get(),
        ];
        
        return view('admin.notification.advanced_send', compact('counts', 'data'));
    }

    /**
     * Send notification to all users (only those with push_notification = true)
     */
    public function sendToAllUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $users = User::whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'all'
        );

        return $this->handleResult($request, $result);
    }

    /**
     * Send notification to all companies (only those with push_notification = true)
     */
    public function sendToCompanies(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $users = User::where('usertype', 'company')
            ->whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'companies'
        );

        return $this->handleResult($request, $result);
    }

    /**
     * Send notification to all students (only those with push_notification = true)
     */
    public function sendToStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $users = User::where('usertype', 'student')
            ->whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'students'
        );

        return $this->handleResult($request, $result);
    }

    /**
     * Send notification to all professionals (only those with push_notification = true)
     */
    public function sendToProfessionals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $users = User::where('usertype', 'professional')
            ->whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'professionals'
        );

        return $this->handleResult($request, $result);
    }

    /**
     * Send notification to subscribed users (companies with active packages, only those with push_notification = true)
     */
    public function sendToSubscribedUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $users = User::where('usertype', 'company')
            ->whereNotNull('package_id')
            ->whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'subscribed'
        );

        return $this->handleResult($request, $result);
    }

    /**
     * Send notification to custom selected users (only those with push_notification = true)
     */
    public function sendToCustomUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Only include users who have push_notification enabled
        $users = User::whereIn('id', $request->user_ids)
            ->whereNotNull('firebase_token')
            ->where('push_notification', true)
            ->get();

        // Calculate how many were filtered out
        $totalRequested = count($request->user_ids);
        $actualRecipients = $users->count();
        $filteredOut = $totalRequested - $actualRecipients;

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'custom',
            $request->user_ids
        );

        // Add filtered count to result message
        if ($filteredOut > 0) {
            $result['message'] .= " (Skipped {$filteredOut} users with notifications disabled)";
            $result['data']['skipped_count'] = $filteredOut;
        }

        return $this->handleResult($request, $result);
    }

    /**
     * Handle the result of sending notifications
     */
    private function handleResult($request, $result)
    {
        // Store in session for success page
        session()->flash('notification_title', $request->title);
        session()->flash('notification_message', $request->message);
        session()->flash('notification_image', $request->image);
        session()->flash('notification_target', $request->target_type ?? $request->route()->getActionMethod());
        session()->flash('recipients', $result['data']['recipient_preview'] ?? []);
        session()->flash('skipped_count', $result['data']['skipped_count'] ?? 0);

        // Check if it's an AJAX request
        if ($request->expectsJson()) {
            return response()->json($result);
        }

        // Redirect to success page
        return redirect()->route('admin.notifications.success', ['id' => $result['data']['notification_id']])
            ->with('success', "Notification sent to {$result['data']['success_count']} users successfully. Failed: {$result['data']['failed_count']}");
    }

    /**
     * Generic method to send bulk notifications
     */
    private function sendBulkNotification($users, $title, $message, $image = null, $targetType = 'broadcast', $targetUserIds = [], $customData = [])
    {
        $admin = Auth::guard('admin')->user();
        
        $successCount = 0;
        $failedCount = 0;
        $responseData = [];
        $recipientPreview = [];

        // Prepare default data payload
        $dataPayload = [
            'screen' => 'home',
            'type' => 'admin',
            'action' => 'open_app',
            'sent_at' => now()->toDateTimeString(),
        ];

        // Merge custom data if provided (for advanced notifications)
        if (!empty($customData)) {
            $dataPayload = array_merge($dataPayload, $customData);
        }

        if ($image) {
            $dataPayload['image'] = $image;
        }

        foreach ($users as $index => $user) {
            try {
                $response = $this->fcmService->fcmSendNotification(
                    $user->firebase_token,
                    $title,
                    $message,
                    $dataPayload
                );

                $responseData[] = [
                    'user_id' => $user->id,
                    'name' => $user->getName(),
                    'email' => $user->email,
                    'type' => $user->usertype,
                    'push_enabled' => $user->push_notification,
                    'success' => true,
                    'response' => $response
                ];
                
                // Store preview for first 10 recipients
                if ($index < 10) {
                    $recipientPreview[] = [
                        'name' => $user->getName(),
                        'email' => $user->email,
                        'type' => $user->usertype,
                        'success' => true
                    ];
                }
                
                $successCount++;
                
            } catch (Exception $e) {
                Log::error('Failed to send notification to user: ' . $user->id, [
                    'error' => $e->getMessage()
                ]);
                
                $responseData[] = [
                    'user_id' => $user->id,
                    'name' => $user->getName(),
                    'email' => $user->email,
                    'type' => $user->usertype,
                    'push_enabled' => $user->push_notification,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                
                if ($index < 10) {
                    $recipientPreview[] = [
                        'name' => $user->getName(),
                        'email' => $user->email,
                        'type' => $user->usertype,
                        'success' => false
                    ];
                }
                
                $failedCount++;
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }

        // Save notification record
        $notification = AdminNotification::create([
            'title' => $title,
            'message' => $message,
            'type' => $targetType === 'custom' ? 'custom' : 'broadcast',
            'target_users' => $targetType === 'custom' ? $targetUserIds : null,
            'target_type' => $targetType,
            'total_recipients' => $users->count(),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'response_data' => $responseData,
            'sent_by' => $admin->id,
        ]);

        return [
            'success' => true,
            'message' => "Notification sent to {$successCount} users successfully. Failed: {$failedCount}",
            'data' => [
                'notification_id' => $notification->id,
                'total_recipients' => $users->count(),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'skipped_count' => 0,
                'recipient_preview' => $recipientPreview
            ]
        ];
    }

    /**
     * Search users for custom notification (only show users with push_notification = true)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('q', '');
        $type = $request->get('type', '');

        $query = User::whereNotNull('firebase_token')
            ->where('push_notification', true);

        if ($type && $type !== 'all') {
            $query->where('usertype', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        // Fetch results and ensure uniqueness at the collection level to avoid SQL dialect issues
        $users = $query->limit(40)->get()->unique('id')->take(20)->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->usertype === 'company' 
                    ? ($user->company_name ?? $user->name) 
                    : $user->getName(),
                'email' => $user->email,
                'usertype' => $user->usertype,
                'has_fcm' => !is_null($user->firebase_token),
                'push_enabled' => $user->push_notification,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Search posts for advanced notification deep linking
     */
    public function searchPosts(Request $request)
    {
        $search = $request->get('q', '');

        $query = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.id', 'posts.title', 'users.first_name', 'users.last_name', 'posts.created_at')
            ->where('posts.is_active', true);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('posts.title', 'LIKE', "%{$search}%")
                  ->orWhere('posts.id', '=', $search);
            });
        }

        $posts = $query->limit(10)->get()->map(function($post) {
            return [
                'id' => $post->id,
                'title' => $post->title ?: 'Untitled Post',
                'user_name' => $post->first_name . ' ' . $post->last_name,
                'date' => Carbon::parse($post->created_at)->format('d M Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * Show notification success page
     */
    public function success($id)
    {
        $notification = AdminNotification::with('sentBy')->findOrFail($id);
        
        $data = [
            'notification_id' => $notification->id,
            'total_recipients' => $notification->total_recipients,
            'success_count' => $notification->success_count,
            'failed_count' => $notification->failed_count,
        ];

        // Get recipient preview from session or notification data
        $recipients = session('recipients', []);
        $skippedCount = session('skipped_count', 0);
        
        // If no recipients in session, generate from response_data
        if (empty($recipients) && $notification->response_data) {
            $recipients = collect($notification->response_data)
                ->take(10)
                ->map(function($item) {
                    return [
                        'name' => $item['name'] ?? 'Unknown',
                        'email' => $item['email'] ?? '',
                        'type' => $item['type'] ?? 'user',
                        'success' => $item['success'] ?? false
                    ];
                })
                ->toArray();
        }

        return view('admin.notification.success', compact('data', 'recipients', 'notification', 'skippedCount'));
    }

    /**
     * Send notification (generic method for form submission)
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_type' => 'required|in:all,companies,students,professionals,subscribed,custom',
            'user_ids' => 'required_if:target_type,custom|array',
            'user_ids.*' => 'required_if:target_type,custom|exists:users,id',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        switch ($request->target_type) {
            case 'all':
                return $this->sendToAllUsers($request);
            case 'companies':
                return $this->sendToCompanies($request);
            case 'students':
                return $this->sendToStudents($request);
            case 'professionals':
                return $this->sendToProfessionals($request);
            case 'subscribed':
                return $this->sendToSubscribedUsers($request);
            case 'custom':
                return $this->sendToCustomUsers($request);
            default:
                return redirect()->back()->with('error', 'Invalid target type');
        }
    }

    /**
     * Send advanced notification
     */
    public function sendAdvancedNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_type' => 'required',
            'image' => 'nullable|url',
            'action_type' => 'nullable|string',
            'action_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Logic for advanced targeting
        $query = User::whereNotNull('firebase_token')->where('push_notification', true);

        if ($request->target_type === 'custom') {
            $query->whereIn('id', $request->user_ids);
        } elseif ($request->target_type !== 'all') {
             $query->where('usertype', $request->target_type);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No eligible users found for selected targeting')->withInput();
        }

        // Determine screen for mobile navigation and map action values
        $actionType = $request->action_type ?? 'open_app';
        $screen = $actionType === 'open_app' ? 'home' : $actionType;

        // Prepare data payload with advanced features
        $dataPayload = [
            'screen' => (string)$screen,
            'type' => (string)$screen,
            'action' => (string)$actionType,
            'sent_at' => now()->toDateTimeString(),
        ];

        // If action value is provided, add it and map to specific keys for app compatibility
        if ($request->filled('action_value')) {
            $value = (string)$request->action_value;
            $dataPayload['action_value'] = $value;
            
            // Map to specific keys based on screen type for deep linking
            if ($screen === 'post_detail') {
                $dataPayload['post_id'] = $value;
            } elseif ($screen === 'profile') {
                $dataPayload['user_id'] = $value;
            } elseif ($screen === 'chat') {
                $dataPayload['chat_session_id'] = $value;
            } elseif ($screen === 'open_url') {
                $dataPayload['url'] = $value;
            }
        }

        $result = $this->sendBulkNotification(
            $users, 
            $request->title, 
            $request->message, 
            $request->image, 
            'advanced_' . $request->target_type,
            $request->user_ids ?? [],
            $dataPayload
        );

        return $this->handleResult($request, $result);
    }

    /**
     * View notification history
     */
    public function history(Request $request)
    {
        $query = AdminNotification::with('sentBy');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('target_type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.notification.history', compact('notifications'));
    }

    /**
     * View single notification details
     */
    public function viewNotification($id)
    {
        $notification = AdminNotification::with('sentBy')->findOrFail($id);
        
        // Get detailed recipient list if available
        $recipients = [];
        if ($notification->response_data) {
            $recipients = collect($notification->response_data)->map(function($item) {
                return (object) $item;
            });
        }

        return view('admin.notification.view', compact('notification', 'recipients'));
    }

    /**
     * Delete notification history
     */
    public function deleteNotification($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Notification statistics
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', '30_days');
        $dateRange = $this->getDateRange($period);

        $stats = [
            'overview' => $this->getNotificationOverview($dateRange),
            'by_type' => $this->getNotificationsByType($dateRange),
            'daily_stats' => $this->getDailyNotificationStats($dateRange),
            'success_rate' => $this->getSuccessRateOverTime($dateRange),
        ];

        return view('admin.notification.stats', compact('stats', 'period', 'dateRange'));
    }

    /**
     * Helper Methods
     */
    private function getDateRange($period)
    {
        $end = Carbon::now();
        
        switch ($period) {
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
            default:
                $start = Carbon::now()->subDays(30);
        }

        return ['start' => $start, 'end' => $end];
    }

    private function getNotificationOverview($dateRange)
    {
        $total = AdminNotification::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $totalRecipients = AdminNotification::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('total_recipients');
        $totalSuccess = AdminNotification::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('success_count');
        $totalFailed = AdminNotification::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('failed_count');

        return [
            'total_notifications' => $total,
            'total_recipients' => $totalRecipients,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'success_rate' => $totalRecipients > 0 ? round(($totalSuccess / $totalRecipients) * 100, 2) : 0,
        ];
    }

    private function getNotificationsByType($dateRange)
    {
        return AdminNotification::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select('target_type', DB::raw('count(*) as count'), DB::raw('sum(total_recipients) as recipients'))
            ->groupBy('target_type')
            ->get();
    }

    private function getDailyNotificationStats($dateRange)
    {
        $stats = [];
        $current = clone $dateRange['start'];

        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            $dayStats = AdminNotification::whereDate('created_at', $dateStr)
                ->select(
                    DB::raw('count(*) as count'),
                    DB::raw('sum(total_recipients) as recipients'),
                    DB::raw('sum(success_count) as success'),
                    DB::raw('sum(failed_count) as failed')
                )
                ->first();

            $stats[] = [
                'date' => $dateStr,
                'count' => $dayStats->count ?? 0,
                'recipients' => $dayStats->recipients ?? 0,
                'success' => $dayStats->success ?? 0,
                'failed' => $dayStats->failed ?? 0,
            ];

            $current->addDay();
        }

        return $stats;
    }

    private function getSuccessRateOverTime($dateRange)
    {
        $stats = [];
        $current = clone $dateRange['start'];

        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            $dayStats = AdminNotification::whereDate('created_at', $dateStr)
                ->select(
                    DB::raw('sum(total_recipients) as recipients'),
                    DB::raw('sum(success_count) as success')
                )
                ->first();

            $rate = ($dayStats->recipients ?? 0) > 0 
                ? round(($dayStats->success / $dayStats->recipients) * 100, 2) 
                : 0;

            $stats[] = [
                'date' => $dateStr,
                'rate' => $rate,
            ];

            $current->addDay();
        }

        return $stats;
    }

    private function calculateSuccessRate()
    {
        $totalRecipients = AdminNotification::sum('total_recipients');
        $totalSuccess = AdminNotification::sum('success_count');
        
        return $totalRecipients > 0 ? round(($totalSuccess / $totalRecipients) * 100, 2) : 0;
    }
}