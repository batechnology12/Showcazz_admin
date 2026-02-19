<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class StaticPageController extends Controller
{
    /**
     * Get terms and conditions
     */
    public function getTerms()
    {
        try {
            $page = StaticPage::where('page_type', 'terms_conditions')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terms and conditions not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions retrieved successfully',
                'data' => [
                    'title' => $page->title,
                    'content' => $page->content,
                    'version' => $page->version,
                    'updated_at' => $page->updated_at
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve terms', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve terms and conditions',
            ], 500);
        }
    }

    /**
     * Get privacy policy
     */
    public function getPrivacy()
    {
        try {
            $page = StaticPage::where('page_type', 'privacy_policy')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Privacy policy not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Privacy policy retrieved successfully',
                'data' => [
                    'title' => $page->title,
                    'content' => $page->content,
                    'version' => $page->version,
                    'updated_at' => $page->updated_at
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve privacy policy', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve privacy policy',
            ], 500);
        }
    }
}