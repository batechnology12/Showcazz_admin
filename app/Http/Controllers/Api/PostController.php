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
use App\User;
use App\Company;
use App\JobSkillManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            if ($request->category_id == 5) {
                return $this->createJobPost($request, $user);
            }
            
            return $this->createRegularPost($request, $user);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
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
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
            'is_published' => 'boolean',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
            'tagged_companies' => 'nullable|array',
            'tagged_companies.*' => 'exists:companies,id',
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
            'category_id' => 'required|in:5',
            'subcategory_id' => 'required|in:13,14,15', // 13: Mini Mission, 14: Internship, 15: Full-Time
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,zip|max:10240',
            'is_published' => 'boolean',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
            'tagged_companies' => 'nullable|array',
            'tagged_companies.*' => 'exists:companies,id',
            // Common job fields
            'company_id' => 'required|exists:companies,id',
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
            // Handle image uploads
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
                'company_id' => $request->company_id,
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

            try {
                $existingLike = PostLike::where('post_id', $post->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingLike) {
                    // Unlike
                    $existingLike->delete();
                    $post->decrement('likes_count');
                    $liked = false;
                    $message = 'Post unliked';
                } else {
                    // Like
                    PostLike::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ]);
                    $post->increment('likes_count');
                    $liked = true;
                    $message = 'Post liked';
                }

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
                throw $e;
            }

        } catch (Exception $e) {
            
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
    // public function getPosts(Request $request)
    // {

    //     try {
    //         $user = Auth::user();
    //         $perPage = $request->get('per_page', 20);
    //         $page = $request->get('page', 1);
            
    //         $query = Post::with([
    //             'postType',
    //             'category',
    //             'subcategory',
    //             'user',
    //             'taggedUsers',
    //             'taggedCompanies',
    //         ])
    //         ->where('is_active', true)
    //         ->where('is_published', true)
    //         ->orderBy('created_at', 'desc');

    //         // Apply filters
    //         if ($request->has('post_type_id')) {
    //             $query->where('post_type_id', $request->post_type_id);
    //         }
            
    //         if ($request->has('category_id')) {
    //             $query->where('category_id', $request->category_id);
    //         }
            
    //         if ($request->has('subcategory_id')) {
    //             $query->where('subcategory_id', $request->subcategory_id);
    //         }
            
    //         if ($request->has('user_id')) {
    //             $query->where('user_id', $request->user_id);
    //         }
            
    //         if ($request->has('search')) {
    //             $search = $request->search;
    //             $query->where(function($q) use ($search) {
    //                 $q->where('title', 'like', "%{$search}%")
    //                   ->orWhere('content', 'like', "%{$search}%")
    //                   ->orWhere('short_description', 'like', "%{$search}%");
    //             });
    //         }

    //         // Get posts with pagination
    //         $posts = $query->paginate($perPage, ['*'], 'page', $page);

    //         // Format response
    //         $formattedPosts = $posts->map(function ($post) use ($user) {
    //             return $this->formatPostResponse($post, false);
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Posts retrieved successfully',
    //             'data' => [
    //                 'posts' => $formattedPosts,
    //                 'pagination' => [
    //                     'current_page' => $posts->currentPage(),
    //                     'per_page' => $posts->perPage(),
    //                     'total' => $posts->total(),
    //                     'last_page' => $posts->lastPage(),
    //                 ]
    //             ]
    //         ]);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to retrieve posts',
    //             'errors' => (object)['server' => 'An error occurred']
    //         ], 500);
    //     }
    // }

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

    /**
     * Handle post tags
     */
    private function handlePostTags($postId, $taggedUsers, $taggedCompanies)
    {
        foreach ($taggedUsers as $userId) {
            PostTag::create([
                'post_id' => $postId,
                'tagged_user_id' => $userId,
            ]);
        }

        foreach ($taggedCompanies as $companyId) {
            PostTag::create([
                'post_id' => $postId,
                'tagged_company_id' => $companyId,
            ]);
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

    /**
     * Format post response
     */
    // private function formatPostResponse($post, $detailed = false)
    // {
    //     $user = Auth::user();

    //     $images = $post->images ? json_decode($post->images, true) : [];
    //     $files = $post->files ? json_decode($post->files, true) : [];
    //     $techStack = $post->tech_stack ? json_decode($post->tech_stack, true) : [];
        
    //     $formatted = [
    //         'id' => $post->id,
    //         'title' => $post->getDisplayTitleAttribute(),
    //         'content' => $post->content,
    //         'short_description' => $post->short_description,
    //         'images' => array_map(function($image) use ($post) {
    //             return $post->is_job_post 
    //                 ? asset('job_images/' . $image)
    //                 : asset('post_images/' . $image);
    //         }, $images),
    //         'files' => array_map(function($file) use ($post) {
    //             return $post->is_job_post 
    //                 ? asset('job_files/' . $file)
    //                 : asset('post_files/' . $file);
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
    //         'author' => $post->user ? [
    //             'id' => $post->user->id,
    //             'name' => $post->user->name,
    //             'usertype' => $post->user->usertype,
    //             'image' => $post->user->image ? asset('user_images/' . $post->user->image) : null,
    //         ] : null,
    //         'tagged_users' => $post->taggedUsers->map(function ($user) {
    //             return [
    //                 'id' => $user->id,
    //                 'name' => $user->name,
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
    //         'job_id' => $post->job_id,
    //     ];

    //     // Add category-specific data
    //     $categoryId = $post->category_id;
    //     $subcategoryId = $post->subcategory_id;
        
    //     if ($categoryId == 1) { // Projects
    //         if ($subcategoryId == 1) { // Mini innovation/fun innovation
    //             $formatted['project_data'] = [
    //                 'tech_stack' => $techStack,
    //                 'idea_or_goal' => $post->idea_or_goal,
    //                 'outcome_or_fun_element' => $post->outcome_or_fun_element,
    //             ];
    //         } elseif ($subcategoryId == 2) { // Real Project
    //             $formatted['project_data'] = [
    //                 'tech_stack' => $techStack,
    //                 'project_domain' => $post->project_domain,
    //                 'role_in_project' => $post->role_in_project,
    //                 'duration_start' => $post->duration_start,
    //                 'duration_end' => $post->duration_end,
    //             ];
    //         }
    //     } elseif ($categoryId == 2) { // Achievements
    //         if ($subcategoryId == 3) { // Certification
    //             $formatted['achievement_data'] = [
    //                 'certification_title' => $post->certification_title,
    //                 'technology_topic' => $post->technology_topic,
    //             ];
    //         } elseif ($subcategoryId == 4) { // Rewards / Recognitions
    //             $formatted['achievement_data'] = [
    //                 'award_name' => $post->award_name,
    //                 'technology_topic' => $post->technology_topic,
    //             ];
    //         } elseif ($subcategoryId == 5) { // Congratulate Someone
    //             $formatted['achievement_data'] = [
    //                 'occasion_title' => $post->occasion_title,
    //                 'message' => $post->message,
    //                 'technology_topic' => $post->technology_topic,
    //             ];
    //         }
    //     } elseif ($categoryId == 3) { // Events
    //         $formatted['event_data'] = [
    //             'event_date' => $post->event_date,
    //             'event_end_date' => $post->event_end_date,
    //             'technology_topic' => $post->technology_topic,
    //             'result_rank' => $post->result_rank,
    //             'organizer_id' => $post->organizer_id,
    //             'host_id' => $post->host_id,
    //         ];
    //     } elseif ($categoryId == 4) { // Knowledge Sharing
    //         $formatted['knowledge_data'] = [
    //             'idea_title' => $post->idea_title,
    //             'guide_title' => $post->guide_title,
    //             'technology_topic' => $post->technology_topic,
    //         ];
    //     } elseif ($categoryId == 5) { // Jobs
    //         $formatted['job_data'] = [
    //             'deliverables' => $post->deliverables,
    //             'timeline_start' => $post->timeline_start,
    //             'timeline_end' => $post->timeline_end,
    //             'role_type' => $post->role_type,
    //             'work_mode' => $post->work_mode,
    //             'key_deliverables' => $post->key_deliverables,
    //             'internship_duration' => $post->internship_duration,
    //             'stipend_amount' => $post->stipend_amount,
    //             'stipend_currency' => $post->stipend_currency,
    //             'convertible_to_full_time' => $post->convertible_to_full_time,
    //             'ctc_amount' => $post->ctc_amount,
    //             'ctc_currency' => $post->ctc_currency,
    //             'application_deadline' => $post->application_deadline,
    //         ];
    //     }

    //     // Add detailed data if requested
    //     if ($detailed && $user) {
    //         $formatted['likes'] = $post->likes->take(10)->map(function ($like) {
    //             return [
    //                 'user_id' => $like->user_id,
    //                 'name' => $like->user->name,
    //                 'image' => $like->user->image ? asset('user_images/' . $like->user->image) : null,
    //                 'liked_at' => $like->created_at,
    //             ];
    //         });
            
    //         $formatted['comments'] = $post->comments->map(function ($comment) {
    //             return [
    //                 'id' => $comment->id,
    //                 'content' => $comment->content,
    //                 'created_at' => $comment->created_at,
    //                 'author' => $comment->user ? [
    //                     'id' => $comment->user->id,
    //                     'name' => $comment->user->name,
    //                     'image' => $comment->user->image ? asset('user_images/' . $comment->user->image) : null,
    //                 ] : null,
    //                 'replies' => $comment->replies->map(function ($reply) {
    //                     return [
    //                         'id' => $reply->id,
    //                         'content' => $reply->content,
    //                         'created_at' => $reply->created_at,
    //                         'author' => $reply->user ? [
    //                             'id' => $reply->user->id,
    //                             'name' => $reply->user->name,
    //                             'image' => $reply->user->image ? asset('user_images/' . $reply->user->image) : null,
    //                         ] : null,
    //                     ];
    //                 }),
    //             ];
    //         });
    //     }

    //     return $formatted;
    // }



    private function formatPostResponse($post, $detailed = false)
{
    $user = Auth::user();

    // FIX: Check if already an array before json_decode
    $images = is_array($post->images) ? $post->images : (json_decode($post->images ?? '', true) ?: []);
    $files = is_array($post->files) ? $post->files : (json_decode($post->files ?? '', true) ?: []);
    $techStack = is_array($post->tech_stack) ? $post->tech_stack : (json_decode($post->tech_stack ?? '', true) ?: []);
    
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
        'author' => $post->user ? [
            'id' => $post->user->id,
            'name' => $post->user->name,
            'usertype' => $post->user->usertype,
            'image' => $post->user->image ? asset('user_images/' . $post->user->image) : null,
        ] : null,
        'tagged_users' => $post->taggedUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
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
    
    if ($categoryId == 1) { // Projects
        if ($subcategoryId == 1) { // Mini innovation/fun innovation
            $formatted['project_data'] = [
                'tech_stack' => $techStack,
                'idea_or_goal' => $post->idea_or_goal,
                'outcome_or_fun_element' => $post->outcome_or_fun_element,
            ];
        } elseif ($subcategoryId == 2) { // Real Project
            $formatted['project_data'] = [
                'tech_stack' => $techStack,
                'project_domain' => $post->project_domain,
                'role_in_project' => $post->role_in_project,
                'duration_start' => $post->duration_start,
                'duration_end' => $post->duration_end,
            ];
        }
    } elseif ($categoryId == 2) { // Achievements
        if ($subcategoryId == 3) { // Certification
            $formatted['achievement_data'] = [
                'certification_title' => $post->certification_title,
                'technology_topic' => $post->technology_topic,
            ];
        } elseif ($subcategoryId == 4) { // Rewards / Recognitions
            $formatted['achievement_data'] = [
                'award_name' => $post->award_name,
                'technology_topic' => $post->technology_topic,
            ];
        } elseif ($subcategoryId == 5) { // Congratulate Someone
            $formatted['achievement_data'] = [
                'occasion_title' => $post->occasion_title,
                'message' => $post->message,
                'technology_topic' => $post->technology_topic,
            ];
        }
    } elseif ($categoryId == 3) { // Events
        $formatted['event_data'] = [
            'event_date' => $post->event_date,
            'event_end_date' => $post->event_end_date,
            'technology_topic' => $post->technology_topic,
            'result_rank' => $post->result_rank,
            'organizer_id' => $post->organizer_id,
            'host_id' => $post->host_id,
        ];
    } elseif ($categoryId == 4) { // Knowledge Sharing
        $formatted['knowledge_data'] = [
            'idea_title' => $post->idea_title,
            'guide_title' => $post->guide_title,
            'technology_topic' => $post->technology_topic,
        ];
    } elseif ($categoryId == 5) { // Jobs
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
        $formatted['likes'] = $post->likes->take(10)->map(function ($like) {
            return [
                'user_id' => $like->user_id,
                'name' => $like->user->name,
                'image' => $like->user->image ? asset('user_images/' . $like->user->image) : null,
                'liked_at' => $like->created_at,
            ];
        });
        
        $formatted['comments'] = $post->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'author' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'image' => $comment->user->image ? asset('user_images/' . $comment->user->image) : null,
                ] : null,
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'created_at' => $reply->created_at,
                        'author' => $reply->user ? [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'image' => $reply->user->image ? asset('user_images/' . $reply->user->image) : null,
                        ] : null,
                    ];
                }),
            ];
        });
    }

    return $formatted;
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
}