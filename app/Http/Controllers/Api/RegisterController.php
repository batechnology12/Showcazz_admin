<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\JobTitle;
use App\JobSkill;
use App\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;
use ImgUploader;
use Illuminate\Support\Facades\DB;


class RegisterController extends Controller
{

    private function generateUniqueId($type = 'user')
    {
        $prefix = 'SCHWZ';
        if ($type === 'company') {
            $latest = Company::orderBy('id', 'desc')->first();
        } 
        else {
            $latest = User::orderBy('id', 'desc')->first();
        }
        if ($latest && !empty($latest->unique_id)) {
            $numberPart = substr($latest->unique_id, strlen($prefix));
            $nextNumber = str_pad((int)$numberPart + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '000001';
        }
        
        return $prefix . $nextNumber;
    }


    public function register(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validate only basic registration fields
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email|unique:companies,email',
                'password' => 'required|string|min:8|confirmed',
                'user_type' => 'required|in:student,professional,company',
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

            $userType = $request->user_type;
            
            if ($userType === 'company') {
                // Create basic company record
                $company = Company::create([
                    'name' => '', // Will be updated later
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'unique_id' => $this->generateUniqueId('company'),
                    'visibility_control' => 'public', // Default
                    'slug' => Str::random(10) . '-' . time(), // Temporary slug
                    'is_active' => 1,
                ]);

                $token = $company->createToken('company_auth_token')->plainTextToken;
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Company registered successfully. Please complete your profile.',
                    'data' => [
                        'user' => [
                            'id' => $company->id,
                            'email' => $company->email,
                            'unique_id' => $company->unique_id,
                            'usertype' => 'company',
                            'profile_completed' => false, // Flag for frontend
                        ],
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'role' => 'company'
                    ]
                ], 200);

            } else {
                // Create basic user record (Student/Professional)
                $user = User::create([
                    'first_name' => '', // Will be updated later
                    'last_name' => '', // Will be updated later
                    'name' => 'New User', // Temporary name
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'usertype' => $userType,
                    'unique_id' => $this->generateUniqueId('user'),
                    'visibility_control' => 'public', // Default
                    'is_active' => 1,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => ucfirst($userType) . ' registered successfully. Please complete your profile.',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                            'unique_id' => $user->unique_id,
                            'usertype' => $user->usertype,
                            'profile_completed' => false, // Flag for frontend
                        ],
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'role' => $user->usertype
                    ]
                ], 200);
            }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'errors' => (object)[
                    'server' => 'An error occurred during registration'
                ]
            ], 500);
        }
    }

    
   


   public function completeUserProfile(Request $request)
    {
        
        
       
        
        try {
            $user = $request->user();
            
            if (!$user || $user instanceof \App\Company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type or not authenticated'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:100',
                'college_name' => 'nullable|string|max:255',
                'school_name' => 'nullable|string|max:255',
                'degree' => 'nullable|string|max:255',
                'course_duration' => 'nullable|string|max:100',
                'specialization' => 'nullable|array', // Keep as array for input
                'specialization.*' => 'exists:job_skills,id', // Changed to job_skills table
                'portfolio_website' => 'nullable|url|max:255',
                'area_of_interest_ids' => 'nullable|array',
                'area_of_interest_ids.*' => 'exists:job_titles,id',
                'visibility_control' => 'sometimes|in:public,private',
                'phone' => 'nullable|string|max:10',
                'headline' => 'nullable|string|max:255',
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

            DB::beginTransaction();

            try {
                // Update user basic info
                $user->first_name = $request->full_name;
                $user->name = $request->full_name;
                // Update other fields
                $user->college_name = $request->college_name;
                $user->school_name = $request->school_name;
                $user->degree = $request->degree;
                $user->start_year = $request->start_year;
                $user->end_year = $request->end_year;
                $user->currently_pursuing = $request->currently_pursuing;
                $user->course_duration = $request->course_duration;
                
                // Professional-specific fields
                if ($user->usertype == 'professional' || $user->usertype == 'student') {
                    // Handle specialization array - store as comma-separated string
                    if (!empty($request->specialization) && is_array($request->specialization)) {
                        // Store as comma-separated string in the VARCHAR column
                        $user->specialization = implode(',', $request->specialization);
                    } else {
                        // Clear specialization if empty
                        $user->specialization = null;
                    }
                    
                    $user->portfolio_website = $request->portfolio_website;
                }

                // Common fields
                $user->visibility_control = $request->visibility_control ?? 'public';
                $user->phone = $request->phone;

                // Handle area of interests (integer[] array column)
                // if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                //     // Get first job title for headline
                //     $firstJobTitleId = $request->area_of_interest_ids[0];
                //     $jobTitle = \App\JobTitle::find($firstJobTitleId);
                //     $user->headline = $jobTitle ? $jobTitle->job_title : $request->headline;
                    
                //     // For PostgreSQL integer[] column, use array literal
                //     $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interest_ids)) . '}';
                //     $user->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
                    
                    
                //     dd($user->area_of_interest_id);
                // } else {
                //     $user->headline = $request->headline;
                //     // Clear area of interests if empty
                //     $user->area_of_interest_id = DB::raw("'{}'");
                // }
                
                
                if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {

                    $firstJobTitleId = $request->area_of_interest_ids[0];
                    $jobTitle = \App\JobTitle::find($firstJobTitleId);
                
                    
                    
                    $user->area_of_interest_id = $request->area_of_interest_ids;
                
                    // ← NOW WILL SHOW ARRAY
                
                } else {
                
                   // $user->headline = $request->headline;
                    $user->area_of_interest_id = [];
                }
                
                $user->headline = $request->headline;
                
               

                
                $user->save();
                DB::commit();

                // Get area of interest names for response
                $areaOfInterests = [];
                if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                    $jobTitles = \App\JobTitle::whereIn('id', $request->area_of_interest_ids)
                        ->where('is_active', 1)
                        ->get(['id', 'job_title']);
                    $areaOfInterests = $jobTitles->map(function($jobTitle) {
                        return [
                            'id' => $jobTitle->id,
                            'name' => $jobTitle->job_title
                        ];
                    })->toArray();
                }

              
                // Get specialization names for response
                $specializations = [];
                if (!empty($request->specialization) && is_array($request->specialization) && 
                    ($user->usertype == 'professional' || $user->usertype == 'student')) {
                    
                    $jobSkills = \App\JobSkill::whereIn('id', $request->specialization)
                        ->where('is_active', 1)
                        ->get(['id', 'job_skill']);
                    
                    $specializations = $jobSkills->map(function($skill) {
                        return [
                            'id' => $skill->id,
                            'name' => $skill->job_skill
                        ];
                    })->toArray();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Profile completed successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->getName(),
                            'email' => $user->email,
                            'unique_id' => $user->unique_id,
                            'usertype' => $user->usertype,
                            'visibility_control' => $user->visibility_control,
                            'college_name' => $user->college_name,
                            'school_name' => $user->school_name,
                            'degree' => $user->degree,
                            'course_duration' => $user->course_duration,
                            'headline' => $user->headline,
                            'phone' => $user->phone,
                            'portfolio_website' => $user->portfolio_website,
                            'area_of_interests' => $areaOfInterests,
                            'specializations' => $specializations,
                            'profile_completed' => true,
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile completion failed',
                'errors' => (object)[
                    'server' => 'An error occurred: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    public function completeCompanyProfile(Request $request)
    {
        try {
            $company = $request->user();
            
            if (!$company || !($company instanceof \App\Company)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type or not authenticated'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'website' => 'nullable|url',
                'linkedin_url' => 'nullable|url',
                'gst_number' => 'nullable|string|max:100',
                'upi_id' => 'nullable|string|max:100',
                'visibility_control' => 'sometimes|in:public,private',
                'description' => 'nullable|string',
                'phone' => 'nullable|string|max:10',
                'location' => 'nullable|string|max:255',
                'industry_id' => 'nullable|exists:industries,id',
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

            // Update company details
            $company->name = $request->name;
            $company->ceo = $request->company_name;
            $company->website = $request->website;
            $company->linkedin_url = $request->linkedin_url;
            $company->gst_number = $request->gst_number;
            $company->upi_id = $request->upi_id;
            $company->visibility_control = $request->visibility_control ?? 'public';
            $company->description = $request->description;
            $company->phone = $request->phone;
            $company->location = $request->location;
            $company->industry_id = $request->industry_id;
            
            // Update slug with actual company name
            $company->slug = Str::slug($request->name . '-' . $company->id);
         
            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Company profile completed successfully',
                'data' => [
                    'user' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email,
                        'unique_id' => $company->unique_id,
                        'usertype' => 'company',
                        'visibility_control' => $company->visibility_control,
                        'website' => $company->website,
                        'profile_completed' => true,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile completion failed',
                'errors' => (object)[
                    'server' => $e->getMessage()
                ]
            ], 500);
        }
    }


    public function checkProfileCompletion(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $isCompleted = false;
            $missingFields = [];

            if ($user instanceof \App\Company) {
                // Check if company profile is completed
                $requiredFields = ['name', 'slug'];
                foreach ($requiredFields as $field) {
                    if (empty($user->$field)) {
                        $missingFields[] = $field;
                    }
                }
                $isCompleted = empty($missingFields);
            } else {
                // Check if user profile is completed
                $requiredFields = ['first_name', 'last_name'];
                foreach ($requiredFields as $field) {
                    if (empty($user->$field)) {
                        $missingFields[] = $field;
                    }
                }
                $isCompleted = empty($missingFields);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'profile_completed' => $isCompleted,
                    'missing_fields' => $missingFields,
                    'user_type' => $user instanceof \App\Company ? 'company' : $user->usertype
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check profile completion',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }
    
    
    public function getEditProfileData(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $response = [
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'data' => []
            ];

            if ($user instanceof \App\Company) {
                $response['data'] = $this->getCompanyEditData($user);
            } else {
                $response['data'] = $this->getUserEditData($user);
            }

            return response()->json($response);

        } catch (Exception $e) {
            
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile data',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get company edit data
     */
    private function getCompanyEditData($company)
    {
        // Get all industries for dropdown
        $industries = Industry::where('is_active', 1)
            ->select('id', 'industry')
            ->orderBy('industry')
            ->get();

        // Parse area_of_interest_id (if company uses this)
        $areaOfInterestIds = [];
        if (!empty($company->area_of_interest_id)) {
            if (is_string($company->area_of_interest_id) && strpos($company->area_of_interest_id, '{') === 0) {
                $ids = trim($company->area_of_interest_id, '{}');
                $areaOfInterestIds = array_map('intval', explode(',', $ids));
            } elseif (is_string($company->area_of_interest_id)) {
                $areaOfInterestIds = array_map('intval', explode(',', $company->area_of_interest_id));
            }
        }

        // Get job titles for area of interests
        $areaOfInterests = [];
        if (!empty($areaOfInterestIds)) {
            $areaOfInterests = JobTitle::whereIn('id', $areaOfInterestIds)
                ->where('is_active', 1)
                ->pluck('job_title', 'id')
                ->toArray();
        }

        return [
            'user_type' => 'company',
            'basic_info' => [
                'name' => $company->name ?? '',
                'email' => $company->email ?? '',
                'phone' => $company->phone ?? '',
                'website' => $company->website ?? '',
                'linkedin_url' => $company->linkedin_url ?? '',
                'description' => $company->description ?? '',
                'location' => $company->location ?? '',
                'visibility_control' => $company->visibility_control ?? 'public',
            ],
            'business_info' => [
                'gst_number' => $company->gst_number ?? '',
                'upi_id' => $company->upi_id ?? '',
                'industry_id' => $company->industry_id ?? '',
            ],
            'images' => [
                'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
            ],
            'dropdowns' => [
                'industries' => $industries,
                'area_of_interests' => array_keys($areaOfInterests), // Selected IDs
            ],
            'profile_completion' => $this->calculateProfileCompletion($company),
        ];
    }

    /**
     * Get user (student/professional) edit data
     */
    private function getUserEditData($user)
    {
        // Get all job titles for area of interest dropdown
        $jobTitles = JobTitle::where('is_active', 1)
            ->select('id', 'job_title')
            ->orderBy('job_title')
            ->get();

        // Get all skills for specialization dropdown
        $skills = JobSkill::where('is_active', 1)
            ->select('id', 'job_skill')
            ->orderBy('job_skill')
            ->get();

        // Get all industries
        $industries = Industry::where('is_active', 1)
            ->select('id', 'industry')
            ->orderBy('industry')
            ->get();

        // Parse area_of_interest_id
        // $areaOfInterestIds = [];
        // if (!empty($user->area_of_interest_id)) {
        //     if (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
        //         $ids = trim($user->area_of_interest_id, '{}');
        //         $areaOfInterestIds = array_map('intval', explode(',', $ids));
        //     } elseif (is_string($user->area_of_interest_id)) {
        //         $areaOfInterestIds = array_map('intval', explode(',', $user->area_of_interest_id));
        //     }
        // }
        
        $areaOfInterestIds = [];
        if (!empty($user->area_of_interest_id)) {
        
            // If PostgreSQL already returns array
            if (is_array($user->area_of_interest_id)) {
                $areaOfInterestIds = array_map('intval', $user->area_of_interest_id);
            }
            // If returned as string like "{1,2}"
            elseif (is_string($user->area_of_interest_id)) {
                $ids = trim($user->area_of_interest_id, '{}');
                $areaOfInterestIds = array_map('intval', explode(',', $ids));
            }
        }


        // Parse specialization
        $specializationIds = [];
        if (!empty($user->specialization)) {
            $specializationIds = array_map('intval', explode(',', $user->specialization));
        }

        $userData = [
            'user_type' => $user->usertype ?? 'professional',
            'basic_info' => [
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'headline' => $user->headline ?? '',
                'summary' => $user->getProfileSummary('summary') ?? '',
                'location' => $user->location ?? '',
                'start_year' => $user->start_year ?? '',
                'end_year' => $user->end_year ?? '',
                'date_of_birth' => $user->date_of_birth ?? '',
                'visibility_control' => $user->visibility_control ?? 'public',
            ],
            'education_info' => [
                'college_name' => $user->college_name ?? '',
                'school_name' => $user->school_name ?? '',
                'degree' => $user->degree ?? '',
                'course_duration' => $user->course_duration ?? '',
            ],
            'professional_info' => $user->usertype === 'professional' ? [
                'portfolio_website' => $user->portfolio_website ?? '',
                'gst_number' => $user->gst_number ?? '',
                'upi_id' => $user->upi_id ?? '',
                'industry_id' => $user->industry_id ?? '',
            ] : [],
            'student_info' => $user->usertype === 'student' ? [
                'currently_pursuing' => !empty($user->course_duration) && strpos($user->course_duration, 'Present') !== false,
            ] : [],
            'images' => [
                'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
                'cover_image' => $user->cover_image ? asset('user_images/' . $user->cover_image) : null,
            ],
            'dropdowns' => [
                'area_of_interests' => $areaOfInterestIds, // Selected IDs
                'specializations' => $specializationIds, // Selected skill IDs
                'all_job_titles' => $jobTitles,
                'all_skills' => $skills,
                'all_industries' => $industries,
            ],
            'profile_completion' => $this->calculateProfileCompletion($user),
        ];

        return $userData;
    }

    /**
     * Update profile for all user types
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            DB::beginTransaction();

            if ($user instanceof \App\Company) {
                $result = $this->updateCompanyProfile($user, $request);
            } else {
                $result = $this->updateUserProfile($user, $request);
            }

            DB::commit();

            return response()->json($result);

        } catch (Exception $e) {
            DB::rollBack();
            
        
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'errors' => (object)['server' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Update company profile
     */
    private function updateCompanyProfile($company, $request)
    {
        $validator = Validator::make($request->all(), [
            // Basic Info
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'visibility_control' => 'sometimes|in:public,private',
            
            // Business Info
            'gst_number' => 'nullable|string|max:100',
            'upi_id' => 'nullable|string|max:100',
            'industry_id' => 'nullable|exists:industries,id',
            
            // Area of Interests
            'area_of_interests' => 'nullable|array',
            'area_of_interests.*' => 'exists:job_titles,id',
            
            // Images
            'logo' => 'nullable|string', // Base64 or URL
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$errors
            ];
        }

        // Update basic info
        $company->name = $request->name;
        $company->email = $request->email;
        $company->phone = $request->phone;
        $company->website = $request->website;
        $company->linkedin_url = $request->linkedin_url;
        $company->description = $request->description;
        $company->location = $request->location;
        $company->visibility_control = $request->visibility_control ?? $company->visibility_control;
        $company->industry_id = $request->industry_id;

        // Update business info
        $company->gst_number = $request->gst_number;
        $company->upi_id = $request->upi_id;

        // Handle area of interests
        if ($request->has('area_of_interests')) {
            if (!empty($request->area_of_interests) && is_array($request->area_of_interests)) {
                $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interests)) . '}';
                $company->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
            } else {
                $company->area_of_interest_id = DB::raw("'{}'");
            }
        }

        // Handle logo update
        if ($request->has('logo') && !empty($request->logo)) {
            $this->handleImageUpload($company, $request->logo, 'logo', 'company_logos');
        } elseif ($request->get('remove_logo', false)) {
            $this->deleteImage($company->logo, 'company_logos');
            $company->logo = null;
        }

        // Update slug if name changed
        if ($company->isDirty('name')) {
            $company->slug = Str::slug($request->name . '-' . $company->id);
        }

        $company->save();

        return [
            'success' => true,
            'message' => 'Company profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'usertype' => 'company',
                    'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                    'slug' => $company->slug,
                    'profile_completed' => $this->isProfileComplete($company),
                ]
            ]
        ];
    }

    /**
     * Update user (student/professional) profile
     */
    private function updateUserProfile($user, $request)
    {
        $userType = $user->usertype ?? 'professional';

        $validationRules = [
            // Basic Info
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'headline' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'visibility_control' => 'sometimes|in:public,private',
            
            // Education Info
            'college_name' => 'nullable|string|max:255',
            'school_name' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:255',
            'course_duration' => 'nullable|string|max:100',
            
            // Specialization & Interests
            'area_of_interests' => 'nullable|array',
            'area_of_interests.*' => 'exists:job_titles,id',
            'specializations' => 'nullable|array',
            'specializations.*' => 'exists:job_skills,id',
            
            // Images
            'profile_image' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'remove_profile_image' => 'nullable|boolean',
            'remove_cover_image' => 'nullable|boolean',
        ];

        // Add professional-specific fields
        if ($userType === 'professional') {
            $validationRules['portfolio_website'] = 'nullable|url|max:255';
            $validationRules['gst_number'] = 'nullable|string|max:100';
            $validationRules['upi_id'] = 'nullable|string|max:100';
            $validationRules['industry_id'] = 'nullable|exists:industries,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$errors
            ];
        }

        // Update basic info
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->headline = $request->headline;
        $user->location = $request->location;
        $user->date_of_birth = $request->date_of_birth;
        $user->visibility_control = $request->visibility_control ?? $user->visibility_control;
        
        $user->start_year = $request->start_year;
        $user->end_year = $request->end_year;
        $user->currently_pursuing = $request->currently_pursuing;

        // Update education info
        $user->college_name = $request->college_name;
        $user->school_name = $request->school_name;
        $user->degree = $request->degree;
        
        // Handle course duration for students
        if ($userType === 'student' && $request->has('currently_pursuing')) {
            if ($request->currently_pursuing && $request->course_duration) {
                $user->course_duration = $request->course_duration . ' - Present';
            } else {
                $user->course_duration = $request->course_duration;
            }
        } else {
            $user->course_duration = $request->course_duration;
        }

        // Update professional-specific fields
        if ($userType === 'professional') {
            $user->portfolio_website = $request->portfolio_website;
            $user->gst_number = $request->gst_number;
            $user->upi_id = $request->upi_id;
            $user->industry_id = $request->industry_id;
        }

        // Handle area of interests
        if ($request->has('area_of_interest_ids')) {
            
           
            if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interest_ids)) . '}';
                $user->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
            } else {
                $user->area_of_interest_id = DB::raw("'{}'");
            }
        }

        // Handle specialization/skills
        if ($request->has('specializations')) {
            if (!empty($request->specializations) && is_array($request->specializations)) {
                $user->specialization = implode(',', $request->specializations);
            } else {
                $user->specialization = null;
            }
        }

        // Handle profile image
        if ($request->has('profile_image') && !empty($request->profile_image)) {
            $this->handleImageUpload($user, $request->profile_image, 'image', 'user_images');
        } elseif ($request->get('remove_profile_image', false)) {
            $this->deleteImage($user->image, 'user_images');
            $user->image = null;
        }

        // Handle cover image
        if ($request->has('cover_image') && !empty($request->cover_image)) {
            $this->handleImageUpload($user, $request->cover_image, 'cover_image', 'user_images');
        } elseif ($request->get('remove_cover_image', false)) {
            $this->deleteImage($user->cover_image, 'user_images');
            $user->cover_image = null;
        }

        $user->save();

        // Get updated dropdown data
        $areaOfInterests = [];
        if (!empty($request->area_of_interests) && is_array($request->area_of_interests)) {
            $jobTitles = JobTitle::whereIn('id', $request->area_of_interests)
                ->where('is_active', 1)
                ->get(['id', 'job_title']);
            $areaOfInterests = $jobTitles->map(function($jobTitle) {
                return [
                    'id' => $jobTitle->id,
                    'name' => $jobTitle->job_title
                ];
            })->toArray();
        }

        $specializations = [];
        if (!empty($request->specializations) && is_array($request->specializations)) {
            $jobSkills = JobSkill::whereIn('id', $request->specializations)
                ->where('is_active', 1)
                ->get(['id', 'job_skill']);
            $specializations = $jobSkills->map(function($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->job_skill
                ];
            })->toArray();
        }

        return [
            'success' => true,
            'message' => ucfirst($userType) . ' profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->getName(),
                    'email' => $user->email,
                    'usertype' => $user->usertype,
                    'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
                    'cover_image' => $user->cover_image ? asset('user_images/' . $user->cover_image) : null,
                    'profile_completed' => $this->isProfileComplete($user),
                    'area_of_interests' => $areaOfInterests,
                    'specializations' => $specializations,
                ]
            ]
        ];
    }

    /**
     * Handle image upload (base64 or URL)
     */
    private function handleImageUpload($model, $imageData, $field, $folder)
    {
        try {
            // Delete old image if exists
            if (!empty($model->$field)) {
                $this->deleteImage($model->$field, $folder);
            }

            // Check if it's base64
            if (strpos($imageData, 'data:image') === 0) {
                // Base64 image
                list($type, $imageData) = explode(';', $imageData);
                list(, $imageData) = explode(',', $imageData);
                $imageData = base64_decode($imageData);
                
                // Generate filename
                $fileName = $model->id . '_' . time() . '_' . Str::random(10) . '.png';
                $filePath = public_path($folder . '/' . $fileName);
                
                // Save file
                file_put_contents($filePath, $imageData);
                
                $model->$field = $fileName;
            } elseif (strpos($imageData, 'http') === 0) {
                // URL - extract filename
                $fileName = basename(parse_url($imageData, PHP_URL_PATH));
                $model->$field = $fileName;
            } else {
                // Already a filename
                $model->$field = $imageData;
            }

        } catch (Exception $e) {
            Log::error('Image Upload Error: ' . $e->getMessage());
            throw new Exception('Failed to upload image');
        }
    }

    /**
     * Delete image file
     */
    private function deleteImage($fileName, $folder)
    {
        if (!$fileName) return;

        $filePath = public_path($folder . '/' . $fileName);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

     public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed|different:current_password',
                'password_confirmation' => 'required|string|min:8',
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
    
            $email = $request->email;
            $currentPassword = $request->current_password;
            $newPassword = $request->password;
    
            // Find user/company
            $user = User::where('email', $email)->first();
            $company = Company::where('email', $email)->first();
    
            if (!$user && !$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)[
                        'email' => 'User not found'
                    ]
                ], 404);
            }
    
            // Validate current password
            if ($user) {
                if (!Hash::check($currentPassword, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => (object)[
                            'current_password' => 'Current password is incorrect'
                        ]
                    ], 401);
                }
            } else {
                if (!Hash::check($currentPassword, $company->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => (object)[
                            'current_password' => 'Current password is incorrect'
                        ]
                    ], 401);
                }
            }
    
            // Update password
            if ($user) {
                $user->password = Hash::make($newPassword);
                $user->save();
                $userType = 'user';
            } else {
                $company->password = Hash::make($newPassword);
                $company->save();
                $userType = 'company';
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'email' => $email,
                    'user_type' => $userType,
                    'reset' => true
                ]
            ]);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }    

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
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
    
            // Try User login (Student/Professional)
            $user = User::where('email', $request->email)->first();
            
            if ($user && Hash::check($request->password, $user->password)) {
                try {
                    $token = $user->createToken('auth_token')->plainTextToken;
                    
                    // Check profile completion status
                    $isProfileCompleted = $this->checkUserProfileCompletion($user);
                    
                    $userData = [
                        'id' => $user->id,
                        'name' => $user->getName(),
                        'email' => $user->email,
                        'unique_id' => $user->unique_id,
                        'usertype' => $user->usertype ?? 'professional',
                        'email_verified_at' => $user->email_verified_at,
                        'profile_image' => $user->image ? asset('user_images/'.$user->image) : null,
                        'headline' => $user->headline ?? null,
                        'location' => $user->location ?? null,
                        'is_profile_completed' => $isProfileCompleted, // Add this
                    ];
    
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => $userData,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'role' => $user->usertype ?? 'professional',
                            'profile_completed' => $isProfileCompleted, // Also include at top level
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    Log::error('Token creation failed for user: ' . $user->id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication failed',
                        'errors' => (object)[
                            'auth' => 'Unable to create authentication token'
                        ]
                    ], 500);
                }
            }
    
            // Try Company login
            $company = Company::where('email', $request->email)->first();
            
            if ($company && Hash::check($request->password, $company->password)) {
                try {
                    $token = $company->createToken('company_auth_token')->plainTextToken;
                    
                    // Check profile completion status
                    $isProfileCompleted = $this->checkCompanyProfileCompletion($company);
                    
                    $companyData = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email,
                        'unique_id' => $company->unique_id,
                        'usertype' => 'company',
                        'slug' => $company->slug,
                        'email_verified_at' => $company->email_verified_at,
                        'logo' => $company->logo ? asset('company_logos/'.$company->logo) : null,
                        'industry' => $company->getIndustry('industry') ?? null,
                        'location' => $company->location ?? null,
                        'is_profile_completed' => $isProfileCompleted, // Add this
                    ];
    
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => $companyData,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'role' => 'company',
                            'profile_completed' => $isProfileCompleted, // Also include at top level
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    Log::error('Token creation failed for company: ' . $company->id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication failed',
                        'errors' => (object)[
                            'auth' => 'Unable to create authentication token'
                        ]
                    ], 500);
                }
            }
    
            // Invalid credentials
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors' => (object)[
                    'email' => 'These credentials do not match our records.'
                ]
            ], 401);
    
        } catch (Exception $e) {
            // Log the unexpected error
            Log::error('Login API Error: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'errors' => (object)[
                    'server' => 'Internal server error. Please try again later.'
                ]
            ], 500);
        }
    }
    
    /**
     * Check User Profile Completion Helper
     */
    private function checkUserProfileCompletion($user)
    {
        try {
            $requiredFields = ['first_name'];
            $isCompleted = true;
            
            foreach ($requiredFields as $field) {
                if (empty($user->$field)) {
                    $isCompleted = false;
                    break;
                }
            }
            
            return $isCompleted;
        } catch (Exception $e) {
            Log::error('Error checking user profile completion: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check Company Profile Completion Helper
     */
    private function checkCompanyProfileCompletion($company)
    {
        try {
            $requiredFields = ['name', 'slug'];
            $isCompleted = true;
            
            foreach ($requiredFields as $field) {
                if (empty($company->$field)) {
                    $isCompleted = false;
                    break;
                }
            }
            
            return $isCompleted;
        } catch (Exception $e) {
            Log::error('Error checking company profile completion: ' . $e->getMessage());
            return false;
        }
    }


   

    /**
     * Logout API with try-catch
     */
    public function logout(Request $request)
    {
        try {
            $authenticatable = $request->user();
            
            if ($authenticatable) {
                $authenticatable->currentAccessToken()->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
            
        } catch (Exception $e) {
          
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'errors' => (object)[
                    'server' => 'Unable to logout. Please try again.'
                ]
            ], 500);
        }
    }

  

     public function profile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // =======================
            // BASIC PROFILE DATA
            // =======================
            if ($user instanceof \App\Company) {
                $profileData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'unique_id' => $user->unique_id,
                    'usertype' => 'company',
                    'slug' => $user->slug,
                    'email_verified_at' => $user->email_verified_at,
                    'profile_image' => $user->logo ? asset('company_logos/' . $user->logo) : null,
                    'industry' => $user->getIndustry('industry') ?? null,
                    'location' => $user->location ?? null,
                    'description' => $user->description ?? null,
                    'website' => $user->website ?? null,
                    'phone' => $user->phone ?? null,
                ];
            } else {
                $profileData = [
                    'id' => $user->id,
                    'name' => $user->getName(),
                    'email' => $user->email,
                     'unique_id' => $user->unique_id,
                    'usertype' => $user->usertype ?? 'professional',
                    'email_verified_at' => $user->email_verified_at,
                    'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
                    'cover_image' => $user->cover_image ? asset('user_images/' . $user->cover_image) : null,
                    'headline' => $user->headline ?? null,
                    'location' => $user->location ?? null,
                    'summary' => $user->getProfileSummary('summary') ?? null,
                    'industry' => $user->getIndustry('industry') ?? null,
                    'date_of_birth' => $user->date_of_birth ?? null,
                    'phone' => $user->phone ?? null,
                ];
            }

            // =======================
            // EXTRA PROFILE DATA
            // =======================
            $profileData['profile_completion_percentage'] = $this->calculateProfileCompletion($user);
            $profileData['stats'] = $this->getProfileStats($user);
            $profileData['posts'] = $this->getUserPostsWithStats($user);
            $profileData['connections'] = $this->getConnectionStats($user);
            $profileData['opportunities'] = $this->getOpportunityStats($user);

            if ($user instanceof \App\User) {
                $profileData['job_applications'] = $this->getUserJobApplications($user);
            }

            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);

        } catch (\Exception $e) {
            Log::error('Profile API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch profile'
            ], 500);
        }
    }

    // ==================================================
    // PROFILE COMPLETION
    // ==================================================
    private function calculateProfileCompletion($user)
    {
        $fields = $user instanceof \App\Company
            ? ['name','unique_id','visibility_control','email','description','website','logo']
            : ['first_name','email','visibility_control','college_name','school_name','degree','image'];

        $completed = 0;

        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }

        return count($fields)
            ? round(($completed / count($fields)) * 100)
            : 0;
    }

    // ==================================================
    // PROFILE STATS
    // ==================================================
    
    private function getProfileStats($user)
{
    $likes = \App\PostLike::whereHas('post', function($q) use ($user) {
        $q->where('user_id', $user->id)
          ->where('is_active', true)
          ->where('is_published', true);
    })->count();

    $shares = \App\PostShare::whereHas('post', function($q) use ($user) {
        $q->where('user_id', $user->id)
          ->where('is_active', true)
          ->where('is_published', true);
    })->count();

    $worthDiscussing = \App\UserMessage::where(function ($q) use ($user) {
        $q->where('to_id', $user->id)->orWhere('from_id', $user->id);
    })
    ->whereHas('chatType', fn($q) => $q->where('slug', 'worth_discussing'))
    ->count();

    return [
        'likes' => $this->formatNumber($likes),
        'likes_raw' => $likes,
        'worth_discussing' => $worthDiscussing,
        'shares' => $this->formatNumber($shares),
        'shares_raw' => $shares
    ];
}
   

    // ==================================================
    // POSTS
    // ==================================================
    private function getUserPostsWithStats($user)
    {
        return \App\Post::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('post_type_id', 1)
            ->latest()
            // ->take(10)
            ->get()
            ->map(function ($post) {
                $images = $post->images ? json_decode($post->images, true) : [];

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'short_description' => $post->short_description,
                    'category' => optional($post->category)->name,
                    'subcategory' => optional($post->subcategory)->name,
                    'is_job_post' => $post->is_job_post ?? false,
                    'job_id' => $post->job_id,
                    'stats' => [
                        'views' => $post->views_count ?? 0,
                        'likes' => $post->likes_count ?? 0,
                        'comments' => $post->comments_count ?? 0,
                        'shares' => $post->shares_count ?? 0,
                    ],
                    'image' => $images[0] ?? null,
                    'image_url' => isset($images[0]) ? asset('post_images/' . $images[0]) : null,
                    'created_at' => $post->created_at,
                ];
            });
    }

    // ==================================================
    // JOB APPLICATIONS
    // ==================================================
    private function getUserJobApplications($user)
    {
        if (!class_exists(\App\JobApplication::class)) {
            return [];
        }

        return \App\JobApplication::where('user_id', $user->id)
            ->with('job.company')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($app) => [
                'id' => $app->id,
                'job_id' => $app->job_id,
                'job_title' => optional($app->job)->title,
                'company_name' => optional(optional($app->job)->company)->name,
                'status' => $app->status,
                'applied_at' => $app->created_at,
                'is_active' => optional($app->job)->is_active ?? false
            ]);
    }

    // ==================================================
    // CONNECTIONS
    // ==================================================
    private function getConnectionStats($user)
    {
        if ($user instanceof \App\Company) {
            $followers = \App\FavouriteCompany::where('company_id', $user->id)->count();

            return [
                'followers' => $this->formatNumber($followers),
                'followers_raw' => $followers
            ];
        }

        $following = \App\UserConnection::where('follower_id', $user->id)
            ->where('status', 'accepted')->count();

        $followers = \App\UserConnection::where('following_id', $user->id)
            ->where('status', 'accepted')->count();

        return [
            'following' => $this->formatNumber($following),
            'following_raw' => $following,
            'followers' => $this->formatNumber($followers),
            'followers_raw' => $followers
        ];
    }

    // ==================================================
    // OPPORTUNITIES (FIXED ERROR HERE)
    // ==================================================
    private function getOpportunityStats($user)
    {
        if ($user instanceof \App\Company) {
            $jobs = \App\Job::where('company_id', $user->id)
                ->where('is_active', true)->count();

            return [
                'job_posts' => $jobs,
                'active_jobs' => $jobs
            ];
        }
        
        
        return [
            'recommended_jobs' => \App\Job::where('is_active', true)->count(),
            'applied_jobs' => collect($this->getUserJobApplications($user))->count()
        ];
    }

    // ==================================================
    // FORMAT NUMBER
    // ==================================================
    private function formatNumber($number)
    {
        if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
        if ($number >= 1000) return round($number / 1000, 1) . 'K';

        return (string) $number;
    }
    
    
    
  
    
    /**
     * Update Profile Picture/Logo (Single API for both Users and Companies)
     */
    // public function updateProfilePicture(Request $request)
    // {
    //     try {
    //         $authenticatedUser = $request->user();
            
    //         if (!$authenticatedUser) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Not authenticated'
    //             ], 401);
    //         }

    //         $validator = Validator::make($request->all(), [
    //             'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Single field for both
    //         ], [
    //             'image.required' => 'Please select an image file',
    //             'image.image' => 'The file must be a valid image',
    //             'image.mimes' => 'The image must be a JPEG, PNG, JPG, or GIF file',
    //             'image.max' => 'The image size must not exceed 5MB',
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

    //         // Check user type and handle accordingly
    //         if ($authenticatedUser instanceof \App\Company) {
    //             // Handle Company logo update
    //             $image = $request->file('image');
                
    //             // Delete old logo if exists
    //             if ($authenticatedUser->logo) {
    //                 $this->deleteCompanyLogo($authenticatedUser->id);
    //             }
                
    //             // Upload new logo
    //             $fileName = ImgUploader::UploadImage(
    //                 'company_logos', 
    //                 $image, 
    //                 $authenticatedUser->name, 
    //                 300, 
    //                 300, 
    //                 false
    //             );
                
    //             $authenticatedUser->logo = $fileName;
    //             $authenticatedUser->save();

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Company logo updated successfully',
    //                 'data' => [
    //                     'user_type' => 'company',
    //                     'image_url' => asset('company_logos/' . $fileName),
    //                     'name' => $authenticatedUser->name,
    //                     'usertype' => 'company'
    //                 ]
    //             ]);

    //         } else {
    //             // Handle User (Student/Professional) profile picture update
    //             // Get usertype from User model (student/professional)
    //             $userType = $authenticatedUser->usertype ?? 'user';
                
    //             // Delete old profile picture if exists
    //             if ($authenticatedUser->image) {
    //                 $this->deleteUserImage($authenticatedUser->id);
    //             }
                
    //             $image = $request->file('image');
    //             $fileName = ImgUploader::UploadImage(
    //                 'user_images', 
    //                 $image, 
    //                 $authenticatedUser->getName(), 
    //                 300, 
    //                 300, 
    //                 false
    //             );
                
    //             $authenticatedUser->image = $fileName;
    //             $authenticatedUser->save();

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Profile picture updated successfully',
    //                 'data' => [
    //                     'user_type' => $userType, // student or professional
    //                     'usertype' => $userType, // Added for consistency
    //                     'image_url' => asset('user_images/' . $fileName),
    //                     'name' => $authenticatedUser->getName()
    //                 ]
    //             ]);
    //         }

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update profile picture',
    //             'errors' => (object)[
    //                 'server' => 'An error occurred: ' . $e->getMessage()
    //             ]
    //         ], 500);
    //     }
    // }
    
    
    /**
     * Update Profile Picture/Logo (Single API for both Users and Companies) with Watermark
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            $authenticatedUser = $request->user();
            
            if (!$authenticatedUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            ], [
                'image.required' => 'Please select an image file',
                'image.image' => 'The file must be a valid image',
                'image.mimes' => 'The image must be a JPEG, PNG, JPG, or GIF file',
                'image.max' => 'The image size must not exceed 5MB',
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
    
            // Watermark configuration
            $watermarkText = "showcazz";
            $watermarkFontSize = 24; // Font size
            $watermarkPadding = 10; // Padding from edges
            $watermarkOpacity = 0.7; // 0-1 transparency
            $watermarkColor = [255, 255, 255]; // White color for watermark
            $watermarkShadowColor = [0, 0, 0]; // Black shadow for better visibility
    
            // Function to add watermark to image
            $addWatermark = function($imagePath) use ($watermarkText, $watermarkFontSize, $watermarkPadding, $watermarkOpacity, $watermarkColor, $watermarkShadowColor) {
                try {
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
                        default:
                            return false; // Unsupported image type
                    }
                    
                    if (!$image) {
                        return false;
                    }
    
                    // Allocate colors
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
    
                    // Load font - using built-in GD font
                    // You can also use TrueType fonts: putenv('GDFONTPATH=' . realpath('.'));
                    // $fontPath = public_path('fonts/arial.ttf'); // If using TTF font
                    
                    // Calculate text position (bottom right)
                    // For GD built-in font
                    $textWidth = imagefontwidth($watermarkFontSize) * strlen($watermarkText);
                    $textHeight = imagefontheight($watermarkFontSize);
                    
                    $x = $width - $textWidth - $watermarkPadding;
                    $y = $height - $textHeight - $watermarkPadding;
    
                    // Add text shadow (slightly offset)
                    imagestring($image, $watermarkFontSize, $x + 1, $y + 1, $watermarkText, $shadowColor);
                    // Add main text
                    imagestring($image, $watermarkFontSize, $x, $y, $watermarkText, $textColor);
    
                    // Save the image
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            imagejpeg($image, $imagePath, 90);
                            break;
                        case IMAGETYPE_PNG:
                            imagepng($image, $imagePath, 9);
                            break;
                        case IMAGETYPE_GIF:
                            imagegif($image, $imagePath);
                            break;
                    }
    
                    // Free memory
                    imagedestroy($image);
                    
                    return true;
                } catch (Exception $e) {
                    Log::error('Watermark Error: ' . $e->getMessage());
                    return false;
                }
            };
    
            // Check user type and handle accordingly
            if ($authenticatedUser instanceof \App\Company) {
                // Handle Company logo update
                $image = $request->file('image');
                
                // Delete old logo if exists
                if ($authenticatedUser->logo) {
                    $this->deleteCompanyLogo($authenticatedUser->id);
                }
                
                // Upload new logo
                $fileName = ImgUploader::UploadImage(
                    'company_logos', 
                    $image, 
                    $authenticatedUser->name, 
                    300, 
                    300, 
                    false
                );
                
                // Add watermark to the uploaded image
                $imagePath = public_path('company_logos/' . $fileName);
                if (file_exists($imagePath)) {
                    $addWatermark($imagePath);
                }
                
                $authenticatedUser->logo = $fileName;
                $authenticatedUser->save();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Company logo updated successfully',
                    'data' => [
                        'user_type' => 'company',
                        'image_url' => asset('company_logos/' . $fileName),
                        'name' => $authenticatedUser->name,
                        'usertype' => 'company'
                    ]
                ]);
    
            } else {
                // Handle User (Student/Professional) profile picture update
                $userType = $authenticatedUser->usertype ?? 'user';
                
                // Delete old profile picture if exists
                if ($authenticatedUser->image) {
                    $this->deleteUserImage($authenticatedUser->id);
                }
                
                $image = $request->file('image');
                $fileName = ImgUploader::UploadImage(
                    'user_images', 
                    $image, 
                    $authenticatedUser->getName(), 
                    300, 
                    300, 
                    false
                );
                
                // Add watermark to the uploaded image
                $imagePath = public_path('user_images/' . $fileName);
                if (file_exists($imagePath)) {
                    $addWatermark($imagePath);
                }
                
                $authenticatedUser->image = $fileName;
                $authenticatedUser->save();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully',
                    'data' => [
                        'user_type' => $userType,
                        'usertype' => $userType,
                        'image_url' => asset('user_images/' . $fileName),
                        'name' => $authenticatedUser->getName()
                    ]
                ]);
            }
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture',
                'errors' => (object)[
                    'server' => 'An error occurred: ' . $e->getMessage()
                ]
            ], 500);
        }
    }
    
    

    /**
     * Delete User Image Helper
     */
    private function deleteUserImage($userId)
    {
        try {
            $user = User::find($userId);
            if ($user && $user->image) {
                $imagePath = public_path('user_images/' . $user->image);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete user image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete Company Logo Helper
     */
    private function deleteCompanyLogo($companyId)
    {
        try {
            $company = Company::find($companyId);
            if ($company && $company->logo) {
                $logoPath = public_path('company_logos/' . $company->logo);
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete company logo: ' . $e->getMessage());
            return false;
        }
    }
    
    
    /**
     * Get all profile details - Simplified version for your UI
     */
    public function profileAlldetails(Request $request)
    {
        try {
            $user = $request->user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            // Common response structure
            $response = [
                'success' => true,
                'message' => 'Profile details retrieved successfully',
                'data' => []
            ];
    
            // For Users (Students/Professionals)
            if ($user instanceof \App\User) {
                $response['data'] = $this->getUserCommonDetails($user);
            } 
            // For Companies
            elseif ($user instanceof \App\Company) {
                $response['data'] = $this->getCompanyCommonDetails($user);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type'
                ], 400);
            }
    
            return response()->json($response);
    
        } catch (\Exception $e) {
            Log::error('Profile All Details API Error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch profile details'
            ], 500);
        }
    }
    
    /**
     * Get user common details (Student/Professional)
     */
    private function getUserCommonDetails($user)
    {
        // Get area of interests as array of names
        $areaOfInterests = [];
        if (!empty($user->area_of_interest_id)) {
            // Handle PostgreSQL array or comma-separated string
            if (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
                // PostgreSQL array format: {1,2,3}
                $ids = trim($user->area_of_interest_id, '{}');
                $ids = array_map('intval', explode(',', $ids));
            } elseif (is_string($user->area_of_interest_id)) {
                // Comma-separated string format
                $ids = array_map('intval', explode(',', $user->area_of_interest_id));
            } else {
                $ids = [];
            }
            
            if (!empty($ids)) {
                $jobTitles = \App\JobTitle::whereIn('id', $ids)
                    ->where('is_active', 1)
                    ->get(['id', 'job_title']);
                
                $areaOfInterests = $jobTitles->pluck('job_title')->toArray();
            }
        }
    
        // Get specialization/skills as array of names
        $skills = [];
        if (!empty($user->specialization)) {
            // Handle comma-separated skill IDs
            $skillIds = array_map('intval', explode(',', $user->specialization));
            
            if (!empty($skillIds)) {
                $jobSkills = \App\JobSkill::whereIn('id', $skillIds)
                    ->where('is_active', 1)
                    ->get(['id', 'job_skill']);
                
                $skills = $jobSkills->pluck('job_skill')->toArray();
            }
        }
    
        // Common response structure for both Student and Professional
        $commonData = [
            'full_name' => $user->getName() ?? '',
            'unique_id' => $user->unique_id ?? '',
            'role' => $user->usertype ?? 'professional', // 'student' or 'professional'
            'college_name' => $user->college_name ?? '',
            'school_name' => $user->school_name ?? '',
            'degree' => $user->degree ?? '',
            'course_duration' => $user->course_duration ?? '',
            'skills' => $skills, // Array of skill names
            'area_of_interests' => $areaOfInterests, // Array of interest names
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
        ];
    
        // Add role-specific fields
        if ($user->usertype === 'student') {
            $commonData['currently_pursuing'] = !empty($user->course_duration) && strpos($user->course_duration, 'Present') !== false;
            
        } elseif ($user->usertype === 'professional') {
            $commonData['job_title'] = $user->headline ?? '';
            $commonData['specialization'] = $user->specialization ?? '';
            $commonData['portfolio_website'] = $user->portfolio_website ?? '';
            $commonData['gst_number'] = $user->gst_number ?? ''; // If stored in user table
            $commonData['upi_id'] = $user->upi_id ?? ''; // If stored in user table
            $commonData['address'] = $user->address ?? $user->location ?? '';
        }
    
        return $commonData;
    }
    
    /**
     * Get company common details
     */
    private function getCompanyCommonDetails($company)
    {
        return [
            'company_name' => $company->name ?? '',
            'unique_id' => $company->unique_id ?? '',
            'email' => $company->email ?? '',
            'phone' => $company->phone ?? '',
            'website' => $company->website ?? '',
            'linkedin_url' => $company->linkedin_url ?? '',
            'gst_number' => $company->gst_number ?? '',
            'upi_id' => $company->upi_id ?? '',
            'address' => $company->location ?? $company->address ?? '',
            'description' => $company->description ?? '',
            'logo' => $company->logo ? asset('company_logos/' . $company->logo) : null,
            'industry' => $company->getIndustry('industry') ?? '',
        ];
    }
    
}