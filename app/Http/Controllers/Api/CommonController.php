<?php

namespace App\Http\Controllers\Api;

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
            $lang   = $request->get('lang', 'en');
            $search = trim($request->get('search'));
    
            $query = JobTitle::query()
                ->where('lang', $lang)
                ->where('is_active', 1)
                ->with(['subTitles' => function ($q) use ($lang) {
                    $q->where('lang', $lang)
                      ->where('is_active', 1)
                      ->orderBy('sort_order', 'asc')
                      ->orderBy('job_sub_title', 'asc');
                }]);
    
            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search, $lang) {
                    $q->where('job_title', 'ILIKE', "%{$search}%")
                      ->orWhereHas('subTitles', function ($subQuery) use ($search, $lang) {
                          $subQuery->where('lang', $lang)
                                   ->where('is_active', 1)
                                   ->where('job_sub_title', 'ILIKE', "%{$search}%");
                      });
                });
            }
    
            $jobTitles = $query
                ->orderBy('sort_order', 'asc')
                ->orderBy('job_title', 'asc')
                ->get();
    
            $data = $jobTitles->map(function ($jobTitle) use ($search) {
    
                $subTitles = $jobTitle->subTitles;
    
                if (!empty($search)) {
    
                    $titleMatched = stripos($jobTitle->job_title, $search) !== false;
    
                    if (!$titleMatched) {
                        // Only filter sub titles if title did NOT match
                        $subTitles = $subTitles->filter(function ($sub) use ($search) {
                            return stripos($sub->job_sub_title, $search) !== false;
                        });
                    }
                }
    
                return [
                    'id' => $jobTitle->id,
                    'name' => $jobTitle->job_title,
                    'status' => (bool) $jobTitle->is_active,
                    'sub_titles' => $subTitles->values()->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->job_sub_title,
                            'status' => (bool) $sub->is_active,
                        ];
                    }),
                ];
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Job titles retrieved successfully',
                'data' => $data
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