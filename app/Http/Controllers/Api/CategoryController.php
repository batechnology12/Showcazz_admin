<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PostType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        try {
            $query = Category::with(['postType', 'subcategories' => function($query) {
                $query->where('is_active', true);
            }])->where('is_active', true);

            // Filter by post type
            if ($request->has('post_type_id')) {
                $query->where('post_type_id', $request->post_type_id);
            }

            // Filter by post type slug
            if ($request->has('post_type_slug')) {
                $postType = PostType::where('slug', $request->post_type_slug)->first();
                if ($postType) {
                    $query->where('post_type_id', $postType->id);
                }
            }

            // Search filter
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            $categories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories by post type
     */
    public function byPostType($postTypeId)
    {
        try {
            $categories = Category::with(['subcategories' => function($query) {
                $query->where('is_active', true);
            }])
            ->where('post_type_id', $postTypeId)
            ->where('is_active', true)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories dropdown for a post type
     */
    public function dropdown(Request $request)
    {
        try {
            $query = Category::where('is_active', true)
                ->select('id', 'name', 'slug', 'post_type_id');

            if ($request->has('post_type_id')) {
                $query->where('post_type_id', $request->post_type_id);
            }

            $categories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories dropdown retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories dropdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}