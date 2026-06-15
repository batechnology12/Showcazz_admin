<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\Models\PostType;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ChatType;
use App\PostTag;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\UserMessage;
use App\Models\PostRepost;
use App\Models\ChatSession;
use App\User;
use App\JobSkill;
use App\UserConnection;
use App\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostController extends Controller
{
    // Status constants to match UniversalConnectionController
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    // Job categories - 5: Mini Mission, 6: Internship, 7: Fresher Role
    const JOB_CATEGORIES = [5, 6, 7];
    
    
    protected $notificationService;
    
    public function __construct()
    {
        $this->notificationService = app(\App\Services\NotificationService::class);
    }

    // ============================================
    // POST CREATION METHODS FOR 13 TYPES
    // ============================================

    /**
     * Create a new post (handles all 13 types)
     */
    public function createPost(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if this is a job post (category 5, 6, or 7)
            $categoryId = $request->category_id;
            $isJobPost = in_array($categoryId, self::JOB_CATEGORIES);
            
            // If it's a job post and user is company, check package quota
            if ($isJobPost && $user->usertype === 'company') {
                $canPost = $this->checkJobPostingQuota($user);
                
                if (!$canPost) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have exhausted your job posting quota. Please upgrade your package.',
                        'errors' => (object)['quota' => 'Insufficient job posting quota']
                    ], 403);
                }
            }
            
            return $this->createRegularPost($request, $user, $isJobPost);
        } catch (Exception $e) {
            Log::error('Create post failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * Check if company can post a job based on package quota
     */
    private function checkJobPostingQuota($user)
    {
        // If user has no package, cannot post job
        if (!$user->package_id) {
            return false;
        }
        // Check if package is active and has remaining listings
        $isActive = $user->package_end_date && 
                   Carbon::parse($user->package_end_date)->isFuture();
        $hasListings = ($user->jobs_quota - $user->availed_jobs_quota) > 0;
        return $isActive && $hasListings;
    }

    /**
     * Create regular post (Handles all 13 types including jobs)
     */
    private function createRegularPost(Request $request, $user, $isJobPost = false)
    {
        $validator = Validator::make($request->all(), [
            'post_type_id' => 'required|exists:post_types,id',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
            'is_published' => 'boolean',
        ]);

        // Add validation rules based on category and subcategory
        $categoryId = $request->category_id;
        $subcategoryId = $request->subcategory_id;
        
        // Category 1: Projects
        if ($categoryId == 1) {
            if ($subcategoryId == 1) { // Mini innovation/fun innovation
                $validator->addRules([
                    'tech_stack' => 'required|array',
                    'tech_stack.*' => 'string|max:100',
                    'idea_or_goal' => 'required|string',
                    'outcome_or_fun_element' => 'required|string',
                ]);
            } elseif ($subcategoryId == 2) { // Real Project
                $validator->addRules([
                    'tech_stack' => 'nullable|array',
                    'tech_stack.*' => 'string|max:100',
                    'project_domain' => 'required|string|max:255',
                    'role_in_project' => 'required|string|max:255',
                    'duration_start' => 'required|date',
                    'duration_end' => 'required|date|after_or_equal:duration_start',
                ]);
            }
        }
        // Category 2: Achievements
        elseif ($categoryId == 2) {
            if ($subcategoryId == 3) { // Certification
                $validator->addRules([
                    'certification_title' => 'required|string|max:255',
                    'technology_topic' => 'required|string|max:255',
                ]);
            } elseif ($subcategoryId == 4) { // Rewards / Recognitions
                $validator->addRules([
                    'award_name' => 'required|string|max:255',
                    'technology_topic' => 'required|string|max:255',
                ]);
            } elseif ($subcategoryId == 5) { // Congratulate Someone
                $validator->addRules([
                    'occasion_title' => 'required|string|max:255',
                    'message' => 'required|string',
                    'technology_topic' => 'nullable|string|max:255',
                    'tagged_users' => 'required|array|min:1',
                    'tagged_users.*' => 'exists:users,id',
                ]);
            }
        }
        // Category 3: Events
        elseif ($categoryId == 3) {
            $validator->addRules([
                // 'event_date' => 'nullable|date',
                // 'event_end_date' => 'nullable|date|after_or_equal:event_date',
                'technology_topic' => 'required|string|max:255',
            ]);
            
            if ($subcategoryId == 6) { // Hosted Event
                // No additional rules
            } elseif ($subcategoryId == 7) { // Attended an Event
                $validator->addRules([
                    'organizer_id' => 'nullable|exists:users,id',
                ]);
            } elseif ($subcategoryId == 8) { // Hackathon
                $validator->addRules([
                    'result_rank' => 'nullable|string|max:100',
                ]);
            } elseif ($subcategoryId == 9) { // Webinar
                $validator->addRules([
                    'host_id' => 'nullable|exists:users,id',
                ]);
            }
        }
        // Category 4: Knowledge Sharing
        elseif ($categoryId == 4) {
            $validator->addRules([
                'technology_topic' => 'required|string|max:255',
            ]);
            
            if ($subcategoryId == 10) { // Ideas/Suggestions
                $validator->addRules([
                    'idea_title' => 'required|string|max:255',
                ]);
            } elseif ($subcategoryId == 11) { // Playbook/Guide
                $validator->addRules([
                    'guide_title' => 'required|string|max:255',
                ]);
            } elseif ($subcategoryId == 12) { // Trying New
                // No additional rules
            }
        }
        // Category 5: Jobs - Mini Mission
        elseif ($categoryId == 5) {
            // Mini Mission (subcategory_id = 13)
            if ($subcategoryId == 13) { 
                $validator->addRules([
                    'deliverables' => 'required|string',
                    'timeline_start' => 'nullable|date',
                    'timeline_end' => 'nullable|date|after_or_equal:timeline_start',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'salary_range' => 'nullable|string|max:100',
                    'experience_required' => 'nullable|string|max:100',
                  //  'skills_required' => 'nullable|array',
                   // 'skills_required.*' => 'exists:job_skills,id',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
        }
        // Category 6: Internship
        elseif ($categoryId == 6) {
            // Internship (subcategory_id = 14)
            if ($subcategoryId == 14) {
                $validator->addRules([
                    'role_type' => 'required|in:intern,fresher',
                    'work_mode' => 'required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'required|string',
                    'internship_duration' => 'required|string|max:100',
                    'stipend_amount' => 'nullable|numeric',
                    'stipend_currency' => 'nullable|string|size:3',
                    'convertible_to_full_time' => 'boolean',
                 //   'application_deadline' => 'required|date|after:today',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                  ////  'skills_required' => 'nullable|array',
                   // 'skills_required.*' => 'exists:job_skills,id',
                    'benefits' => 'nullable|string',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
        }
        // Category 7: Fresher Role (New)
        elseif ($categoryId == 7) {
            // Fresher Role (subcategory_id = 15 or any)
            $validator->addRules([
                'role_type' => 'required|in:fresher,full_time',
                'work_mode' => 'required|in:onsite,remote,hybrid',
                'key_deliverables' => 'required|string',
                'ctc_amount' => 'required|numeric',
                'ctc_currency' => 'required|string|size:3',
             //   'application_deadline' => 'required|date|after:today',
               
              
            //    'skills_required' => 'required|array',
             //   'skills_required.*' => 'exists:job_skills,id',
                'experience_required' => 'nullable|string|max:100',
                'benefits' => 'nullable|string',
                'application_url' => 'nullable|url|max:500',
            ]);
        }

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$errors
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $uploadPath = public_path('post_images');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                foreach ($request->file('images') as $image) {
                    $imageName = 'post_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $imageName);
                    
                    // Add watermark to the uploaded image
                    $this->addWatermarkToImage($uploadPath . '/' . $imageName);
                    
                    // Upload to DigitalOcean Spaces
                    \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_images', new \Illuminate\Http\File($uploadPath . '/' . $imageName), $imageName, 'public');
                    // Delete local temp file
                    @unlink($uploadPath . '/' . $imageName);
                    
                    $imagePaths[] = $imageName;
                }
            }

            // Handle file uploads
            $filePaths = [];
            if ($request->hasFile('files')) {
                $uploadPath = public_path('post_files');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                foreach ($request->file('files') as $file) {
                    $fileName = 'file_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);
                    
                    // Upload to DigitalOcean Spaces
                    \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_files', new \Illuminate\Http\File($uploadPath . '/' . $fileName), $fileName, 'public');
                    // Delete local temp file
                    @unlink($uploadPath . '/' . $fileName);
                    
                    $filePaths[] = $fileName;
                }
            }

            // Determine if this is a job post (category_id 5, 6, or 7)
            $isJobPost = in_array($categoryId, self::JOB_CATEGORIES);

            // Create post data
            $postData = [
                'user_id' => $user->id,
                'post_user_type' => $user->usertype,
                'post_type_id' => $request->post_type_id,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'title' => $request->title,
                'content' => $request->content,
                'short_description' => $request->short_description,
                'images' => !empty($imagePaths) ? json_encode($imagePaths) : null,
                'files' => !empty($filePaths) ? json_encode($filePaths) : null,
                'is_published' => $request->is_published ?? true,
                'is_active' => true,
                'is_job_post' => $isJobPost,
            ];

            // Add all possible fields (they will be null if not present)
            $allPossibleFields = [
                // Project fields
                'tech_stack', 'idea_or_goal', 'outcome_or_fun_element', 
                'project_domain', 'role_in_project', 'duration_start', 'duration_end',
                
                // Achievement fields
                'certification_title', 'award_name', 'technology_topic', 
                'occasion_title', 'message',
                
                // Event fields
                'event_date', 'event_end_date', 'result_rank', 'organizer_id', 'host_id',
                
                // Knowledge sharing fields
                'idea_title', 'guide_title',
                
                // Job fields (for all job types)
                'deliverables', 'timeline_start', 'timeline_end',
                'role_type', 'work_mode', 'key_deliverables', 'internship_duration','mini_duration','achieve_date','reward_date',
                'stipend_amount', 'stipend_currency', 'convertible_to_full_time',
                'ctc_amount', 'ctc_currency', 'application_deadline',
                'company_name', 'job_location', 'salary_range', 'experience_required',
                'skills_required', 'benefits', 'application_url'
            ];

            foreach ($allPossibleFields as $field) {
                if ($request->has($field)) {
                    // Handle JSON fields
                    if (in_array($field, ['tech_stack', 'skills_required'])) {
                        $postData[$field] = json_encode($request->$field);
                    } else {
                        $postData[$field] = $request->$field;
                    }
                }
            }

            $post = Post::create($postData);

            // Handle tags
            $this->handlePostTags($post->id, $request->tagged_users ?? []);

            // If this is a job post and user is company, increment the availed_jobs_quota
            if ($isJobPost && $user->usertype === 'company') {
                $user->availed_jobs_quota += 1;
                $user->save();
                
                Log::info('Job posting quota updated', [
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'used_listings' => $user->availed_jobs_quota,
                    'total_listings' => $user->jobs_quota
                ]);
            }

            DB::commit();

            $post->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers']);

            $this->send_notification($post);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $this->formatPostResponse($post)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files if post creation failed
            if (!empty($imagePaths)) {
                foreach ($imagePaths as $imagePath) {
                    @unlink(public_path('post_images/' . $imagePath));
                }
            }
            if (!empty($filePaths)) {
                foreach ($filePaths as $filePath) {
                    @unlink(public_path('post_files/' . $filePath));
                }
            }
            
            throw $e;
        }
    }

    /**
     * Get user's remaining job posting quota
     */
    public function getJobPostingQuota(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->usertype !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only companies have job posting quota'
                ], 403);
            }

            $hasPackage = !is_null($user->package_id);
            $isActive = $user->package_end_date && 
                       Carbon::parse($user->package_end_date)->isFuture();
            $remainingListings = ($user->jobs_quota ?? 0) - ($user->availed_jobs_quota ?? 0);
            $canPost = $hasPackage && $isActive && $remainingListings > 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'has_package' => $hasPackage,
                    'package_active' => $isActive,
                    'total_listings' => $user->jobs_quota ?? 0,
                    'used_listings' => $user->availed_jobs_quota ?? 0,
                    'remaining_listings' => $remainingListings,
                    'can_post' => $canPost,
                    'job_posts_count' => Post::where('user_id', $user->id)
                        ->whereIn('category_id', self::JOB_CATEGORIES)
                        ->count(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get job posting quota failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get job posting quota',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    private function send_notification($post)
    {
        $postUserId = $post->user_id; // Post owner
    
        // Get all accepted connections of the post owner
        $connections = UserConnection::where(function ($q) use ($postUserId) {
                $q->where('follower_id', $postUserId)
                  ->orWhere('following_id', $postUserId);
            })
            ->where('status', self::STATUS_ACCEPTED)
            ->get();
    
        $userIds = [];
    
        foreach ($connections as $conn) {
    
            $otherUserId = $conn->follower_id == $postUserId
                ? $conn->following_id
                : $conn->follower_id;
    
            $userIds[] = $otherUserId;
        }
    
        // Remove duplicates
        $userIds = array_unique($userIds);
    
        // Fetch users with firebase token
        $followers = User::whereIn('id', $userIds)
            ->whereNotNull('firebase_token')
            ->get();
    
        foreach ($followers as $user) {
            
            // Check user push notification preference
            if (!$user->push_notification) {
                 continue; // Skip if notification disabled
            }
            
    
            $fcmService = app(\App\Services\FCMService::class);
    
            $title = "New post from " . ($post->user->first_name ?? 'User');

            $body = \Illuminate\Support\Str::limit(
                strip_tags($post->content),
                100
            );

            $dataPayload = [
                'screen' => 'post_detail',
                'post_id' => (string) $post->id,
                'user_id' => (string) $user->id,
                'type' => 'new_post'
            ];
    
            $fcmService->fcmSendNotification(
                $user->firebase_token,
                $title,
                $body,
                $dataPayload
            );
        }
    }
    
    
    private function addWatermarkToImage($imagePath)
    {
        try {
            
            $user = Auth::user();
           // $watermarkText = "showcazz #" . $user->unique_id;
           // $watermarkText        = "showcazz";
            $watermarkText = $user->unique_id;
            $watermarkPadding     = 20;
            $watermarkColor       = [255, 255, 255];
            $watermarkShadowColor = [0, 0, 0];
    
            // ✅ Font cached in /tmp (allowed by open_basedir)
            $fontPath = '/tmp/DejaVuSans-Bold.ttf';
    
            // Delete if corrupt/incomplete from previous failed download
            if (file_exists($fontPath) && filesize($fontPath) < 10000) {
                unlink($fontPath);
            }
    
            // Download font if not cached
            if (!file_exists($fontPath)) {
                $fontUrls = [
                    'https://github.com/owncloud/docs/raw/master/fonts/dejavu-sans-bold.ttf',
                    'https://raw.githubusercontent.com/mobilejazz/harmony-reference/master/assets/fonts/DejaVuSans-Bold.ttf',
                    'https://db.onlinewebfonts.com/t/bc3e195079415f04ef5925380a2a40f6.ttf',
                ];
    
                $fontData = false;
                foreach ($fontUrls as $url) {
                    $fontData = @file_get_contents($url);
                    if ($fontData !== false && strlen($fontData) > 10000) {
                        Log::info('Watermark: Font downloaded from: ' . $url);
                        break;
                    }
                    $fontData = false;
                }
    
                if ($fontData === false) {
                    Log::error('Watermark Error: All font download URLs failed.');
                    return false;
                }
    
                file_put_contents($fontPath, $fontData);
            }
    
            // Validate image
            if (!file_exists($imagePath) || !is_readable($imagePath)) {
                Log::error('Watermark Error: Image not found or not readable: ' . $imagePath);
                return false;
            }
    
            list($width, $height, $type) = getimagesize($imagePath);
    
            // Create image resource
            switch ($type) {
                case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($imagePath); break;
                case IMAGETYPE_PNG:  $image = imagecreatefrompng($imagePath);  break;
                case IMAGETYPE_GIF:  $image = imagecreatefromgif($imagePath);  break;
                case IMAGETYPE_WEBP: $image = imagecreatefromwebp($imagePath); break;
                default:
                    Log::error('Watermark Error: Unsupported image type: ' . $type);
                    return false;
            }
    
            if (!$image) {
                Log::error('Watermark Error: Failed to create image resource.');
                return false;
            }
    
            // Convert PNG to truecolor so alpha/blending works correctly
            if ($type === IMAGETYPE_PNG) {
                $trueColor = imagecreatetruecolor($width, $height);
                imagealphablending($trueColor, false);
                imagesavealpha($trueColor, true);
                imagecopy($trueColor, $image, 0, 0, 0, 0, $width, $height);
                imagedestroy($image);
                $image = $trueColor;
            }
    
            // Enable blending before drawing
            imagealphablending($image, true);
    
            // Light/semi-transparent white text + subtle shadow
            // Alpha: 0 = fully opaque, 127 = fully transparent
            $textColor   = imagecolorallocatealpha($image, 255, 255, 255, 80);  // light white
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 100);       // subtle shadow
    
            // Font size = 5% of image width, minimum 24px
            // $fontSize = max(24, (int) round($width * 0.05));
            $fontSize = max(10, (int) round($width * 0.03));
            // Measure text dimensions
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $watermarkText);
            if ($bbox === false) {
                Log::error('Watermark Error: imagettfbbox failed — font may be corrupt, deleting cached font.');
                unlink($fontPath);
                return false;
            }
    
            $textWidth  = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
    
            // ✅ Position: left side, bottom
            $x = $watermarkPadding;
            $y = $height - $watermarkPadding;
    
            // Draw shadow then text on top
            imagettftext($image, $fontSize, 0, $x + 2, $y + 2, $shadowColor, $fontPath, $watermarkText);
            imagettftext($image, $fontSize, 0, $x,     $y,     $textColor,   $fontPath, $watermarkText);
    
            // Save image back to same path
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($image, $imagePath, 90);
                    break;
                case IMAGETYPE_PNG:
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    imagepng($image, $imagePath, 9);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($image, $imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($image, $imagePath, 90);
                    break;
            }
    
            imagedestroy($image);
            return true;
    
        } catch (Exception $e) {
            Log::error('Watermark Error: ' . $e->getMessage());
            return false;
        }
    }
    
    

    // ============================================
    // EDIT POST DETAILS
    // ============================================
    
    public function editPostDetails($id)
    {
        try {
            $user = Auth::user();
    
            $post = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
            ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found or you are not authorized to edit it',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Post details retrieved for editing',
                'data' => $this->formatPostForEdit($post)
            ]);
    
        } catch (\Exception $e) {
    
            Log::error('Edit post details failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post details',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Format post for edit
     */
    private function formatPostForEdit($post)
    {
        // Decode JSON fields safely
        $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
        $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
        $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
        $skillsRequired = is_array($post->skills_required) ? $post->skills_required : (json_decode($post->skills_required ?? '', true) ?: []);
    
        $taggedUsers = $post->taggedUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->getName(),
                'image' => $user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null,
                'usertype' => $user->usertype ?? 'professional',
            ];
        });

        $formatted = [
            'id' => $post->id,
            'post_type_id' => $post->post_type_id,
            'category_id' => $post->category_id,
            'subcategory_id' => $post->subcategory_id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'is_published' => $post->is_published,
            'is_job_post' => $post->is_job_post,
    
            'existing_images' => array_map(function ($image) {
                $path = public_path('post_images/' . $image);
                return [
                    'name' => $image,
                    'url' => env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image),
                    'size' => file_exists($path) ? filesize($path) : 0
                ];
            }, $images),
    
            'existing_files' => array_map(function ($file) {
                $path = public_path('post_files/' . $file);
                return [
                    'name' => $file,
                    'url' => env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_files/' . $file) : asset('post_files/' . $file),
                    'size' => file_exists($path) ? filesize($path) : 0
                ];
            }, $files),
    
            'tagged_users' => $taggedUsers,
        ];
    
        $allFields = [
            'tech_stack' => $techStack,
            'idea_or_goal' => $post->idea_or_goal,
            'outcome_or_fun_element' => $post->outcome_or_fun_element,
            'project_domain' => $post->project_domain,
            'role_in_project' => $post->role_in_project,
            'duration_start' => $post->duration_start,
            'duration_end' => $post->duration_end,
            'certification_title' => $post->certification_title,
            'award_name' => $post->award_name,
            'technology_topic' => $post->technology_topic,
            'occasion_title' => $post->occasion_title,
            'message' => $post->message,
            'event_date' => $post->event_date,
            'event_end_date' => $post->event_end_date,
            'result_rank' => $post->result_rank,
            'organizer_id' => $post->organizer_id,
            'host_id' => $post->host_id,
            'idea_title' => $post->idea_title,
            'guide_title' => $post->guide_title,
            'deliverables' => $post->deliverables,
            'timeline_start' => $post->timeline_start,
            'timeline_end' => $post->timeline_end,
            'role_type' => $post->role_type,
            'work_mode' => $post->work_mode,
            'key_deliverables' => $post->key_deliverables,
            'internship_duration' => $post->internship_duration,
            'mini_duration' => $post->mini_duration,
            'duration_new' => $post->internship_duration ?? $post->mini_duration,
            'achieve_date' => $post->achieve_date,
            'reward_date' => $post->reward_date,
            'stipend_amount' => $post->stipend_amount,
            'stipend_currency' => $post->stipend_currency,
            'convertible_to_full_time' => $post->convertible_to_full_time,
            'ctc_amount' => $post->ctc_amount,
            'ctc_currency' => $post->ctc_currency,
            'application_deadline' => $post->application_deadline,
            'company_name' => $post->company_name,
            'job_location' => $post->job_location,
            'salary_range' => $post->salary_range,
            'experience_required' => $post->experience_required,
            'skills_required' => $skillsRequired,
            'benefits' => $post->benefits,
            'application_url' => $post->application_url,
        ];
    
        foreach ($allFields as $field => $value) {
            if ($value !== null && $value !== '') {
                $formatted[$field] = $value;
            }
        }
    
        return $formatted;
    }

    // ============================================
    // UPDATE POST
    // ============================================

    /**
     * Update post
     */
    public function updatePost(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $post = Post::where('id', $id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found or you are not authorized',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }

            return $this->updateRegularPost($request, $post, $user);

        } catch (Exception $e) {
            Log::error('Update post failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

   
    
    private function updateRegularPost(Request $request, $post, $user)
    {
    
        $validator = Validator::make($request->all(), [
            'post_type_id' => 'sometimes|required|exists:post_types,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'subcategory_id' => 'nullable',
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
            'is_published' => 'boolean',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'remove_files' => 'nullable|array',
            'remove_files.*' => 'string',
        ]);
    
        $categoryId = $request->category_id ?? $post->category_id;
        $subcategoryId = $request->subcategory_id ?? $post->subcategory_id;
    
        $this->addCategorySpecificValidationRules($validator, $categoryId, $subcategoryId, $request);
    
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$errors
            ], 422);
        }
    
        DB::beginTransaction();
    
        try {
    
            // ========= IMAGES =========
            $imagePaths = $this->getExistingImages($post);
    
            if ($request->hasFile('images')) {
                $uploadPath = public_path('post_images');
    
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
    
                foreach ($request->file('images') as $image) {
                    $imageName = 'post_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $imageName);
    
                    $this->addWatermarkToImage($uploadPath . '/' . $imageName);
    
                    // Upload to DigitalOcean Spaces
                    \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_images', new \Illuminate\Http\File($uploadPath . '/' . $imageName), $imageName, 'public');
                    // Delete local temp file
                    @unlink($uploadPath . '/' . $imageName);
    
                    $imagePaths[] = $imageName;
                }
            }
    
            if ($request->has('remove_images')) {
                foreach ($request->remove_images as $img) {
                    $key = array_search($img, $imagePaths);
                    if ($key !== false) {
                        @unlink(public_path('post_images/' . $img));
                        unset($imagePaths[$key]);
                    }
                }
                $imagePaths = array_values($imagePaths);
            }
    
            // ========= FILES =========
            $filePaths = $this->getExistingFiles($post);
    
            if ($request->hasFile('files')) {
                $uploadPath = public_path('post_files');
    
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
    
                foreach ($request->file('files') as $file) {
                    $fileName = 'file_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);
                    
                    // Upload to DigitalOcean Spaces
                    \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_files', new \Illuminate\Http\File($uploadPath . '/' . $fileName), $fileName, 'public');
                    // Delete local temp file
                    @unlink($uploadPath . '/' . $fileName);
                    
                    $filePaths[] = $fileName;
                }
            }
    
            if ($request->has('remove_files')) {
                foreach ($request->remove_files as $file) {
                    $key = array_search($file, $filePaths);
                    if ($key !== false) {
                        @unlink(public_path('post_files/' . $file));
                        unset($filePaths[$key]);
                    }
                }
                $filePaths = array_values($filePaths);
            }
    
            // ========= BASIC FIELDS =========
            $updateData = [];
    
            $basicFields = [
                'post_type_id', 'category_id', 'subcategory_id',
                'title', 'content', 'short_description', 'is_published'
            ];
    
            foreach ($basicFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }
    
            // ========= CATEGORY FLAG =========
            if ($request->has('category_id')) {
                $updateData['is_job_post'] = in_array($request->category_id, self::JOB_CATEGORIES);
            }
    
            // ========= SAVE MEDIA =========
            $updateData['images'] = !empty($imagePaths) ? json_encode($imagePaths) : null;
            $updateData['files']  = !empty($filePaths) ? json_encode($filePaths) : null;
    
            // ========= SKILLS REQUIRED (FINAL FIX) =========
            // if ($request->exists('skills_required')) {
    
            //     $skills = $request->skills_required;
    
            //     if ($skills === null || (is_array($skills) && empty($skills))) {
            //         $updateData['skills_required'] = null;
            //     } elseif (is_array($skills)) {
            //         $updateData['skills_required'] = json_encode(array_values(array_unique($skills)));
            //     }
            // }
            
            // ========= SKILLS REQUIRED (FORCE CLEAR IF NOT SENT) =========
            if ($request->has('skills_required')) {
            
                $skills = $request->skills_required;
            
                if ($skills === null || (is_array($skills) && empty($skills))) {
                    $updateData['skills_required'] = null;
                } elseif (is_array($skills)) {
                    $updateData['skills_required'] = json_encode(array_values(array_unique($skills)));
                }
            
            } else {
                // 🔥 IMPORTANT: if not sent, clear old data
                $updateData['skills_required'] = null;
            }
    
            // ========= TECH STACK =========
            if ($request->exists('tech_stack')) {
    
                $tech = $request->tech_stack;
    
                if ($tech === null || (is_array($tech) && empty($tech))) {
                    $updateData['tech_stack'] = null;
                } elseif (is_array($tech)) {
                    $updateData['tech_stack'] = json_encode(array_values(array_unique($tech)));
                }
            }
    
            // ========= OTHER FIELDS =========
            $allFields = [
                'idea_or_goal','outcome_or_fun_element','project_domain','role_in_project',
                'duration_start','duration_end','certification_title','award_name',
                'technology_topic','occasion_title','message','event_date','event_end_date',
                'result_rank','organizer_id','host_id','idea_title','guide_title',
                'deliverables','timeline_start','timeline_end','role_type','work_mode',
                'key_deliverables','internship_duration','mini_duration','achieve_date',
                'reward_date','stipend_amount','stipend_currency','convertible_to_full_time',
                'ctc_amount','ctc_currency','application_deadline','company_name',
                'job_location','salary_range','experience_required','benefits','application_url'
            ];
    
            foreach ($allFields as $field) {
                if ($request->has($field) && !isset($updateData[$field])) {
                    $updateData[$field] = $request->$field;
                }
            }
    
            // ========= UPDATE =========
            $post->update($updateData);
    
            // ========= TAGS =========
            if ($request->has('tagged_users')) {
                PostTag::where('post_id', $post->id)->delete();
                $this->handlePostTags($post->id, $request->tagged_users ?? []);
            }
    
            DB::commit();
    
            $post->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers']);
    
            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $this->formatPostResponse($post)
            ]);
    
        } catch (Exception $e) {
    
            DB::rollBack();
    
            // cleanup uploads if failed
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    @unlink(public_path('post_images/' . $image->getClientOriginalName()));
                }
            }
    
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    @unlink(public_path('post_files/' . $file->getClientOriginalName()));
                }
            }
    
            throw $e;
        }
    }
    
    
   

    /**
     * Helper method to add category-specific validation rules
     */
    private function addCategorySpecificValidationRules($validator, $categoryId, $subcategoryId, $request)
    {
        // Category 1: Projects
        if ($categoryId == 1) {
            if ($subcategoryId == 1) { // Mini innovation/fun innovation
                $validator->addRules([
                    'tech_stack' => 'sometimes|required|array',
                    'tech_stack.*' => 'string|max:100',
                    'idea_or_goal' => 'sometimes|required|string',
                    'outcome_or_fun_element' => 'sometimes|required|string',
                ]);
            } elseif ($subcategoryId == 2) { // Real Project
                $validator->addRules([
                    'tech_stack' => 'nullable|array',
                    'tech_stack.*' => 'string|max:100',
                    'project_domain' => 'sometimes|required|string|max:255',
                    'role_in_project' => 'sometimes|required|string|max:255',
                    'duration_start' => 'sometimes|required|date',
                    'duration_end' => 'sometimes|required|date|after_or_equal:duration_start',
                ]);
            }
        }
        // Category 2: Achievements
        elseif ($categoryId == 2) {
            if ($subcategoryId == 3) { // Certification
                $validator->addRules([
                    'certification_title' => 'sometimes|required|string|max:255',
                    'technology_topic' => 'sometimes|required|string|max:255',
                ]);
            } elseif ($subcategoryId == 4) { // Rewards / Recognitions
                $validator->addRules([
                    'award_name' => 'sometimes|required|string|max:255',
                    'technology_topic' => 'sometimes|required|string|max:255',
                ]);
            } elseif ($subcategoryId == 5) { // Congratulate Someone
                $validator->addRules([
                    'occasion_title' => 'sometimes|required|string|max:255',
                    'message' => 'sometimes|required|string',
                    'technology_topic' => 'nullable|string|max:255',
                    'tagged_users' => 'sometimes|required|array|min:1',
                    'tagged_users.*' => 'exists:users,id',
                ]);
            }
        }
        // Category 3: Events
        elseif ($categoryId == 3) {
            $validator->addRules([
                // 'event_date' => 'sometimes|required|date',
                // 'event_end_date' => 'nullable|date|after_or_equal:event_date',
                'technology_topic' => 'sometimes|required|string|max:255',
            ]);
            
            if ($subcategoryId == 8) { // Hackathon
                $validator->addRules([
                    'result_rank' => 'nullable|string|max:100',
                ]);
            } elseif ($subcategoryId == 7) { // Attended an Event
                $validator->addRules([
                    'organizer_id' => 'nullable|exists:users,id',
                ]);
            } elseif ($subcategoryId == 9) { // Webinar
                $validator->addRules([
                    'host_id' => 'nullable|exists:users,id',
                ]);
            }
        }
        // Category 4: Knowledge Sharing
        elseif ($categoryId == 4) {
            $validator->addRules([
                'technology_topic' => 'sometimes|required|string|max:255',
            ]);
            
            if ($subcategoryId == 10) { // Ideas/Suggestions
                $validator->addRules([
                    'idea_title' => 'sometimes|required|string|max:255',
                ]);
            } elseif ($subcategoryId == 11) { // Playbook/Guide
                $validator->addRules([
                    'guide_title' => 'sometimes|required|string|max:255',
                ]);
            }
        }
        // Category 5: Jobs - Mini Mission
        elseif ($categoryId == 5) {
            // Mini Mission (subcategory_id = 13)
            if ($subcategoryId == 13) {
                $validator->addRules([
                    'deliverables' => 'sometimes|required|string',
                    'timeline_start' => 'sometimes|required|date',
                    'timeline_end' => 'sometimes|required|date|after_or_equal:timeline_start',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'salary_range' => 'nullable|string|max:100',
                    'experience_required' => 'nullable|string|max:100',
                    'skills_required' => 'nullable|array',
                    'skills_required.*' => 'exists:job_skills,id',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
        }
        // Category 6: Internship
        elseif ($categoryId == 6) {
            // Internship (subcategory_id = 14)
            if ($subcategoryId == 14) {
                $validator->addRules([
                    'role_type' => 'sometimes|required|in:intern,fresher',
                    'work_mode' => 'sometimes|required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'sometimes|required|string',
                    'internship_duration' => 'sometimes|required|string|max:100',
                    'stipend_amount' => 'nullable|numeric',
                    'stipend_currency' => 'nullable|string|size:3',
                    'convertible_to_full_time' => 'boolean',
                 //   'application_deadline' => 'sometimes|required|date|after:today',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'skills_required' => 'nullable|array',
                    'skills_required.*' => 'exists:job_skills,id',
                    'benefits' => 'nullable|string',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
        }
        // Category 7: Fresher Role
        elseif ($categoryId == 7) {
            // Fresher Role
            $validator->addRules([
                'role_type' => 'sometimes|required|in:fresher,full_time',
                'work_mode' => 'sometimes|required|in:onsite,remote,hybrid',
                'key_deliverables' => 'sometimes|required|string',
                'ctc_amount' => 'sometimes|required|numeric',
                'ctc_currency' => 'sometimes|required|string|size:3',
               // 'application_deadline' => 'sometimes|required|date|after:today',
                'company_name' => 'sometimes|required|string|max:255',
                'job_location' => 'sometimes|required|string|max:255',
                'skills_required' => 'sometimes|required|array',
                'skills_required.*' => 'exists:job_skills,id',
                'experience_required' => 'nullable|string|max:100',
                'benefits' => 'nullable|string',
                'application_url' => 'nullable|url|max:500',
            ]);
        }
    }

    // ============================================
    // POST INTERACTIONS
    // ============================================

    
    
    
    public function toggleLike($id)
    {
        try {
            $user = Auth::user();
    
            $post = Post::where('id', $id)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            $existingLike = PostLike::where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->first();
    
            if ($existingLike) {
                $existingLike->delete();
                $post->decrement('likes_count');
                $liked = false;
                $message = 'Post unliked';
            } else {
                PostLike::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
                $post->increment('likes_count');
                $liked = true;
                $message = 'Post liked';
            }
    
            $post->refresh();
            DB::commit();
            
            
          
            // ✅ Send notification using NotificationService (only if liked, not unliked)
            if ($liked && $post->user_id != $user->id) {
               
                $title = $user->first_name . " liked your post";
                $body = \Illuminate\Support\Str::limit(strip_tags($post->content), 100);
                
                $actionPayload = [
                    'post_id' => $post->id,
                    'action' => 'view_post'
                ];
                
             
    
                $this->notificationService->send(
                    $post->user_id,  // recipient
                    'post_like',      // type
                    $title,           // title
                    $body,            // body
                    'post_detail',    // screen
                    $actionPayload,   // action payload
                    $user->id         // from_user_id
                );
            }
    
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'liked' => $liked,
                    'likes_count' => $post->likes_count,
                ]
            ]);
    
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Toggle like failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    public function addComment(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'parent_comment_id' => 'nullable|exists:post_comments,id',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            $user = Auth::user();
            
            $post = Post::where('id', $id)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                $comment = PostComment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'parent_comment_id' => $request->parent_comment_id,
                    'content' => $request->content,
                    'is_active' => true,
                ]);
    
                $post->increment('comments_count');
    
                DB::commit();
    
                $comment->load('user');
    
                // ✅ Send notification using NotificationService
                if ($post->user_id != $user->id) {
                    $title = $user->first_name . " commented on your post";
                    $body = \Illuminate\Support\Str::limit(strip_tags($request->content), 100);
                    
                    $actionPayload = [
                        'post_id' => $post->id,
                        'action' => 'view_post'
                    ];
    
                    $this->notificationService->send(
                        $post->user_id,  // recipient
                        'post_comment',   // type
                        $title,           // title
                        $body,            // body
                        'post_detail',    // screen
                        $actionPayload,   // action payload
                        $user->id         // from_user_id
                    );
                }
    
                return response()->json([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'data' => [
                        'comment' => $this->formatCommentResponse($comment),
                        'comments_count' => $post->comments_count,
                    ]
                ]);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Add comment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
    /**
     * Share post
     */
    // public function sharePost(Request $request, $id)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'shared_to_user_id' => 'nullable|exists:users,id',
    //         ]);
    
    //         if ($validator->fails()) {
    //             $errors = [];
    //             foreach ($validator->errors()->toArray() as $field => $messages) {
    //                 $errors[$field] = $messages[0];
    //             }
                
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors' => (object)$errors
    //             ], 422);
    //         }
    
    //         $user = Auth::user();
            
    //         $post = Post::where('id', $id)
    //             ->where('is_active', true)
    //             ->where('is_published', true)
    //             ->first();
    
    //         if (!$post) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Post not found',
    //                 'errors' => (object)['post' => 'Post not found']
    //             ], 404);
    //         }
    
    //         DB::beginTransaction();
    
    //         try {
    //             $share = PostShare::create([
    //                 'post_id' => $post->id,
    //                 'user_id' => $user->id,
    //                 'shared_to_user_id' => $request->shared_to_user_id,
    //             ]);
    
    //             $post->increment('shares_count');
    
    //             // ============================================
    //             // IF SHARING TO A SPECIFIC USER - HANDLE CHAT
    //             // ============================================
    //             if ($request->has('shared_to_user_id') && $request->shared_to_user_id) {
                    
    //                 // Don't create chat if sharing to self
    //                 if ($user->id != $request->shared_to_user_id) {
                        
    //                     // Get chat type (general)
    //                     $chatType = ChatType::where('slug', 'general')->first();
                        
    //                     if ($chatType) {
                            
    //                         // Get post image URL if exists
    //                         $images = $post->images ? json_decode($post->images, true) : [];
                            
    //                         // Create the URL for message_txt
    //                         if (!empty($images)) {
    //                             // Use the first image URL with post ID appended
    //                             $messageText = asset('post_images/' . $images[0]) . '/' . $post->id;
    //                         } else {
    //                             // If no image, just use post URL with ID
    //                             $messageText = url('/post/' . $post->id);
    //                         }
                            
    //                         // Get receiver data
    //                         $receiverData = $this->getEntityData($request->shared_to_user_id);
    //                         $currentUserData = $this->getEntityData($user->id);
                            
    //                         if ($receiverData && $currentUserData) {
                                
    //                             // CHECK IF CHAT SESSION ALREADY EXISTS BETWEEN THESE USERS
    //                             $existingSession = ChatSession::where(function($query) use ($user, $request, $chatType) {
    //                                     $query->where('user1_id', $user->id)
    //                                           ->where('user2_id', $request->shared_to_user_id)
    //                                           ->where('chat_type_id', $chatType->id);
    //                                 })
    //                                 ->orWhere(function($query) use ($user, $request, $chatType) {
    //                                     $query->where('user1_id', $request->shared_to_user_id)
    //                                           ->where('user2_id', $user->id)
    //                                           ->where('chat_type_id', $chatType->id);
    //                                 })
    //                                 ->where('is_active', true)
    //                                 ->first();
                                
    //                             if ($existingSession) {
    //                                 // USE EXISTING CHAT SESSION
    //                                 $chatSession = $existingSession;
                                    
    //                                 // Increment unread count
    //                                 $chatSession->increment('unread_count');
                                    
    //                                 // Update last message
    //                                 $chatSession->update([
    //                                     'last_message_at' => now(),
    //                                     'last_message' => Str::limit("Shared a post", 100)
    //                                 ]);
                                    
    //                                 $sessionId = $chatSession->id;
                                    
    //                             } else {
    //                                 // CREATE NEW CHAT SESSION
    //                                 $sessionId = ChatSession::generateId(
    //                                     $user->id,
    //                                     $request->shared_to_user_id,
    //                                     $post->id,
    //                                     $chatType->id
    //                                 );
                                    
    //                                 // Create new chat session
    //                                 $chatSession = ChatSession::create([
    //                                     'id' => $sessionId,
    //                                     'user1_id' => min($user->id, $request->shared_to_user_id),
    //                                     'user2_id' => max($user->id, $request->shared_to_user_id),
    //                                     'chat_type_id' => $chatType->id,
    //                                     'worth_discussing_point_id' => null,
    //                                     'last_message_at' => now(),
    //                                     'last_message' => Str::limit("Shared a post", 100),
    //                                     'unread_count' => 1,
    //                                     'is_active' => true
    //                                 ]);
    //                             }
                                
    //                             // Set message type based on whether it has image
    //                             $messageType = !empty($images) ? 'image' : 'text';
                                
    //                             // Create message with ONLY the URL in message_txt
    //                             $message = UserMessage::create([
    //                                 'listing_id' => $post->id,
    //                                 'listing_title' => $post->title,
    //                                 'from_id' => $user->id,
    //                                 'to_id' => $request->shared_to_user_id,
    //                                 'to_email' => $receiverData['email'],
    //                                 'to_name' => $receiverData['name'],
    //                                 'from_name' => $currentUserData['name'],
    //                                 'from_email' => $currentUserData['email'],
    //                                 'from_phone' => $currentUserData['phone'],
    //                                 'message_txt' => $messageText,
    //                                 'subject' => 'Shared a post: ' . $post->title,
    //                                 'chat_type_id' => $chatType->id,
    //                                 'chat_session_id' => $sessionId,
    //                                 'worth_discussing_point_id' => null,
    //                                 'status' => 'active',
    //                                 'is_read' => false,
    //                                 'message_type' => $messageType,
    //                             ]);
    //                         }
    //                     }
    //                 }
    //             }
    
    //             DB::commit();
    
    //             // ✅ Send notification using NotificationService (if not self-share)
    //             if ($request->shared_to_user_id != $user->id) {
    //                 $title = $user->first_name . " shared your post";
    //                 $body = \Illuminate\Support\Str::limit(strip_tags($post->content), 100);
                    
    //                 $actionPayload = [
    //                     'post_id' => $post->id,
    //                     'action' => 'view_post'
    //                 ];
    
    //                 $this->notificationService->send(
    //                     $request->shared_to_user_id,  // recipient
    //                     'post_share',     // type
    //                     $title,           // title
    //                     $body,            // body
    //                     'post_detail',    // screen
    //                     $actionPayload,   // action payload
    //                     $user->id         // from_user_id
    //                 );
    //             }
    
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Post shared successfully',
    //                 'data' => [
    //                     'share_id' => $share->id,
    //                     'post_id' => $post->id,
    //                     'shares_count' => $post->shares_count,
    //                     'chat_initiated' => ($request->has('shared_to_user_id') && $request->shared_to_user_id && $user->id != $request->shared_to_user_id) ? true : false,
    //                     'chat_session_id' => isset($chatSession) ? $chatSession->id : null,
    //                     'chat_session_exists' => isset($existingSession) ? true : false,
    //                     'message_id' => isset($message) ? $message->id : null,
    //                     'message_txt' => isset($messageText) ? $messageText : null,
    //                 ]
    //             ]);
    
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    
    //     } catch (Exception $e) {
    //         Log::error('Share post failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to share post',
    //             'errors' => (object)['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }
    
    // public function sharePost(Request $request, $id)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'shared_to_user_id' => 'nullable|exists:users,id',
    //             'initial_message' => 'nullable|string|max:500', // NEW: optional custom message
    //         ]);
    
    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors' => (object)$validator->errors()->toArray()
    //             ], 422);
    //         }
    
    //         $user = Auth::user();
            
    //         // ==============================================
    //         // UPDATE LAST ACTIVITY (like ChatController)
    //         // ==============================================
    //         $this->updateLastActivity($user);
            
    //         $post = Post::where('id', $id)
    //             ->where('is_active', true)
    //             ->where('is_published', true)
    //             ->first();
    
    //         if (!$post) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Post not found',
    //                 'errors' => (object)['post' => 'Post not found']
    //             ], 404);
    //         }
    
    //         DB::beginTransaction();
    
    //         try {
    //             // Create share record
    //             $share = PostShare::create([
    //                 'post_id' => $post->id,
    //                 'user_id' => $user->id,
    //                 'shared_to_user_id' => $request->shared_to_user_id,
    //             ]);
    
    //             $post->increment('shares_count');
    
    //             $chatInitiated = false;
    //             $chatSession = null;
    //             $message = null;
    //             $isRestored = false;
    
    //             // ==============================================
    //             // CHAT INITIALIZATION (FULL LOGIC)
    //             // ==============================================
    //             if ($request->has('shared_to_user_id') && $request->shared_to_user_id) {
                    
    //                 // Don't create chat if sharing to self
    //                 if ($user->id != $request->shared_to_user_id) {
                        
    //                     // ==============================================
    //                     // 1. PERMISSION CHECKS (MISSING BEFORE)
    //                     // ==============================================
                        
    //                     // Check if users can chat (not blocked)
    //                     if (!$this->canChat($user->id, $request->shared_to_user_id)) {
    //                         DB::commit();
    //                         return response()->json([
    //                             'success' => true,
    //                             'message' => 'Post shared successfully, but chat not initiated due to block restrictions',
    //                             'data' => [
    //                                 'share_id' => $share->id,
    //                                 'post_id' => $post->id,
    //                                 'shares_count' => $post->shares_count,
    //                                 'chat_initiated' => false,
    //                                 'reason' => 'blocked'
    //                             ]
    //                         ]);
    //                     }
                        
    //                     // Check message visibility permission
    //                     $visibilityMap = $this->getVisibilityMap();
    //                     $connectionIds = $this->getUserConnections($user->id);
                        
    //                     if (!$this->canMessage($user->id, $request->shared_to_user_id, $connectionIds, $visibilityMap)) {
    //                         DB::commit();
    //                         return response()->json([
    //                             'success' => true,
    //                             'message' => 'Post shared successfully, but chat not initiated due to privacy settings',
    //                             'data' => [
    //                                 'share_id' => $share->id,
    //                                 'post_id' => $post->id,
    //                                 'shares_count' => $post->shares_count,
    //                                 'chat_initiated' => false,
    //                                 'reason' => 'privacy_restricted'
    //                             ]
    //                         ]);
    //                     }
                        
    //                     // ==============================================
    //                     // 2. GET CHAT TYPE
    //                     // ==============================================
    //                     $chatType = ChatType::where('slug', 'general')->first();
                        
    //                     if ($chatType) {
                            
    //                         // ==============================================
    //                         // 3. CHECK FOR EXISTING SESSION (INCLUDING DELETED)
    //                         // ==============================================
    //                         $existingSession = ChatSession::where(function($query) use ($user, $request, $chatType) {
    //                                 $query->where('user1_id', $user->id)
    //                                       ->where('user2_id', $request->shared_to_user_id)
    //                                       ->where('chat_type_id', $chatType->id);
    //                             })
    //                             ->orWhere(function($query) use ($user, $request, $chatType) {
    //                                 $query->where('user1_id', $request->shared_to_user_id)
    //                                       ->where('user2_id', $user->id)
    //                                       ->where('chat_type_id', $chatType->id);
    //                             })
    //                             ->first(); // Include deleted sessions
                            
    //                         // ==============================================
    //                         // 4. HANDLE SOFT-DELETED SESSION (RESTORE LOGIC)
    //                         // ==============================================
    //                         if ($existingSession) {
    //                             $deletedBy = [];
    //                             if ($existingSession->deleted_by) {
    //                                 if (is_string($existingSession->deleted_by)) {
    //                                     $deletedBy = json_decode($existingSession->deleted_by, true);
    //                                 } else {
    //                                     $deletedBy = $existingSession->deleted_by;
    //                                 }
    //                                 if (!is_array($deletedBy)) {
    //                                     $deletedBy = [];
    //                                 }
    //                             }
                                
    //                             $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
    //                             $isDeletedByReceiver = in_array($request->shared_to_user_id, $deletedBy);
                                
    //                             // Restore for current user if needed
    //                             if ($isDeletedByCurrentUser) {
    //                                 $deletedBy = array_filter($deletedBy, function($id) use ($user) {
    //                                     return $id != $user->id;
    //                                 });
    //                                 $existingSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
    //                                 $isRestored = true;
    //                             }
                                
    //                             // Restore for receiver if needed
    //                             if ($isDeletedByReceiver) {
    //                                 $deletedBy = array_filter($deletedBy, function($id) use ($request) {
    //                                     return $id != $request->shared_to_user_id;
    //                                 });
    //                                 $existingSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
    //                                 $isRestored = true;
    //                             }
                                
    //                             // Make sure session is active
    //                             $existingSession->is_active = true;
    //                             $existingSession->save();
                                
    //                             $chatSession = $existingSession;
    //                             $sessionId = $chatSession->id;
                                
    //                         } else {
    //                             // ==============================================
    //                             // 5. CREATE NEW SESSION
    //                             // ==============================================
    //                             $sessionId = ChatSession::generateId(
    //                                 $user->id,
    //                                 $request->shared_to_user_id,
    //                                 $post->id,
    //                                 $chatType->id
    //                             );
                                
    //                             $chatSession = ChatSession::create([
    //                                 'id' => $sessionId,
    //                                 'user1_id' => min($user->id, $request->shared_to_user_id),
    //                                 'user2_id' => max($user->id, $request->shared_to_user_id),
    //                                 'post_id' => $post->id,
    //                                 'chat_type_id' => $chatType->id,
    //                                 'worth_discussing_point_id' => null,
    //                                 'last_message_at' => now(),
    //                                 'last_message' => Str::limit("Shared a post: " . $post->title, 100),
    //                                 'unread_count' => 1,
    //                                 'is_active' => true,
    //                                 'deleted_by' => null
    //                             ]);
    //                         }
                            
    //                         // ==============================================
    //                         // 6. PREPARE MESSAGE CONTENT
    //                         // ==============================================
    //                         $images = $post->images ? json_decode($post->images, true) : [];
                            
    //                         // Build rich message content
    //                         $customMessage = $request->initial_message;
    //                         $shareMessage = $this->buildShareMessageContent($post, $images, $customMessage);
    //                         $messageType = !empty($images) ? 'share_with_image' : 'share';
                            
    //                         // Get user data
    //                         $receiverData = $this->getEntityData($request->shared_to_user_id);
    //                         $currentUserData = $this->getEntityData($user->id);
                            
    //                         if ($receiverData && $currentUserData) {
                                
    //                             // ==============================================
    //                             // 7. CREATE MESSAGE
    //                             // ==============================================
    //                             $message = UserMessage::create([
    //                                 'listing_id' => $post->id,
    //                                 'listing_title' => $post->title,
    //                                 'from_id' => $user->id,
    //                                 'to_id' => $request->shared_to_user_id,
    //                                 'to_email' => $receiverData['email'],
    //                                 'to_name' => $receiverData['name'],
    //                                 'from_name' => $currentUserData['name'],
    //                                 'from_email' => $currentUserData['email'],
    //                                 'from_phone' => $currentUserData['phone'],
    //                                 'message_txt' => $shareMessage,
    //                                 'subject' => 'Shared a post: ' . $post->title,
    //                                 'chat_type_id' => $chatType->id,
    //                                 'chat_session_id' => $sessionId,
    //                                 'worth_discussing_point_id' => null,
    //                                 'status' => 'active',
    //                                 'is_read' => false,
    //                                 'message_type' => $messageType,
    //                             ]);
                                
    //                             // ==============================================
    //                             // 8. UPDATE SESSION STATS
    //                             // ==============================================
    //                             $chatSession->update([
    //                                 'last_message_at' => now(),
    //                                 'last_message' => Str::limit($shareMessage, 100)
    //                             ]);
                                
    //                             // Only increment unread count if not restored (already handled)
    //                             if (!$existingSession || !$isRestored) {
    //                                 $chatSession->increment('unread_count');
    //                             } else {
    //                                 // If restored, set unread count to 1 for the new message
    //                                 $chatSession->unread_count = 1;
    //                                 $chatSession->save();
    //                             }
                                
    //                             $chatInitiated = true;
                                
    //                             // ==============================================
    //                             // 9. SEND PUSH NOTIFICATION (MISSING BEFORE)
    //                             // ==============================================
    //                             $this->sendChatPushNotification($user, $request->shared_to_user_id, $shareMessage, $chatSession);
    //                         }
    //                     }
    //                 }
    //             }
    
    //             DB::commit();
    
    //             // ==============================================
    //             // 10. SEND POST SHARE NOTIFICATION (EXISTING)
    //             // ==============================================
    //             if ($request->shared_to_user_id != $user->id) {
    //                 $title = $user->first_name . " shared your post";
    //                 $body = \Illuminate\Support\Str::limit(strip_tags($post->content), 100);
                    
    //                 $actionPayload = [
    //                     'post_id' => $post->id,
    //                     'action' => 'view_post'
    //                 ];
    
    //                 $this->notificationService->send(
    //                     $request->shared_to_user_id,
    //                     'post_share',
    //                     $title,
    //                     $body,
    //                     'post_detail',
    //                     $actionPayload,
    //                     $user->id
    //                 );
    //             }
    
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Post shared successfully',
    //                 'data' => [
    //                     'share_id' => $share->id,
    //                     'post_id' => $post->id,
    //                     'shares_count' => $post->shares_count,
    //                     'chat_initiated' => $chatInitiated,
    //                     'chat_session_id' => $chatSession ? $chatSession->id : null,
    //                     'chat_session_restored' => $isRestored,
    //                     'message_id' => $message ? $message->id : null,
    //                 ]
    //             ]);
    
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    
    //     } catch (Exception $e) {
    //         Log::error('Share post failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to share post',
    //             'errors' => (object)['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }
    
    
    
    
    // // ==============================================
    // // CHAT HELPER METHODS (COPY FROM CHATCONTROLLER)
    // // ==============================================
    
    // private function updateLastActivity($user)
    // {
    //     if (!$user) return;
    //     try {
    //         $user->last_activity = now();
    //         $user->save();
    //     } catch (Exception $e) {
    //         Log::error('Failed to update last activity', [
    //             'error' => $e->getMessage(),
    //             'user_id' => $user->id ?? null
    //         ]);
    //     }
    // }
    
    // private function canChat($user1Id, $user2Id)
    // {
    //     if (!$user1Id || !$user2Id) return false;
        
    //     if (BlockedUser::isBlocked($user1Id, $user2Id)) return false;
    //     if (BlockedUser::isBlocked($user2Id, $user1Id)) return false;
        
    //     $blockedInConnection = UserConnection::where(function($query) use ($user1Id, $user2Id) {
    //         $query->where('follower_id', $user1Id)
    //               ->where('following_id', $user2Id)
    //               ->where('status', self::STATUS_BLOCKED);
    //     })->orWhere(function($query) use ($user1Id, $user2Id) {
    //         $query->where('follower_id', $user2Id)
    //               ->where('following_id', $user1Id)
    //               ->where('status', self::STATUS_BLOCKED);
    //     })->exists();
    
    //     return !$blockedInConnection;
    // }
    
    // private function getMessageVisibility($targetId, $visibilityMap = [])
    // {
    //     if (isset($visibilityMap[$targetId])) {
    //         return $visibilityMap[$targetId]['message_visibility_control'] ?? 'public';
    //     }
    //     $user = User::find($targetId);
    //     return $user ? ($user->message_visibility_control ?? 'public') : 'public';
    // }
    
    // private function canMessage($currentUserId, $targetId, $connectionIds = [], $visibilityMap = [])
    // {
    //     if ($currentUserId == $targetId) return true;
        
    //     $messageVisibility = $this->getMessageVisibility($targetId, $visibilityMap);
        
    //     if ($messageVisibility == 'public') return true;
    //     if ($messageVisibility == 'private') return in_array($targetId, $connectionIds);
        
    //     return true;
    // }
    
    // private function getUserConnections($userId)
    // {
    //     $following = UserConnection::where('follower_id', $userId)
    //         ->where('status', self::STATUS_ACCEPTED)
    //         ->pluck('following_id')
    //         ->toArray();
        
    //     $followers = UserConnection::where('following_id', $userId)
    //         ->where('status', self::STATUS_ACCEPTED)
    //         ->pluck('follower_id')
    //         ->toArray();
        
    //     return array_unique(array_merge($following, $followers));
    // }
    
    // private function getVisibilityMap()
    // {
    //     $users = User::select('id', 'usertype', 'message_visibility_control', 'last_activity')->get();
    //     $visibilityMap = [];
        
    //     foreach ($users as $user) {
    //         $visibilityMap[$user->id] = [
    //             'type' => $user->usertype === 'company' ? 'company' : 'user',
    //             'message_visibility_control' => $user->message_visibility_control ?? 'public',
    //             'last_activity' => $user->last_activity ?? null,
    //             'is_online' => $this->isUserOnline($user->last_activity),
    //         ];
    //     }
    //     return $visibilityMap;
    // }
    
    // private function isUserOnline($lastActivity)
    // {
    //     if (!$lastActivity) return false;
    //     $lastActivityTime = $lastActivity instanceof Carbon ? $lastActivity : Carbon::parse($lastActivity);
    //     return $lastActivityTime->diffInSeconds(now()) < 30;
    // }
    
    // private function sendChatPushNotification($fromUser, $toUserId, $messageText, $chatSession)
    // {
    //     try {
    //         $receiver = User::find($toUserId);
    //         if (!$receiver || $receiver->push_notification != 1 || empty($receiver->firebase_token)) {
    //             return false;
    //         }
            
    //         $senderName = $fromUser->first_name 
    //             ? $fromUser->first_name . ' ' . $fromUser->last_name 
    //             : ($fromUser->name ?? 'Someone');
            
    //         $title = $senderName . " shared a post with you";
    //         $body = Str::limit($messageText, 100);
            
    //         $actionPayload = [
    //             'chat_session_id' => (string) $chatSession->id,
    //             'from_user_id' => (string) $fromUser->id,
    //             'action' => 'open_chat'
    //         ];
            
    //         return $this->notificationService->send(
    //             $toUserId,
    //             'chat_message',
    //             $title,
    //             $body,
    //             'chat',
    //             $actionPayload,
    //             $fromUser->id
    //         );
    //     } catch (Exception $e) {
    //         Log::error('Failed to send chat push notification', ['error' => $e->getMessage()]);
    //         return false;
    //     }
    // }
    
    
    // private function buildShareMessageContent($post, $images, $customMessage = null)
    // {
    //     // Build the image URL with post ID appended
    //     if (!empty($images)) {
    //         // Format: image_url/post_id
    //         return asset('post_images/' . $images[0]) . '/' . $post->id;
    //     }
        
    //     // If no image, just return post URL
    //     return url('/post/' . $post->id);
    // }
    
    
    /**
     * Share post
     */
    public function sharePost(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'shared_to_user_id' => 'nullable|exists:users,id',
                'initial_message' => 'nullable|string|max:500',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            $user = Auth::user();
            
            // Update last activity
            $this->updateLastActivity($user);
            
            $post = Post::where('id', $id)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Create share record
                $share = PostShare::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'shared_to_user_id' => $request->shared_to_user_id,
                ]);
    
                $post->increment('shares_count');
    
                $chatInitiated = false;
                $chatSession = null;
                $message = null;
                $isRestored = false;
    
                // Chat initialization
                if ($request->has('shared_to_user_id') && $request->shared_to_user_id) {
                    
                    // Don't create chat if sharing to self
                    if ($user->id != $request->shared_to_user_id) {
                        
                        // ==============================================
                        // 1. PERMISSION CHECKS
                        // ==============================================
                        
                        // Check if users can chat (not blocked)
                        if (!$this->canChat($user->id, $request->shared_to_user_id)) {
                            DB::commit();
                            return response()->json([
                                'success' => true,
                                'message' => 'Post shared successfully, but chat not initiated due to block restrictions',
                                'data' => [
                                    'share_id' => $share->id,
                                    'post_id' => $post->id,
                                    'shares_count' => $post->shares_count,
                                    'chat_initiated' => false,
                                    'reason' => 'blocked'
                                ]
                            ]);
                        }
                        
                        // Check message visibility permission
                        $visibilityMap = $this->getVisibilityMap();
                        $connectionIds = $this->getUserConnections($user->id);
                        
                        if (!$this->canMessage($user->id, $request->shared_to_user_id, $connectionIds, $visibilityMap)) {
                            DB::commit();
                            return response()->json([
                                'success' => true,
                                'message' => 'Post shared successfully, but chat not initiated due to privacy settings',
                                'data' => [
                                    'share_id' => $share->id,
                                    'post_id' => $post->id,
                                    'shares_count' => $post->shares_count,
                                    'chat_initiated' => false,
                                    'reason' => 'privacy_restricted'
                                ]
                            ]);
                        }
                        
                        // ==============================================
                        // 2. GET CHAT TYPE
                        // ==============================================
                        $chatType = ChatType::where('slug', 'general')->first();
                        
                        if ($chatType) {
                            
                            // ==============================================
                            // 3. CHECK FOR EXISTING SESSION (INCLUDING DELETED)
                            // ==============================================
                            $existingSession = ChatSession::where(function($query) use ($user, $request, $chatType) {
                                    $query->where('user1_id', $user->id)
                                          ->where('user2_id', $request->shared_to_user_id)
                                          ->where('chat_type_id', $chatType->id);
                                })
                                ->orWhere(function($query) use ($user, $request, $chatType) {
                                    $query->where('user1_id', $request->shared_to_user_id)
                                          ->where('user2_id', $user->id)
                                          ->where('chat_type_id', $chatType->id);
                                })
                                ->first();
                            
                            // ==============================================
                            // 4. HANDLE SOFT-DELETED SESSION (RESTORE LOGIC)
                            // ==============================================
                            if ($existingSession) {
                                $deletedBy = [];
                                if ($existingSession->deleted_by) {
                                    if (is_string($existingSession->deleted_by)) {
                                        $deletedBy = json_decode($existingSession->deleted_by, true);
                                    } else {
                                        $deletedBy = $existingSession->deleted_by;
                                    }
                                    if (!is_array($deletedBy)) {
                                        $deletedBy = [];
                                    }
                                }
                                
                                $isDeletedByCurrentUser = in_array($user->id, $deletedBy);
                                $isDeletedByReceiver = in_array($request->shared_to_user_id, $deletedBy);
                                
                                // Restore for current user if needed
                                if ($isDeletedByCurrentUser) {
                                    $deletedBy = array_filter($deletedBy, function($id) use ($user) {
                                        return $id != $user->id;
                                    });
                                    $existingSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                                    $isRestored = true;
                                }
                                
                                // Restore for receiver if needed
                                if ($isDeletedByReceiver) {
                                    $deletedBy = array_filter($deletedBy, function($id) use ($request) {
                                        return $id != $request->shared_to_user_id;
                                    });
                                    $existingSession->deleted_by = !empty($deletedBy) ? json_encode(array_values($deletedBy)) : null;
                                    $isRestored = true;
                                }
                                
                                // Make sure session is active
                                $existingSession->is_active = true;
                                $existingSession->save();
                                
                                $chatSession = $existingSession;
                                $sessionId = $chatSession->id;
                                
                            } else {
                                // ==============================================
                                // 5. CREATE NEW SESSION
                                // ==============================================
                                $sessionId = ChatSession::generateId(
                                    $user->id,
                                    $request->shared_to_user_id,
                                    $post->id,
                                    $chatType->id
                                );
                                
                                $chatSession = ChatSession::create([
                                    'id' => $sessionId,
                                    'user1_id' => min($user->id, $request->shared_to_user_id),
                                    'user2_id' => max($user->id, $request->shared_to_user_id),
                                    'post_id' => $post->id,
                                    'chat_type_id' => $chatType->id,
                                    'worth_discussing_point_id' => null,
                                    'last_message_at' => now(),
                                    'last_message' => Str::limit("Shared a post", 100),
                                    'unread_count' => 1,
                                    'is_active' => true,
                                    'deleted_by' => null
                                ]);
                            }
                            
                            // ==============================================
                            // 6. PREPARE MESSAGE CONTENT (OLD STYLE - ONLY URL)
                            // ==============================================
                            $images = $post->images ? json_decode($post->images, true) : [];
                            
                            // Create the URL for message_txt (EXACTLY LIKE OLD CODE)
                            if (!empty($images)) {
                                // Use the first image URL with post ID appended
                                $messageText = asset('post_images/' . $images[0]) . '/' . $post->id;
                            } else {
                                // If no image, just use post URL with ID
                                $messageText = url('/post/' . $post->id);
                            }
                            
                            // If user added custom message, prepend it
                            if ($request->filled('initial_message')) {
                                $messageText = $request->initial_message . "\n\n" . $messageText;
                            }
                            
                            // Set message type based on whether it has image (OLD STYLE)
                            $messageType = !empty($images) ? 'image' : 'text';
                            
                            // Get user data
                            $receiverData = $this->getEntityData($request->shared_to_user_id);
                            $currentUserData = $this->getEntityData($user->id);
                            
                            if ($receiverData && $currentUserData) {
                                
                                // ==============================================
                                // 7. CREATE MESSAGE
                                // ==============================================
                                $message = UserMessage::create([
                                    'listing_id' => $post->id,
                                    'listing_title' => $post->title,
                                    'from_id' => $user->id,
                                    'to_id' => $request->shared_to_user_id,
                                    'to_email' => $receiverData['email'],
                                    'to_name' => $receiverData['name'],
                                    'from_name' => $currentUserData['name'],
                                    'from_email' => $currentUserData['email'],
                                    'from_phone' => $currentUserData['phone'],
                                    'message_txt' => $messageText,
                                    'subject' => 'Shared a post: ' . $post->title,
                                    'chat_type_id' => $chatType->id,
                                    'chat_session_id' => $sessionId,
                                    'worth_discussing_point_id' => null,
                                    'status' => 'active',
                                    'is_read' => false,
                                    'message_type' => $messageType,
                                ]);
                                
                                // ==============================================
                                // 8. UPDATE SESSION STATS
                                // ==============================================
                                $chatSession->update([
                                    'last_message_at' => now(),
                                    'last_message' => Str::limit("Shared a post", 100)
                                ]);
                                
                                // Handle unread count properly
                                if (!$existingSession) {
                                    // New session - unread count already 1
                                    // No action needed
                                } elseif ($isRestored) {
                                    // Restored session - set unread count to 1
                                    $chatSession->unread_count = 1;
                                    $chatSession->save();
                                } else {
                                    // Existing active session - increment
                                    $chatSession->increment('unread_count');
                                }
                                
                                $chatInitiated = true;
                                
                                // ==============================================
                                // 9. SEND PUSH NOTIFICATION
                                // ==============================================
                                $this->sendChatPushNotification($user, $request->shared_to_user_id, $post, $chatSession);
                            }
                        }
                    }
                }
    
                DB::commit();
    
                // ==============================================
                // 10. SEND POST SHARE NOTIFICATION
                // ==============================================
                if ($request->shared_to_user_id && $request->shared_to_user_id != $user->id) {
                    $title = $user->first_name . " shared your post";
                    $body = \Illuminate\Support\Str::limit(strip_tags($post->content), 100);
                    
                    $actionPayload = [
                        'post_id' => $post->id,
                        'action' => 'view_post'
                    ];
    
                    $this->notificationService->send(
                        $request->shared_to_user_id,
                        'post_share',
                        $title,
                        $body,
                        'post_detail',
                        $actionPayload,
                        $user->id
                    );
                }
    
                return response()->json([
                    'success' => true,
                    'message' => 'Post shared successfully',
                    'data' => [
                        'share_id' => $share->id,
                        'post_id' => $post->id,
                        'shares_count' => $post->shares_count,
                        'chat_initiated' => $chatInitiated,
                        'chat_session_id' => $chatSession ? $chatSession->id : null,
                        'chat_session_restored' => $isRestored,
                        'message_id' => $message ? $message->id : null,
                        'message_txt' => isset($messageText) ? $messageText : null,
                    ]
                ]);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Share post failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to share post',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Send push notification for shared post in chat
     */
    private function sendChatPushNotification($fromUser, $toUserId, $post, $chatSession)
    {
        try {
            $receiver = User::find($toUserId);
            
            if (!$receiver) {
                Log::warning('Push notification failed: Receiver not found', ['to_user_id' => $toUserId]);
                return false;
            }
            
            if ($receiver->push_notification != 1 || empty($receiver->firebase_token)) {
                return false;
            }
            
            $senderName = $fromUser->first_name 
                ? $fromUser->first_name . ' ' . $fromUser->last_name 
                : ($fromUser->name ?? 'Someone');
            
            $title = $senderName . " shared a post with you";
            $body = "Shared a post: " . ($post ? $post->title : 'a post');
            
            $actionPayload = [
                'chat_session_id' => (string) $chatSession->id,
                'from_user_id' => (string) $fromUser->id,
                'action' => 'open_chat'
            ];
            
            return $this->notificationService->send(
                $toUserId,
                'chat_message',
                $title,
                $body,
                'chat',
                $actionPayload,
                $fromUser->id
            );
            
        } catch (Exception $e) {
            Log::error('Failed to send chat push notification', [
                'error' => $e->getMessage(),
                'to_user_id' => $toUserId
            ]);
            return false;
        }
    }
    
    // ==============================================
    // CHAT HELPER METHODS
    // ==============================================
    
    private function updateLastActivity($user)
    {
        if (!$user) return;
        try {
            $user->last_activity = now();
            $user->save();
        } catch (Exception $e) {
            Log::error('Failed to update last activity', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);
        }
    }
    
    private function canChat($user1Id, $user2Id)
    {
        if (!$user1Id || !$user2Id) return false;
        
        if (BlockedUser::isBlocked($user1Id, $user2Id)) return false;
        if (BlockedUser::isBlocked($user2Id, $user1Id)) return false;
        
        $blockedInConnection = UserConnection::where(function($query) use ($user1Id, $user2Id) {
            $query->where('follower_id', $user1Id)
                  ->where('following_id', $user2Id)
                  ->where('status', self::STATUS_BLOCKED);
        })->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('follower_id', $user2Id)
                  ->where('following_id', $user1Id)
                  ->where('status', self::STATUS_BLOCKED);
        })->exists();
    
        return !$blockedInConnection;
    }
    
    private function getMessageVisibility($targetId, $visibilityMap = [])
    {
        if (isset($visibilityMap[$targetId])) {
            return $visibilityMap[$targetId]['message_visibility_control'] ?? 'public';
        }
        $user = User::find($targetId);
        return $user ? ($user->message_visibility_control ?? 'public') : 'public';
    }
    
    private function canMessage($currentUserId, $targetId, $connectionIds = [], $visibilityMap = [])
    {
        if ($currentUserId == $targetId) return true;
        
        $messageVisibility = $this->getMessageVisibility($targetId, $visibilityMap);
        
        if ($messageVisibility == 'public') return true;
        if ($messageVisibility == 'private') return in_array($targetId, $connectionIds);
        
        return true;
    }
    
    private function getUserConnections($userId)
    {
        $following = UserConnection::where('follower_id', $userId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $followers = UserConnection::where('following_id', $userId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        return array_unique(array_merge($following, $followers));
    }
    
    private function getVisibilityMap()
    {
        $users = User::select('id', 'usertype', 'message_visibility_control', 'last_activity')->get();
        $visibilityMap = [];
        
        foreach ($users as $user) {
            $visibilityMap[$user->id] = [
                'type' => $user->usertype === 'company' ? 'company' : 'user',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
                'last_activity' => $user->last_activity ?? null,
                'is_online' => $this->isUserOnline($user->last_activity),
            ];
        }
        return $visibilityMap;
    }
    
    private function isUserOnline($lastActivity)
    {
        if (!$lastActivity) return false;
        $lastActivityTime = $lastActivity instanceof Carbon ? $lastActivity : Carbon::parse($lastActivity);
        return $lastActivityTime->diffInSeconds(now()) < 30;
    }
    
    // private function getEntityData($id)
    // {
    //     if (!$id) {
    //         return null;
    //     }
    
    //     $user = User::find($id);
        
    //     if ($user) {
    //         if ($user->usertype === 'company') {
    //             return [
    //                 'id' => $user->id,
    //                 'name' => $user->company_name ?? $user->name ?? 'Unknown Company',
    //                 'usertype' => 'company',
    //                 'email' => $user->email ?? null,
    //                 'phone' => $user->phone ?? null,
    //                 'image' => $user->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $user->company_logo) : asset('company_logos/' . $user->company_logo)) : 
    //                           ($user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null),
    //             ];
    //         }
            
    //         $firstName = $user->first_name ?? '';
    //         $lastName = $user->last_name ?? '';
    //         $name = trim($firstName . ' ' . $lastName);
    //         $name = $name ?: ($user->name ?? 'Unknown User');
            
    //         return [
    //             'id' => $user->id,
    //             'name' => $name,
    //             'usertype' => $user->usertype ?? 'user',
    //             'email' => $user->email,
    //             'phone' => $user->phone,
    //             'image' => $user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null,
    //         ];
    //     }
    
    //     return [
    //         'id' => $id,
    //         'name' => 'Unknown User',
    //         'usertype' => 'unknown',
    //         'email' => null,
    //         'phone' => null,
    //         'image' => null,
    //     ];
    // }
        
  
   

    // ============================================
    // GET POSTS METHODS
    // ============================================

    /**
     * Get all posts with filters
     */
    public function getPosts(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $query = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
            ])
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('post_type_id')) {
                $query->where('post_type_id', $request->post_type_id);
            }
            
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('subcategory_id')) {
                $query->where('subcategory_id', $request->subcategory_id);
            }
            
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            $posts = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedPosts = $posts->map(function ($post) use ($user) {
                return $this->formatPostResponse($post, false);
            });

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => [
                    'posts' => $formattedPosts,
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get posts failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get post by ID - UPDATED with worth discussing count
     */
    public function getPost($id)
    {
        
       
        try {
            $user = Auth::user();
            
            $post = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
                'likes' => function($query) {
                    $query->with('user')->limit(10);
                },
                'comments' => function($query) {
                    $query->with(['user', 'replies.user'])->limit(20);
                },
                'shares' => function($query) {
                    $query->with('user')->limit(10);
                }
            ])
            ->where('id', $id)
           
            ->first();
            
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }
            
            
            $isAuthor = $user && $post->user_id == $user->id;
            
            if (!$isAuthor) {
                 if ($post->is_active == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The post was not active from user',
                        'errors' => (object)['post' => 'Post not found or deleted']
                    ], 404);
                }
                
            }
            
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }
            
            

            // Record view
            if ($user) {
                PostView::firstOrCreate(
                    [
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'ip_address' => request()->ip()
                    ]
                );
                
                $post->views_count = PostView::where('post_id', $post->id)->count();
                $post->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $this->formatPostResponse($post, true)
            ]);

        } catch (Exception $e) {
            Log::error('Get post failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get user's posts
     */
    public function getUserPosts(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $query = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
            ])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

            if ($request->has('post_type_id')) {
                $query->where('post_type_id', $request->post_type_id);
            }
            
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('is_published')) {
                $query->where('is_published', $request->is_published);
            }

            $posts = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedPosts = $posts->map(function ($post) use ($user) {
                return $this->formatPostResponse($post, false);
            });

            return response()->json([
                'success' => true,
                'message' => 'Your posts retrieved successfully',
                'data' => [
                    'posts' => $formattedPosts,
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get user posts failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your posts',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Delete post
     */
    // public function deletePost($id)
    // {
    //     try {
    //         $user = Auth::user();
            
    //         $post = Post::where('id', $id)
    //             ->where('user_id', $user->id)
    //             ->first();

    //         if (!$post) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Post not found or you are not authorized',
    //                 'errors' => (object)['post' => 'Post not found']
    //             ], 404);
    //         }

    //         DB::beginTransaction();

    //         try {
    //             // Delete associated files
    //             if ($post->images) {
    //                 $images = json_decode($post->images, true);
    //                 if (is_array($images)) {
    //                     foreach ($images as $image) {
    //                         @unlink(public_path('post_images/' . $image));
    //                     }
    //                 }
    //             }
                
    //             if ($post->files) {
    //                 $files = json_decode($post->files, true);
    //                 if (is_array($files)) {
    //                     foreach ($files as $file) {
    //                         @unlink(public_path('post_files/' . $file));
    //                     }
    //                 }
    //             }

    //             // Soft delete the post
    //             $post->delete();
    //             DB::commit();

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Post deleted successfully',
    //                 'data' => ['deleted_post_id' => $id]
    //             ]);

    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }

    //     } catch (Exception $e) {
    //         Log::error('Delete post failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete post',
    //             'errors' => (object)['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }
    
    /**
     * Delete post
     */
    public function deletePost($id)
    {
        try {
            $user = Auth::user();
            
            $post = Post::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found or you are not authorized',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Check if this post is a repost
                if ($post->is_repost) {
                    // Find the repost record
                    $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
                    
                    if ($repostRecord) {
                        // Get the original post to decrement its repost count
                        $originalPost = Post::find($repostRecord->original_post_id);
                        
                        if ($originalPost) {
                            // Decrement repost count on original post
                            $originalPost->decrement('repost_count');
                        }
                        
                        // Delete the repost record
                        $repostRecord->delete();
                    }
                }
                
                // Delete associated files
                if ($post->images) {
                    $images = json_decode($post->images, true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            @unlink(public_path('post_images/' . $image));
                        }
                    }
                }
                
                if ($post->files) {
                    $files = json_decode($post->files, true);
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            @unlink(public_path('post_files/' . $file));
                        }
                    }
                }
                
                // Delete associated tags
                PostTag::where('post_id', $post->id)->delete();
                
                // Delete associated likes
                PostLike::where('post_id', $post->id)->delete();
                
                // Delete associated comments
                PostComment::where('post_id', $post->id)->delete();
                
                // Delete associated shares
                PostShare::where('post_id', $post->id)->delete();
                
                // Delete associated views
                PostView::where('post_id', $post->id)->delete();
                
                // Soft delete the post
                $post->delete();
                
                DB::commit();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Post deleted successfully',
                    'data' => [
                        'deleted_post_id' => $id,
                        'is_repost' => $post->is_repost,
                        'original_post_id' => $post->original_post_id
                    ]
                ]);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Delete post failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get post statistics
     */
    public function getPostStats($id)
    {
        try {
            $post = Post::where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }

            // Get top likers
            $topLikers = PostLike::where('post_id', $post->id)
                ->with('user')
                ->limit(10)
                ->get()
                ->map(function ($like) {
                    $userData = $this->getEntityData($like->user_id);
                    return [
                        'user_id' => $userData['id'],
                        'name' => $userData['name'],
                        'usertype' => $userData['usertype'],
                        'image' => $userData['image'],
                        'liked_at' => $like->created_at,
                    ];
                });

            // Get comment statistics
            $commentStats = PostComment::where('post_id', $post->id)
                ->where('is_active', true)
                ->selectRaw('COUNT(*) as total_comments, COUNT(DISTINCT user_id) as unique_commenters')
                ->first();

            // Get share statistics
            $shareStats = PostShare::where('post_id', $post->id)
                ->selectRaw('COUNT(*) as total_shares, COUNT(DISTINCT user_id) as unique_sharers')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Post statistics retrieved',
                'data' => [
                    'post_id' => $post->id,
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'shares_count' => $post->shares_count,
                    'stats' => [
                        'comments' => $commentStats ? [
                            'total' => $commentStats->total_comments,
                            'unique_commenters' => $commentStats->unique_commenters,
                        ] : ['total' => 0, 'unique_commenters' => 0],
                        'shares' => $shareStats ? [
                            'total' => $shareStats->total_shares,
                            'unique_sharers' => $shareStats->unique_sharers,
                        ] : ['total' => 0, 'unique_sharers' => 0],
                    ],
                    'top_likers' => $topLikers,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get post stats failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get post statistics',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    // ============================================
    // REPOST METHODS
    // ============================================

    /**
     * Repost a post
     */
    public function repostPost(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'repost_comment' => 'nullable|string|max:1000',
                'is_published' => 'boolean',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            $post = Post::where('id', $id)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            $originalPost = $this->getOriginalPost($post);
    
            $alreadyReposted = PostRepost::where('original_post_id', $originalPost->id)
                ->where('user_id', $user->id)
                ->exists();
    
            if ($alreadyReposted) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reposted this post',
                    'errors' => (object)['repost' => 'Already reposted']
                ], 400);
            }
    
            DB::beginTransaction();
    
            try {
                $repostContent = $this->prepareRepostContent($originalPost, $request->repost_comment);
                
                $repostData = [
                    'user_id' => $user->id,
                    'post_user_type' => $user->usertype,
                    'post_type_id' => $originalPost->post_type_id,
                    'category_id' => $originalPost->category_id,
                    'subcategory_id' => $originalPost->subcategory_id,
                    'title' => $originalPost->title,
                    'content' => $repostContent,
                    'short_description' => $originalPost->short_description,
                    'images' => $originalPost->images,
                    'files' => $originalPost->files,
                    'is_published' => $request->is_published ?? true,
                    'is_active' => true,
                    'original_post_id' => $originalPost->id,
                    'is_repost' => true,
                ];
    
                // Copy all category-specific fields
                $fieldsToCopy = [
                    'tech_stack', 'idea_or_goal', 'outcome_or_fun_element', 'project_domain',
                    'role_in_project', 'duration_start', 'duration_end', 'certification_title',
                    'award_name', 'technology_topic', 'occasion_title', 'message', 'event_date',
                    'event_end_date', 'result_rank', 'organizer_id', 'host_id', 'idea_title',
                    'guide_title', 'deliverables', 'timeline_start', 'timeline_end', 'role_type',
                    'work_mode', 'key_deliverables', 'internship_duration', 'stipend_amount',
                    'stipend_currency', 'convertible_to_full_time', 'ctc_amount', 'ctc_currency',
                    'application_deadline', 'company_name', 'job_location', 'salary_range',
                    'experience_required', 'skills_required', 'benefits', 'application_url',
                    'is_job_post'
                ];
    
                foreach ($fieldsToCopy as $field) {
                    if ($originalPost->$field !== null) {
                        $repostData[$field] = $originalPost->$field;
                    }
                }
    
                $repost = Post::create($repostData);
    
                PostRepost::create([
                    'original_post_id' => $originalPost->id,
                    'reposted_post_id' => $repost->id,
                    'user_id' => $user->id,
                    'repost_comment' => $request->repost_comment,
                ]);
    
                if ($originalPost->taggedUsers) {
                    foreach ($originalPost->taggedUsers as $taggedUser) {
                        PostTag::create([
                            'post_id' => $repost->id,
                            'tagged_user_id' => $taggedUser->id,
                        ]);
                    }
                }
    
                $originalPost->increment('repost_count');
    
                DB::commit();
    
                $repost->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers']);
    
                return response()->json([
                    'success' => true,
                    'message' => 'Post reposted successfully',
                    'data' => [
                        'repost' => $this->formatPostResponse($repost),
                        'original_post_id' => $originalPost->id,
                        'repost_count' => $originalPost->repost_count,
                        'type' => 'repost'
                    ]
                ], 201);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Repost failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to repost',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get post likers
     */
    public function getPostLikers(Request $request, $postId)
    {
        try {
            $currentUser = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            $post = Post::where('id', $postId)
                ->where('is_active', true)
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found']
                ], 404);
            }
    
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
    
            $likesQuery = PostLike::where('post_id', $postId)
                ->orderBy('created_at', 'desc');
    
            if ($search) {
                $likesQuery->whereHas('user', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }
    
            $likes = $likesQuery->paginate($perPage);
    
            $formattedLikers = $likes->map(function ($like) use ($currentUser) {
                $likerData = $this->getEntityData($like->user_id);
                $connectionStatus = $this->getConnectionStatus($currentUser, $like->user_id);
                
                return [
                    'id' => $likerData['id'],
                    'name' => $likerData['name'],
                    'usertype' => $likerData['usertype'],
                    'image' => $likerData['image'],
                    'headline' => $likerData['headline'] ?? null,
                    'liked_at' => $like->created_at,
                    'liked_at_formatted' => $like->created_at->diffForHumans(),
                    'connection_status' => $connectionStatus,
                    'is_connected' => $this->isConnected($currentUser->id, $like->user_id),
                ];
            });
    
            $allLikerIds = PostLike::where('post_id', $postId)->pluck('user_id');
            
            $connectedCount = 0;
            $notConnectedCount = 0;
            
            foreach ($allLikerIds as $likerId) {
                if ($this->isConnected($currentUser->id, $likerId)) {
                    $connectedCount++;
                } else {
                    $notConnectedCount++;
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Post likers retrieved successfully',
                'data' => [
                    'post_id' => (int)$postId,
                    'total_likes' => $post->likes_count,
                    'likers' => $formattedLikers,
                    'stats' => [
                        'total' => $likes->total(),
                        'connected' => $connectedCount,
                        'not_connected' => $notConnectedCount,
                        'current_page' => $likes->currentPage(),
                        'per_page' => $likes->perPage(),
                        'last_page' => $likes->lastPage(),
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Get post likers failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get post likers',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function handlePostTags($postId, $taggedUsers)
    {
        if (is_array($taggedUsers)) {
            foreach ($taggedUsers as $userId) {
                PostTag::create([
                    'post_id' => $postId,
                    'tagged_user_id' => $userId,
                ]);
            }
        }
    }

    private function getExistingImages($post)
    {
        if (!$post->images) {
            return [];
        }
        $images = json_decode($post->images, true);
        return is_array($images) ? $images : [];
    }

    private function getExistingFiles($post)
    {
        if (!$post->files) {
            return [];
        }
        $files = json_decode($post->files, true);
        return is_array($files) ? $files : [];
    }

    private function getOriginalPost($post)
    {
        if ($post->original_post_id) {
            $original = Post::find($post->original_post_id);
            if ($original && $original->original_post_id) {
                return $this->getOriginalPost($original);
            }
            return $original ?: $post;
        }
        return $post;
    }

    private function prepareRepostContent($originalPost, $userComment = null)
    {
        $originalAuthor = $this->getAuthorData($originalPost);
        $originalAuthorName = $originalAuthor['name'] ?? 'Unknown User';
        
        $content = '';
        
        if ($userComment) {
            $content .= '<div class="repost-comment" style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff;">';
            $content .= '<p style="margin: 0; font-style: italic;">"' . e($userComment) . '"</p>';
            $content .= '</div>';
        }
        
        $content .= '<div class="original-post-attribution" style="margin-top: 10px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa;">';
        $content .= '<div style="margin-bottom: 10px; font-size: 0.9rem; color: #666;">';
        $content .= '🔁 <strong>Reposted from ' . e($originalAuthorName) . '</strong>';
        $content .= '</div>';
        $content .= '<hr style="margin: 10px 0;">';
        $content .= '<div class="original-content">';
        $content .= $originalPost->content;
        $content .= '</div>';
        $content .= '</div>';
        
        return $content;
    }

    /**
     * Check if users are connected (accepted status)
     */
    private function isConnected($currentUserId, $targetId)
    {
        if ($currentUserId == $targetId) {
            return true;
        }

        // Check if there's an accepted connection in either direction
        $connection = UserConnection::where(function($query) use ($currentUserId, $targetId) {
                $query->where('follower_id', $currentUserId)
                      ->where('following_id', $targetId)
                      ->where('status', self::STATUS_ACCEPTED);
            })
            ->orWhere(function($query) use ($currentUserId, $targetId) {
                $query->where('follower_id', $targetId)
                      ->where('following_id', $currentUserId)
                      ->where('status', self::STATUS_ACCEPTED);
            })
            ->exists();

        return $connection;
    }

    /**
     * Get connection status - UPDATED to match UniversalConnectionController
     */
    private function getConnectionStatus($currentUser, $targetId)
    {
        if (!$targetId) {
            return 'none';
        }
        
        if ($currentUser->id == $targetId) {
            return 'self';
        }
        
        // Check if blocked in BlockedUser table
        if (BlockedUser::isBlocked($currentUser->id, $targetId)) {
            return 'blocked';
        }
        
        if (BlockedUser::isBlocked($targetId, $currentUser->id)) {
            return 'blocked_by_them';
        }
        
        $connection = UserConnection::where(function($query) use ($currentUser, $targetId) {
            $query->where('follower_id', $currentUser->id)
                  ->where('following_id', $targetId);
        })->orWhere(function($query) use ($currentUser, $targetId) {
            $query->where('follower_id', $targetId)
                  ->where('following_id', $currentUser->id);
        })->first();
    
        if (!$connection) {
            return 'none';
        }
    
        if ($connection->follower_id == $currentUser->id) {
            return $connection->status;
        } else {
            if ($connection->status == self::STATUS_ACCEPTED) {
                return self::STATUS_ACCEPTED;
            } elseif ($connection->status == self::STATUS_PENDING) {
                return 'pending_from_them';
            } elseif ($connection->status == self::STATUS_BLOCKED) {
                return 'blocked_by_them';
            }
        }
    
        return 'none';
    }

    /**
     * Get worth discussing count for a post
     */
    // private function getPostWorthDiscussingCount($postId, $userId)
    // {
    //     return ChatSession::where('post_id', $postId)
    //         ->whereNotNull('worth_discussing_point_id')
    //         ->where('is_active', true)
    //         ->where(function ($q) use ($userId) {
    //             $q->where('user1_id', $userId)
    //               ->orWhere('user2_id', $userId);
    //         })
    //         ->count();
    // }
    
        /**
     * Get worth discussing count for a specific post
     * Counts unique users who have discussed this post with the post owner
     * Based on messages, not sessions
     */
    private function getPostWorthDiscussingCount($postId, $userId)
    {
        // Get unique users who have discussed this specific post with the post owner
        $uniqueUsersForPost = DB::table('user_messages')
            ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
            ->where('chat_types.slug', 'worth_discussing')
            ->where('user_messages.listing_id', $postId)  // Based on POST ID
            ->where(function($q) use ($userId) {
                // The post owner is either sender or receiver
                $q->where('user_messages.from_id', $userId)
                  ->orWhere('user_messages.to_id', $userId);
            })
            ->select(DB::raw('CASE 
                WHEN user_messages.from_id = ' . $userId . ' THEN user_messages.to_id 
                ELSE user_messages.from_id 
            END as other_user_id'))
            ->distinct()
            ->get()
            ->pluck('other_user_id')
            ->unique()
            ->count();
        
        return $uniqueUsersForPost;
    }

    /**
     * Get blocked user IDs for filtering
     */
    private function getBlockedUserIds($userId)
    {
        $blockedIds = [];
        
        // From BlockedUser table (users I blocked)
        if (class_exists('\App\BlockedUser')) {
            $blockedFromBlockedTable = \App\BlockedUser::where('blocker_id', $userId)
                ->pluck('blocked_id')
                ->toArray();
            $blockedIds = array_merge($blockedIds, $blockedFromBlockedTable);
        }
        
        // From UserConnection table (users I blocked)
        $blockedFromConnection = UserConnection::where('follower_id', $userId)
            ->where('status', self::STATUS_BLOCKED)
            ->pluck('following_id')
            ->toArray();
        
        $blockedIds = array_merge($blockedIds, $blockedFromConnection);
        
        return array_unique($blockedIds);
    }

    /**
     * Format post response - COMPLETELY REWRITTEN with proper repost and company handling
     */
    private function formatPostResponse($post, $detailed = false)
    {
        $user = Auth::user();
    
        $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
        $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
        $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
        
        // Process skills_required to include both IDs and names
        $skillsRequired = [];
        if ($post->skills_required) {
            $skillIds = is_array($post->skills_required) 
                ? $post->skills_required 
                : (json_decode($post->skills_required ?? '', true) ?: []);
            
            if (!empty($skillIds)) {
                $skills = JobSkill::whereIn('id', $skillIds)
                    ->where('is_active', 1)
                    ->get(['id', 'job_skill']);
                
                $skillsRequired = $skills->map(function($skill) {
                    return [
                        'id' => $skill->id,
                        'name' => $skill->job_skill
                    ];
                })->toArray();
            }
        }
        
        $authorData = $this->getAuthorData($post);
        
        // Get connection status for author
        $authorConnectionStatus = $user ? $this->getConnectionStatus($user, $authorData['id']) : 'none';
        
        // Get worth discussing count
        $worthDiscussingCount = $user ? $this->getPostWorthDiscussingCount($post->id, $user->id) : 0;
        
        // Get blocked user IDs
        $excludedUserIds = $user ? $this->getBlockedUserIds($user->id) : [];
        
        // Check if this post is a repost
        $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
        $isRepost = !is_null($repostRecord);
        $repostInfo = null;
        $repostComment = null;
        
        if ($isRepost && $repostRecord) {
            $repostComment = $repostRecord->repost_comment;
            
            // Get original post
            $originalPost = Post::find($repostRecord->original_post_id);
            
            if ($originalPost) {
                // Get original author data (could be user or company)
                $originalAuthor = User::find($originalPost->user_id);
                $originalAuthorData = null;
                
                if ($originalAuthor) {
                    if ($originalAuthor->usertype === 'company') {
                        $originalAuthorData = [
                            'id' => $originalAuthor->id,
                            'name' => $originalAuthor->company_name ?? $originalAuthor->name ?? 'Unknown Company',
                            'usertype' => 'company',
                            'image' => $originalAuthor->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $originalAuthor->company_logo) : asset('company_logos/' . $originalAuthor->company_logo)) : 
                                       ($originalAuthor->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $originalAuthor->image) : asset('user_images/' . $originalAuthor->image)) : null),
                            'headline' => $originalAuthor->company_description ?? $originalAuthor->headline,
                        ];
                    } else {
                        $originalAuthorData = [
                            'id' => $originalAuthor->id,
                            'name' => $originalAuthor->getName(),
                            'usertype' => $originalAuthor->usertype ?? 'user',
                            'image' => $originalAuthor->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $originalAuthor->image) : asset('user_images/' . $originalAuthor->image)) : null,
                            'headline' => $originalAuthor->headline,
                        ];
                    }
                }
                
                // Parse original post images
                $originalImages = $originalPost->images ? json_decode($originalPost->images, true) : [];
                $formattedOriginalImages = array_map(function($img) {
                    return env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $img) : asset('post_images/' . $img);
                }, $originalImages);
                
                $repostInfo = [
                    'repost_record_id' => $repostRecord->id,
                    'repost_comment' => $repostComment,
                    'reposted_at' => $repostRecord->created_at,
                    'reposted_at_formatted' => $repostRecord->created_at->diffForHumans(),
                    'original_post' => [
                        'id' => $originalPost->id,
                        'title' => $originalPost->title,
                        'content' => html_entity_decode(strip_tags($originalPost->content)),
                        'short_description' => $originalPost->short_description,
                        'category_id' => $originalPost->category_id,
                        'subcategory_id' => $originalPost->subcategory_id,
                        'images' => $formattedOriginalImages,
                        'thumbnail' => !empty($formattedOriginalImages) ? $formattedOriginalImages[0] : null,
                        'created_at' => $originalPost->created_at,
                        'created_at_formatted' => $originalPost->created_at ? $originalPost->created_at->diffForHumans() : null,
                        'author' => $originalAuthorData,
                        'stats' => [
                            'views' => $originalPost->views_count ?? 0,
                            'likes' => $originalPost->likes_count ?? 0,
                            'comments' => $originalPost->comments_count ?? 0,
                            'shares' => $originalPost->shares_count ?? 0,
                            'reposts' => $originalPost->repost_count ?? 0,
                        ],
                    ],
                ];
            }
        }
        
        // Format tagged users with proper company handling
        $taggedUsers = $post->taggedUsers
            ->filter(function ($taggedUser) use ($excludedUserIds) {
                // Only filter out BLOCKED users, not unfollowed ones
                return !in_array($taggedUser->id, $excludedUserIds);
            })
            ->map(function ($taggedUser) use ($user) {
                // Get proper name and image based on user type
                $userName = '';
                $userImage = null;
                $userHeadline = null;
                
                if ($taggedUser->usertype === 'company') {
                    $userName = $taggedUser->company_name ?? $taggedUser->name ?? 'Unknown Company';
                    $userImage = $taggedUser->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $taggedUser->company_logo) : asset('company_logos/' . $taggedUser->company_logo)) : 
                                ($taggedUser->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $taggedUser->image) : asset('user_images/' . $taggedUser->image)) : null);
                    $userHeadline = $taggedUser->company_description ?? $taggedUser->headline;
                } else {
                    $userName = $taggedUser->getName();
                    $userImage = $taggedUser->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $taggedUser->image) : asset('user_images/' . $taggedUser->image)) : null;
                    $userHeadline = $taggedUser->headline;
                }
                
                $connectionStatus = $user ? $this->getConnectionStatus($user, $taggedUser->id) : 'none';
                $isConnected = $user ? $this->isConnected($user->id, $taggedUser->id) : false;
                
                return [
                    'id' => $taggedUser->id,
                    'name' => $userName,
                    'usertype' => $taggedUser->usertype,
                    'image' => $userImage,
                    'headline' => $userHeadline,
                    'connection_status' => $connectionStatus,
                    'is_connected' => $isConnected,
                ];
            })->values();

        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => html_entity_decode(strip_tags($post->content)),
            'short_description' => $post->short_description,
            'images' => array_map(function($image) {
                return env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image);
            }, $images),
            'files' => array_map(function($file) {
                return env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_files/' . $file) : asset('post_files/' . $file);
            }, $files),
            'is_published' => $post->is_published,
            'is_liked' => $user ? $post->isLikedByUser($user->id) : false,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'created_at_formatted' => $post->created_at ? $post->created_at->diffForHumans() : null,
            'stats' => [
                'views' => $post->views_count ?? 0,
                'likes' => $post->likes_count ?? 0,
                'comments' => $post->comments_count ?? 0,
                'shares' => $post->shares_count ?? 0,
                'reposts' => $post->repost_count ?? 0,
                'worth_discussing' => $worthDiscussingCount,
            ],
            'post_type' => $post->postType ? [
                'id' => $post->postType->id,
                'name' => $post->postType->name,
                'slug' => $post->postType->slug,
            ] : null,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'subcategory' => $post->subcategory ? [
                'id' => $post->subcategory->id,
                'name' => $post->subcategory->name,
                'slug' => $post->subcategory->slug,
            ] : null,
            'author' => array_merge($authorData, [
                'connection_status' => $authorConnectionStatus,
                'is_connected' => $user ? $this->isConnected($user->id, $authorData['id']) : false,
            ]),
            'tagged_users' => $taggedUsers,
            'is_job_post' => $post->is_job_post ?? false,
            'is_repost' => $isRepost,
            'repost_comment' => $repostComment,
            'repost_info' => $repostInfo,
            'original_post_id' => $post->original_post_id,
        ];
        
        // Check if already applied (for job posts)
        if ($user && in_array($post->category_id, self::JOB_CATEGORIES)) {
            $existingApplication = UserMessage::where('listing_id', $post->id)
                ->where('from_id', $user->id)
                ->where('message_type', 'job_application')
                ->orWhere(function($q) use ($post, $user) {
                    $q->where('chat_type_id', function($query) {
                        $query->select('id')->from('chat_types')->where('slug', 'job_application');
                    })
                    ->where('from_id', $user->id)
                    ->where('listing_id', $post->id);
                })
                ->exists();

            $formatted['already_applied'] = $existingApplication;
        } else {
            $formatted['already_applied'] = false;  
        }

        // Prepare all fields with skills_required now containing names
        $allFields = [
            // Project fields
            'tech_stack' => $techStack,
            'idea_or_goal' => $post->idea_or_goal,
            'outcome_or_fun_element' => $post->outcome_or_fun_element,
            'project_domain' => $post->project_domain,
            'role_in_project' => $post->role_in_project,
            'duration_start' => $post->duration_start,
            'duration_end' => $post->duration_end,
            
            // Achievement fields
            'certification_title' => $post->certification_title,
            'award_name' => $post->award_name,
            'technology_topic' => $post->technology_topic,
            'occasion_title' => $post->occasion_title,
            'message' => $post->message,
            
            // Event fields
            'event_date' => $post->event_date,
            'event_end_date' => $post->event_end_date,
            'result_rank' => $post->result_rank,
            'organizer_id' => $post->organizer_id,
            'host_id' => $post->host_id,
            
            // Knowledge sharing fields
            'idea_title' => $post->idea_title,
            'guide_title' => $post->guide_title,
            
            // Job fields
            'deliverables' => $post->deliverables,
            'timeline_start' => $post->timeline_start,
            'timeline_end' => $post->timeline_end,
            'role_type' => $post->role_type,
            'work_mode' => $post->work_mode,
            'key_deliverables' => $post->key_deliverables,
            'internship_duration' => $post->internship_duration,
            'stipend_amount' => $post->stipend_amount,
            'stipend_currency' => $post->stipend_currency,
            'convertible_to_full_time' => $post->convertible_to_full_time,
            'ctc_amount' => $post->ctc_amount,
            'ctc_currency' => $post->ctc_currency,
            'application_deadline' => $post->application_deadline,
            'company_name' => $post->company_name,
            'job_location' => $post->job_location,
            'salary_range' => $post->salary_range,
            'experience_required' => $post->experience_required,
            'skills_required' => $skillsRequired,
            'benefits' => $post->benefits,
            'application_url' => $post->application_url,
        ];
    
        // Merge all fields
        $formatted = array_merge($formatted, $allFields);
    
        // Add detailed data if requested
        if ($detailed && $user) {
            $userIds = $post->likes->pluck('user_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
    
            $formatted['likes'] = $post->likes->take(10)->map(function ($like) use ($users, $user) {
                $likeData = $this->getLikeAuthorData($like, $users);
                if ($likeData) {
                    $likeData['connection_status'] = $this->getConnectionStatus($user, $likeData['user_id']);
                    $likeData['is_connected'] = $this->isConnected($user->id, $likeData['user_id']);
                }
                return $likeData;
            })->filter();
    
            $formatted['comments'] = $this->formatComments($post->comments, $user);
        }
    
        return $formatted;
    }

    /**
     * Get author data - UPDATED to use post_user_type
     */
    private function getAuthorData($post)
    {
        $authorId = null;
        $authorName = null;
        $authorImage = null;
        $authorHeadline = null;
        $authorUserType = $post->post_user_type ?? 'user';

        $userRecord = User::find($post->user_id);
        
        if ($userRecord) {
            $authorId = $userRecord->id;
            $authorUserType = $userRecord->usertype;
            
            if ($userRecord->usertype === 'company') {
                $authorName = $userRecord->company_name ?? $userRecord->name;
                $authorImage = $userRecord->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $userRecord->company_logo) : asset('company_logos/' . $userRecord->company_logo)) : 
                               ($userRecord->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $userRecord->image) : asset('user_images/' . $userRecord->image)) : null);
                $authorHeadline = $userRecord->company_description ?? $userRecord->headline;
            } else {
                $authorName = $userRecord->getName();
                $authorImage = $userRecord->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $userRecord->image) : asset('user_images/' . $userRecord->image)) : null;
                $authorHeadline = $userRecord->headline;
            }
        }

        if (!$authorId) {
            $authorName = 'Unknown User';
            $authorUserType = 'unknown';
            $authorId = $post->user_id;
        }

        return [
            'id' => $authorId,
            'name' => $authorName,
            'usertype' => $authorUserType,
            'image' => $authorImage,
            'headline' => $authorHeadline,
        ];
    }

    /**
     * Get like author data
     */
    private function getLikeAuthorData($like, $users)
    {
        $currentUser = Auth::user();
        
        if (isset($users[$like->user_id])) {
            $user = $users[$like->user_id];
            
            $isConnected = $this->isConnected($currentUser->id, $user->id);
            
            if ($user->usertype === 'company') {
                return [
                    'user_id' => $user->id,
                    'name' => $user->company_name ?? $user->name,
                    'usertype' => 'company',
                    'image' => $user->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $user->company_logo) : asset('company_logos/' . $user->company_logo)) : 
                               ($user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null),
                    'liked_at' => $like->created_at,
                    'is_connected' => $isConnected,
                ];
            }
            
            return [
                'user_id' => $user->id,
                'name' => $user->getName(),
                'usertype' => $user->usertype,
                'image' => $user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null,
                'liked_at' => $like->created_at,
                'is_connected' => $isConnected,
            ];
        }
    
        return null;
    }

    /**
     * Format comments - UPDATED with connection status
     */
    private function formatComments($comments, $currentUser = null)
    {
        if (!$currentUser) {
            $currentUser = Auth::user();
        }
        
        return $comments->map(function ($comment) use ($currentUser) {
            
            $authorData = $this->getEntityData($comment->user_id);
            
            // Add connection status for comment author
            if ($authorData && $currentUser) {
                $authorData['connection_status'] = $this->getConnectionStatus($currentUser, $authorData['id']);
                $authorData['is_connected'] = $this->isConnected($currentUser->id, $authorData['id']);
            }
            
            $replies = collect();
            if (isset($comment->replies) && $comment->replies) {
                $replies = $comment->replies->map(function ($reply) use ($currentUser) {
                    $replyAuthorData = $this->getEntityData($reply->user_id);
                    if ($replyAuthorData && $currentUser) {
                        $replyAuthorData['connection_status'] = $this->getConnectionStatus($currentUser, $replyAuthorData['id']);
                        $replyAuthorData['is_connected'] = $this->isConnected($currentUser->id, $replyAuthorData['id']);
                    }
                    
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'created_at' => $reply->created_at,
                        'author' => $replyAuthorData,
                    ];
                });
            }

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'author' => $authorData,
                'replies' => $replies,
            ];
        });
    }

    /**
     * Format single comment response
     */
    private function formatCommentResponse($comment)
    {
        $currentUser = Auth::user();
        $authorData = $this->getEntityData($comment->user_id);
        
        if ($authorData && $currentUser) {
            $authorData['connection_status'] = $this->getConnectionStatus($currentUser, $authorData['id']);
            $authorData['is_connected'] = $this->isConnected($currentUser->id, $authorData['id']);
        }
        
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at,
            'author' => $authorData,
        ];
    }

    /**
     * Get entity data
     */
    private function getEntityData($id)
    {
        if (!$id) {
            return null;
        }

        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                return [
                    'id' => $user->id,
                    'name' => $user->company_name ?? $user->name,
                    'email' => $user->email ?? $user->email,
                    'phone' => $user->phone,
                    'usertype' => 'company',
                    'image' => $user->company_logo ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $user->company_logo) : asset('company_logos/' . $user->company_logo)) : 
                               ($user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null),
                    'headline' => $user->company_description ?? $user->headline,
                ];
            }
            
            return [
                'id' => $user->id,
                'name' => $user->getName(),
                'email' => $user->email,
                'phone' => $user->phone,
                'usertype' => $user->usertype ?? 'user',
                'image' => $user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null,
                'headline' => $user->headline,
            ];
        }

        return [
            'id' => $id,
            'name' => 'Unknown User',
            'usertype' => 'unknown',
            'image' => null,
            'headline' => null,
        ];
    }
    
    public function updateRepostComment(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'repost_comment' => 'required|string|max:1000',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            // Find the repost post
            $repost = Post::where('id', $id)
                ->where('user_id', $user->id)
                ->where('is_repost', true)
                ->where('is_active', true)
                ->first();
    
            if (!$repost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repost not found or you are not authorized',
                    'errors' => (object)['repost' => 'Repost not found']
                ], 404);
            }
    
            // Get original post
            $originalPost = Post::find($repost->original_post_id);
            
            if (!$originalPost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original post not found',
                    'errors' => (object)['original' => 'Original post not found']
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Get original author data
                $originalAuthor = $this->getAuthorData($originalPost);
                $originalAuthorName = $originalAuthor['name'] ?? 'Unknown User';
                
                // Build new content with updated comment
                $newContent = $this->buildRepostContent(
                    $originalPost,
                    $request->repost_comment,
                    $originalAuthorName
                );
    
                // Update only the content (repost comment part)
                $repost->update([
                    'content' => $newContent,
                ]);
    
                // Update the repost record comment
                $repostRecord = PostRepost::where('reposted_post_id', $repost->id)->first();
                if ($repostRecord) {
                    $repostRecord->update([
                        'repost_comment' => $request->repost_comment
                    ]);
                }
    
                DB::commit();
    
                // Reload relationships
                $repost->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers']);
    
                return response()->json([
                    'success' => true,
                    'message' => 'Repost comment updated successfully',
                    'data' => [
                        'repost' => $this->formatPostResponse($repost),
                        'updated_fields' => [
                            'repost_comment' => $request->repost_comment
                        ]
                    ]
                ]);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            Log::error('Update repost comment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'repost_id' => $id,
                'user_id' => Auth::id() ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update repost comment',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }
    
    private function buildRepostContent($originalPost, $userComment, $originalAuthorName)
    {
        $content = '';
        
        if ($userComment) {
            $content .= '<div class="repost-comment" style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff;">';
            $content .= '<p style="margin: 0; font-style: italic;">"' . e($userComment) . '"</p>';
            $content .= '</div>';
        }
        
        $content .= '<div class="original-post-attribution" style="margin-top: 10px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa;">';
        $content .= '<div style="margin-bottom: 10px; font-size: 0.9rem; color: #666;">';
        $content .= '🔁 <strong>Reposted from ' . e($originalAuthorName) . '</strong>';
        $content .= '</div>';
        $content .= '<hr style="margin: 10px 0;">';
        $content .= '<div class="original-content">';
        $content .= $originalPost->content;
        $content .= '</div>';
        $content .= '</div>';
        
        return $content;
    }
}