<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Post;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\UserConnection;
use App\Job;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\PostType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class AdminCompanyController extends Controller
{
    protected $fcmService;

    /**
     * Constructor with admin auth middleware
     */
    public function __construct(FCMService $fcmService)
    {
        $this->middleware('auth:admin');
        $this->fcmService = $fcmService;
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Get company data from users table (where usertype = 'company')
     */
    private function getCompanyQuery()
    {
        return User::where('usertype', 'company');
    }

    /**
     * Get company by ID (ensures it's a company user)
     */
    private function findCompany($id)
    {
        return User::where('usertype', 'company')->find($id);
    }

    /**
     * Get company name
     */
    private function getCompanyName($company)
    {
        return $company->company_name ?? $company->name ?? 'Unknown Company';
    }

    /**
     * Format company data for display
     */
    private function formatCompanyData($company)
    {
        return [
            'id' => $company->id,
            'name' => $this->getCompanyName($company),
            'email' => $company->email,
            'phone' => $company->phone,
            'website' => $company->company_website,
            'description' => $company->company_description,
            'location' => $company->company_location,
            'logo' => $company->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $company->company_logo) : asset('company_logos/' . $company->company_logo)) : null,
            'slug' => $company->company_slug,
            'is_active' => $company->is_active,
            'is_featured' => $company->is_featured,
            'verified' => $company->verified,
            'created_at' => $company->created_at,
            'updated_at' => $company->updated_at,
            'industry_id' => $company->company_industry_id,
            'established_in' => $company->company_established_in,
            'no_of_employees' => $company->company_no_of_employees,
            'gst_number' => $company->gst_number,
            'upi_id' => $company->upi_id,
            'linkedin_url' => $company->linkedin_url,
            'package_id' => $company->package_id,
            'package_start_date' => $company->package_start_date,
            'package_end_date' => $company->package_end_date,
            'jobs_quota' => $company->jobs_quota,
            'availed_jobs_quota' => $company->availed_jobs_quota,
        ];
    }

    /**
     * Get document status for company
     */
    private function getDocumentStatus($company)
    {
        $documents = [];

        $documentFields = [
            'incorporation_or_formation_certificate' => 'Incorporation Certificate',
            'valid_tax_clearance' => 'Tax Clearance',
            'proof_of_address' => 'Proof of Address',
            'other_supporting_documents' => 'Other Documents',
        ];

        foreach ($documentFields as $field => $label) {
            $statusField = $field . '_status';
            $commentField = $field . '_comment';

            // Check if these fields exist in the users table
            if (Schema::hasColumn('users', $field)) {
                $documents[$field] = [
                    'label' => $label,
                    'file' => $company->$field ?? null,
                    'status' => $company->$statusField ?? 0,
                    'comment' => $company->$commentField ?? null,
                    'url' => $company->$field ? asset('company_documents/' . $company->id . '/' . $company->$field) : null,
                ];
            }
        }

        return $documents;
    }

    /**
     * Check if all required documents are verified
     */
    private function checkAllDocumentsVerified($company)
    {
        $documentFields = [
            'incorporation_or_formation_certificate_status',
            'valid_tax_clearance_status',
            'proof_of_address_status',
        ];

        foreach ($documentFields as $field) {
            if (Schema::hasColumn('users', $field) && ($company->$field ?? 0) != 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get document status text
     */
    private function getDocumentStatusText($status)
    {
        switch ($status) {
            case 0:
                return 'Pending';
            case 1:
                return 'Approved';
            case 2:
                return 'Rejected';
            default:
                return 'Unknown';
        }
    }

    // ============================================
    // COMPANY MANAGEMENT
    // ============================================

    /**
     * Display a listing of companies
     */
    public function index(Request $request)
    {
        $query = $this->getCompanyQuery();

        // Apply filters
        if ($request->filled('name')) {
            $query->where(function($q) use ($request) {
                $q->where('company_name', 'LIKE', '%' . $request->name . '%')
                  ->orWhere('name', 'LIKE', '%' . $request->name . '%');
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'LIKE', '%' . $request->email . '%');
        }

        if ($request->filled('is_active') && $request->is_active != '-1') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('is_featured') && $request->is_featured != '-1') {
            $query->where('is_featured', $request->is_featured);
        }

        if ($request->filled('verified') && $request->verified != '-1') {
            $query->where('verified', $request->verified);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get counts for each company
        $companies = $query->orderBy('id', 'DESC')->paginate(20);
        
        // Add counts to each company
        foreach ($companies as $company) {
            $company->posts_count = Post::where('user_id', $company->id)->count();
            $company->job_posts_count = Post::where('user_id', $company->id)->where('is_job_post', true)->count();
            $company->regular_posts_count = Post::where('user_id', $company->id)->where('is_job_post', false)->count();
            $company->followers_count = UserConnection::where('following_id', $company->id)
                ->where('status', 'accepted')
                ->count();
            $company->total_engagement = $this->getCompanyEngagementTotal($company->id);
        }

        return view('admin.company_new.index', compact('companies'));
    }

    /**
     * Show complete company details with all posts and analytics
     */
    public function show($id)
    {
        $company = $this->findCompany($id);

        if (!$company) {
            abort(404, 'Company not found');
        }

        // ============================================
        // POSTS DATA - All posts by this company
        // ============================================
        
        // Get all posts with pagination
        $posts = Post::where('user_id', $company->id)
            ->with([
                'postType', 
                'category', 
                'subcategory',
                'taggedUsers',
            ])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'posts_page');

        // Get job posts separately
        $jobPosts = Post::where('user_id', $company->id)
            ->where('is_job_post', true)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'job_posts_page');

        // Get regular posts separately
        $regularPosts = Post::where('user_id', $company->id)
            ->where('is_job_post', false)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'regular_posts_page');

        // ============================================
        // ENGAGEMENT DATA
        // ============================================
        
        // Get all likes on company's posts
        $likes = PostLike::whereIn('post_id', function($query) use ($company) {
                $query->select('id')->from('posts')->where('user_id', $company->id);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'likes_page');

        // Get all comments on company's posts
        $comments = PostComment::whereIn('post_id', function($query) use ($company) {
                $query->select('id')->from('posts')->where('user_id', $company->id);
            })
            ->with(['user', 'post'])
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'comments_page');

        // Get all shares of company's posts
        $shares = PostShare::whereIn('post_id', function($query) use ($company) {
                $query->select('id')->from('posts')->where('user_id', $company->id);
            })
            ->with(['user', 'post'])
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'shares_page');

        // Get all views of company's posts
        $views = PostView::whereIn('post_id', function($query) use ($company) {
                $query->select('id')->from('posts')->where('user_id', $company->id);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'views_page');

        // ============================================
        // FOLLOWERS DATA
        // ============================================
        
        $followers = UserConnection::where('following_id', $company->id)
            ->where('status', 'accepted')
            ->with('follower')
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'followers_page');

        // ============================================
        // ANALYTICS DATA
        // ============================================
        
        $analytics = $this->getDetailedAnalytics($company->id);

        // ============================================
        // DOCUMENT VERIFICATION
        // ============================================
        
        $documents = $this->getDocumentStatus($company);

        // ============================================
        // RECENT ACTIVITY
        // ============================================
        
        $recentActivity = $this->getRecentActivity($company->id);

        // ============================================
        // POST TYPES AND CATEGORIES SUMMARY
        // ============================================
        
        $postTypeSummary = $this->getPostTypeSummary($company->id);
        $categorySummary = $this->getCategorySummary($company->id);

        return view('admin.company_new.show', compact(
            'company',
            'posts',
            'jobPosts',
            'regularPosts',
            'likes',
            'comments',
            'shares',
            'views',
            'followers',
            'analytics',
            'documents',
            'recentActivity',
            'postTypeSummary',
            'categorySummary'
        ));
    }

    // ============================================
    // POST MANAGEMENT METHODS
    // ============================================

    /**
     * Get all posts of a company (API endpoint for AJAX)
     */
    public function getCompanyPosts($companyId, Request $request)
    {
        $posts = Post::where('user_id', $companyId)
            ->with(['postType', 'category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * View single post details
     */
    public function showPost($postId)
    {
        $post = Post::with([
            'postType',
            'category',
            'subcategory',
            'user',
            'taggedUsers',
            'likes' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'comments' => function($query) {
                $query->with('user')->whereNull('parent_comment_id')->orderBy('created_at', 'desc');
            },
            'shares' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'views' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            }
        ])->findOrFail($postId);

        // Get comment replies
        foreach ($post->comments as $comment) {
            $comment->replies = PostComment::where('parent_comment_id', $comment->id)
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // Get engagement stats
        $stats = [
            'total_likes' => $post->likes->count(),
            'total_comments' => $post->comments->count(),
            'total_shares' => $post->shares->count(),
            'total_views' => $post->views->count(),
            'unique_viewers' => $post->views->unique('user_id')->count(),
            'likes_trend' => $this->getLikesTrend($postId),
            'comments_trend' => $this->getCommentsTrend($postId)
        ];

        return view('admin.company_new.post_detail', compact('post', 'stats'));
    }

    /**
     * Delete a post
     */
    public function deletePost($postId)
    {
        DB::beginTransaction();
        
        try {
            $post = Post::findOrFail($postId);
            
            // Delete associated files
            if ($post->images) {
                $images = json_decode($post->images, true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        $path = public_path('post_images/' . $image);
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }
            }
            
            if ($post->files) {
                $files = json_decode($post->files, true);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        $path = public_path('post_files/' . $file);
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }
            }

            // Delete related records
            PostLike::where('post_id', $postId)->delete();
            PostComment::where('post_id', $postId)->delete();
            PostShare::where('post_id', $postId)->delete();
            PostView::where('post_id', $postId)->delete();
            
            // Delete the post
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
     * Bulk delete posts
     */
    public function bulkDeletePosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            foreach ($request->post_ids as $postId) {
                $post = Post::find($postId);
                
                // Delete files
                if ($post->images) {
                    $images = json_decode($post->images, true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            $path = public_path('post_images/' . $image);
                            if (file_exists($path)) {
                                @unlink($path);
                            }
                        }
                    }
                }
                
                // Delete related records
                PostLike::where('post_id', $postId)->delete();
                PostComment::where('post_id', $postId)->delete();
                PostShare::where('post_id', $postId)->delete();
                PostView::where('post_id', $postId)->delete();
                
                $post->delete();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($request->post_ids) . ' posts deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle post published status
     */
    public function togglePostStatus($postId)
    {
        $post = Post::findOrFail($postId);
        $post->is_published = !$post->is_published;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post status updated',
            'new_status' => $post->is_published
        ]);
    }

    // ============================================
    // COMMENT MANAGEMENT
    // ============================================

    /**
     * Delete a comment
     */
    public function deleteComment($commentId)
    {
        $comment = PostComment::findOrFail($commentId);
        $postId = $comment->post_id;
        
        // Count total comments to be deleted (parent + all replies)
        $repliesCount = PostComment::where('parent_comment_id', $commentId)->count();
        $totalToDelete = 1 + $repliesCount;
        
        // Update post comments count
        $post = Post::find($postId);
        if ($post) {
            $post->decrement('comments_count', $totalToDelete);
        }
        
        // Delete replies if any
        PostComment::where('parent_comment_id', $commentId)->delete();
        
        // Delete the parent comment
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Toggle comment status (active/inactive)
     */
    public function toggleCommentStatus($commentId)
    {
        $comment = PostComment::findOrFail($commentId);
        $comment->is_active = !$comment->is_active;
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Comment status updated',
            'new_status' => $comment->is_active
        ]);
    }

    // ============================================
    // COMPANY STATUS MANAGEMENT
    // ============================================

    /**
     * Update company status (active/inactive)
     */
    public function updateStatus(Request $request, $id)
    {
        $company = $this->findCompany($id);
        
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $company->is_active = $request->is_active;
        $company->save();

        // If deactivated, logout from app (Sanctum)
        if (!$company->is_active) {
            $company->tokens()->delete();
        }

        // Send Push Notification
        if (!empty($company->firebase_token) && $company->push_notification) {
            $status = $company->is_active ? 'Activated' : 'Deactivated';
            $title = "Company Account " . $status;
            $body = $company->is_active 
                ? "Your company account has been activated. You can now use all features." 
                : "Your company account has been deactivated by admin. Please contact support.";
            
            $this->fcmService->fcmSendNotification($company->firebase_token, $title, $body, [
                'type' => 'account_status',
                'is_active' => (string)$company->is_active
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company status updated successfully',
            'new_status' => $company->is_active
        ]);
    }

    /**
     * Update featured status
     */
    public function updateFeatured(Request $request, $id)
    {
        $company = $this->findCompany($id);
        
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'is_featured' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $company->is_featured = $request->is_featured;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => 'Company featured status updated successfully',
            'new_status' => $company->is_featured
        ]);
    }

    // ============================================
    // DOCUMENT VERIFICATION
    // ============================================

    /**
     * Update document verification status
     */
    public function verifyDocument(Request $request, $id)
    {
        $company = $this->findCompany($id);
        
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'status' => 'required|in:0,1,2',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Check if document fields exist in the table
        $statusField = $request->document_type . '_status';
        $commentField = $request->document_type . '_comment';

        if (!Schema::hasColumn('users', $statusField)) {
            return response()->json(['success' => false, 'message' => 'Document type not found'], 404);
        }

        $company->$statusField = $request->status;
        $company->$commentField = $request->comment;
        
        // Check if all documents are verified to auto-verify company
        $allVerified = $this->checkAllDocumentsVerified($company);
        if ($allVerified && $company->verified == 0) {
            $company->verified = 1;
            $company->verified_at = now();
            $company->verified_by = auth()->guard('admin')->user()->id ?? null;
        }

        $company->save();

        return response()->json([
            'success' => true,
            'message' => 'Document verification status updated',
            'document' => [
                'type' => $request->document_type,
                'status' => $request->status,
                'status_text' => $this->getDocumentStatusText($request->status),
                'comment' => $request->comment,
            ]
        ]);
    }

    // ============================================
    // EXPORT & NOTIFICATIONS
    // ============================================

    /**
     * Export company data as JSON
     */
    public function exportData($id)
    {
        $company = $this->findCompany($id);

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $posts = Post::where('user_id', $company->id)
            ->with(['postType', 'category', 'subcategory', 'likes', 'comments', 'shares', 'views'])
            ->get();

        $data = [
            'company' => $this->formatCompanyData($company),
            'posts' => $posts,
            'statistics' => $this->getDetailedAnalytics($company->id),
            'exported_at' => now()->toDateTimeString(),
        ];

        $filename = 'company_' . $company->id . '_' . Str::slug($this->getCompanyName($company)) . '_' . date('Y-m-d') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Send notification to company
     */
    public function sendNotification(Request $request, $id)
    {
        $company = $this->findCompany($id);
        
        if (!$company) {
            return redirect()->back()->with('error', 'Company not found');
        }
        
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Send email notification
        try {
            \Mail::send('emails.admin_company_notification', [
                'company' => $this->formatCompanyData($company),
                'subject' => $request->subject,
                'messageBody' => $request->message,
            ], function ($message) use ($company, $request) {
                $message->to($company->email, $this->getCompanyName($company))
                    ->subject($request->subject);
            });

            Log::info('Admin notification sent to company: ' . $company->email);

            return redirect()->back()->with('success', 'Notification sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send notification to company: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Delete company
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $company = $this->findCompany($id);
            
            if (!$company) {
                return response()->json(['success' => false, 'message' => 'Company not found'], 404);
            }
            
            // Delete all posts by this company
            $posts = Post::where('user_id', $company->id)->get();
            foreach ($posts as $post) {
                // Delete files
                if ($post->images) {
                    $images = json_decode($post->images, true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            $path = public_path('post_images/' . $image);
                            if (file_exists($path)) {
                                @unlink($path);
                            }
                        }
                    }
                }
                
                // Delete related records
                PostLike::where('post_id', $post->id)->delete();
                PostComment::where('post_id', $post->id)->delete();
                PostShare::where('post_id', $post->id)->delete();
                PostView::where('post_id', $post->id)->delete();
                
                $post->delete();
            }
            
            // Delete company logo
            if ($company->company_logo) {
                $logoPath = public_path('company_logos/' . $company->company_logo);
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
            }
            
            // Delete company documents if they exist in the table
            $documentFields = [
                'incorporation_or_formation_certificate',
                'valid_tax_clearance',
                'proof_of_address',
                'other_supporting_documents'
            ];
            
            foreach ($documentFields as $field) {
                if (Schema::hasColumn('users', $field) && $company->$field) {
                    $docPath = public_path('company_documents/' . $company->id . '/' . $company->$field);
                    if (file_exists($docPath)) {
                        @unlink($docPath);
                    }
                }
            }
            
            // Delete company directory if exists
            $companyDir = public_path('company_documents/' . $company->id);
            if (is_dir($companyDir)) {
                rmdir($companyDir);
            }
            
            // Delete the user record (this is the company)
            $company->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed analytics for company
     */
    private function getDetailedAnalytics($companyId)
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        $lastWeek = Carbon::now()->subWeek();
        $lastYear = Carbon::now()->subYear();

        // Posts statistics
        $totalPosts = Post::where('user_id', $companyId)->count();
        $totalJobPosts = Post::where('user_id', $companyId)->where('is_job_post', true)->count();
        $totalRegularPosts = Post::where('user_id', $companyId)->where('is_job_post', false)->count();
        
        // Posts by time period
        $postsToday = Post::where('user_id', $companyId)->whereDate('created_at', today())->count();
        $postsThisWeek = Post::where('user_id', $companyId)->where('created_at', '>=', $lastWeek)->count();
        $postsThisMonth = Post::where('user_id', $companyId)->where('created_at', '>=', $lastMonth)->count();
        $postsThisYear = Post::where('user_id', $companyId)->where('created_at', '>=', $lastYear)->count();

        // Engagement metrics
        $totalLikes = PostLike::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->count();

        $totalComments = PostComment::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->count();

        $totalShares = PostShare::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->count();

        $totalViews = PostView::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->count();

        // Unique users engaged
        $uniqueLikers = PostLike::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->distinct('user_id')->count('user_id');

        $uniqueCommenters = PostComment::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->distinct('user_id')->count('user_id');

        $uniqueSharers = PostShare::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->distinct('user_id')->count('user_id');

        $uniqueViewers = PostView::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })->distinct('user_id')->count('user_id');

        // Engagement by day of week - using MySQL/PostgreSQL compatible query
        if (DB::connection()->getDriverName() === 'pgsql') {
            $engagementByDay = DB::select("
                SELECT 
                    EXTRACT(DOW FROM created_at) as day_of_week,
                    COUNT(*) as count
                FROM post_likes 
                WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)
                GROUP BY EXTRACT(DOW FROM created_at)
                ORDER BY EXTRACT(DOW FROM created_at)
            ", [$companyId]);
        } else {
            // MySQL
            $engagementByDay = DB::select("
                SELECT 
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as count
                FROM post_likes 
                WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)
                GROUP BY DAYOFWEEK(created_at)
                ORDER BY DAYOFWEEK(created_at)
            ", [$companyId]);
        }

        $engagementByDay = collect($engagementByDay)->map(function($item) {
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $dayIndex = (int)$item->day_of_week;
            // MySQL DAYOFWEEK returns 1=Sunday, 2=Monday, etc.
            // PostgreSQL EXTRACT(DOW) returns 0=Sunday, 1=Monday, etc.
            $item->day_name = $days[$dayIndex - 1] ?? 'Unknown';
            return $item;
        });

        // Top performing posts
        $topLikedPosts = DB::select("
            SELECT 
                p.id, 
                p.title,
                COUNT(pl.id) as likes_count
            FROM posts p
            LEFT JOIN post_likes pl ON p.id = pl.post_id
            WHERE p.user_id = ?
            GROUP BY p.id, p.title
            ORDER BY likes_count DESC
            LIMIT 5
        ", [$companyId]);

        $topCommentedPosts = DB::select("
            SELECT 
                p.id, 
                p.title,
                COUNT(pc.id) as comments_count
            FROM posts p
            LEFT JOIN post_comments pc ON p.id = pc.post_id
            WHERE p.user_id = ?
            GROUP BY p.id, p.title
            ORDER BY comments_count DESC
            LIMIT 5
        ", [$companyId]);

        $topViewedPosts = DB::select("
            SELECT 
                id, 
                title,
                views_count
            FROM posts
            WHERE user_id = ?
            ORDER BY views_count DESC NULLS LAST
            LIMIT 5
        ", [$companyId]);

        // Follower statistics
        $totalFollowers = UserConnection::where('following_id', $companyId)
            ->where('status', 'accepted')
            ->count();
            
        $newFollowersToday = UserConnection::where('following_id', $companyId)
            ->where('status', 'accepted')
            ->whereDate('created_at', today())
            ->count();
            
        $newFollowersThisWeek = UserConnection::where('following_id', $companyId)
            ->where('status', 'accepted')
            ->where('created_at', '>=', $lastWeek)
            ->count();
            
        $newFollowersThisMonth = UserConnection::where('following_id', $companyId)
            ->where('status', 'accepted')
            ->where('created_at', '>=', $lastMonth)
            ->count();

        // Job statistics
        $totalJobs = Job::where('company_id', $companyId)->count();
        $activeJobs = Job::where('company_id', $companyId)->where('is_active', true)->count();
        $expiredJobs = Job::where('company_id', $companyId)->where('expiry_date', '<', $now)->count();
        
        // Application statistics - Check which table exists
        $totalApplications = 0;
        
        // Try different possible table names
        if (Schema::hasTable('job_applications')) {
            $totalApplications = DB::table('job_applications')
                ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->where('jobs.company_id', $companyId)
                ->count();
        } elseif (Schema::hasTable('job_apply')) {
            $totalApplications = DB::table('job_apply')
                ->join('jobs', 'jobs.id', '=', 'job_apply.job_id')
                ->where('jobs.company_id', $companyId)
                ->count();
        } elseif (Schema::hasTable('job_applies')) {
            $totalApplications = DB::table('job_applies')
                ->join('jobs', 'jobs.id', '=', 'job_applies.job_id')
                ->where('jobs.company_id', $companyId)
                ->count();
        }

        return [
            'posts' => [
                'total' => $totalPosts,
                'job_posts' => $totalJobPosts,
                'regular_posts' => $totalRegularPosts,
                'today' => $postsToday,
                'this_week' => $postsThisWeek,
                'this_month' => $postsThisMonth,
                'this_year' => $postsThisYear,
            ],
            'engagement' => [
                'likes' => $totalLikes,
                'comments' => $totalComments,
                'shares' => $totalShares,
                'views' => $totalViews,
                'unique_likers' => $uniqueLikers,
                'unique_commenters' => $uniqueCommenters,
                'unique_sharers' => $uniqueSharers,
                'unique_viewers' => $uniqueViewers,
                'by_day_of_week' => $engagementByDay,
            ],
            'top_posts' => [
                'liked' => $topLikedPosts,
                'commented' => $topCommentedPosts,
                'viewed' => $topViewedPosts,
            ],
            'followers' => [
                'total' => $totalFollowers,
                'new_today' => $newFollowersToday,
                'new_this_week' => $newFollowersThisWeek,
                'new_this_month' => $newFollowersThisMonth,
            ],
            'jobs' => [
                'total' => $totalJobs,
                'active' => $activeJobs,
                'expired' => $expiredJobs,
            ],
            'applications' => $totalApplications,
        ];
    }

    /**
     * Get post type summary for a company
     */
    private function getPostTypeSummary($companyId)
    {
        return DB::table('posts')
            ->join('post_types', 'posts.post_type_id', '=', 'post_types.id')
            ->where('posts.user_id', $companyId)
            ->select('post_types.name', DB::raw('COUNT(*) as count'))
            ->groupBy('post_types.name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get category summary for a company
     */
    private function getCategorySummary($companyId)
    {
        return DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->where('posts.user_id', $companyId)
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get likes trend for a post
     */
    private function getLikesTrend($postId)
    {
        return PostLike::where('post_id', $postId)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }

    /**
     * Get comments trend for a post
     */
    private function getCommentsTrend($postId)
    {
        return PostComment::where('post_id', $postId)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }

    /**
     * Get total engagement for a company
     */
    private function getCompanyEngagementTotal($companyId)
    {
        $likes = PostLike::whereIn('post_id', function($query) use ($companyId) {
            $query->select('id')->from('posts')->where('user_id', $companyId);
        })->count();

        $comments = PostComment::whereIn('post_id', function($query) use ($companyId) {
            $query->select('id')->from('posts')->where('user_id', $companyId);
        })->count();

        $shares = PostShare::whereIn('post_id', function($query) use ($companyId) {
            $query->select('id')->from('posts')->where('user_id', $companyId);
        })->count();

        return $likes + $comments + $shares;
    }

    /**
     * Get recent activity for a company
     */
    private function getRecentActivity($companyId)
    {
        $activities = collect();

        // Recent posts
        $recentPosts = Post::where('user_id', $companyId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($post) {
                return [
                    'type' => 'post',
                    'description' => 'Created a new post: ' . $post->title,
                    'created_at' => $post->created_at,
                    'url' => '',
                    'icon' => 'fa-file-text',
                    'color' => 'blue',
                ];
            });

        // Recent likes on posts
        $recentLikes = PostLike::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })
            ->with('user', 'post')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($like) {
                $userName = $like->user ? $like->user->getName() : 'Someone';
                $postTitle = $like->post ? $like->post->title : 'Unknown';
                return [
                    'type' => 'like',
                    'description' => $userName . ' liked post: ' . $postTitle,
                    'created_at' => $like->created_at,
                    'url' => '',
                    'icon' => 'fa-heart',
                    'color' => 'red',
                ];
            });

        // Recent comments
        $recentComments = PostComment::whereIn('post_id', function($query) use ($companyId) {
                $query->select('id')->from('posts')->where('user_id', $companyId);
            })
            ->with('user', 'post')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($comment) {
                $userName = $comment->user ? $comment->user->getName() : 'Someone';
                $postTitle = $comment->post ? $comment->post->title : 'Unknown';
                return [
                    'type' => 'comment',
                    'description' => $userName . ' commented on: ' . $postTitle,
                    'created_at' => $comment->created_at,
                    'url' => '',
                    'icon' => 'fa-comment',
                    'color' => 'green',
                ];
            });

        // Recent followers
        $recentFollowers = UserConnection::where('following_id', $companyId)
            ->where('status', 'accepted')
            ->with('follower')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($follower) {
                $followerName = $follower->follower ? $follower->follower->getName() : 'Unknown User';
                return [
                    'type' => 'follower',
                    'description' => 'New follower: ' . $followerName,
                    'created_at' => $follower->created_at,
                    'url' => '',
                    'icon' => 'fa-user-plus',
                    'color' => 'purple',
                ];
            });

        // Recent jobs
        $recentJobs = Job::where('company_id', $companyId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($job) {
                return [
                    'type' => 'job',
                    'description' => 'Posted a new job: ' . $job->title,
                    'created_at' => $job->created_at,
                    'url' => '#',
                    'icon' => 'fa-briefcase',
                    'color' => 'orange',
                ];
            });

        $activities = $activities->merge($recentPosts)
            ->merge($recentLikes)
            ->merge($recentComments)
            ->merge($recentFollowers)
            ->merge($recentJobs)
            ->sortByDesc('created_at')
            ->take(50);

        return $activities;
    }
}