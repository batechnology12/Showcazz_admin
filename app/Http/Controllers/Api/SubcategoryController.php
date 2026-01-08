<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories
     */
    public function index(Request $request)
    {
        try {
            $query = Subcategory::with(['category', 'category.postType'])
                ->where('is_active', true);

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by category slug
            if ($request->has('category_slug')) {
                $category = Category::where('slug', $request->category_slug)->first();
                if ($category) {
                    $query->where('category_id', $category->id);
                }
            }

            // Filter by post type
            if ($request->has('post_type_id')) {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('post_type_id', $request->post_type_id);
                });
            }

            // Search filter
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            $subcategories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $subcategories,
                'message' => 'Subcategories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subcategories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subcategories by category
     */
    public function byCategory($categoryId)
    {
        try {
            $subcategories = Subcategory::with(['category'])
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subcategories,
                'message' => 'Subcategories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subcategories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subcategories dropdown for a category
     */
    public function dropdown(Request $request)
    {
        try {
            $query = Subcategory::where('is_active', true)
                ->select('id', 'name', 'slug', 'category_id');

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $subcategories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $subcategories,
                'message' => 'Subcategories dropdown retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subcategories dropdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}