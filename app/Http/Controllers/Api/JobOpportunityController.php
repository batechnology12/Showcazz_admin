<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\User;
use App\Company;
use App\UserMessage;
use App\Models\ChatType;
use App\Models\ChatSession;
use App\PostLike;
use App\PostView;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class JobOpportunityController extends Controller
{
    /**
     * Helper: Get entity data (works for both User & Company)
     */
    private function getEntityData($id)
    {
        if (!$id) {
            return null;
        }

        // Check companies table
        $company = Company::find($id);
        if ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'usertype' => 'company',
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'slug' => $company->slug,
                'description' => $company->description,
                'location' => $company->location,
                'website' => $company->website,
                'entity_type' => 'company'
            ];
        }

        // Check users table
        $user = User::find($id);
        if ($user) {
            // If user is a company (usertype = company)
            if ($user->usertype === 'company') {
                $companyRecord = Company::where('user_id', $user->id)->first();
                if ($companyRecord) {
                    return [
                        'id' => $companyRecord->id,
                        'name' => $companyRecord->name,
                        'email' => $companyRecord->email ?? $user->email,
                        'usertype' => 'company',
                        'image' => $companyRecord->logo ? asset('company_logos/' . $companyRecord->logo) : null,
                        'slug' => $companyRecord->slug,
                        'description' => $companyRecord->description,
                        'location' => $companyRecord->location,
                        'website' => $companyRecord->website,
                        'entity_type' => 'company'
                    ];
                }
            }

            // Regular user
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $name = $name ?: ($user->name ?? 'Unknown User');

            return [
                'id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'usertype' => $user->usertype ?? 'user',
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'headline' => $user->headline,
                'location' => $user->location,
                'portfolio_website' => $user->portfolio_website,
                'entity_type' => 'user'
            ];
        }

        return null;
    }

    /**
     * Helper: Get skill names from IDs
     */
    private function getSkillNames($skillIds)
    {
        if (empty($skillIds)) {
            return [];
        }

        // If it's already an array of IDs
        if (is_array($skillIds)) {
            $skills = JobSkill::whereIn('id', $skillIds)
                ->where('is_active', 1)
                ->get(['id', 'job_skill']);
            
            return $skills->map(function($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->job_skill
                ];
            })->toArray();
        }

        // If it's a comma-separated string
        if (is_string($skillIds)) {
            $ids = array_map('intval', explode(',', $skillIds));
            $skills = JobSkill::whereIn('id', $ids)
                ->where('is_active', 1)
                ->get(['id', 'job_skill']);
            
            return $skills->map(function($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->job_skill
                ];
            })->toArray();
        }

        // If it's JSON
        $decoded = json_decode($skillIds, true);
        if (is_array($decoded)) {
            return $this->getSkillNames($decoded);
        }

        return [];
    }

    /**
     * Helper: Format post/job data
     */
    private function formatJobPost($post, $currentUser = null)
    {
        if (!$currentUser) {
            $currentUser = Auth::user();
        }

        // Get author data
        $authorData = $this->getEntityData($post->user_id);

        // Parse skills - get both IDs and names
        $skills = [];
        if ($post->skills_required) {
            $skills = $this->getSkillNames($post->skills_required);
        }

        // Parse tech stack (keep as array of strings)
        $techStack = [];
        if ($post->tech_stack) {
            if (is_array($post->tech_stack)) {
                $techStack = $post->tech_stack;
            } else {
                $techStack = json_decode($post->tech_stack, true) ?: [];
            }
        }

        // Parse images
        $images = [];
        if ($post->images) {
            if (is_array($post->images)) {
                $images = array_map(function($img) {
                    return asset('post_images/' . $img);
                }, $post->images);
            } else {
                $images = array_map(function($img) {
                    return asset('post_images/' . $img);
                }, json_decode($post->images, true) ?: []);
            }
        }

        // Check if user has already applied for this job
        $hasApplied = false;
        if ($currentUser && ($post->category_id == 5 || $post->category_id == 6)) {
            $hasApplied = UserMessage::where('listing_id', $post->id)
                ->where('from_id', $currentUser->id)
                ->where('chat_type_id', function($query) {
                    $query->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->exists();
        }

        // Check if user has liked this post
        $isLiked = $currentUser ? PostLike::where('post_id', $post->id)
            ->where('user_id', $currentUser->id)
            ->exists() : false;

        // Check connection status with author
        $connectionStatus = $this->checkConnectionStatus($currentUser, $post->user_id);

        // Determine opportunity type label
        $typeLabel = '';
        if ($post->category_id == 5) {
            $typeLabel = 'Mini Mission';
        } elseif ($post->category_id == 6) {
            $typeLabel = 'Internship';
        }

        $formatted = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'category_id' => $post->category_id,
            'subcategory_id' => $post->subcategory_id,
            'type_label' => $typeLabel,
            'is_job_post' => $post->is_job_post ?? ($post->category_id == 5 || $post->category_id == 6),
            'images' => $images,
            'created_at' => $post->created_at,
            'created_at_formatted' => $post->created_at->diffForHumans(),
            'updated_at' => $post->updated_at,
            'is_published' => $post->is_published,
            'stats' => [
                'views' => $post->views_count ?? 0,
                'likes' => $post->likes_count ?? 0,
                'comments' => $post->comments_count ?? 0,
                'shares' => $post->shares_count ?? 0,
            ],
            'user_interaction' => [
                'is_liked' => $isLiked,
                'has_applied' => $hasApplied,
                'connection_status' => $connectionStatus,
            ],
            'author' => $authorData ? [
                'id' => $authorData['id'],
                'name' => $authorData['description'] ?? $authorData['name'],
                'usertype' => $authorData['usertype'],
                'image' => $authorData['image'],
                'location' => $authorData['location'] ?? null,
                'entity_type' => $authorData['entity_type'],
            ] : null,
        ];

        // Add job-specific fields
        if ($post->category_id == 5 || $post->category_id == 6) {
            $formatted['job_details'] = [
                'role_type' => $post->role_type,
                'work_mode' => $post->work_mode,
                'key_deliverables' => $post->key_deliverables,
                'internship_duration' => $post->internship_duration,
                'experience_required' => $post->experience_required,
                'skills_required' => $skills,
                'tech_stack' => $techStack,
                'benefits' => $post->benefits,
                'salary_range' => $post->salary_range,
                'company_name' => $post->company_name,
                'job_location' => $post->job_location,
                'application_url' => $post->application_url,
                'application_deadline' => $post->application_deadline,
                'application_deadline_formatted' => $post->application_deadline ? 
                    Carbon::parse($post->application_deadline)->format('d M Y') : null,
                'is_expired' => $post->application_deadline ? 
                    Carbon::parse($post->application_deadline)->isPast() : false,
            ];

            // Add category-specific fields
            if ($post->category_id == 5) { // Mini Mission
                $formatted['job_details']['deliverables'] = $post->deliverables;
                $formatted['job_details']['timeline_start'] = $post->timeline_start;
                $formatted['job_details']['timeline_end'] = $post->timeline_end;
                $formatted['job_details']['type'] = 'mini_mission';
            } elseif ($post->category_id == 6) { // Internship
                $formatted['job_details']['stipend_amount'] = $post->stipend_amount;
                $formatted['job_details']['stipend_currency'] = $post->stipend_currency;
                $formatted['job_details']['convertible_to_full_time'] = $post->convertible_to_full_time;
                $formatted['job_details']['type'] = 'internship';
            }
        }

        return $formatted;
    }

    /**
     * Helper: Check connection status between users
     */
    private function checkConnectionStatus($currentUser, $targetUserId)
    {
        if (!$currentUser || $currentUser->id == $targetUserId) {
            return 'self';
        }

        // Check if target is a company
        $isCompany = Company::where('id', $targetUserId)->exists();
        
        if ($isCompany) {
            $isFollowing = \App\FavouriteCompany::where('user_id', $currentUser->id)
                ->where('company_id', $targetUserId)
                ->exists();
            
            return $isFollowing ? 'following' : 'not_following';
        }

        // Check user connection
        $connection = \App\UserConnection::where(function($query) use ($currentUser, $targetUserId) {
            $query->where('follower_id', $currentUser->id)
                  ->where('following_id', $targetUserId);
        })->orWhere(function($query) use ($currentUser, $targetUserId) {
            $query->where('follower_id', $targetUserId)
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

    // ============================================
    // 1. GET OPPORTUNITIES API (Category 5 & 6)
    // ============================================

    /**
     * Get all opportunities (mini_mission: cat 5, internship: cat 6)
     * With filters for mini mission and internship
     */
    public function getOpportunities(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|in:mini_mission,internship', // Filter by opportunity type
                'work_mode' => 'nullable|in:onsite,remote,hybrid', // Filter for both types
                'location' => 'nullable|string|max:255',
                'min_salary' => 'nullable|numeric',
                'max_salary' => 'nullable|numeric',
                'skills' => 'nullable|array',
                'skills.*' => 'string',
                'search' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'sort_by' => 'nullable|in:latest,deadline,salary_low,salary_high,popular',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'show_expired' => 'nullable|boolean',
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

            $perPage = $request->per_page ?? 20;
            $page = $request->page ?? 1;

            // Base query - only category_id = 5 (mini_mission) OR 6 (internship)
            $query = Post::with(['user', 'category', 'subcategory'])
                ->whereIn('category_id', [5, 6])
                ->where('is_active', true)
                ->whereNull('original_post_id')
                ->where('is_published', true);

            // FILTER 1: By type (mini_mission or internship)
            if ($request->filled('type')) {
                if ($request->type == 'mini_mission') {
                    $query->where('category_id', 5);
                } elseif ($request->type == 'internship') {
                    $query->where('category_id', 6);
                }
            }

            // FILTER 2: By work mode (works for both mini mission and internship)
            if ($request->filled('work_mode')) {
                $query->where('work_mode', $request->work_mode);
            }

            // FILTER 3: By location
            if ($request->filled('location')) {
                $query->where('job_location', 'LIKE', '%' . $request->location . '%');
            }

            // FILTER 4: By company name
            if ($request->filled('company_name')) {
                $query->where('company_name', 'LIKE', '%' . $request->company_name . '%');
            }

            // FILTER 5: By skills
            if ($request->filled('skills') && is_array($request->skills)) {
                $query->where(function($q) use ($request) {
                    foreach ($request->skills as $skill) {
                        $q->orWhere('skills_required', 'LIKE', '%' . $skill . '%');
                    }
                });
            }

            // FILTER 6: By salary range (works for both)
            if ($request->filled('min_salary') || $request->filled('max_salary')) {
                $query->where(function($q) use ($request) {
                    if ($request->filled('min_salary')) {
                        $q->where('stipend_amount', '>=', $request->min_salary)
                          ->orWhere('ctc_amount', '>=', $request->min_salary);
                    }
                    if ($request->filled('max_salary')) {
                        $q->where('stipend_amount', '<=', $request->max_salary)
                          ->orWhere('ctc_amount', '<=', $request->max_salary);
                    }
                });
            }

            // FILTER 7: Search in title, content, company_name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('short_description', 'LIKE', "%{$search}%")
                      ->orWhere('key_deliverables', 'LIKE', "%{$search}%");
                });
            }

            // FILTER 8: Exclude expired unless requested
            if (!$request->show_expired) {
                $query->where(function($q) {
                    $q->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', Carbon::now());
                });
            }

            // Apply sorting
            switch ($request->sort_by) {
                case 'deadline':
                    $query->orderByRaw('application_deadline ASC NULLS LAST');
                    break;
                case 'salary_low':
                    $query->orderByRaw('COALESCE(stipend_amount, ctc_amount, 0) ASC');
                    break;
                case 'salary_high':
                    $query->orderByRaw('COALESCE(stipend_amount, ctc_amount, 0) DESC');
                    break;
                case 'popular':
                    $query->orderByRaw('(views_count * 1 + likes_count * 2 + comments_count * 3) DESC');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Get paginated results
            $opportunities = $query->paginate($perPage, ['*'], 'page', $page);

            // Format opportunities for response
            $formattedOpportunities = $opportunities->map(function($opportunity) use ($user) {
                return $this->formatJobPost($opportunity, $user);
            });

            // Get filter counts for sidebar
            $filterCounts = $this->getOpportunityFilterCounts($request);

            return response()->json([
                'success' => true,
                'message' => 'Opportunities retrieved successfully',
                'data' => [
                    'opportunities' => $formattedOpportunities,
                    'filters' => [
                        'available' => $filterCounts,
                        'active' => [
                            'type' => $request->type ?? 'all',
                            'work_mode' => $request->work_mode,
                            'location' => $request->location,
                            'skills' => $request->skills,
                        ]
                    ],
                    'pagination' => [
                        'current_page' => $opportunities->currentPage(),
                        'per_page' => $opportunities->perPage(),
                        'total' => $opportunities->total(),
                        'last_page' => $opportunities->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve opportunities',
                'errors' => (object)[
                    'server' => $e->getMessage(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get single opportunity details
     */
    public function getOpportunityDetails($id)
    {
        try {
            $user = Auth::user();

            $opportunity = Post::with(['user', 'category', 'subcategory'])
                ->whereIn('category_id', [5, 6])
                ->where('id', $id)
                ->whereNull('original_post_id')
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if (!$opportunity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Opportunity not found',
                    'errors' => (object)['opportunity' => 'Not found']
                ], 404);
            }

            // Record view
            if ($user) {
                PostView::firstOrCreate(
                    ['post_id' => $opportunity->id, 'user_id' => $user->id],
                    ['ip_address' => request()->ip()]
                );
                $opportunity->increment('views_count');
            }

            // Get similar opportunities
            $similarOpportunities = $this->getSimilarOpportunities($opportunity, $user);

            return response()->json([
                'success' => true,
                'message' => 'Opportunity details retrieved successfully',
                'data' => [
                    'opportunity' => $this->formatJobPost($opportunity, $user),
                    'similar_opportunities' => $similarOpportunities,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve opportunity details',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get similar opportunities based on skills, location, type
     */
    private function getSimilarOpportunities($opportunity, $user)
    {
        $query = Post::whereIn('category_id', [5, 6])
            ->where('id', '!=', $opportunity->id)
            ->whereNull('original_post_id')
            ->where('is_active', true)
            ->where('is_published', true);

        // Match by category (same type)
        if ($opportunity->category_id) {
            $query->where('category_id', $opportunity->category_id);
        }

        // Match by skills
        if ($opportunity->skills_required) {
            $skills = is_array($opportunity->skills_required) 
                ? $opportunity->skills_required 
                : (json_decode($opportunity->skills_required, true) ?? []);
            
            if (!empty($skills)) {
                $skillNames = array_column($this->getSkillNames($skills), 'name');
                $query->where(function($q) use ($skillNames) {
                    foreach ($skillNames as $skill) {
                        $q->orWhere('skills_required', 'LIKE', '%' . $skill . '%');
                    }
                });
            }
        }

        // Match by location
        if ($opportunity->job_location) {
            $query->orWhere('job_location', 'LIKE', '%' . $opportunity->job_location . '%');
        }

        return $query->limit(5)
            ->get()
            ->map(function($similar) use ($user) {
                return $this->formatJobPost($similar, $user);
            });
    }

    /**
     * Get filter counts for opportunities
     */
    private function getOpportunityFilterCounts($request)
    {
        $baseQuery = Post::whereIn('category_id', [5, 6])
            ->where('is_active', true)
            ->whereNull('original_post_id')
            ->where('is_published', true);

        // Exclude expired unless requested
        if (!$request->show_expired) {
            $baseQuery->where(function($q) {
                $q->whereNull('application_deadline')
                  ->orWhere('application_deadline', '>=', Carbon::now());
            });
        }

        return [
            'total' => $baseQuery->count(),
            'by_type' => [
                'mini_mission' => (clone $baseQuery)->where('category_id', 5)->count(),
                'internship' => (clone $baseQuery)->where('category_id', 6)->count(),
            ],
            'by_work_mode' => [
                'onsite' => (clone $baseQuery)->where('work_mode', 'onsite')->count(),
                'remote' => (clone $baseQuery)->where('work_mode', 'remote')->count(),
                'hybrid' => (clone $baseQuery)->where('work_mode', 'hybrid')->count(),
            ],
        ];
    }

    // ============================================
    // 2. OPPORTUNITIES POSTED BY USER API
    // ============================================

    /**
     * Get opportunities posted by the authenticated user
     */
    public function getPostedOpportunities(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'type' => 'nullable|in:all,mini_mission,internship',
                'status' => 'nullable|in:active,expired,all',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
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

            $perPage = $request->per_page ?? 20;
            $page = $request->page ?? 1;

            $query = Post::where('user_id', $user->id)
                ->whereIn('category_id', [5, 6]) 
                ->whereNull('original_post_id')
                ->orderBy('created_at', 'desc');

            // Filter by type (category)
            if ($request->filled('type') && $request->type != 'all') {
                if ($request->type == 'mini_mission') {
                    $query->where('category_id', 5);
                } elseif ($request->type == 'internship') {
                    $query->where('category_id', 6);
                }
            }

            // Filter by status
            if ($request->filled('status') && $request->status != 'all') {
                if ($request->status == 'active') {
                    $query->where(function($q) {
                        $q->whereNull('application_deadline')
                          ->orWhere('application_deadline', '>=', Carbon::now());
                    });
                } elseif ($request->status == 'expired') {
                    $query->where('application_deadline', '<', Carbon::now());
                }
            }

            $opportunities = $query->paginate($perPage, ['*'], 'page', $page);

            // Get application counts for each opportunity
            $formattedOpportunities = $opportunities->map(function($opportunity) use ($user) {
                $formatted = $this->formatJobPost($opportunity, $user);
                
                // Add application stats
                $applications = UserMessage::where('listing_id', $opportunity->id)
                    ->where('chat_type_id', function($query) {
                        $query->select('id')->from('chat_types')->where('slug', 'job_application');
                    })
                    ->with('from_id')
                    ->get();

                $formatted['application_stats'] = [
                    'total_applications' => $applications->count(),
                    'unique_applicants' => $applications->pluck('from_id')->unique()->count(),
                    'applications' => $applications->map(function($app) {
                        $applicantData = $this->getEntityData($app->from_id);
                        return [
                            'id' => $app->id,
                            'applicant' => $applicantData,
                            'message' => $app->message_txt,
                            'applied_at' => $app->created_at,
                            'applied_at_formatted' => $app->created_at->diffForHumans(),
                        ];
                    }),
                ];

                return $formatted;
            });

            return response()->json([
                'success' => true,
                'message' => 'Posted opportunities retrieved successfully',
                'data' => [
                    'opportunities' => $formattedOpportunities,
                    'stats' => [
                        'total' => $opportunities->total(),
                        'active' => $opportunities->where('application_deadline', '>=', Carbon::now())->count(),
                        'expired' => $opportunities->where('application_deadline', '<', Carbon::now())->count(),
                    ],
                    'pagination' => [
                        'current_page' => $opportunities->currentPage(),
                        'per_page' => $opportunities->perPage(),
                        'total' => $opportunities->total(),
                        'last_page' => $opportunities->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posted opportunities',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    // ============================================
    // 3. OPPORTUNITIES APPLIED BY USER API
    // ============================================

    /**
     * Get opportunities applied by the authenticated user
     */
    public function getAppliedOpportunities(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:all,pending,under_review',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
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

            $perPage = $request->per_page ?? 20;
            $page = $request->page ?? 1;

            // Get all applications (messages) sent by this user for category 5 or 6 posts
            $applicationsQuery = UserMessage::where('from_id', $user->id)
                ->where('chat_type_id', function($query) {
                    $query->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->whereHas('post', function($query) {
                    $query->whereIn('category_id', [5, 6]);
                })
                ->with(['post', 'chatSession'])
                ->orderBy('created_at', 'desc');

            $applications = $applicationsQuery->paginate($perPage, ['*'], 'page', $page);

            $formattedApplications = $applications->map(function($application) use ($user) {
                $opportunity = $application->post;
                
                if (!$opportunity) {
                    return null;
                }

                $opportunityData = $this->formatJobPost($opportunity, $user);
                
                // Get chat session messages for this application
                $chatMessages = UserMessage::where('chat_session_id', $application->chat_session_id)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function($msg) use ($user) {
                        $senderData = $this->getEntityData($msg->from_id);
                        return [
                            'id' => $msg->id,
                            'message' => $msg->message_txt,
                            'is_from_me' => $msg->from_id == $user->id,
                            'sender_name' => $senderData['name'] ?? 'Unknown',
                            'sender_image' => $senderData['image'] ?? null,
                            'created_at' => $msg->created_at,
                            'created_at_formatted' => $msg->created_at->diffForHumans(),
                        ];
                    });

                // Determine application status
                $status = 'Applied';
                $lastMessage = $chatMessages->last();
                if ($lastMessage && $lastMessage['is_from_me'] == false) {
                    $status = 'viewed';
                }

                // Parse JSON data if available
                $applicationData = [];
                if ($application->json_data) {
                    $applicationData = json_decode($application->json_data, true) ?: [];
                }

                return [
                    'id' => $application->id,
                    'opportunity' => $opportunityData,
                    'application_message' => $application->message_txt,
                    'portfolio_links' => $applicationData['portfolio_links'] ?? [],
                    'resume_link' => $applicationData['resume_link'] ?? null,
                    'additional_notes' => $applicationData['additional_notes'] ?? null,
                    'answers' => $applicationData['answers'] ?? [],
                    'applied_at' => $application->created_at,
                    'applied_at_formatted' => $application->created_at->diffForHumans(),
                    'status' => $status,
                    'chat_session_id' => $application->chat_session_id,
                    'conversation' => $chatMessages,
                    'unread_count' => $chatMessages->where('is_from_me', false)
                        ->where('is_read', false)->count(),
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'message' => 'Applied opportunities retrieved successfully',
                'data' => [
                    'applications' => $formattedApplications,
                    'stats' => [
                        'total' => $applications->total(),
                        'pending' => $formattedApplications->where('status', 'pending')->count(),
                        'under_review' => $formattedApplications->where('status', 'under_review')->count(),
                    ],
                    'pagination' => [
                        'current_page' => $applications->currentPage(),
                        'per_page' => $applications->perPage(),
                        'total' => $applications->total(),
                        'last_page' => $applications->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve applied opportunities',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    // ============================================
    // 4. APPLY TO OPPORTUNITY API
    // ============================================

    /**
     * Apply to an opportunity (mini_mission or internship)
     */
    public function applyToOpportunity(Request $request, $opportunityId)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'application_message' => 'required|string|min:10|max:5000',
                'portfolio_links' => 'nullable|array',
                'portfolio_links.*' => 'url|max:500',
                'resume_link' => 'nullable|url|max:500',
                'additional_notes' => 'nullable|string|max:1000',
                'answers' => 'nullable|array',
                'answers.*.question' => 'required_with:answers|string',
                'answers.*.answer' => 'required_with:answers|string',
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

            // Get the opportunity post
            $opportunity = Post::where('id', $opportunityId)
                ->whereIn('category_id', [5, 6])
                ->where('is_active', true)
                ->whereNull('original_post_id')
                ->where('is_published', true)
                ->first();

            if (!$opportunity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Opportunity not found',
                    'errors' => (object)['opportunity' => 'Not found']
                ], 404);
            }

            // Check if expired
            if ($opportunity->application_deadline && Carbon::parse($opportunity->application_deadline)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application deadline has passed',
                    'errors' => (object)['opportunity' => 'This opportunity has expired']
                ], 400);
            }

            // Check if already applied
            $existingApplication = UserMessage::where('listing_id', $opportunity->id)
                ->where('from_id', $user->id)
                ->where('chat_type_id', function($query) {
                    $query->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->exists();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already applied to this opportunity',
                    'errors' => (object)['application' => 'Already applied']
                ], 400);
            }

            // Prevent self-application
            if ($user->id == $opportunity->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot apply to your own posting',
                    'errors' => (object)['opportunity' => 'You cannot apply to your own']
                ], 400);
            }

            // Get user data
            $userData = $this->getEntityData($user->id);
            
            // Get poster data
            $posterData = $this->getEntityData($opportunity->user_id);
            
            if (!$posterData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Poster not found',
                    'errors' => (object)['opportunity' => 'Invalid posting']
                ], 404);
            }

            // Get chat type
            $chatType = \App\Models\ChatType::where('slug', 'job_application')->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat type not configured',
                    'errors' => (object)['system' => 'Application system error']
                ], 500);
            }

            // Build application message
            $fullMessage = $this->buildApplicationMessage(
                $request->application_message,
                $request->portfolio_links ?? [],
                $request->resume_link,
                $request->additional_notes,
                $request->answers ?? []
            );

            // Generate session ID
            $sessionId = $this->generateApplicationSessionId(
                $user->id,
                $opportunity->user_id,
                $opportunity->id,
                $chatType->id
            );

            DB::beginTransaction();

            try {
                // Create or update chat session
                $chatSession = \App\Models\ChatSession::updateOrCreate(
                    ['id' => $sessionId],
                    [
                        'user1_id' => min($user->id, $opportunity->user_id),
                        'user2_id' => max($user->id, $opportunity->user_id),
                        'post_id' => $opportunity->id,
                        'chat_type_id' => $chatType->id,
                        'last_message_at' => now(),
                        'last_message' => Str::limit($request->application_message, 100),
                        'unread_count' => 1,
                        'is_active' => true
                    ]
                );

                // Create the application message
                $message = UserMessage::create([
                    'listing_id' => $opportunity->id,
                    'listing_title' => $opportunity->title,
                    'from_id' => $user->id,
                    'to_id' => $opportunity->user_id,
                    'to_email' => $posterData['email'],
                    'to_name' => $posterData['name'],
                    'from_name' => $userData['name'],
                    'from_email' => $userData['email'],
                    'from_phone' => $userData['phone'] ?? null,
                    'message_txt' => $fullMessage,
                    'subject' => "Application: {$opportunity->title}",
                    'chat_type_id' => $chatType->id,
                    'chat_session_id' => $sessionId,
                    'status' => 'active',
                    'is_read' => false,
                    'message_type' => 'job_application',
                    'json_data' => json_encode([
                        'portfolio_links' => $request->portfolio_links ?? [],
                        'resume_link' => $request->resume_link,
                        'additional_notes' => $request->additional_notes,
                        'answers' => $request->answers ?? [],
                        'applied_at' => now()->toDateTimeString(),
                    ]),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Application submitted successfully',
                    'data' => [
                        'application_id' => $message->id,
                        'chat_session_id' => $sessionId,
                        'opportunity' => [
                            'id' => $opportunity->id,
                            'title' => $opportunity->title,
                            'type' => $opportunity->category_id == 5 ? 'mini_mission' : 'internship',
                            'company' => $opportunity->company_name ?? $posterData['name'],
                        ],
                        'message' => [
                            'id' => $message->id,
                            'content' => $message->message_txt,
                            'sent_at' => $message->created_at,
                            'sent_at_formatted' => $message->created_at->diffForHumans(),
                        ]
                    ]
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Build application message from all inputs
     */
    private function buildApplicationMessage($message, $portfolioLinks, $resumeLink, $additionalNotes, $answers)
    {
        $fullMessage = $message . "\n\n";

        if (!empty($portfolioLinks)) {
            $fullMessage .= "📁 Portfolio Links:\n";
            foreach ($portfolioLinks as $index => $link) {
                $fullMessage .= ($index + 1) . ". {$link}\n";
            }
            $fullMessage .= "\n";
        }

        if ($resumeLink) {
            $fullMessage .= "📄 Resume: {$resumeLink}\n\n";
        }

        if (!empty($answers)) {
            $fullMessage .= "❓ Additional Information:\n";
            foreach ($answers as $qa) {
                $fullMessage .= "Q: {$qa['question']}\n";
                $fullMessage .= "A: {$qa['answer']}\n\n";
            }
        }

        if ($additionalNotes) {
            $fullMessage .= "📝 Additional Notes:\n{$additionalNotes}\n";
        }

        return $fullMessage;
    }

    /**
     * Generate unique session ID for application chat
     */
    private function generateApplicationSessionId($userId, $posterId, $postId, $chatTypeId)
    {
        $parts = [
            min($userId, $posterId),
            max($userId, $posterId),
            $postId,
            $chatTypeId
        ];
        return md5(implode('_', $parts));
    }

    /**
     * Get application questions for an opportunity
     */
    public function getApplicationQuestions($opportunityId)
    {
        try {
            $opportunity = Post::find($opportunityId);
            
            if (!$opportunity || !in_array($opportunity->category_id, [5, 6])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Opportunity not found',
                    'errors' => (object)['opportunity' => 'Not found']
                ], 404);
            }

            // Default questions
            $questions = [
                [
                    'id' => 'experience',
                    'question' => 'Do you have relevant experience for this role?',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'id' => 'start_date',
                    'question' => 'When can you start?',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'id' => 'why_you',
                    'question' => 'Why are you the best candidate for this position?',
                    'type' => 'textarea',
                    'required' => true,
                ],
            ];

            // Add role-specific questions
            if ($opportunity->category_id == 6) { // Internship
                $questions[] = [
                    'id' => 'duration',
                    'question' => 'Can you commit to the full internship duration?',
                    'type' => 'boolean',
                    'required' => true,
                ];
            } elseif ($opportunity->category_id == 5) { // Mini Mission
                $questions[] = [
                    'id' => 'portfolio',
                    'question' => 'Please share links to similar work you have done',
                    'type' => 'urls',
                    'required' => false,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Application questions retrieved',
                'data' => [
                    'opportunity_id' => $opportunity->id,
                    'opportunity_title' => $opportunity->title,
                    'type' => $opportunity->category_id == 5 ? 'mini_mission' : 'internship',
                    'questions' => $questions,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve questions',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }
}