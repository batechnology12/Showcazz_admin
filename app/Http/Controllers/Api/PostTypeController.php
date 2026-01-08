<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PostType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostTypeController extends Controller
{
    /**
     * Display a listing of post types with categories and subcategories
     */
    public function index(Request $request)
    {
        try {
            $query = PostType::with(['categories' => function($query) {
                $query->where('is_active', true)
                      ->with(['subcategories' => function($q) {
                          $q->where('is_active', true);
                      }]);
            }])->where('is_active', true);

            // Search filter
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            $postTypes = $query->get();

            return response()->json([
                'success' => true,
                'data' => $postTypes,
                'message' => 'Post types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified post type
     */
    public function show($id)
    {
        try {
            $postType = PostType::with(['categories' => function($query) {
                $query->where('is_active', true)
                      ->with(['subcategories' => function($q) {
                          $q->where('is_active', true);
                      }]);
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $postType,
                'message' => 'Post type retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post type not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get post types for dropdown (minimal data)
     */
    public function dropdown()
    {
        try {
            $postTypes = PostType::where('is_active', true)
                ->select('id', 'name', 'slug')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $postTypes,
                'message' => 'Post types dropdown retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post types dropdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}