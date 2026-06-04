<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\User;
use App\UserMessage;
use App\Models\ChatType;
use App\Models\ChatSession;
use App\PostLike;
use App\PostView;
use App\JobSkill;
use App\UserConnection;
use App\BlockedUser;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class JobOpportunityController extends Controller
{
    // Status constants
    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED  = 'blocked';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ============================================
    // PRIVATE HELPERS
    // ============================================

    private function getEntityData($id)
    {
        if (!$id) return null;

        $user = User::find($id);
        if (!$user) return null;

        if ($user->usertype === 'company') {
            return [
                'id'          => $user->id,
                'name'        => $user->company_name ?? $user->name ?? 'Unknown Company',
                'email'       => $user->email,
                'usertype'    => 'company',
                'image'       => $user->company_logo
                                    ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('company_logos/' . $user->company_logo) : asset('company_logos/' . $user->company_logo))
                                    : ($user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null),
                'slug'        => $user->company_slug,
                'description' => $user->company_description,
                'location'    => $user->company_location ?? $user->location,
                'website'     => $user->company_website,
                'headline'    => $user->company_description,
                'phone'       => $user->phone,
                'entity_type' => 'company',
            ];
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: ($user->name ?? 'Unknown User');

        return [
            'id'                => $user->id,
            'name'              => $name,
            'email'             => $user->email,
            'usertype'          => $user->usertype ?? 'user',
            'image'             => $user->image ? (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) : null,
            'headline'          => $user->headline,
            'location'          => $user->location,
            'portfolio_website' => $user->portfolio_website,
            'phone'             => $user->phone,
            'entity_type'       => 'user',
        ];
    }

    private function getSkillNames($skillIds)
    {
        if (empty($skillIds)) return [];

        $integerIds = [];

        if (is_array($skillIds)) {
            foreach ($skillIds as $id) {
                if (is_numeric($id)) $integerIds[] = (int) $id;
            }
        } elseif (is_string($skillIds)) {
            $decoded = json_decode($skillIds, true);
            if (is_array($decoded)) {
                foreach ($decoded as $id) {
                    if (is_numeric($id)) $integerIds[] = (int) $id;
                }
            } elseif (strpos($skillIds, ',') !== false) {
                foreach (explode(',', $skillIds) as $part) {
                    $trimmed = trim($part);
                    if (is_numeric($trimmed)) $integerIds[] = (int) $trimmed;
                }
            } elseif (is_numeric($skillIds)) {
                $integerIds[] = (int) $skillIds;
            }
        }

        $integerIds = array_unique($integerIds);
        if (empty($integerIds)) return [];

        return JobSkill::whereIn('id', $integerIds)
            ->where('is_active', 1)
            ->get(['id', 'job_skill'])
            ->map(fn($skill) => ['id' => (int) $skill->id, 'name' => $skill->job_skill])
            ->toArray();
    }

    private function formatJobPost($post, $currentUser = null)
    {
        if (!$currentUser) $currentUser = Auth::user();

        $authorData = $this->getEntityData($post->user_id);

        $skills = $post->skills_required ? $this->getSkillNames($post->skills_required) : [];

        $techStack = [];
        if ($post->tech_stack) {
            $techStack = is_array($post->tech_stack)
                ? $post->tech_stack
                : (json_decode($post->tech_stack, true) ?: []);
        }

        $images = [];
        if ($post->images) {
            $imgArray = is_array($post->images) ? $post->images : (json_decode($post->images, true) ?: []);
            $images   = array_map(fn($img) => env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $img) : asset('post_images/' . $img), $imgArray);
        }

        $hasApplied = false;
        if ($currentUser && in_array($post->category_id, [5, 6, 7])) {
            $hasApplied = UserMessage::where('listing_id', $post->id)
                ->where('from_id', $currentUser->id)
                ->where('chat_type_id', fn($q) => $q->select('id')->from('chat_types')->where('slug', 'job_application'))
                ->exists();
        }

        $isLiked = $currentUser
            ? PostLike::where('post_id', $post->id)->where('user_id', $currentUser->id)->exists()
            : false;

        $connectionStatus = $this->checkConnectionStatus($currentUser, $post->user_id);

        $typeLabel = match ($post->category_id) {
            5 => 'Mini Mission',
            6 => 'Internship',
            7 => 'Fresher Role',
            default => '',
        };

        $formatted = [
            'id'                => $post->id,
            'title'             => $post->title,
            'content'           => $post->content,
            'short_description' => $post->short_description,
            'category_id'       => $post->category_id,
            'subcategory_id'    => $post->subcategory_id,
            'type_label'        => $typeLabel,
            'is_job_post'       => $post->is_job_post ?? in_array($post->category_id, [5, 6, 7]),
            'images'            => $images,
            'created_at'        => $post->created_at,
            'created_at_formatted' => $post->created_at->diffForHumans(),
            'updated_at'        => $post->updated_at,
            'is_published'      => $post->is_published,
            'is_active'      => $post->is_active,
            'stats'             => [
                'views'    => $post->views_count ?? 0,
                'likes'    => $post->likes_count ?? 0,
                'comments' => $post->comments_count ?? 0,
                'shares'   => $post->shares_count ?? 0,
            ],
            'user_interaction'  => [
                'is_liked'          => $isLiked,
                'has_applied'       => $hasApplied,
                'connection_status' => $connectionStatus,
            ],
            'author' => $authorData ? [
                'id'          => $authorData['id'],
                'name'        => $authorData['name'],
                'usertype'    => $authorData['usertype'],
                'image'       => $authorData['image'],
                'location'    => $authorData['location'] ?? null,
                'entity_type' => $authorData['entity_type'],
            ] : null,
        ];

        if (in_array($post->category_id, [5, 6, 7])) {
            $formatted['job_details'] = [
                'role_type'                      => $post->role_type,
                'work_mode'                      => $post->work_mode,
                'key_deliverables'               => $post->key_deliverables,
                'internship_duration'            => $post->internship_duration,
                'duration_new'                   => $post->internship_duration ?? $post->mini_duration,
                'experience_required'            => $post->experience_required,
                'skills_required'                => $skills,
                'tech_stack'                     => $techStack,
                'benefits'                       => $post->benefits,
                'salary_range'                   => $post->salary_range,
                'company_name'                   => $post->company_name,
                'job_location'                   => $post->job_location,
                'application_url'                => $post->application_url,
                'application_deadline'           => $post->application_deadline,
                'application_deadline_formatted' => $post->application_deadline
                                                        ? Carbon::parse($post->application_deadline)->format('d M Y')
                                                        : null,
                'is_expired'                     => $post->application_deadline
                                                        ? Carbon::parse($post->application_deadline)->isPast()
                                                        : false,
            ];

            if ($post->category_id == 5) {
                $formatted['job_details']['deliverables']      = $post->deliverables;
                $formatted['job_details']['timeline_start']    = $post->timeline_start;
                $formatted['job_details']['timeline_end']      = $post->timeline_end;
                $formatted['job_details']['type']              = 'mini_mission';
            } elseif ($post->category_id == 6) {
                $formatted['job_details']['stipend_amount']           = $post->stipend_amount;
                $formatted['job_details']['stipend_currency']         = $post->stipend_currency;
                $formatted['job_details']['convertible_to_full_time'] = $post->convertible_to_full_time;
                $formatted['job_details']['type']                     = 'internship';
            } elseif ($post->category_id == 7) {
                $formatted['job_details']['stipend_amount']           = $post->ctc_amount;
                $formatted['job_details']['stipend_currency']         = $post->ctc_currency;
                $formatted['job_details']['convertible_to_full_time'] = $post->application_deadline;
                $formatted['job_details']['type']                     = 'fresherrole';
            }
        }

        return $formatted;
    }

    private function checkConnectionStatus($currentUser, $targetUserId)
    {
        if (!$currentUser || $currentUser->id == $targetUserId) return 'self';

        $targetUser = User::find($targetUserId);
        $isCompany  = $targetUser && $targetUser->usertype === 'company';

        if ($isCompany) {
            $isFollowing = UserConnection::where('follower_id', $currentUser->id)
                ->where('following_id', $targetUserId)
                ->where('status', self::STATUS_ACCEPTED)
                ->exists();
            return $isFollowing ? 'following' : 'none';
        }

        $connection = UserConnection::where(function ($q) use ($currentUser, $targetUserId) {
                $q->where('follower_id', $currentUser->id)->where('following_id', $targetUserId);
            })
            ->orWhere(function ($q) use ($currentUser, $targetUserId) {
                $q->where('follower_id', $targetUserId)->where('following_id', $currentUser->id);
            })
            ->first();

        if (!$connection) return 'none';

        if ($connection->follower_id == $currentUser->id) return $connection->status;

        if ($connection->status == self::STATUS_ACCEPTED) return self::STATUS_ACCEPTED;
        if ($connection->status == self::STATUS_PENDING)  return 'pending_from_them';
        if ($connection->status == self::STATUS_BLOCKED)  return 'blocked_by_them';

        return 'none';
    }

    private function canChat($user1Id, $user2Id)
    {
        if (!$user1Id || !$user2Id) return false;

        if (BlockedUser::isBlocked($user1Id, $user2Id) || BlockedUser::isBlocked($user2Id, $user1Id)) {
            return false;
        }

        return !UserConnection::where(function ($q) use ($user1Id, $user2Id) {
                $q->where('follower_id', $user1Id)->where('following_id', $user2Id)->where('status', self::STATUS_BLOCKED);
            })
            ->orWhere(function ($q) use ($user1Id, $user2Id) {
                $q->where('follower_id', $user2Id)->where('following_id', $user1Id)->where('status', self::STATUS_BLOCKED);
            })
            ->exists();
    }

    // ============================================
    // 1. GET OPPORTUNITIES
    // ============================================
    
    
    public function changejobStatus(Request $request, $id)
    {
        try {
            // Find the job post
            $post = Post::where('id', $id)
                ->whereIn('category_id', [5, 6, 7]) // Only job-related categories
                ->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job post not found or invalid job type'
                ], 404);
            }

            // Check authorization - only post owner or admin can change status
            $currentUser = Auth::user();
            if (!$currentUser || ($post->user_id != $currentUser->id && !$currentUser->hasRole('admin'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to change this job status'
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
                'reason' => 'nullable|string|max:500' // Optional reason for status change
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update status
            $oldStatus = $post->is_active;
            $post->is_active = $request->is_active;
            $post->save();

            // Return response with updated job data using your existing format method
            $formattedJob = $this->formatJobPost($post, $currentUser);

            return response()->json([
                'success' => true,
                'message' => $request->is_active ? 'Job post activated successfully' : 'Job post deactivated successfully',
                'data' => [
                    'job' => $formattedJob,
                    'status_changed' => [
                        'old_status' => (bool) $oldStatus,
                        'new_status' => (bool) $post->is_active
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change job status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOpportunities(Request $request)
    {
        
       
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'type'         => 'nullable|in:mini_mission,internship',
                'work_mode'    => 'nullable|in:onsite,remote,hybrid',
                'location'     => 'nullable|string|max:255',
                'min_salary'   => 'nullable|numeric',
                'max_salary'   => 'nullable|numeric',
                'skills'       => 'nullable|array',
                'skills.*'     => 'string',
                'search'       => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'sort_by'      => 'nullable|in:latest,deadline,salary_low,salary_high,popular',
                'page'         => 'nullable|integer|min:1',
                'per_page'     => 'nullable|integer|min:1|max:50',
                'show_expired' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => (object) $errors], 422);
            }

            $perPage = $request->per_page ?? 100;
            $page    = $request->page ?? 1;

            $query = Post::with(['user', 'category', 'subcategory'])
                ->whereIn('category_id', [5, 6, 7])
                ->where('is_active', true)
                ->whereNull('original_post_id')
                ->where('is_published', true);

            // if ($request->filled('type')) {
            //     $query->where('category_id', $request->type == 'mini_mission' ? 5 : [6, 7]);
            // }
            
             if ($request->filled('type')) {
                if ($request->type == 'mini_mission') {
                    $query->where('category_id', 5);
                } else {
                   
                    $query->whereIn('category_id', [6, 7]);
                }
            }

            if ($request->filled('work_mode'))    $query->where('work_mode', $request->work_mode);
            if ($request->filled('location'))     $query->where('job_location', 'LIKE', '%' . $request->location . '%');
            if ($request->filled('company_name')) $query->where('company_name', 'LIKE', '%' . $request->company_name . '%');

            if ($request->filled('skills') && is_array($request->skills)) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->skills as $skill) {
                        $q->orWhere('skills_required', 'LIKE', '%' . $skill . '%');
                    }
                });
            }

            if ($request->filled('min_salary') || $request->filled('max_salary')) {
                $query->where(function ($q) use ($request) {
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

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title',             'LIKE', "%{$search}%")
                      ->orWhere('content',         'LIKE', "%{$search}%")
                      ->orWhere('company_name',    'LIKE', "%{$search}%")
                      ->orWhere('short_description', 'LIKE', "%{$search}%")
                      ->orWhere('key_deliverables', 'LIKE', "%{$search}%");
                });
            }

            if (!$request->show_expired) {
                $query->where(function ($q) {
                    $q->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', Carbon::now());
                });
            }

            switch ($request->sort_by) {
                case 'deadline':    $query->orderByRaw('application_deadline ASC NULLS LAST'); break;
                case 'salary_low':  $query->orderByRaw('COALESCE(stipend_amount, ctc_amount, 0) ASC'); break;
                case 'salary_high': $query->orderByRaw('COALESCE(stipend_amount, ctc_amount, 0) DESC'); break;
                case 'popular':     $query->orderByRaw('(views_count * 1 + likes_count * 2 + comments_count * 3) DESC'); break;
                default:            $query->orderBy('created_at', 'desc'); break;
            }

            $opportunities         = $query->paginate($perPage, ['*'], 'page', $page);
            $formattedOpportunities = $opportunities->map(fn($opp) => $this->formatJobPost($opp, $user));
            $filterCounts          = $this->getOpportunityFilterCounts($request);

            return response()->json([
                'success' => true,
                'message' => 'Opportunities retrieved successfully',
                'data'    => [
                    'opportunities' => $formattedOpportunities,
                    'filters'       => [
                        'available' => $filterCounts,
                        'active'    => [
                            'type'      => $request->type ?? 'all',
                            'work_mode' => $request->work_mode,
                            'location'  => $request->location,
                            'skills'    => $request->skills,
                        ]
                    ],
                    'pagination' => [
                        'current_page' => $opportunities->currentPage(),
                        'per_page'     => $opportunities->perPage(),
                        'total'        => $opportunities->total(),
                        'last_page'    => $opportunities->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve opportunities', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }

    // ============================================
    // 2. GET OPPORTUNITY DETAILS
    // ============================================

    public function getOpportunityDetails($id)
    {
        try {
            $user = Auth::user();

            $opportunity = Post::with(['user', 'category', 'subcategory'])
                ->whereIn('category_id', [5, 6, 7])
                ->where('id', $id)
                ->whereNull('original_post_id')
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if (!$opportunity) {
                return response()->json(['success' => false, 'message' => 'Opportunity not found', 'errors' => (object) ['opportunity' => 'Not found']], 404);
            }

            if ($user) {
                PostView::firstOrCreate(
                    ['post_id' => $opportunity->id, 'user_id' => $user->id],
                    ['ip_address' => request()->ip()]
                );
                $opportunity->increment('views_count');
            }

            return response()->json([
                'success' => true,
                'message' => 'Opportunity details retrieved successfully',
                'data'    => [
                    'opportunity'          => $this->formatJobPost($opportunity, $user),
                    'similar_opportunities' => $this->getSimilarOpportunities($opportunity, $user),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve opportunity details', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }

    private function getSimilarOpportunities($opportunity, $user)
    {
        $query = Post::whereIn('category_id', [5, 6, 7])
            ->where('id', '!=', $opportunity->id)
            ->whereNull('original_post_id')
            ->where('is_active', true)
            ->where('is_published', true);

        if ($opportunity->category_id) {
            $query->where('category_id', $opportunity->category_id);
        }

        if ($opportunity->skills_required) {
            $skills     = is_array($opportunity->skills_required) ? $opportunity->skills_required : (json_decode($opportunity->skills_required, true) ?? []);
            $skillNames = array_column($this->getSkillNames($skills), 'name');
            if (!empty($skillNames)) {
                $query->where(function ($q) use ($skillNames) {
                    foreach ($skillNames as $skill) {
                        $q->orWhere('skills_required', 'LIKE', '%' . $skill . '%');
                    }
                });
            }
        }

        if ($opportunity->job_location) {
            $query->orWhere('job_location', 'LIKE', '%' . $opportunity->job_location . '%');
        }

        return $query->limit(5)->get()->map(fn($similar) => $this->formatJobPost($similar, $user));
    }

    private function getOpportunityFilterCounts($request)
    {
        $baseQuery = Post::whereIn('category_id', [5, 6, 7])
            ->where('is_active', true)
            ->whereNull('original_post_id')
            ->where('is_published', true);

        if (!$request->show_expired) {
            $baseQuery->where(function ($q) {
                $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', Carbon::now());
            });
        }

        return [
            'total'       => $baseQuery->count(),
            'by_type'     => [
                'mini_mission' => (clone $baseQuery)->where('category_id', 5)->count(),
                'internship'   => (clone $baseQuery)->where('category_id', 6)->count(),
            ],
            'by_work_mode' => [
                'onsite' => (clone $baseQuery)->where('work_mode', 'onsite')->count(),
                'remote' => (clone $baseQuery)->where('work_mode', 'remote')->count(),
                'hybrid' => (clone $baseQuery)->where('work_mode', 'hybrid')->count(),
            ],
        ];
    }

    // ============================================
    // 3. POSTED OPPORTUNITIES BY USER
    // ============================================

    public function getPostedOpportunities(Request $request)
    {
        

        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'type'     => 'nullable|in:all,mini_mission,internship',
                'status'   => 'nullable|in:active,expired,all',
                'page'     => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => (object) $errors], 422);
            }

            $perPage = $request->per_page ?? 100;
            $page    = $request->page ?? 1;

            $query = Post::where('user_id', $user->id)
                ->whereIn('category_id', [5, 6, 7])
                ->whereNull('original_post_id')
                ->orderBy('created_at', 'desc');

            // if ($request->filled('type') && $request->type != 'all') {
            //     $query->where('category_id', $request->type == 'mini_mission' ? 5 : 6);
            // }
            
            if ($request->filled('type') && $request->type != 'all') {
                if ($request->type == 'mini_mission') {
                    $query->where('category_id', 5);
                } else {
                    
                  
                    $query->whereIn('category_id', [6, 7]);
                }
            }

            if ($request->filled('status') && $request->status != 'all') {
                if ($request->status == 'active') {
                    $query->where(fn($q) => $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', Carbon::now()));
                } else {
                    $query->where('application_deadline', '<', Carbon::now());
                }
            }

            $opportunities = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedOpportunities = $opportunities->map(function ($opportunity) use ($user) {
                $formatted    = $this->formatJobPost($opportunity, $user);
                $applications = UserMessage::where('listing_id', $opportunity->id)
                    ->where('chat_type_id', fn($q) => $q->select('id')->from('chat_types')->where('slug', 'job_application'))
                    ->get();

                $formatted['application_stats'] = [
                    'total_applications' => $applications->count(),
                    'unique_applicants'  => $applications->pluck('from_id')->unique()->count(),
                    'applications'       => $applications->map(function ($app) {
                        $applicantData = $this->getEntityData($app->from_id);
                        return [
                            'id'                   => $app->id,
                            'applicant'            => $applicantData,
                            'message'              => $app->message_txt,
                            'applied_at'           => $app->created_at,
                            'applied_at_formatted' => $app->created_at->diffForHumans(),
                        ];
                    }),
                ];

                return $formatted;
            });

            return response()->json([
                'success' => true,
                'message' => 'Posted opportunities retrieved successfully',
                'data'    => [
                    'opportunities' => $formattedOpportunities,
                    'stats'         => [
                        'total'   => $opportunities->total(),
                        'active'  => $opportunities->filter(fn($o) => !$o->application_deadline || Carbon::parse($o->application_deadline) >= Carbon::now())->count(),
                        'expired' => $opportunities->filter(fn($o) => $o->application_deadline && Carbon::parse($o->application_deadline) < Carbon::now())->count(),
                    ],
                    'pagination' => [
                        'current_page' => $opportunities->currentPage(),
                        'per_page'     => $opportunities->perPage(),
                        'total'        => $opportunities->total(),
                        'last_page'    => $opportunities->lastPage(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve posted opportunities', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }

   
    
    /**
     * Get applied opportunities with proper status tracking
     */
    public function getAppliedOpportunities(Request $request)
    {
        try {
            $user = Auth::user();
    
            $validator = Validator::make($request->all(), [
                'status'   => 'nullable|in:all,pending,under_review',
                'page'     => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => (object) $errors], 422);
            }
    
            $perPage = $request->per_page ?? 100;
            $page    = $request->page ?? 1;
    
            $applications = UserMessage::where('from_id', $user->id)
                ->where('chat_type_id', fn($q) => $q->select('id')->from('chat_types')->where('slug', 'job_application'))
                ->whereHas('post', fn($q) => $q->whereIn('category_id', [5, 6, 7]))
                ->with(['post', 'chatSession'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
    
            // Group by chat session (all applications for the same company go to one conversation)
            $grouped = [];
            foreach ($applications as $application) {
                $sid = $application->chat_session_id;
                if (!isset($grouped[$sid]) || $application->created_at > $grouped[$sid]->created_at) {
                    $grouped[$sid] = $application;
                }
            }
    
            $formattedApplications = collect($grouped)->map(function ($application) use ($user) {
                $opportunity = $application->post;
                if (!$opportunity) return null;
    
                $opportunityData = $this->formatJobPost($opportunity, $user);
    
                // Get all messages for this chat session (all applications with this company)
                $chatMessages = UserMessage::where('chat_session_id', $application->chat_session_id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->sortBy('created_at')
                    ->map(function ($msg) use ($user) {
                        $senderData = $this->getEntityData($msg->from_id);
                        return [
                            'id'                   => $msg->id,
                            'message'              => $msg->message_txt,
                            'is_from_me'           => $msg->from_id == $user->id,
                            'sender_name'          => $senderData['name'] ?? 'Unknown',
                            'sender_image'         => $senderData['image'] ?? null,
                            'created_at'           => $msg->created_at,
                            'created_at_formatted' => $msg->created_at->diffForHumans(),
                            'is_read'              => $msg->is_read,
                            'read_at'              => $msg->read_at,
                        ];
                    })->values();
    
                // Get the latest message in the chat
                $latestMessage = UserMessage::where('chat_session_id', $application->chat_session_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
    
                // Get all unread messages for the user
                $unreadCount = UserMessage::where('chat_session_id', $application->chat_session_id)
                    ->where('to_id', $user->id)
                    ->where('is_read', false)
                    ->count();
    
                // Determine status based on conditions
                $status = $this->determineApplicationStatus($application, $latestMessage, $user);
    
                $applicationData = $application->json_data ? (json_decode($application->json_data, true) ?: []) : [];
    
                return [
                    'id'                    => $application->id,
                    'opportunity'           => $opportunityData,
                    'application_message'   => $application->message_txt,
                    'portfolio_links'       => $applicationData['portfolio_links'] ?? [],
                    'resume_link'           => $applicationData['resume_link'] ?? null,
                    'additional_notes'      => $applicationData['additional_notes'] ?? null,
                    'answers'               => $applicationData['answers'] ?? [],
                    'applied_at'            => $application->created_at,
                    'applied_at_formatted'  => $application->created_at->diffForHumans(),
                    'status'                => $status,
                    'chat_session_id'       => $application->chat_session_id,
                    'conversation'          => $chatMessages,
                    'unread_count'          => $unreadCount,
                    'latest_message'        => $latestMessage ? [
                        'id'           => $latestMessage->id,
                        'message'      => $latestMessage->message_txt,
                        'is_from_me'   => $latestMessage->from_id == $user->id,
                        'created_at'   => $latestMessage->created_at,
                        'is_read'      => $latestMessage->is_read,
                    ] : null,
                ];
            })->filter()->values();
    
            // Filter by status if requested
            if ($request->status && $request->status != 'all') {
                $filteredApplications = $formattedApplications->filter(function($app) use ($request) {
                    if ($request->status == 'pending') {
                        return $app['status'] == 'applied';
                    }
                    if ($request->status == 'under_review') {
                        return $app['status'] == 'viewed' || $app['status'] == 'replied';
                    }
                    return true;
                });
            } else {
                $filteredApplications = $formattedApplications;
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Applied opportunities retrieved successfully',
                'data'    => [
                    'applications' => $filteredApplications->values(),
                    'stats'        => [
                        'total'        => $formattedApplications->count(),
                        'pending'      => $formattedApplications->where('status', 'applied')->count(),
                        'under_review' => $formattedApplications->whereIn('status', ['viewed', 'replied'])->count(),
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page'     => $perPage,
                        'total'        => $formattedApplications->count(),
                        'last_page'    => (int) ceil($formattedApplications->count() / $perPage),
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve applied opportunities', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }
    
    /**
     * Determine the application status based on message history
     * 
     * @param UserMessage $application The initial application message
     * @param UserMessage|null $latestMessage The latest message in the chat
     * @param User $user The authenticated user
     * @return string Status: 'applied', 'viewed', or 'replied'
     */
    private function determineApplicationStatus($application, $latestMessage, $user)
    {
        // Get all messages in this chat session
        $allMessages = UserMessage::where('chat_session_id', $application->chat_session_id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Check if recipient has replied (any message from recipient to applicant)
        $hasReplyFromRecipient = $allMessages->where('from_id', '!=', $user->id)->where('to_id', $user->id)->count() > 0;
        
        // If recipient has replied, status is 'replied'
        if ($hasReplyFromRecipient) {
            return 'replied';
        }
        
        // Check if any message from applicant has been read by recipient
        $applicantMessages = $allMessages->where('from_id', $user->id);
        $hasReadMessage = $applicantMessages->where('is_read', true)->count() > 0;
        
        if ($hasReadMessage) {
            return 'viewed';
        }
        
        // Default: just applied, no interaction yet
        return 'applied';
    }

    // ============================================
    // 5. APPLY TO OPPORTUNITY
    // ============================================

    public function applyToOpportunity(Request $request, $opportunityId)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'application_message' => 'required|string|min:10|max:5000',
                'portfolio_links'     => 'nullable|array',
                'portfolio_links.*'   => 'url|max:500',
                'resume_link'         => 'nullable|url|max:500',
                'additional_notes'    => 'nullable|string|max:1000',
                'answers'             => 'nullable|array',
                'answers.*.question'  => 'required_with:answers|string',
                'answers.*.answer'    => 'required_with:answers|string',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => (object) $errors], 422);
            }

            $opportunity = Post::where('id', $opportunityId)
                ->whereIn('category_id', [5, 6, 7])
                ->where('is_active', true)
                ->whereNull('original_post_id')
                ->where('is_published', true)
                ->first();

            if (!$opportunity) {
                return response()->json(['success' => false, 'message' => 'Opportunity not found', 'errors' => (object) ['opportunity' => 'Not found']], 404);
            }

            // if ($opportunity->application_deadline && Carbon::parse($opportunity->application_deadline)->isPast()) {
            //     return response()->json(['success' => false, 'message' => 'Application deadline has passed', 'errors' => (object) ['opportunity' => 'This opportunity has expired']], 400);
            // }

            if ($user->id == $opportunity->user_id) {
                return response()->json(['success' => false, 'message' => 'Cannot apply to your own posting', 'errors' => (object) ['opportunity' => 'You cannot apply to your own']], 400);
            }

            if (!$this->canChat($user->id, $opportunity->user_id)) {
                return response()->json(['success' => false, 'message' => 'Cannot apply due to block restrictions', 'errors' => (object) ['opportunity' => 'You cannot communicate with this user']], 403);
            }

            $userData   = $this->getEntityData($user->id);
            $posterData = $this->getEntityData($opportunity->user_id);

            if (!$posterData) {
                return response()->json(['success' => false, 'message' => 'Poster not found', 'errors' => (object) ['opportunity' => 'Invalid posting']], 404);
            }

            $chatType = ChatType::where('slug', 'job_application')->first();
            if (!$chatType) {
                return response()->json(['success' => false, 'message' => 'Chat type not configured', 'errors' => (object) ['system' => 'Application system error']], 500);
            }

            $fullMessage = $this->buildApplicationMessage(
                $request->application_message,
                $request->portfolio_links ?? [],
                $request->resume_link,
                $request->additional_notes,
                $request->answers ?? []
            );

            $timestamp = now()->timestamp;
            $sessionId = $this->generateApplicationSessionId(
                $user->id,
                $opportunity->user_id,
                $opportunity->id,
                $chatType->id,
                $timestamp
            );

            DB::beginTransaction();

            try {
                $chatSession = ChatSession::updateOrCreate(
                    ['id' => $sessionId],
                    [
                        'user1_id'        => min($user->id, $opportunity->user_id),
                        'user2_id'        => max($user->id, $opportunity->user_id),
                        'post_id'         => $opportunity->id,
                        'chat_type_id'    => $chatType->id,
                        'last_message_at' => now(),
                        'last_message'    => Str::limit($request->application_message, 100),
                        'unread_count'    => 1,
                        'is_active'       => true,
                    ]
                );

                $message = UserMessage::create([
                    'listing_id'      => $opportunity->id,
                    'listing_title'   => $opportunity->title,
                    'from_id'         => $user->id,
                    'to_id'           => $opportunity->user_id,
                    'to_email'        => $posterData['email'],
                    'to_name'         => $posterData['name'],
                    'from_name'       => $userData['name'],
                    'from_email'      => $userData['email'],
                    'from_phone'      => $userData['phone'] ?? null,
                    'message_txt'     => $fullMessage,
                    'subject'         => "Application: {$opportunity->title}",
                    'chat_type_id'    => $chatType->id,
                    'chat_session_id' => $sessionId,
                    'status'          => 'active',
                    'is_read'         => false,
                    'message_type'    => 'job_application',
                    'json_data'       => json_encode([
                        'portfolio_links'  => $request->portfolio_links ?? [],
                        'resume_link'      => $request->resume_link,
                        'additional_notes' => $request->additional_notes,
                        'answers'          => $request->answers ?? [],
                        'applied_at'       => now()->toDateTimeString(),
                    ]),
                ]);

                DB::commit();

                // ── NOTIFICATION 1: Notify the JOB POSTER that someone applied ────────
                // "யாரோ உங்கள் job-ல apply பண்ணாங்க"
                $typeLabel  = match ($opportunity->category_id) {
                    5 => 'Mini Mission',
                    6 => 'Internship',
                    7 => 'Fresher Role',
                    default => 'Job',
                };

                $this->notificationService->send(
                    $opportunity->user_id,                            // recipient = job poster
                    'job_application_received',                       // type
                    'New Application Received',                       // title
                    $userData['name'] . ' applied for your ' . $typeLabel . ': "' . Str::limit($opportunity->title, 50) . '"',
                    'job_applications',                               // screen → deep-link to applicants list
                    [
                        'post_id'          => (string) $opportunity->id,
                        'application_id'   => (string) $message->id,
                        'chat_session_id'  => $sessionId,
                        'applicant_id'     => (string) $user->id,
                        'type'             => 'job_application_received',
                    ],
                    $user->id                                         // from_user_id = applicant
                );

                // ── NOTIFICATION 2: Confirm to the APPLICANT that their apply was sent ─
                // "நீங்க apply பண்ணீங்க — confirmation"
                $this->notificationService->send(
                    $user->id,                                        // recipient = applicant
                    'job_application_sent',                           // type
                    'Application Submitted Successfully',             // title
                    'Your application for "' . Str::limit($opportunity->title, 50) . '" has been sent to ' . $posterData['name'] . '.',
                    'applied_jobs',                                   // screen → deep-link to my applications
                    [
                        'post_id'         => (string) $opportunity->id,
                        'application_id'  => (string) $message->id,
                        'chat_session_id' => $sessionId,
                        'poster_id'       => (string) $opportunity->user_id,
                        'type'            => 'job_application_sent',
                    ],
                    $user->id                                         // from_user_id = self
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Application submitted successfully',
                    'data'    => [
                        'application_id'  => $message->id,
                        'chat_session_id' => $sessionId,
                        'opportunity'     => [
                            'id'      => $opportunity->id,
                            'title'   => $opportunity->title,
                            'type'    => $opportunity->category_id == 5 ? 'mini_mission' : 'internship',
                            'company' => $opportunity->company_name ?? $posterData['name'],
                        ],
                        'message' => [
                            'id'               => $message->id,
                            'content'          => $message->message_txt,
                            'sent_at'          => $message->created_at,
                            'sent_at_formatted' => $message->created_at->diffForHumans(),
                        ]
                    ]
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to submit application', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }

    // ============================================
    // 6. GET APPLICATION QUESTIONS
    // ============================================

    public function getApplicationQuestions($opportunityId)
    {
        try {
            $opportunity = Post::find($opportunityId);

            if (!$opportunity || !in_array($opportunity->category_id, [5, 6, 7])) {
                return response()->json(['success' => false, 'message' => 'Opportunity not found', 'errors' => (object) ['opportunity' => 'Not found']], 404);
            }

            $questions = [
                ['id' => 'experience', 'question' => 'Do you have relevant experience for this role?',      'type' => 'text',     'required' => true],
                ['id' => 'start_date', 'question' => 'When can you start?',                                  'type' => 'text',     'required' => false],
                ['id' => 'why_you',    'question' => 'Why are you the best candidate for this position?',    'type' => 'textarea', 'required' => true],
            ];

            if ($opportunity->category_id == 6) {
                $questions[] = ['id' => 'duration',  'question' => 'Can you commit to the full internship duration?', 'type' => 'boolean', 'required' => true];
            } elseif ($opportunity->category_id == 5) {
                $questions[] = ['id' => 'portfolio', 'question' => 'Please share links to similar work you have done', 'type' => 'urls',    'required' => false];
            }

            return response()->json([
                'success' => true,
                'message' => 'Application questions retrieved',
                'data'    => [
                    'opportunity_id'    => $opportunity->id,
                    'opportunity_title' => $opportunity->title,
                    'type'              => $opportunity->category_id == 5 ? 'mini_mission' : 'internship',
                    'questions'         => $questions,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve questions', 'errors' => (object) ['server' => $e->getMessage()]], 500);
        }
    }

    // ============================================
    // PRIVATE ACTION HELPERS
    // ============================================

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
                $fullMessage .= "Q: {$qa['question']}\nA: {$qa['answer']}\n\n";
            }
        }

        if ($additionalNotes) {
            $fullMessage .= "📝 Additional Notes:\n{$additionalNotes}\n";
        }

        return $fullMessage;
    }

    private function generateApplicationSessionId($userId, $posterId, $postId, $chatTypeId, $timestamp = null)
    {
        return md5(implode('_', [
            min($userId, $posterId),
            max($userId, $posterId),
            $postId,
            $chatTypeId,
            $timestamp ?? time(),
        ]));
    }
}