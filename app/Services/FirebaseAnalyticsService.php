<?php
// app/Services/FirebaseAnalyticsService.php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\AnalyticsReporting;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\Dimension;
use Google\Service\AnalyticsReporting\ReportRequest;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FirebaseAnalyticsService
{
    protected $analytics;
    protected $viewId;
    
    public function __construct()
    {
        $this->viewId = env('GA_VIEW_ID'); // Your Google Analytics View ID
        $this->initializeAnalytics();
    }
    
    /**
     * Initialize Google Analytics service
     */
    private function initializeAnalytics()
    {
        try {
            // Using base_path() so the file can be committed to Git (moved to root directory)
            $serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);
            
            // Fix literal newline characters if they were escaped during save
            if (isset($serviceAccount['private_key'])) {
                $serviceAccount['private_key'] = str_replace('\\n', "\n", $serviceAccount['private_key']);
            }
            
            $client = new GoogleClient();
            $client->setAuthConfig($serviceAccount);
            $client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
            
            $this->analytics = new Google_Service_Analytics($client);
            
        } catch (\Exception $e) {
            Log::error('Analytics initialization failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get Daily Active Users (DAU)
     */
    public function getDailyActiveUsers($date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            $response = $this->analytics->data_ga->get(
                'ga:' . $this->viewId,
                $date,
                $date,
                'ga:users'
            );
            
            return $response->getTotalsForAllResults()['ga:users'] ?? 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to get DAU', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Get Monthly Active Users (MAU)
     */
    public function getMonthlyActiveUsers()
    {
        try {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
            
            $response = $this->analytics->data_ga->get(
                'ga:' . $this->viewId,
                $startDate,
                $endDate,
                'ga:users'
            );
            
            return $response->getTotalsForAllResults()['ga:users'] ?? 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to get MAU', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Get screen views
     */
    public function getScreenViews($days = 7)
    {
        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $endDate = date('Y-m-d');
            
            $response = $this->analytics->data_ga->get(
                'ga:' . $this->viewId,
                $startDate,
                $endDate,
                'ga:screenViews',
                [
                    'dimensions' => 'ga:screenName',
                    'sort' => '-ga:screenViews',
                    'max-results' => 20
                ]
            );
            
            $screenViews = [];
            if ($response->getRows()) {
                foreach ($response->getRows() as $row) {
                    $screenViews[] = [
                        'screen_name' => $row[0],
                        'views' => (int)$row[1]
                    ];
                }
            }
            
            return $screenViews;
            
        } catch (\Exception $e) {
            Log::error('Failed to get screen views', ['error' => $e->getMessage()]);
            return [];
        }
    }
}