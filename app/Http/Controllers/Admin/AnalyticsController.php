<?php
// app/Http/Controllers/Admin/AnalyticsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    // ============================================
    // VIEW METHODS
    // ============================================
    
    public function dashboard(Request $request)
    {
        try {
            return view('admin.analytics.dashboard');
        } catch (\Exception $e) {
            Log::error('Analytics dashboard view failed', ['error' => $e->getMessage()]);
            return view('admin.analytics.dashboard')->with('error', 'Failed to load analytics view');
        }
    }
    
    public function retention(Request $request)
    {
        try {
            return view('admin.analytics.retention');
        } catch (\Exception $e) {
            Log::error('Retention view failed', ['error' => $e->getMessage()]);
            return view('admin.analytics.retention')->with('error', 'Failed to load retention view');
        }
    }
    
    public function realtime(Request $request)
    {
        try {
            return view('admin.analytics.realtime');
        } catch (\Exception $e) {
            Log::error('Realtime view failed', ['error' => $e->getMessage()]);
            return view('admin.analytics.realtime')->with('error', 'Failed to load realtime view');
        }
    }
    
    // ============================================
    // API METHODS
    // ============================================
    
    public function getDashboardData(Request $request)
    {
        try {
            $days = $request->days ?? 30;
            
            $data = [
                'dau' => $this->getDailyActiveUsers(),
                'mau' => $this->getMonthlyActiveUsers(),
                'wau' => $this->getWeeklyActiveUsers(),
                'local' => $this->getLocalAnalytics($days),
                'users' => $this->getUserStatistics(),
                'engagement' => $this->getEngagementMetrics($days),
                'daily_activity' => $this->getDailyActivity($days),
                'user_growth' => $this->getUserGrowth($days),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Analytics API failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    public function getRetentionData(Request $request)
    {
        try {
            $cohorts = [1, 3, 7, 14, 30];
            $retention = [];
            
            foreach ($cohorts as $day) {
                $signupDate = now()->subDays($day)->toDateString();
                
                $totalUsers = DB::table('users')
                    ->whereDate('created_at', $signupDate)
                    ->count();
                    
                $returningUsers = DB::table('users')
                    ->whereDate('created_at', $signupDate)
                    ->where('last_activity', '>=', now()->subDays($day))
                    ->count();
                    
                $retention["day_{$day}"] = $totalUsers > 0 
                    ? round(($returningUsers / $totalUsers) * 100, 2) 
                    : 0;
            }
            
            return response()->json($retention);
            
        } catch (\Exception $e) {
            Log::error('Failed to get retention', ['error' => $e->getMessage()]);
            return response()->json([
                'day_1' => 0,
                'day_3' => 0,
                'day_7' => 0,
                'day_14' => 0,
                'day_30' => 0
            ]);
        }
    }
    
    public function getRealtimeData(Request $request)
    {
        try {
            $activeUsers = DB::table('users')
                ->where('last_activity', '>=', now()->subMinutes(5))
                ->count();
                
            $activeNow = DB::table('users')
                ->where('last_activity', '>=', now()->subMinutes(1))
                ->count();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'active_users_5min' => $activeUsers,
                    'active_users_1min' => $activeNow,
                    'last_updated' => now()->toDateTimeString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'active_users_5min' => 0,
                    'active_users_1min' => 0,
                    'last_updated' => now()->toDateTimeString()
                ]
            ]);
        }
    }
    
    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================
    
    private function getDailyActiveUsers()
    {
        try {
            return DB::table('users')
                ->whereDate('last_activity', today())
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getWeeklyActiveUsers()
    {
        try {
            return DB::table('users')
                ->where('last_activity', '>=', now()->subDays(7))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getMonthlyActiveUsers()
    {
        try {
            return DB::table('users')
                ->where('last_activity', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getLocalAnalytics($days = 30)
    {
        try {
            return [
                'total_users' => DB::table('users')->count(),
                'active_users' => DB::table('users')
                    ->where('last_activity', '>=', now()->subDays($days))
                    ->count(),
                'total_posts' => DB::table('posts')->count(),
                'total_messages' => DB::table('user_messages')->count(),
                'total_comments' => DB::table('comments')->count(),
                'total_likes' => DB::table('likes')->count(),
                'new_users_today' => DB::table('users')
                    ->whereDate('created_at', today())
                    ->count(),
                'new_posts_today' => DB::table('posts')
                    ->whereDate('created_at', today())
                    ->count(),
            ];
        } catch (\Exception $e) {
            return [
                'total_users' => 0,
                'active_users' => 0,
                'total_posts' => 0,
                'total_messages' => 0,
                'total_comments' => 0,
                'total_likes' => 0,
                'new_users_today' => 0,
                'new_posts_today' => 0,
            ];
        }
    }
    
    private function getUserStatistics()
    {
        try {
            return [
                'total' => DB::table('users')->count(),
                'active_30d' => DB::table('users')
                    ->where('last_activity', '>=', now()->subDays(30))
                    ->count(),
                'active_7d' => DB::table('users')
                    ->where('last_activity', '>=', now()->subDays(7))
                    ->count(),
                'new_today' => DB::table('users')
                    ->whereDate('created_at', today())
                    ->count(),
                'new_this_week' => DB::table('users')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'by_type' => [
                    'users' => DB::table('users')->where('usertype', 'user')->count(),
                    'companies' => DB::table('users')->where('usertype', 'company')->count(),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active_30d' => 0,
                'active_7d' => 0,
                'new_today' => 0,
                'new_this_week' => 0,
                'by_type' => ['users' => 0, 'companies' => 0]
            ];
        }
    }
    
    private function getEngagementMetrics($days = 30)
    {
        try {
            $startDate = now()->subDays($days);
            
            $totalSessions = DB::table('chat_sessions')
                ->where('created_at', '>=', $startDate)
                ->count();
                
            $totalMessages = DB::table('user_messages')
                ->where('created_at', '>=', $startDate)
                ->count();
                
            $avgMessagesPerSession = 0;
            if ($totalSessions > 0) {
                $avgMessagesPerSession = round($totalMessages / $totalSessions, 1);
            }
            
            return [
                'total_sessions' => $totalSessions,
                'total_messages' => $totalMessages,
                'avg_messages_per_session' => $avgMessagesPerSession,
            ];
        } catch (\Exception $e) {
            return [
                'total_sessions' => 0,
                'total_messages' => 0,
                'avg_messages_per_session' => 0,
            ];
        }
    }
    
    private function getDailyActivity($days = 30)
    {
        try {
            $dailyData = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                
                $dailyData[] = [
                    'date' => $date,
                    'active_users' => DB::table('users')
                        ->whereDate('last_activity', $date)
                        ->count(),
                    'new_users' => DB::table('users')
                        ->whereDate('created_at', $date)
                        ->count(),
                    'posts' => DB::table('posts')
                        ->whereDate('created_at', $date)
                        ->count(),
                    'messages' => DB::table('user_messages')
                        ->whereDate('created_at', $date)
                        ->count(),
                ];
            }
            
            return $dailyData;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getUserGrowth($days = 30)
    {
        try {
            $growth = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $growth[] = [
                    'date' => $date,
                    'count' => DB::table('users')
                        ->whereDate('created_at', '<=', $date)
                        ->count()
                ];
            }
            return $growth;
        } catch (\Exception $e) {
            return [];
        }
    }
}