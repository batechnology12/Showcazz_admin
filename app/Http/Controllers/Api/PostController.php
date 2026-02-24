<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\Job;
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
use App\JobSkillManager;
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
        
        // Check if this is a job post (category_id = 5)
        // if ($request->category_id == 5) {
        //     return $this->createJobPost($request, $user);
        // }
       
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
 * Create regular post (Handles 12 types excluding jobs)
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

    // Additional validation based on category and subcategory
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
        ];

        // Add category-specific fields
        $categorySpecificFields = [
            'tech_stack' => $request->tech_stack ? json_encode($request->tech_stack) : null,
            'idea_or_goal' => $request->idea_or_goal,
            'outcome_or_fun_element' => $request->outcome_or_fun_element,
            'project_domain' => $request->project_domain,
            'role_in_project' => $request->role_in_project,
            'duration_start' => $request->duration_start,
            'duration_end' => $request->duration_end,
            'certification_title' => $request->certification_title,
            'award_name' => $request->award_name,
            'technology_topic' => $request->technology_topic,
            'occasion_title' => $request->occasion_title,
            'message' => $request->message,
            'event_date' => $request->event_date,
            'event_end_date' => $request->event_end_date,
            'result_rank' => $request->result_rank,
            'idea_title' => $request->idea_title,
            'guide_title' => $request->guide_title,
            'organizer_id' => $request->organizer_id,
            'host_id' => $request->host_id,
        ];

        // Filter out null values
        $categorySpecificFields = array_filter($categorySpecificFields);
        $postData = array_merge($postData, $categorySpecificFields);

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
 * Create job post (Handles 3 job types)
 */
