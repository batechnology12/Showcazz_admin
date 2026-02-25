<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\Models\PostType;
use App\Models\Category;
use App\Models\Subcategory;
use App\PostTag;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\Models\PostRepost;
use App\User;
use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\UserConnection;
use App\FavouriteCompany;

class PostController extends Controller
{
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
            return $this->createRegularPost($request, $user);
        } catch (Exception $e) {
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
     * Create regular post (Handles all 13 types including jobs)
     */
    private function createRegularPost(Request $request, $user)
    {
        $validator = Validator::make($request->all(), [
            'post_type_id' => 'required|exists:post_types,id',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
                'event_date' => 'required|date',
                'event_end_date' => 'nullable|date|after_or_equal:event_date',
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
        // Category 5: Jobs (3 types)
        elseif ($categoryId == 5) {
            // Mini Mission (subcategory_id = 13)
            if ($subcategoryId == 13) {
                $validator->addRules([
                    'deliverables' => 'required|string',
                    'timeline_start' => 'required|date',
                    'timeline_end' => 'required|date|after_or_equal:timeline_start',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'salary_range' => 'nullable|string|max:100',
                    'experience_required' => 'nullable|string|max:100',
                    'skills_required' => 'nullable|array',
                    'skills_required.*' => 'string',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
            // Internship (subcategory_id = 14)
            elseif ($subcategoryId == 14) {
                $validator->addRules([
                    'role_type' => 'required|in:intern,fresher',
                    'work_mode' => 'required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'required|string',
                    'internship_duration' => 'required|string|max:100',
                    'stipend_amount' => 'nullable|numeric',
                    'stipend_currency' => 'nullable|string|size:3',
                    'convertible_to_full_time' => 'boolean',
                    'application_deadline' => 'required|date|after:today',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'skills_required' => 'nullable|array',
                    'skills_required.*' => 'string',
                    'benefits' => 'nullable|string',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
            // Full-Time (subcategory_id = 15)
            elseif ($subcategoryId == 15) {
                $validator->addRules([
                    'role_type' => 'required|in:full_time',
                    'work_mode' => 'required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'required|string',
                    'ctc_amount' => 'nullable|numeric',
                    'ctc_currency' => 'nullable|string|size:3',
                    'application_deadline' => 'required|date|after:today',
                    'company_name' => 'nullable|string|max:255',
                    'job_location' => 'nullable|string|max:255',
                    'skills_required' => 'nullable|array',
                    'skills_required.*' => 'string',
                    'benefits' => 'nullable|string',
                    'experience_required' => 'nullable|string|max:100',
                    'application_url' => 'nullable|url|max:500',
                ]);
            }
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
                    $filePaths[] = $fileName;
                }
            }

            // Determine if this is a job post (category_id = 5)
            $isJobPost = ($categoryId == 5);

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
                
                // Job fields
                'deliverables', 'timeline_start', 'timeline_end',
                'role_type', 'work_mode', 'key_deliverables', 'internship_duration',
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
            $this->handlePostTags($post->id, $request->tagged_users ?? [], $request->tagged_companies ?? []);

            DB::commit();

            $post->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers', 'taggedCompanies']);

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
     * Add watermark to image
     */
    private function addWatermarkToImage($imagePath)
    {
        try {
            // Watermark configuration
            $watermarkText = "showcazz";
            $watermarkFontSize = 24;
            $watermarkPadding = 10;
            $watermarkOpacity = 0.7;
            $watermarkColor = [255, 255, 255]; // White
            $watermarkShadowColor = [0, 0, 0]; // Black
            
            // Get image info
            list($width, $height, $type) = getimagesize($imagePath);
            
            // Create image from file based on type
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    $image = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }

            // Allocate colors with transparency
            $textColor = imagecolorallocatealpha(
                $image, 
                $watermarkColor[0], 
                $watermarkColor[1], 
                $watermarkColor[2], 
                (1 - $watermarkOpacity) * 127
            );
            
            $shadowColor = imagecolorallocatealpha(
                $image, 
                $watermarkShadowColor[0], 
                $watermarkShadowColor[1], 
                $watermarkShadowColor[2], 
                (1 - $watermarkOpacity) * 127
            );

            // Using built-in GD font
            $fontSize = min(5, max(1, round($width / 100)));
            $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
            $textHeight = imagefontheight($fontSize);
            
            // Calculate position (bottom right with padding)
            $x = $width - $textWidth - $watermarkPadding;
            $y = $height - $textHeight - $watermarkPadding;
            
            // Ensure watermark doesn't go out of bounds
            $x = max($watermarkPadding, $x);
            $y = max($watermarkPadding, $y);

            // Add text shadow
            imagestring($image, $fontSize, $x + 1, $y + 1, $watermarkText, $shadowColor);
            // Add main text
            imagestring($image, $fontSize, $x, $y, $watermarkText, $textColor);

            // Save the image
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

    /**
     * Get post details for editing
     */
    public function editPostDetails($id)
    {
        try {
            $user = Auth::user();
            
            
            
            // Find the post with all relationships
            $post = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
                'taggedCompanies',
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

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post details',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Format post for edit (returns all fields that were used in creation)
     */
    private function formatPostForEdit($post)
    {
        // Decode JSON fields
        $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
        $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
        $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
        $skillsRequired = is_array($post->skills_required) ? $post->skills_required : (json_decode($post->skills_required ?? '', true) ?: []);
        
        // Get tagged users and companies IDs
        $taggedUserIds = $post->taggedUsers->pluck('id')->toArray();
        $taggedCompanyIds = $post->taggedCompanies->pluck('id')->toArray();

        // Base fields
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
            'existing_images' => array_map(function($image) use ($post) {
                return [
                    'name' => $image,
                    'url' => asset('post_images/' . $image),
                    'size' => file_exists(public_path('post_images/' . $image)) ? filesize(public_path('post_images/' . $image)) : 0
                ];
            }, $images),
            'existing_files' => array_map(function($file) use ($post) {
                $filePath = public_path('post_files/' . $file);
                return [
                    'name' => $file,
                    'url' => asset('post_files/' . $file),
                    'size' => file_exists($filePath) ? filesize($filePath) : 0
                ];
            }, $files),
            'tagged_users' => $taggedUserIds,
            'tagged_companies' => $taggedCompanyIds,
        ];

        // Add all possible fields (they will be null if not present)
        $allPossibleFields = [
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

        // Add only fields that have values
        foreach ($allPossibleFields as $field => $value) {
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Update regular post
     */
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
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
            'is_published' => 'boolean',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'remove_files' => 'nullable|array',
            'remove_files.*' => 'string',
        ]);

        // Get category and subcategory (use existing if not provided)
        $categoryId = $request->category_id ?? $post->category_id;
        $subcategoryId = $request->subcategory_id ?? $post->subcategory_id;
        
        // Add validation rules based on category and subcategory
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
            // Handle image uploads (new images)
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
                    
                    $imagePaths[] = $imageName;
                }
            }

            // Handle file uploads (new files)
            $filePaths = $this->getExistingFiles($post);
            
            if ($request->hasFile('files')) {
                $uploadPath = public_path('post_files');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                foreach ($request->file('files') as $file) {
                    $fileName = 'file_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);
                    $filePaths[] = $fileName;
                }
            }

            // Handle removal of existing images
            if ($request->has('remove_images') && is_array($request->remove_images)) {
                foreach ($request->remove_images as $imageToRemove) {
                    $key = array_search($imageToRemove, $imagePaths);
                    if ($key !== false) {
                        @unlink(public_path('post_images/' . $imageToRemove));
                        unset($imagePaths[$key]);
                    }
                }
                $imagePaths = array_values($imagePaths);
            }

            // Handle removal of existing files
            if ($request->has('remove_files') && is_array($request->remove_files)) {
                foreach ($request->remove_files as $fileToRemove) {
                    $key = array_search($fileToRemove, $filePaths);
                    if ($key !== false) {
                        @unlink(public_path('post_files/' . $fileToRemove));
                        unset($filePaths[$key]);
                    }
                }
                $filePaths = array_values($filePaths);
            }

            // Prepare update data
            $updateData = [];
            
            $basicFields = [
                'post_type_id', 'category_id', 'subcategory_id', 'title', 
                'content', 'short_description', 'is_published'
            ];
            
            foreach ($basicFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            // Update is_job_post if category changes
            if ($request->has('category_id')) {
                $updateData['is_job_post'] = ($request->category_id == 5);
            }

            // Update images and files
            $updateData['images'] = !empty($imagePaths) ? json_encode($imagePaths) : null;
            $updateData['files'] = !empty($filePaths) ? json_encode($filePaths) : null;

            // Add all possible fields
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
                
                // Job fields
                'deliverables', 'timeline_start', 'timeline_end',
                'role_type', 'work_mode', 'key_deliverables', 'internship_duration',
                'stipend_amount', 'stipend_currency', 'convertible_to_full_time',
                'ctc_amount', 'ctc_currency', 'application_deadline',
                'company_name', 'job_location', 'salary_range', 'experience_required',
                'skills_required', 'benefits', 'application_url'
            ];

            foreach ($allPossibleFields as $field) {
                if ($request->has($field)) {
                    if (in_array($field, ['tech_stack', 'skills_required'])) {
                        $updateData[$field] = json_encode($request->$field);
                    } else {
                        $updateData[$field] = $request->$field;
                    }
                }
            }

            // Update the post
            $post->update($updateData);

            // Handle tags update
            if ($request->has('tagged_users') || $request->has('tagged_companies')) {
                PostTag::where('post_id', $post->id)->delete();
                
                $this->handlePostTags(
                    $post->id, 
                    $request->tagged_users ?? [], 
                    $request->tagged_companies ?? []
                );
            }

            DB::commit();

            // Load relationships
            $post->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers', 'taggedCompanies']);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $this->formatPostResponse($post)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            // Clean up newly uploaded files if update failed
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
                'event_date' => 'sometimes|required|date',
                'event_end_date' => 'nullable|date|after_or_equal:event_date',
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
        // Category 5: Jobs
        elseif ($categoryId == 5) {
            if ($subcategoryId == 13) { // Mini Mission
                $validator->addRules([
                    'deliverables' => 'sometimes|required|string',
                    'timeline_start' => 'sometimes|required|date',
                    'timeline_end' => 'sometimes|required|date|after_or_equal:timeline_start',
                ]);
            } elseif ($subcategoryId == 14) { // Internship
                $validator->addRules([
                    'role_type' => 'sometimes|required|in:intern,fresher',
                    'work_mode' => 'sometimes|required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'sometimes|required|string',
                    'internship_duration' => 'sometimes|required|string|max:100',
                    'stipend_amount' => 'nullable|numeric',
                    'stipend_currency' => 'nullable|string|size:3',
                    'convertible_to_full_time' => 'boolean',
                    'application_deadline' => 'sometimes|required|date|after:today',
                ]);
            } elseif ($subcategoryId == 15) { // Full-Time
                $validator->addRules([
                    'role_type' => 'sometimes|required|in:full_time',
                    'work_mode' => 'sometimes|required|in:onsite,remote,hybrid',
                    'key_deliverables' => 'sometimes|required|string',
                    'ctc_amount' => 'nullable|numeric',
                    'ctc_currency' => 'nullable|string|size:3',
                    'application_deadline' => 'sometimes|required|date|after:today',
                ]);
            }
        }
    }

    // ============================================
    // POST INTERACTIONS
    // ============================================

    /**
     * Like/Unlike post
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Add comment
     */
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

                return response()->json([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'data' => [
                        'comment' => $comment,
                        'comments_count' => $post->comments_count,
                    ]
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
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
    public function sharePost(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'shared_to_user_id' => 'nullable|exists:users,id',
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
                $share = PostShare::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'shared_to_user_id' => $request->shared_to_user_id,
                ]);

                $post->increment('shares_count');

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Post shared successfully',
                    'data' => [
                        'share_id' => $share->id,
                        'post_id' => $post->id,
                        'shares_count' => $post->shares_count,
                    ]
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share post',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

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
                'taggedCompanies',
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get post by ID
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
                'taggedCompanies',
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
            ->where('is_active', true)
            ->first();

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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
     public function getPost_job($id)
    {
        try {
            $user = Auth::user();
            
            $post = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
                'taggedCompanies',
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
            ->where('is_active', true)
            ->first();

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
                'data' => $this->formatPostResponse1($post, true)
            ]);

        } catch (Exception $e) {
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
                'taggedCompanies',
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
    public function deletePost($id)
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

            DB::beginTransaction();

            try {
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

                // Soft delete the post
                $post->update(['is_active' => false]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Post deleted successfully',
                    'data' => ['deleted_post_id' => $id]
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
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
                ->with('user:id,name,image')
                ->limit(10)
                ->get()
                ->map(function ($like) {
                    return [
                        'user_id' => $like->user_id,
                        'name' => $like->user->name,
                        'image' => $like->user->image ? asset('user_images/' . $like->user->image) : null,
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
                    'title' => 'Repost: ' . $originalPost->title,
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
                
                if ($originalPost->taggedCompanies) {
                    foreach ($originalPost->taggedCompanies as $taggedCompany) {
                        PostTag::create([
                            'post_id' => $repost->id,
                            'tagged_company_id' => $taggedCompany->id,
                        ]);
                    }
                }
    
                $originalPost->increment('repost_count');
    
                DB::commit();
    
                $repost->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers', 'taggedCompanies']);
    
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

    // ============================================
    // HELPER METHODS
    // ============================================

    private function handlePostTags($postId, $taggedUsers, $taggedCompanies)
    {
        if (is_array($taggedUsers)) {
            foreach ($taggedUsers as $userId) {
                PostTag::create([
                    'post_id' => $postId,
                    'tagged_user_id' => $userId,
                ]);
            }
        }
    
        if (is_array($taggedCompanies)) {
            foreach ($taggedCompanies as $companyId) {
                PostTag::create([
                    'post_id' => $postId,
                    'tagged_company_id' => $companyId,
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
     * Format post response
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
                $skills = \App\JobSkill::whereIn('id', $skillIds)
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
        
        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'images' => array_map(function($image) {
                return asset('post_images/' . $image);
            }, $images),
            'files' => array_map(function($file) {
                return asset('post_files/' . $file);
            }, $files),
            'is_published' => $post->is_published,
            'is_liked' => $user ? $post->isLikedByUser($user->id) : false,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'stats' => [
                'views' => $post->views_count,
                'likes' => $post->likes_count,
                'comments' => $post->comments_count,
                'shares' => $post->shares_count,
                'reposts' => $post->repost_count,
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
            'author' => $authorData,
            'tagged_users' => $post->taggedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'usertype' => $user->usertype,
                    'image' => $user->image ? asset('user_images/' . $user->image) : null,
                ];
            }),
            'tagged_companies' => $post->taggedCompanies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                ];
            }),
            'is_job_post' => $post->is_job_post ?? false,
            'is_repost' => $post->is_repost ?? false,
            'original_post_id' => $post->original_post_id,
        ];
    
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
            
            // Job fields - UPDATED to use skillsRequired with names
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
            'skills_required' => $skillsRequired, // Now contains array of {id, name}
            'benefits' => $post->benefits,
            'application_url' => $post->application_url,
        ];
    
        // Merge all fields
        $formatted = array_merge($formatted, $allFields);
    
        // Add detailed data if requested
        if ($detailed && $user) {
            $userIds = $post->likes->pluck('user_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            $companies = Company::whereIn('id', $userIds)->get()->keyBy('id');
    
            $formatted['likes'] = $post->likes->take(10)->map(function ($like) use ($users, $companies) {
                return $this->getLikeAuthorData($like, $users, $companies);
            })->filter();
    
            $formatted['comments'] = $this->formatComments($post->comments);
        }
    
        return $formatted;
    }
    // private function formatPostResponse($post, $detailed = false)
    // {
        
      
    //     $user = Auth::user();

    //     $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
    //     $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
    //     $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
    //     $skillsRequired = is_array($post->skills_required) ? $post->skills_required : (json_decode($post->skills_required ?? '', true) ?: []);
        
    //     $authorData = $this->getAuthorData($post);
        
    //     $formatted = [
    //         'id' => $post->id,
    //         'title' => $post->title,
    //         'content' => $post->content,
    //         'short_description' => $post->short_description,
    //         'images' => array_map(function($image) {
    //             return asset('post_images/' . $image);
    //         }, $images),
    //         'files' => array_map(function($file) {
    //             return asset('post_files/' . $file);
    //         }, $files),
    //         'is_published' => $post->is_published,
    //         'is_liked' => $user ? $post->isLikedByUser($user->id) : false,
    //         'created_at' => $post->created_at,
    //         'updated_at' => $post->updated_at,
    //         'stats' => [
    //             'views' => $post->views_count,
    //             'likes' => $post->likes_count,
    //             'comments' => $post->comments_count,
    //             'shares' => $post->shares_count,
    //             'reposts' => $post->repost_count,
    //         ],
    //         'post_type' => $post->postType ? [
    //             'id' => $post->postType->id,
    //             'name' => $post->postType->name,
    //             'slug' => $post->postType->slug,
    //         ] : null,
    //         'category' => $post->category ? [
    //             'id' => $post->category->id,
    //             'name' => $post->category->name,
    //             'slug' => $post->category->slug,
    //         ] : null,
    //         'subcategory' => $post->subcategory ? [
    //             'id' => $post->subcategory->id,
    //             'name' => $post->subcategory->name,
    //             'slug' => $post->subcategory->slug,
    //         ] : null,
    //         'author' => $authorData,
    //         'tagged_users' => $post->taggedUsers->map(function ($user) {
    //             return [
    //                 'id' => $user->id,
    //                 'name' => $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
    //                 'usertype' => $user->usertype,
    //                 'image' => $user->image ? asset('user_images/' . $user->image) : null,
    //             ];
    //         }),
    //         'tagged_companies' => $post->taggedCompanies->map(function ($company) {
    //             return [
    //                 'id' => $company->id,
    //                 'name' => $company->name,
    //                 'slug' => $company->slug,
    //                 'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
    //             ];
    //         }),
    //         'is_job_post' => $post->is_job_post ?? false,
    //         'is_repost' => $post->is_repost ?? false,
    //         'original_post_id' => $post->original_post_id,
    //     ];

    //     // Add all category-specific fields
    //     $allFields = [
    //         // Project fields
    //         'tech_stack' => $techStack,
    //         'idea_or_goal' => $post->idea_or_goal,
    //         'outcome_or_fun_element' => $post->outcome_or_fun_element,
    //         'project_domain' => $post->project_domain,
    //         'role_in_project' => $post->role_in_project,
    //         'duration_start' => $post->duration_start,
    //         'duration_end' => $post->duration_end,
            
    //         // Achievement fields
    //         'certification_title' => $post->certification_title,
    //         'award_name' => $post->award_name,
    //         'technology_topic' => $post->technology_topic,
    //         'occasion_title' => $post->occasion_title,
    //         'message' => $post->message,
            
    //         // Event fields
    //         'event_date' => $post->event_date,
    //         'event_end_date' => $post->event_end_date,
    //         'result_rank' => $post->result_rank,
    //         'organizer_id' => $post->organizer_id,
    //         'host_id' => $post->host_id,
            
    //         // Knowledge sharing fields
    //         'idea_title' => $post->idea_title,
    //         'guide_title' => $post->guide_title,
            
    //         // Job fields
    //         'deliverables' => $post->deliverables,
    //         'timeline_start' => $post->timeline_start,
    //         'timeline_end' => $post->timeline_end,
    //         'role_type' => $post->role_type,
    //         'work_mode' => $post->work_mode,
    //         'key_deliverables' => $post->key_deliverables,
    //         'internship_duration' => $post->internship_duration,
    //         'stipend_amount' => $post->stipend_amount,
    //         'stipend_currency' => $post->stipend_currency,
    //         'convertible_to_full_time' => $post->convertible_to_full_time,
    //         'ctc_amount' => $post->ctc_amount,
    //         'ctc_currency' => $post->ctc_currency,
    //         'application_deadline' => $post->application_deadline,
    //         'company_name' => $post->company_name,
    //         'job_location' => $post->job_location,
    //         'salary_range' => $post->salary_range,
    //         'experience_required' => $post->experience_required,
    //         'skills_required' => $skillsRequired,
    //         'benefits' => $post->benefits,
    //         'application_url' => $post->application_url,
    //     ];

    //     // Add only fields that have values
    //     // foreach ($allFields as $field => $value) {
    //     //     if ($value !== null && $value !== '') {
    //     //         $formatted[$field] = $value;
    //     //     }
    //     // }
        
    //      $formatted = array_merge($formatted, $allFields);

    //     // Add detailed data if requested
    //     if ($detailed && $user) {
    //         $userIds = $post->likes->pluck('user_id')->unique();
    //         $users = User::whereIn('id', $userIds)->get()->keyBy('id');
    //         $companies = Company::whereIn('id', $userIds)->get()->keyBy('id');

    //         $formatted['likes'] = $post->likes->take(10)->map(function ($like) use ($users, $companies) {
    //             return $this->getLikeAuthorData($like, $users, $companies);
    //         })->filter();

    //         $formatted['comments'] = $this->formatComments($post->comments);
    //     }

    //     return $formatted;
    // }
    
    
    private function formatPostResponse1($post, $detailed = false)
    {
        
      
        $user = Auth::user();

        $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
        $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
        $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
        $skillsRequired = is_array($post->skills_required) ? $post->skills_required : (json_decode($post->skills_required ?? '', true) ?: []);
        
        $authorData = $this->getAuthorData($post);
        
        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'images' => array_map(function($image) {
                return asset('post_images/' . $image);
            }, $images),
            'files' => array_map(function($file) {
                return asset('post_files/' . $file);
            }, $files),
            'is_published' => $post->is_published,
            'is_liked' => $user ? $post->isLikedByUser($user->id) : false,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'stats' => [
                'views' => $post->views_count,
                'likes' => $post->likes_count,
                'comments' => $post->comments_count,
                'shares' => $post->shares_count,
                'reposts' => $post->repost_count,
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
            'author' => $authorData,
            'tagged_users' => $post->taggedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'usertype' => $user->usertype,
                    'image' => $user->image ? asset('user_images/' . $user->image) : null,
                ];
            }),
            'tagged_companies' => $post->taggedCompanies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                ];
            }),
            'is_job_post' => $post->is_job_post ?? false,
            'is_repost' => $post->is_repost ?? false,
            'original_post_id' => $post->original_post_id,
        ];

        // Add all category-specific fields
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
        
      

        // Add only fields that have values
        // foreach ($allFields as $field => $value) {
        //     if ($value !== null && $value !== '') {
        //         $formatted[$field] = $value;
        //     }
        // }
        
        
        $formatted = array_merge($formatted, $allFields);

        // Add detailed data if requested
        if ($detailed && $user) {
            $userIds = $post->likes->pluck('user_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            $companies = Company::whereIn('id', $userIds)->get()->keyBy('id');

            $formatted['likes'] = $post->likes->take(10)->map(function ($like) use ($users, $companies) {
                return $this->getLikeAuthorData($like, $users, $companies);
            })->filter();

            $formatted['comments'] = $this->formatComments($post->comments);
        }

        return $formatted;
    }

    private function getAuthorData($post)
    {
        $authorId = null;
        $authorName = null;
        $authorImage = null;
        $authorHeadline = null;
        $authorUserType = null;

        $userRecord = User::find($post->user_id);
        
        if ($userRecord) {
            $authorId = $userRecord->id;
            $authorUserType = $userRecord->usertype;

            if ($userRecord->usertype == 'company') {
                $company = Company::where('user_id', $userRecord->id)->first();
                if ($company) {
                    $authorName = $company->name;
                    $authorImage = $company->logo ? asset('company_logos/' . $company->logo) : null;
                    $authorHeadline = $company->description;
                    $authorId = $company->id;
                } else {
                    $authorName = trim(($userRecord->first_name ?? '') . ' ' . ($userRecord->last_name ?? ''));
                    $authorImage = $userRecord->image ? asset('user_images/' . $userRecord->image) : null;
                    $authorHeadline = $userRecord->headline;
                }
            } else {
                $authorName = trim(($userRecord->first_name ?? '') . ' ' . ($userRecord->last_name ?? ''));
                $authorImage = $userRecord->image ? asset('user_images/' . $userRecord->image) : null;
                $authorHeadline = $userRecord->headline;
            }
        } else {
            $companyRecord = Company::find($post->user_id);
            if ($companyRecord) {
                $authorId = $companyRecord->id;
                $authorUserType = 'company';
                $authorName = $companyRecord->name;
                $authorImage = $companyRecord->logo ? asset('company_logos/' . $companyRecord->logo) : null;
                $authorHeadline = $companyRecord->description;
            } else {
                $authorName = 'Unknown User';
                $authorUserType = 'unknown';
                $authorId = $post->user_id;
            }
        }

        return [
            'id' => $authorId,
            'name' => $authorName,
            'usertype' => $authorUserType,
            'image' => $authorImage,
            'headline' => $authorHeadline,
        ];
    }

    private function getLikeAuthorData($like, $users, $companies)
    {
        $currentUser = Auth::user();
        
        if (isset($users[$like->user_id])) {
            $user = $users[$like->user_id];
            
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                if ($company) {
                    $isConnected = $this->checkIfUserConnectedToCompany($currentUser->id, $company->id);
                    
                    return [
                        'user_id' => $company->id,
                        'name' => $company->name,
                        'usertype' => 'company',
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'liked_at' => $like->created_at,
                        'is_friend' => $isConnected,
                    ];
                }
            }
            
            $isConnected = $this->checkIfUsersAreConnected($currentUser->id, $user->id);
            
            return [
                'user_id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'usertype' => $user->usertype,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'liked_at' => $like->created_at,
                'is_friend' => $isConnected,
            ];
        }
    
        if (isset($companies[$like->user_id])) {
            $company = $companies[$like->user_id];
            
            $isConnected = $this->checkIfUserConnectedToCompany($currentUser->id, $company->id);
            
            return [
                'user_id' => $company->id,
                'name' => $company->name,
                'usertype' => 'company',
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'liked_at' => $like->created_at,
                'is_friend' => $isConnected,
            ];
        }
    
        return null;
    }

    private function checkIfUsersAreConnected($currentUserId, $otherUserId)
    {
        if ($currentUserId == $otherUserId) {
            return 'none';
        }
    
        $connection = UserConnection::where(function ($query) use ($currentUserId, $otherUserId) {
            $query->where('follower_id', $currentUserId)
                  ->where('following_id', $otherUserId);
        })->orWhere(function ($query) use ($currentUserId, $otherUserId) {
            $query->where('follower_id', $otherUserId)
                  ->where('following_id', $currentUserId);
        })->first();
    
        if ($connection) {
            if ($connection->follower_id == $currentUserId) {
                return $connection->status; 
            } else {
                if ($connection->status == 'accepted') {
                    return 'accepted';
                } elseif ($connection->status == 'pending') {
                    return 'pending_from_them';
                } elseif ($connection->status == 'blocked') {
                    return 'blocked_by_them';
                }
            }
        }
    
        return 'none';
    }

    private function checkIfUserConnectedToCompany($userId, $companyId)
    {
        $isFollowing = FavouriteCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
        
        return $isFollowing;
    }

    private function formatComments($comments)
    {
        return $comments->map(function ($comment) {
            
            $authorData = $this->getEntityData($comment->user_id);
            
            $replies = collect();
            if (isset($comment->replies) && $comment->replies) {
                $replies = $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'created_at' => $reply->created_at,
                        'author' => $this->getEntityData($reply->user_id),
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

    private function getEntityData($id)
    {
        if (!$id) {
            return null;
        }

        $company = Company::find($id);
        if ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'usertype' => 'company',
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
            ];
        }

        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                $companyRecord = Company::where('user_id', $user->id)->first();
                if ($companyRecord) {
                    return [
                        'id' => $companyRecord->id,
                        'name' => $companyRecord->name,
                        'usertype' => 'company',
                        'image' => $companyRecord->logo ? asset('company_logos/' . $companyRecord->logo) : null,
                    ];
                }
            }
            
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $name = $name ?: ($user->name ?? 'Unknown User');
            
            return [
                'id' => $user->id,
                'name' => $name,
                'usertype' => $user->usertype ?? 'user',
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
            ];
        }

        return [
            'id' => $id,
            'name' => 'Unknown User',
            'usertype' => 'unknown',
            'image' => null,
        ];
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
                    'is_friend' => in_array($connectionStatus, ['accepted', 'following']),
                ];
            });
    
            $allLikerIds = PostLike::where('post_id', $postId)->pluck('user_id');
            
            $connectedCount = 0;
            $notConnectedCount = 0;
            
            foreach ($allLikerIds as $likerId) {
                $status = $this->getConnectionStatus($currentUser, $likerId);
                if (in_array($status, ['accepted', 'following'])) {
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to get post likers',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    private function getConnectionStatus($currentUser, $targetId)
    {
        $isCompany = Company::where('id', $targetId)->exists();
        
        if ($isCompany) {
            $isFollowing = FavouriteCompany::where('user_id', $currentUser->id)
                ->where('company_id', $targetId)
                ->exists();
            
            if ($isFollowing) {
                return 'following';
            }
            
            $isBlocked = UserConnection::where('follower_id', $currentUser->id)
                ->where('following_id', $targetId)
                ->where('status', 'blocked')
                ->exists();
            
            if ($isBlocked) {
                return 'blocked';
            }
            
            return 'not_following';
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
            if ($connection->status == 'accepted') {
                return 'accepted';
            } elseif ($connection->status == 'pending') {
                return 'pending_from_them';
            } elseif ($connection->status == 'blocked') {
                return 'blocked_by_them';
            }
        }
    
        return 'none';
    }
}