<?php
// app/Http/Controllers/Admin/AdminPostController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Post;
use App\User;
use App\Company;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\PostType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminPostController extends Controller
{
    /**
     * Constructor with admin auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Helper function to safely decode JSON fields
     */
    private function decodeField($field)
    {
        if (empty($field)) {
            return [];
        }
        
        if (is_array($field)) {
            return $field;
        }
        
        $decoded = json_decode($field, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get entity name by ID (user or company)
     */
    private function getEntityName($id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                return $company ? $company->name : $user->name;
            }
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
        }
        
        $company = Company::find($id);
        return $company ? $company->name : 'Unknown';
    }

    /**
     * Display a listing of posts
     */
    public function index(Request $request)
    {
        $query = Post::with(['category', 'subcategory', 'postType'])
            ->where('category_id', '!=', 5) // Exclude jobs
            ->where('category_id', '!=', 6); // Exclude internships

        // Apply filters
        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('post_type_id')) {
            $query->where('post_type_id', $request->post_type_id);
        }

        if ($request->filled('status')) {
            if ($request->status == 'published') {
                $query->where('is_published', true);
            } elseif ($request->status == 'draft') {
                $query->where('is_published', false);
            } elseif ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status == 'pending') {
                $query->where('is_published', true)->where('is_active', true);
            }
        }

        if ($request->filled('author')) {
            $authorIds = [];
            
            // Search in users
            $users = User::where('first_name', 'LIKE', '%' . $request->author . '%')
                ->orWhere('last_name', 'LIKE', '%' . $request->author . '%')
                ->orWhere('name', 'LIKE', '%' . $request->author . '%')
                ->pluck('id')
                ->toArray();
            
            // Search in companies
            $companies = Company::where('name', 'LIKE', '%' . $request->author . '%')
                ->pluck('id')
                ->toArray();
            
            $authorIds = array_merge($users, $companies);
            
            if (!empty($authorIds)) {
                $query->whereIn('user_id', $authorIds);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('has_media')) {
            if ($request->has_media == 'images') {
                $query->whereNotNull('images')->where('images', '!=', '[]')->where('images', '!=', 'null');
            } elseif ($request->has_media == 'files') {
                $query->whereNotNull('files')->where('files', '!=', '[]')->where('files', '!=', 'null');
            }
        }

        // Get counts for stats
        $stats = [
            'total' => Post::whereNotIn('category_id', [5, 6])->count(),
            'published' => Post::whereNotIn('category_id', [5, 6])->where('is_published', true)->count(),
            'draft' => Post::whereNotIn('category_id', [5, 6])->where('is_published', false)->count(),
            'with_images' => Post::whereNotIn('category_id', [5, 6])
                ->whereNotNull('images')->where('images', '!=', '[]')->where('images', '!=', 'null')
                ->count(),
            'with_files' => Post::whereNotIn('category_id', [5, 6])
                ->whereNotNull('files')->where('files', '!=', '[]')->where('files', '!=', 'null')
                ->count(),
        ];

        $posts = $query->orderBy('id', 'DESC')->paginate(20);

        // Add author names
        foreach ($posts as $post) {
            $post->author_name = $this->getEntityName($post->user_id);
        }

        // Get filter data
        $categories = Category::where('is_active', true)->get();
        $postTypes = PostType::where('is_active', true)->get();

        return view('admin.post_new.index', compact('posts', 'stats', 'categories', 'postTypes'));
    }

    /**
     * Show post details
     */
    public function show($id)
    {
        $post = Post::with([
            'category',
            'subcategory',
            'postType',
            'taggedUsers',
            'taggedCompanies',
            'likes' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'comments' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'shares' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'views' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Get author info
        $author = null;
        $user = User::find($post->user_id);
        if ($user) {
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                $author = [
                    'id' => $company->id ?? $user->id,
                    'name' => $company->name ?? $user->name,
                    'type' => 'company',
                    'image' => $company->logo ? asset('company_logos/' . $company->logo) : 
                        ($user->image ? asset('user_images/' . $user->image) : null),
                ];
            } else {
                $author = [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
                    'type' => 'user',
                    'image' => $user->image ? asset('user_images/' . $user->image) : null,
                ];
            }
        } else {
            $company = Company::find($post->user_id);
            if ($company) {
                $author = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => 'company',
                    'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                ];
            }
        }

        // Parse media - FIXED: Using helper method instead of nested ternary
        $images = $this->decodeField($post->images);
        $files = $this->decodeField($post->files);

        // Get comment replies
        foreach ($post->comments as $comment) {
            $comment->replies = PostComment::where('parent_comment_id', $comment->id)
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('admin.post_new.show', compact('post', 'author', 'images', 'files'));
    }

    /**
     * Approve post
     */
    public function approvePost($id)
    {
        $post = Post::findOrFail($id);
        $post->is_active = true;
        $post->is_published = true;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post approved successfully'
        ]);
    }

    /**
     * Reject post with reason
     */
    public function rejectPost(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $post = Post::findOrFail($id);
        $post->is_active = false;
        $post->is_published = false;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post rejected successfully',
            'reason' => $request->reason
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeature($id)
    {
        $post = Post::findOrFail($id);
        $post->is_featured = !$post->is_featured;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post featured status updated',
            'new_status' => $post->is_featured
        ]);
    }

    /**
     * Bulk action on posts
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,delete,feature',
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id',
            'reason' => 'required_if:action,reject|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $count = 0;
        foreach ($request->post_ids as $postId) {
            $post = Post::find($postId);
            
            switch ($request->action) {
                case 'approve':
                    $post->is_active = true;
                    $post->is_published = true;
                    $post->save();
                    $count++;
                    break;
                    
                case 'reject':
                    $post->is_active = false;
                    $post->is_published = false;
                    $post->save();
                    $count++;
                    break;
                    
                case 'feature':
                    $post->is_featured = !$post->is_featured;
                    $post->save();
                    $count++;
                    break;
                    
                case 'delete':
                    // Delete media files
                    $images = $this->decodeField($post->images);
                    foreach ($images as $image) {
                        $path = public_path('post_images/' . $image);
                        if (file_exists($path)) @unlink($path);
                    }
                    
                    $files = $this->decodeField($post->files);
                    foreach ($files as $file) {
                        $path = public_path('post_files/' . $file);
                        if (file_exists($path)) @unlink($path);
                    }
                    
                    // Delete related records
                    PostLike::where('post_id', $postId)->delete();
                    PostComment::where('post_id', $postId)->delete();
                    PostShare::where('post_id', $postId)->delete();
                    PostView::where('post_id', $postId)->delete();
                    
                    $post->delete();
                    $count++;
                    break;
            }
        }

        $actionText = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'feature' => 'featured toggled for',
            'delete' => 'deleted'
        ][$request->action];

        return response()->json([
            'success' => true,
            'message' => $count . ' posts ' . $actionText . ' successfully'
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $post = Post::findOrFail($id);
            
            // Delete media files
            $images = $this->decodeField($post->images);
            foreach ($images as $image) {
                $path = public_path('post_images/' . $image);
                if (file_exists($path)) @unlink($path);
            }
            
            $files = $this->decodeField($post->files);
            foreach ($files as $file) {
                $path = public_path('post_files/' . $file);
                if (file_exists($path)) @unlink($path);
            }

            // Delete related records
            PostLike::where('post_id', $id)->delete();
            PostComment::where('post_id', $id)->delete();
            PostShare::where('post_id', $id)->delete();
            PostView::where('post_id', $id)->delete();
            
            $post->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View media of a post
     */
    public function viewMedia($id)
    {
        $post = Post::findOrFail($id);
        
        $images = $this->decodeField($post->images);
        $files = $this->decodeField($post->files);

        return response()->json([
            'success' => true,
            'data' => [
                'images' => array_map(function($img) {
                    return asset('post_images/' . $img);
                }, $images),
                'files' => array_map(function($file) {
                    return [
                        'name' => $file,
                        'url' => asset('post_files/' . $file),
                        'size' => file_exists(public_path('post_files/' . $file)) 
                            ? round(filesize(public_path('post_files/' . $file)) / 1024, 2) . ' KB'
                            : 'Unknown'
                    ];
                }, $files),
            ]
        ]);
    }
}