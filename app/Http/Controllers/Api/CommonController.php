<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\JobTitle;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommonController extends Controller
{
    /**
     * Get all job titles
     */
    public function index(Request $request)
    {
        try {
            // Get language from request or default to 'en'
            $lang = $request->get('lang', 'en');
            
            // Get active job titles for the language
            $jobTitles = JobTitle::where('lang', $lang)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('job_title', 'asc')
                ->get(['id', 'job_title as name', 'is_active as status']);
            
            return response()->json([
                'success' => true,
                'message' => 'Job titles retrieved successfully',
                'data' => $jobTitles
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job titles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all job skills
     */
    public function skills(Request $request)
    {
        try {
            $lang = $request->get('lang', 'en');
            
            // Get active job skills for the language
            $jobSkills = JobSkill::where('lang', $lang)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('job_skill', 'asc')
                ->get(['id', 'job_skill as name', 'is_active as status']);
                
            return response()->json([
                'success' => true,
                'message' => 'Job Skills retrieved successfully',
                'data' => $jobSkills  // Changed variable name here
            ]);  
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job Skills',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}