private function createJobPost(Request $request, $user)
{
    $validator = Validator::make($request->all(), [
        'post_type_id' => 'required|exists:post_types,id',
        'category_id' => 'required',
        'subcategory_id' => 'nullable', // 13: Mini Mission, 14: Internship, 15: Full-Time
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'short_description' => 'nullable|string|max:500',
        'files' => 'nullable|array',
        'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
        'is_published' => 'boolean',
        // Common job fields
        'company_id' => 'nullable',
        'description' => 'required|string',
        'benefits' => 'nullable|string',
        'country_id' => 'nullable|exists:countries,id',
        'state_id' => 'nullable|exists:states,id',
        'city_id' => 'nullable|exists:cities,id',
        'is_freelance' => 'boolean',
        'career_level_id' => 'nullable|exists:career_levels,career_level_id',
        'salary_from' => 'nullable|numeric',
        'salary_to' => 'nullable|numeric|gte:salary_from',
        'hide_salary' => 'boolean',
        'salary_currency' => 'nullable|string|size:3',
        'salary_period_id' => 'nullable|exists:salary_periods,salary_period_id',
        'functional_area_id' => 'nullable|exists:functional_areas,functional_area_id',
        'job_type_id' => 'nullable|exists:job_types,job_type_id',
        'job_shift_id' => 'nullable|exists:job_shifts,job_shift_id',
        'num_of_positions' => 'nullable|integer|min:1',
        'gender_id' => 'nullable|exists:genders,gender_id',
        'expiry_date' => 'nullable|date|after:today',
        'degree_level_id' => 'nullable|exists:degree_levels,degree_level_id',
        'job_experience_id' => 'nullable|exists:job_experiences,job_experience_id',
        'is_featured' => 'boolean',
        'location' => 'nullable|string|max:255',
        'postal_code' => 'nullable|string|max:20',
        'job_advertiser' => 'nullable|string|max:255',
        'application_url' => 'nullable|url|max:500',
        'job_skills' => 'nullable|array',
        'job_skills.*' => 'exists:job_skills,job_skill_id',
    ]);

    // Mini Mission specific validation
    if ($request->subcategory_id == 13) {
        $validator->addRules([
            'deliverables' => 'required|string',
            'timeline_start' => 'required|date',
            'timeline_end' => 'required|date|after_or_equal:timeline_start',
        ]);
    }
    // Internship specific validation
    elseif ($request->subcategory_id == 14) {
        $validator->addRules([
            'role_type' => 'required|in:intern,fresher',
            'work_mode' => 'required|in:onsite,remote,hybrid',
            'key_deliverables' => 'required|string',
            'internship_duration' => 'required|string|max:100',
            'stipend_amount' => 'nullable|numeric',
            'stipend_currency' => 'nullable|string|size:3',
            'convertible_to_full_time' => 'boolean',
            'application_deadline' => 'required|date|after:today',
        ]);
    }
    // Full-Time specific validation
    elseif ($request->subcategory_id == 15) {
        $validator->addRules([
            'role_type' => 'required|in:full_time',
            'work_mode' => 'required|in:onsite,remote,hybrid',
            'key_deliverables' => 'required|string',
            'ctc_amount' => 'nullable|numeric',
            'ctc_currency' => 'nullable|string|size:3',
            'application_deadline' => 'required|date|after:today',
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
        // Handle image uploads (for job posts, allow images as well)
        $imagePaths = [];
        $logo = null;
        if ($request->hasFile('images')) {
            $uploadPath = public_path('job_images');
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            foreach ($request->file('images') as $index => $image) {
                $imageName = 'job_' . time() . '_' . $index . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);
                
                // Add watermark to job images
                $this->addWatermarkToImage($uploadPath . '/' . $imageName);
                
                if ($index === 0) {
                    $logo = $imageName;
                }
                $imagePaths[] = $imageName;
            }
        }

        // Handle file uploads
        $filePaths = [];
        if ($request->hasFile('files')) {
            $uploadPath = public_path('job_files');
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            foreach ($request->file('files') as $file) {
                $fileName = 'job_file_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);
                $filePaths[] = $fileName;
            }
        }

        // Create job in jobs table
        $jobData = [
            'company_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->content,
            'benefits' => $request->benefits,
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'is_freelance' => $request->is_freelance ?? false,
            'career_level_id' => $request->career_level_id,
            'salary_from' => $request->salary_from,
            'salary_to' => $request->salary_to,
            'hide_salary' => $request->hide_salary ?? false,
            'salary_currency' => $request->salary_currency,
            'salary_period_id' => $request->salary_period_id,
            'functional_area_id' => $request->functional_area_id,
            'job_type_id' => $request->job_type_id,
            'job_shift_id' => $request->job_shift_id,
            'num_of_positions' => $request->num_of_positions ?? 1,
            'gender_id' => $request->gender_id,
            'expiry_date' => $request->expiry_date,
            'degree_level_id' => $request->degree_level_id,
            'job_experience_id' => $request->job_experience_id,
            'is_active' => $request->is_published ?? true,
            'is_featured' => $request->is_featured ?? false,
            'search_index' => $this->generateSearchIndex($request),
            'slug' => Str::slug($request->title . '-' . time()),
            'reference' => 'JOB_' . time() . '_' . Str::random(5),
            'location' => $request->location,
            'logo' => $logo,
            'type' => $this->getJobTypeFromSubcategory($request->subcategory_id),
            'postal_code' => $request->postal_code,
            'job_advertiser' => $request->job_advertiser,
            'application_url' => $request->application_url,
            'json_object' => json_encode([
                'post_type_id' => $request->post_type_id,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'short_description' => $request->short_description,
                'images' => $imagePaths,
                'files' => $filePaths,
                'created_by_user_id' => $user->id,
                'deliverables' => $request->deliverables,
                'timeline_start' => $request->timeline_start,
                'timeline_end' => $request->timeline_end,
                'role_type' => $request->role_type,
                'work_mode' => $request->work_mode,
                'key_deliverables' => $request->key_deliverables,
                'internship_duration' => $request->internship_duration,
                'stipend_amount' => $request->stipend_amount,
                'stipend_currency' => $request->stipend_currency,
                'convertible_to_full_time' => $request->convertible_to_full_time,
                'ctc_amount' => $request->ctc_amount,
                'ctc_currency' => $request->ctc_currency,
                'application_deadline' => $request->application_deadline,
                'tagged_users' => $request->tagged_users,
                'tagged_companies' => $request->tagged_companies,
            ]),
        ];

        $job = Job::create($jobData);

        // Handle job skills
        if ($request->has('job_skills') && is_array($request->job_skills)) {
            foreach ($request->job_skills as $skillId) {
                JobSkillManager::create([
                    'job_id' => $job->id,
                    'job_skill_id' => $skillId,
                ]);
            }
        }

        // Create a post record for feed
        $postData = [
            'user_id' => $user->id,
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
            'is_job_post' => true,
            'job_id' => $job->id,
        ];

        // Add job-specific fields to post
        $jobSpecificFields = [
            'deliverables' => $request->deliverables,
            'timeline_start' => $request->timeline_start,
            'timeline_end' => $request->timeline_end,
            'role_type' => $request->role_type,
            'work_mode' => $request->work_mode,
            'key_deliverables' => $request->key_deliverables,
            'internship_duration' => $request->internship_duration,
            'stipend_amount' => $request->stipend_amount,
            'stipend_currency' => $request->stipend_currency,
            'convertible_to_full_time' => $request->convertible_to_full_time,
            'ctc_amount' => $request->ctc_amount,
            'ctc_currency' => $request->ctc_currency,
            'application_deadline' => $request->application_deadline,
        ];

        $jobSpecificFields = array_filter($jobSpecificFields);
        $postData = array_merge($postData, $jobSpecificFields);

        $post = Post::create($postData);

        // Handle tags
        $this->handlePostTags($post->id, $request->tagged_users ?? [], $request->tagged_companies ?? []);

        DB::commit();

        $post->load(['postType', 'category', 'subcategory', 'user', 'taggedUsers', 'taggedCompanies']);
        $job->load(['company', 'functionalArea', 'jobType', 'jobExperience', 'jobSkills']);

        return response()->json([
            'success' => true,
            'message' => 'Job post created successfully',
            'data' => $this->formatJobPostResponse($post, $job)
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        
        if (!empty($imagePaths)) {
            foreach ($imagePaths as $imagePath) {
                @unlink(public_path('job_images/' . $imagePath));
            }
        }
        if (!empty($filePaths)) {
            foreach ($filePaths as $filePath) {
                @unlink(public_path('job_files/' . $filePath));
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
                return false; // Unsupported image type
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

        // Using built-in GD font (can be replaced with TTF if needed)
        // Calculate font size - use a font size proportional to image size
        $fontSize = min(5, max(1, round($width / 100)));
        $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
        $textHeight = imagefontheight($fontSize);
        
        // Calculate position (bottom right with padding)
        $x = $width - $textWidth - $watermarkPadding;
        $y = $height - $textHeight - $watermarkPadding;
        
        // Ensure watermark doesn't go out of bounds
        $x = max($watermarkPadding, $x);
        $y = max($watermarkPadding, $y);

        // Add text shadow (slightly offset for better visibility)
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

        // Free memory
        imagedestroy($image);
        
        return true;
    } catch (Exception $e) {
        Log::error('Watermark Error: ' . $e->getMessage());
        return false;
    }
}

// Alternative: If you want to use TrueType font for better looking watermark
private function addWatermarkWithTTF($imagePath, $watermarkText = "showcazz")
{
    try {
        list($width, $height, $type) = getimagesize($imagePath);
        
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

        // Watermark settings
        $fontSize = 24;
        $angle = 0;
        $fontPath = public_path('fonts/arial.ttf'); // Make sure you have a TTF font file
        
        // If font file doesn't exist, fall back to GD font
        if (!file_exists($fontPath)) {
            return $this->addWatermarkToImage($imagePath); // Use the GD font version
        }

        // Calculate text dimensions
        $bbox = imagettfbbox($fontSize, $angle, $fontPath, $watermarkText);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        
        // Calculate position (bottom right)
        $x = $width - $textWidth - 20;
        $y = $height - 20;
        
        // Ensure position is within bounds
        $x = max(20, $x);
        $y = max($textHeight + 20, $y);

        // Allocate colors
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, 70);
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 70);
        
        // Add shadow
        imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $watermarkText);
        // Add main text
        imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $watermarkText);
        
        // Save image
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
        Log::error('TTF Watermark Error: ' . $e->getMessage());
        return false;
    }
}
    // ============================================
    // CRUD OPERATIONS
    // ============================================

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

            // If job post, load job details
            if ($post->is_job_post && $post->job_id) {
                $job = Job::with(['company', 'functionalArea', 'jobType', 'jobExperience', 'jobSkills'])
                          ->find($post->job_id);
                
                if ($job) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Job post retrieved successfully',
                        'data' => $this->formatJobPostResponse($post, $job, true)
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $this->formatPostResponse($post, true)
            ]);

        } catch (Exception $e) {

            dd([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
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

            // If job post, update differently
            if ($post->is_job_post && $post->job_id) {
                return $this->updateJobPost($request, $post, $user);
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
                // If job post, also update job status
                if ($post->is_job_post && $post->job_id) {
                    $job = Job::find($post->job_id);
                    if ($job) {
                        $job->update(['is_active' => false]);
                    }
                }

                // Delete associated files
                if ($post->images) {
                    $images = json_decode($post->images, true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            $path = $post->is_job_post 
                                ? public_path('job_images/' . $image)
                                : public_path('post_images/' . $image);
                            @unlink($path);
                        }
                    }
                }
                
                if ($post->files) {
                    $files = json_decode($post->files, true);
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            $path = $post->is_job_post 
                                ? public_path('job_files/' . $file)
                                : public_path('post_files/' . $file);
                            @unlink($path);
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
    
                // 👉 Unlike
                $existingLike->delete();
                $post->decrement('likes_count');
    
                $liked = false;
                $message = 'Post unliked';
    
            } else {
    
                // 👉 Like
                PostLike::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
    
                $post->increment('likes_count');
    
                $liked = true;
                $message = 'Post liked';
            }
    
            // 🔥 Refresh latest value
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

            // Get posts with pagination
            $posts = $query->paginate($perPage, ['*'], 'page', $page);

            // Format response
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

            // Apply filters
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
     * Get posts by user ID
     */
    public function getPostsByUserId($userId)
    {
        try {
            $authUser = Auth::user();
            $perPage = request()->get('per_page', 20);
            $page = request()->get('page', 1);
            
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $query = Post::with([
                'postType',
                'category',
                'subcategory',
                'user',
                'taggedUsers',
                'taggedCompanies',
            ])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

            $posts = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedPosts = $posts->map(function ($post) use ($authUser) {
                return $this->formatPostResponse($post, false);
            });

            return response()->json([
                'success' => true,
                'message' => 'User posts retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'usertype' => $user->usertype,
                    ],
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
                'message' => 'Failed to retrieve user posts',
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
    // HELPER METHODS
    // ============================================



    private function handlePostTags($postId, $taggedUsers, $taggedCompanies)
    {
        // Tagged Users
        if (is_array($taggedUsers)) {
            foreach ($taggedUsers as $userId) {
                PostTag::create([
                    'post_id' => $postId,
                    'tagged_user_id' => $userId,
                ]);
            }
        }
    
        // Tagged Companies
        if (is_array($taggedCompanies)) {
            foreach ($taggedCompanies as $companyId) {
                PostTag::create([
                    'post_id' => $postId,
                    'tagged_company_id' => $companyId,
                ]);
            }
        }
    }

    /**
     * Generate search index for job
     */
    private function generateSearchIndex(Request $request)
    {
        $searchData = [
            'title' => $request->title,
            'description' => $request->content,
            'company_id' => $request->company_id,
            'location' => $request->location,
            'skills' => $request->job_skills ?? [],
            'functional_area_id' => $request->functional_area_id,
            'job_type_id' => $request->job_type_id,
        ];
        
        return json_encode($searchData);
    }

    /**
     * Get job type from subcategory
     */
    private function getJobTypeFromSubcategory($subcategoryId)
    {
        switch ($subcategoryId) {
            case 13: return 'mini_mission';
            case 14: return 'internship';
            case 15: return 'full_time';
            default: return 'other';
        }
    }

    /**
     * Update regular post helper
     */
    private function updateRegularPost(Request $request, $post, $user)
    {
        // Similar to create but for update - implement based on your needs
        // This would include validation and update logic for regular posts
        return response()->json([
            'success' => true,
            'message' => 'Regular post update method',
        ]);
    }

    /**
     * Update job post helper
     */
    private function updateJobPost(Request $request, $post, $user)
    {
        // Similar to create but for update - implement based on your needs
        // This would include validation and update logic for job posts
        return response()->json([
            'success' => true,
            'message' => 'Job post update method',
        ]);
    }

   

    private function formatPostResponse($post, $detailed = false)
    {
        $user = Auth::user();

        // FIX: Check if already an array before json_decode
        $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
        $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
        $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
        
        // Get author data
        $authorData = $this->getAuthorData($post);
        
        $formatted = [
            'id' => $post->id,
            'title' => $post->getDisplayTitleAttribute(),
            'content' => $post->content,
            'short_description' => $post->short_description,
            'images' => array_map(function($image) use ($post) {
                return $post->is_job_post 
                    ? asset('job_images/' . $image)
                    : asset('post_images/' . $image);
            }, $images),
            'files' => array_map(function($file) use ($post) {
                return $post->is_job_post 
                    ? asset('job_files/' . $file)
                    : asset('post_files/' . $file);
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
            'job_id' => $post->job_id,
        ];

        // Add category-specific data
        $categoryId = $post->category_id;
        $subcategoryId = $post->subcategory_id;
        
        if ($categoryId == 1) {
            if ($subcategoryId == 1) {
                $formatted['project_data'] = [
                    'tech_stack' => $techStack,
                    'idea_or_goal' => $post->idea_or_goal,
                    'outcome_or_fun_element' => $post->outcome_or_fun_element,
                ];
            } elseif ($subcategoryId == 2) {
                $formatted['project_data'] = [
                    'tech_stack' => $techStack,
                    'project_domain' => $post->project_domain,
                    'role_in_project' => $post->role_in_project,
                    'duration_start' => $post->duration_start,
                    'duration_end' => $post->duration_end,
                ];
            }
        } elseif ($categoryId == 2) {
            if ($subcategoryId == 3) {
                $formatted['achievement_data'] = [
                    'certification_title' => $post->certification_title,
                    'technology_topic' => $post->technology_topic,
                ];
            } elseif ($subcategoryId == 4) {
                $formatted['achievement_data'] = [
                    'award_name' => $post->award_name,
                    'technology_topic' => $post->technology_topic,
                ];
            } elseif ($subcategoryId == 5) {
                $formatted['achievement_data'] = [
                    'occasion_title' => $post->occasion_title,
                    'message' => $post->message,
                    'technology_topic' => $post->technology_topic,
                ];
            }
        } elseif ($categoryId == 3) {
            $formatted['event_data'] = [
                'event_date' => $post->event_date,
                'event_end_date' => $post->event_end_date,
                'technology_topic' => $post->technology_topic,
                'result_rank' => $post->result_rank,
                'organizer_id' => $post->organizer_id,
                'host_id' => $post->host_id,
            ];
        } elseif ($categoryId == 4) {
            $formatted['knowledge_data'] = [
                'idea_title' => $post->idea_title,
                'guide_title' => $post->guide_title,
                'technology_topic' => $post->technology_topic,
            ];
        } elseif ($categoryId == 5) {
            $formatted['job_data'] = [
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
            ];
        }

        // Add detailed data if requested
        if ($detailed && $user) {
            
            // Handle LIKES - Check both users and companies tables
            $userIds = $post->likes->pluck('user_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            $companies = Company::whereIn('id', $userIds)->get()->keyBy('id');

            $formatted['likes'] = $post->likes->take(10)->map(function ($like) use ($users, $companies) {
                return $this->getLikeAuthorData($like, $users, $companies);
            })->filter();

            // Handle COMMENTS - DIRECT TABLE CHECKS, NO RELATIONS
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

        // First check if user exists in users table
        $userRecord = User::find($post->user_id);
        
        if ($userRecord) {
            $authorId = $userRecord->id;
            $authorUserType = $userRecord->usertype;

            // If user is company type
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
                // Regular user
                $authorName = trim(($userRecord->first_name ?? '') . ' ' . ($userRecord->last_name ?? ''));
                $authorImage = $userRecord->image ? asset('user_images/' . $userRecord->image) : null;
                $authorHeadline = $userRecord->headline;
            }
        } else {
            // Check if it's a company directly
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

    //  private function getLikeAuthorData($like, $users, $companies)
    // {
    //     // Check if it's a user
    //     if (isset($users[$like->user_id])) {
    //         $user = $users[$like->user_id];
            
    //         // If user is company type
    //         if ($user->usertype === 'company') {
    //             $company = Company::where('user_id', $user->id)->first();
    //             if ($company) {
    //                 return [
    //                     'user_id' => $company->id,
    //                     'name' => $company->name,
    //                     'usertype' => 'company',
    //                     'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
    //                     'liked_at' => $like->created_at,
    //                 ];
    //             }
    //         }
            
    //         // Regular user
    //         return [
    //             'user_id' => $user->id,
    //             'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
    //             'usertype' => $user->usertype,
    //             'image' => $user->image ? asset('user_images/' . $user->image) : null,
    //             'liked_at' => $like->created_at,
    //         ];
    //     }

    //     // Check if it's a company
    //     if (isset($companies[$like->user_id])) {
    //         $company = $companies[$like->user_id];
    //         return [
    //             'user_id' => $company->id,
    //             'name' => $company->name,
    //             'usertype' => 'company',
    //             'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
    //             'liked_at' => $like->created_at,
    //         ];
    //     }

    //     return null;
    // }
    
    
    
    private function getLikeAuthorData($like, $users, $companies)
    {
        $currentUser = Auth::user();
        
        // Check if it's a user
        if (isset($users[$like->user_id])) {
            $user = $users[$like->user_id];
            
            // If user is company type
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                if ($company) {
                    // Check if current user is connected to this company
                    $isConnected = $this->checkIfUserConnectedToCompany($currentUser->id, $company->id);
                    
                    return [
                        'user_id' => $company->id,
                        'name' => $company->name,
                        'usertype' => 'company',
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'liked_at' => $like->created_at,
                        'is_friend' => $isConnected, // Add connection status
                    ];
                }
            }
            
            // Regular user
            // Check if current user is connected to this user
            $isConnected = $this->checkIfUsersAreConnected($currentUser->id, $user->id);
            
            return [
                'user_id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'usertype' => $user->usertype,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'liked_at' => $like->created_at,
                'is_friend' => $isConnected, // Add connection status
            ];
        }
    
        // Check if it's a company
        if (isset($companies[$like->user_id])) {
            $company = $companies[$like->user_id];
            
            // Check if current user is connected to this company
            $isConnected = $this->checkIfUserConnectedToCompany($currentUser->id, $company->id);
            
            return [
                'user_id' => $company->id,
                'name' => $company->name,
                'usertype' => 'company',
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'liked_at' => $like->created_at,
                'is_friend' => $isConnected, // Add connection status
            ];
        }
    
        return null;
    }
    
    
        /**
     * Check if two users are connected (friends)
     */
    private function checkIfUsersAreConnected($userId1, $userId2)
    {
        if ($userId1 == $userId2) {
            return false; // Same user
        }
        
        // Check if they are connected (accepted follow in either direction)
        $connection = UserConnection::where(function($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId1)
                  ->where('following_id', $userId2)
                  ->where('status', 'accepted');
        })->orWhere(function($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId2)
                  ->where('following_id', $userId1)
                  ->where('status', 'accepted');
        })->first();
        
        return !is_null($connection);
    }
    
    /**
     * Check if user is connected to company (following)
     */
    private function checkIfUserConnectedToCompany($userId, $companyId)
    {
        // Check if user follows this company
        $isFollowing = FavouriteCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
        
        return $isFollowing;
    }
    
    /**
     * Check if current user is connected to a target user/company
     */
    private function checkIfConnected($currentUserId, $targetId, $targetType = 'user')
    {
        if ($targetType === 'company') {
            return $this->checkIfUserConnectedToCompany($currentUserId, $targetId);
        } else {
            return $this->checkIfUsersAreConnected($currentUserId, $targetId);
        }
    }
    
    
    private function formatComments($comments)
    {
        return $comments->map(function ($comment) {
            
            // Get comment author data by checking both tables
            $authorData = $this->getEntityData($comment->user_id);
            
            // Handle replies
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

        // FIRST: Check if this ID exists in companies table (direct company)
        $company = Company::find($id);
        if ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'usertype' => 'company',
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
            ];
        }

        // SECOND: Check users table
        $user = User::find($id);
        if ($user) {
            // Check if this user is actually a company (usertype = company)
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
            
            // Regular user
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

        // Not found in either table
        return [
            'id' => $id,
            'name' => 'Unknown User',
            'usertype' => 'unknown',
            'image' => null,
        ];
    }

    /**
     * Format job post response
     */
    private function formatJobPostResponse($post, $job, $detailed = false)
    {
        $user = Auth::user();
        
        // Get post base data
        $formatted = $this->formatPostResponse($post, $detailed);
        
        // Add job-specific data
        $formatted['job_details'] = [
            'job_id' => $job->id,
            'company' => $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name,
                'slug' => $job->company->slug,
                'logo' => $job->company->logo ? asset('company_logos/' . $job->company->logo) : null,
            ] : null,
            'description' => $job->description,
            'benefits' => $job->benefits,
            'is_freelance' => $job->is_freelance,
            'career_level' => $job->careerLevel ? [
                'id' => $job->careerLevel->career_level_id,
                'name' => $job->careerLevel->career_level,
            ] : null,
            'salary' => [
                'from' => $job->salary_from,
                'to' => $job->salary_to,
                'currency' => $job->salary_currency,
                'period' => $job->salaryPeriod ? [
                    'id' => $job->salaryPeriod->salary_period_id,
                    'name' => $job->salaryPeriod->salary_period,
                ] : null,
                'hide_salary' => $job->hide_salary,
            ],
            'functional_area' => $job->functionalArea ? [
                'id' => $job->functionalArea->functional_area_id,
                'name' => $job->functionalArea->functional_area,
            ] : null,
            'job_type' => $job->jobType ? [
                'id' => $job->jobType->job_type_id,
                'name' => $job->jobType->job_type,
            ] : null,
            'job_shift' => $job->jobShift ? [
                'id' => $job->jobShift->job_shift_id,
                'name' => $job->jobShift->job_shift,
            ] : null,
            'num_of_positions' => $job->num_of_positions,
            'gender' => $job->gender ? [
                'id' => $job->gender->gender_id,
                'name' => $job->gender->gender,
            ] : null,
            'expiry_date' => $job->expiry_date,
            'degree_level' => $job->degreeLevel ? [
                'id' => $job->degreeLevel->degree_level_id,
                'name' => $job->degreeLevel->degree_level,
            ] : null,
            'job_experience' => $job->jobExperience ? [
                'id' => $job->jobExperience->job_experience_id,
                'name' => $job->jobExperience->job_experience,
            ] : null,
            'location' => [
                'country' => $job->country ? $job->country->country : null,
                'state' => $job->state ? $job->state->state : null,
                'city' => $job->city ? $job->city->city : null,
                'full_location' => $job->location,
                'postal_code' => $job->postal_code,
            ],
            'is_active' => $job->is_active,
            'is_featured' => $job->is_featured,
            'slug' => $job->slug,
            'reference' => $job->reference,
            'job_advertiser' => $job->job_advertiser,
            'application_url' => $job->application_url,
            'logo' => $job->logo ? asset('job_images/' . $job->logo) : null,
            'skills' => $job->jobSkills->map(function ($skillManager) {
                return [
                    'id' => $skillManager->job_skill_id,
                    'name' => $skillManager->getJobSkill('job_skill'),
                ];
            }),
            'type' => $job->type,
        ];

        return $formatted;
    }
    
    
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
    
            // Find the original post (could be a post or a repost)
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
    
            // Get the actual original post (if this is a repost, get the original)
            $originalPost = $this->getOriginalPost($post);
    
            // Check if user has already reposted this post (optional - remove if you allow multiple reposts)
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
                // Prepare repost content with attribution
                $repostContent = $this->prepareRepostContent($originalPost, $request->repost_comment);
                
                // Create NEW post (repost)
                $repostData = [
                    'user_id' => $user->id,
                    'post_user_type' => $user->usertype,
                    'post_type_id' => $originalPost->post_type_id,
                    'category_id' => $originalPost->category_id,
                    'subcategory_id' => $originalPost->subcategory_id,
                    'title' => $this->generateRepostTitle($originalPost->title),
                    'content' => $repostContent,
                    'short_description' => $originalPost->short_description,
                    'images' => $originalPost->images, // Copy images from original
                    'files' => $originalPost->files,    // Copy files from original
                    'is_published' => $request->is_published ?? true,
                    'is_active' => true,
                    'original_post_id' => $originalPost->id,
                    'is_repost' => true,
                ];
    
                // Add category-specific fields from original post
                $categorySpecificFields = [
                    'tech_stack' => $originalPost->tech_stack,
                    'idea_or_goal' => $originalPost->idea_or_goal,
                    'outcome_or_fun_element' => $originalPost->outcome_or_fun_element,
                    'project_domain' => $originalPost->project_domain,
                    'role_in_project' => $originalPost->role_in_project,
                    'duration_start' => $originalPost->duration_start,
                    'duration_end' => $originalPost->duration_end,
                    'certification_title' => $originalPost->certification_title,
                    'award_name' => $originalPost->award_name,
                    'technology_topic' => $originalPost->technology_topic,
                    'occasion_title' => $originalPost->occasion_title,
                    'message' => $originalPost->message,
                    'event_date' => $originalPost->event_date,
                    'event_end_date' => $originalPost->event_end_date,
                    'result_rank' => $originalPost->result_rank,
                    'idea_title' => $originalPost->idea_title,
                    'guide_title' => $originalPost->guide_title,
                    'organizer_id' => $originalPost->organizer_id,
                    'host_id' => $originalPost->host_id,
                ];
    
                // Filter out null values
                $categorySpecificFields = array_filter($categorySpecificFields);
                $repostData = array_merge($repostData, $categorySpecificFields);
    
                // Create the repost
                $repost = Post::create($repostData);
    
                // Create repost record
                $repostRecord = PostRepost::create([
                    'original_post_id' => $originalPost->id,
                    'reposted_post_id' => $repost->id,
                    'user_id' => $user->id,
                    'repost_comment' => $request->repost_comment,
                ]);
    
                // Handle tags from original post (copy them)
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
    
                // Increment repost count on original post
                $originalPost->increment('repost_count');
    
                DB::commit();
    
                // Load relationships
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
    
    
    public function getRepost($id)
    {
        try {
            $repost = PostRepost::with(['originalPost', 'repostedPost', 'user'])
                ->find($id);
    
            if (!$repost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repost not found'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Repost details retrieved',
                'data' => [
                    'id' => $repost->id,
                    'repost_comment' => $repost->repost_comment,
                    'created_at' => $repost->created_at,
                    'user' => $this->getEntityData($repost->user_id),
                    'original_post' => $this->formatPostResponse($repost->originalPost),
                    'reposted_post' => $this->formatPostResponse($repost->repostedPost),
                ]
            ]);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get repost details',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    /**
     * Get reposts of a post
     */
    public function getPostReposts($postId)
    {
        try {
            $post = Post::find($postId);
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }
    
            $reposts = PostRepost::where('original_post_id', $postId)
                ->with(['user', 'repostedPost'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
    
            $formattedReposts = $reposts->map(function ($repost) {
                return [
                    'id' => $repost->id,
                    'repost_comment' => $repost->repost_comment,
                    'created_at' => $repost->created_at,
                    'time_ago' => $repost->created_at->diffForHumans(),
                    'user' => $this->getEntityData($repost->user_id),
                    'reposted_post_id' => $repost->reposted_post_id,
                ];
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Post reposts retrieved',
                'data' => [
                    'post_id' => $postId,
                    'total_reposts' => $post->repost_count,
                    'reposts' => $formattedReposts,
                    'pagination' => [
                        'current_page' => $reposts->currentPage(),
                        'per_page' => $reposts->perPage(),
                        'total' => $reposts->total(),
                        'last_page' => $reposts->lastPage(),
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reposts',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
    public function getUserReposts(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
    
            $reposts = PostRepost::where('user_id', $user->id)
                ->with(['originalPost', 'repostedPost'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
    
            $formattedReposts = $reposts->map(function ($repost) {
                return [
                    'id' => $repost->id,
                    'repost_comment' => $repost->repost_comment,
                    'created_at' => $repost->created_at,
                    'time_ago' => $repost->created_at->diffForHumans(),
                    'original_post' => [
                        'id' => $repost->originalPost->id,
                        'title' => $repost->originalPost->title,
                        'author' => $this->getEntityData($repost->originalPost->user_id),
                    ],
                    'reposted_post' => $this->formatPostResponse($repost->repostedPost, false),
                ];
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Your reposts retrieved',
                'data' => [
                    'reposts' => $formattedReposts,
                    'total' => $reposts->total(),
                    'pagination' => [
                        'current_page' => $reposts->currentPage(),
                        'per_page' => $reposts->perPage(),
                        'total' => $reposts->total(),
                        'last_page' => $reposts->lastPage(),
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get your reposts',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
    public function deleteRepost($id)
    {
        try {
            $user = Auth::user();
    
            $repost = PostRepost::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
    
            if (!$repost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repost not found or you are not authorized'
                ], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Delete the reposted post
                $repostedPost = Post::find($repost->reposted_post_id);
                if ($repostedPost) {
                    // Delete associated files
                    if ($repostedPost->images) {
                        $images = json_decode($repostedPost->images, true);
                        if (is_array($images)) {
                            foreach ($images as $image) {
                                @unlink(public_path('post_images/' . $image));
                            }
                        }
                    }
                    
                    if ($repostedPost->files) {
                        $files = json_decode($repostedPost->files, true);
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                @unlink(public_path('post_files/' . $file));
                            }
                        }
                    }
    
                    // Soft delete the reposted post
                    $repostedPost->update(['is_active' => false]);
                }
    
                // Decrement repost count on original post
                $originalPost = Post::find($repost->original_post_id);
                if ($originalPost) {
                    $originalPost->decrement('repost_count');
                }
    
                // Delete repost record
                $repost->delete();
    
                DB::commit();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Repost deleted successfully'
                ]);
    
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete repost',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
    private function getOriginalPost($post)
    {
        if ($post->original_post_id) {
            $original = Post::find($post->original_post_id);
            if ($original && $original->original_post_id) {
                return $this->getOriginalPost($original); // Recursive for chain reposts
            }
            return $original ?: $post;
        }
        return $post;
    }
    
    
    private function prepareRepostContent($originalPost, $userComment = null)
    {
        // Get original author name
        $originalAuthor = $this->getAuthorData($originalPost);
        $originalAuthorName = $originalAuthor['name'] ?? 'Unknown User';
        
        // Build repost content
        $content = '';
        
        // Add user's comment if any (at the top)
        if ($userComment) {
            $content .= '<div class="repost-comment" style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff;">';
            $content .= '<p style="margin: 0; font-style: italic;">"' . e($userComment) . '"</p>';
            $content .= '</div>';
        }
        
        // Add original post attribution
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
     * Generate title for repost
     */
    private function generateRepostTitle($originalTitle)
    {
        return 'Repost: ' . $originalTitle;
    }
    
    
    private function getConnectionStatus($currentUser, $targetId)
    {
        // Check if target is a company
        $isCompany = Company::where('id', $targetId)->exists();
        
        if ($isCompany) {
            // Check if following company
            $isFollowing = FavouriteCompany::where('user_id', $currentUser->id)
                ->where('company_id', $targetId)
                ->exists();
            
            if ($isFollowing) {
                return 'following';
            }
            
            // Check if blocked
            $isBlocked = UserConnection::where('follower_id', $currentUser->id)
                ->where('following_id', $targetId)
                ->where('status', 'blocked')
                ->exists();
            
            if ($isBlocked) {
                return 'blocked';
            }
            
            return 'not_following';
        }
        
        // Check if it's a user
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
            return $connection->status; // 'pending', 'accepted', 'blocked'
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
    
    /**
     * Get mutual connections count between two users
     */
    private function getMutualCount($userId1, $userId2)
    {
        // Get user1's following
        $user1Following = UserConnection::where('follower_id', $userId1)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        // Get user2's following
        $user2Following = UserConnection::where('follower_id', $userId2)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->toArray();
        
        // Count mutual connections
        $mutual = array_intersect($user1Following, $user2Following);
        
        return count($mutual);
    }
    
    
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
    
            // Check if post exists
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
    
            // Get likes query
            $likesQuery = PostLike::where('post_id', $postId)
                ->orderBy('created_at', 'desc');
    
            // Apply search if provided
            if ($search) {
                $likesQuery->whereHas('user', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }
    
            // Get paginated likes
            $likes = $likesQuery->paginate($perPage);
    
            // Format likers with connection status
            $formattedLikers = $likes->map(function ($like) use ($currentUser) {
                $likerData = $this->getEntityData($like->user_id);
                
                // Get connection status
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
                    'mutual_count' => $this->getMutualCount($currentUser->id, $like->user_id),
                ];
            });
    
            // Get counts by connection type
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

    
    
}