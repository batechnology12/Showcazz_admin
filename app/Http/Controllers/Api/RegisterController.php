<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\User;
use App\Company;
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

    
    // public function completeUserProfile(Request $request)
    // {
    //     try {
    //         $user = $request->user();
            
    //         if (!$user || $user instanceof \App\Company) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid user type or not authenticated'
    //             ], 400);
    //         }

    //         $validator = Validator::make($request->all(), [
    //             'full_name' => 'required|string|max:100',
    //             'college_name' => 'nullable|string|max:255',
    //             'school_name' => 'nullable|string|max:255',
    //             'degree' => 'nullable|string|max:255',
    //             'course_duration' => 'nullable|string|max:100',
    //             'specialization' => 'nullable|string|max:255',
    //             'portfolio_website' => 'nullable|url|max:255',
    //             'area_of_interest_ids' => 'nullable|array',
    //             'area_of_interest_ids.*' => 'exists:job_titles,id',
    //             'visibility_control' => 'sometimes|in:public,private',
    //             'phone' => 'nullable|string|max:20',
    //             'headline' => 'nullable|string|max:255',
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

    //         DB::beginTransaction();

    //         try {
    //             // Update user basic info
    //             $user->first_name = $request->full_name;
            
    //             // Update other fields
    //             $user->college_name = $request->college_name;
    //             $user->school_name = $request->school_name;
    //             $user->degree = $request->degree;
    //             $user->course_duration = $request->course_duration;
                
    //             // Professional-specific fields
    //             if ($user->usertype === 'professional') {
    //                 $user->specialization = $request->specialization;
    //                 $user->portfolio_website = $request->portfolio_website;
    //             }

    //             // Common fields
    //             $user->visibility_control = $request->visibility_control ?? 'public';
    //             $user->phone = $request->phone;
                

    //         // Handle area of interests
    //             if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
    //                 // Get first job title for headline
    //                 $firstJobTitleId = $request->area_of_interest_ids[0];
    //                 $jobTitle = \App\JobTitle::find($firstJobTitleId);
    //                 $user->headline = $jobTitle ? $jobTitle->job_title : $request->headline;
                    
    //                 // Sync area of interests (using pivot table if exists)
    //                 if (method_exists($user, 'areaOfInterests')) {
    //                     $user->areaOfInterests()->sync($request->area_of_interest_ids);
    //                     // Don't set area_of_interest_id column if using pivot table
    //                 } else {
    //                     // OPTION 1: Use DB::raw for proper PostgreSQL array format
    //                     $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interest_ids)) . '}';
    //                     $user->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
                        
    //                     // OPTION 2: Or use the array directly (should work with casts)
    //                     // $user->area_of_interest_id = $request->area_of_interest_ids;
    //                 }
    //             } else {
    //                 $user->headline = $request->headline;
    //                 // Clear area of interests if empty
    //                 if (method_exists($user, 'areaOfInterests')) {
    //                     $user->areaOfInterests()->detach();
    //                 } else {
    //                     // For PostgreSQL, use empty array literal
    //                     $user->area_of_interest_id = DB::raw("'{}'");
    //                 }
    //             }
    //             $user->save();
    //             DB::commit();
    //             // Get area of interest names for response
    //             $areaOfInterests = [];
    //             if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
    //                 $jobTitles = \App\JobTitle::whereIn('id', $request->area_of_interest_ids)
    //                     ->where('is_active', 1)
    //                     ->get(['id', 'job_title']);
    //                 $areaOfInterests = $jobTitles->map(function($jobTitle) {
    //                     return [
    //                         'id' => $jobTitle->id,
    //                         'name' => $jobTitle->job_title
    //                     ];
    //                 })->toArray();
    //             }
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Profile completed successfully',
    //                 'data' => [
    //                     'user' => [
    //                         'id' => $user->id,
    //                         'name' => $user->getName(),
    //                         'email' => $user->email,
    //                         'unique_id' => $user->unique_id,
    //                         'usertype' => $user->usertype,
    //                         'visibility_control' => $user->visibility_control,
    //                         'college_name' => $user->college_name,
    //                         'degree' => $user->degree,
    //                         'headline' => $user->headline,
    //                         'area_of_interests' => $areaOfInterests,
    //                         'profile_completed' => true,
    //                     ]
    //                 ]
    //             ]);
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Profile completion failed',
    //             'errors' => (object)[
    //                 'server' => 'An error occurred: ' . $e->getMessage()
    //             ]
    //         ], 500);
    //     }
    // }


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
                'phone' => 'nullable|string|max:20',
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
            
                // Update other fields
                $user->college_name = $request->college_name;
                $user->school_name = $request->school_name;
                $user->degree = $request->degree;
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
                if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                    // Get first job title for headline
                    $firstJobTitleId = $request->area_of_interest_ids[0];
                    $jobTitle = \App\JobTitle::find($firstJobTitleId);
                    $user->headline = $jobTitle ? $jobTitle->job_title : $request->headline;
                    
                    // For PostgreSQL integer[] column, use array literal
                    $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interest_ids)) . '}';
                    $user->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
                } else {
                    $user->headline = $request->headline;
                    // Clear area of interests if empty
                    $user->area_of_interest_id = DB::raw("'{}'");
                }
                
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
                'phone' => 'nullable|string|max:30',
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
                    'server' => 'An error occurred'
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
                    
                    $userData = [
                        'id' => $user->id,
                        'name' => $user->getName(),
                        'email' => $user->email,
                        'usertype' => $user->usertype ?? 'professional',
                        'email_verified_at' => $user->email_verified_at,
                        'profile_image' => $user->image ? asset('user_images/'.$user->image) : null,
                        'headline' => $user->headline ?? null,
                        'location' => $user->location ?? null,
                    ];

                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => $userData,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'role' => $user->usertype ?? 'professional' 
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
                    
                    $companyData = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email,
                        'usertype' => 'company',
                        'slug' => $company->slug,
                        'email_verified_at' => $company->email_verified_at,
                        'logo' => $company->logo ? asset('company_logos/'.$company->logo) : null,
                        'industry' => $company->getIndustry('industry') ?? null,
                        'location' => $company->location ?? null,
                    ];

                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => $companyData,
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'role' => 'company'
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
            Log::error('Logout API Error: ', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'errors' => (object)[
                    'server' => 'Unable to logout. Please try again.'
                ]
            ], 500);
        }
    }

    /**
     * Get Current User/Company Profile with try-catch
     */
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

            // Check if it's a Company or User
            if ($user instanceof \App\Company) {
                $data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'usertype' => 'company',
                    'slug' => $user->slug,
                    'email_verified_at' => $user->email_verified_at,
                    'logo' => $user->logo ? asset('company_logos/'.$user->logo) : null,
                    'industry' => $user->getIndustry('industry') ?? null,
                    'location' => $user->location ?? null,
                    'description' => $user->description ?? null,
                    'website' => $user->website ?? null,
                    'phone' => $user->phone ?? null,
                ];
            } else {
                // It's a User (Student/Professional)
                $data = [
                    'id' => $user->id,
                    'name' => $user->getName(),
                    'email' => $user->email,
                    'usertype' => $user->usertype ?? 'professional',
                    'email_verified_at' => $user->email_verified_at,
                    'profile_image' => $user->image ? asset('user_images/'.$user->image) : null,
                    'cover_image' => $user->cover_image ? asset('user_images/'.$user->cover_image) : null,
                    'headline' => $user->headline ?? null,
                    'location' => $user->location ?? null,
                    'summary' => $user->getProfileSummary('summary') ?? null,
                    'industry' => $user->getIndustry('industry') ?? null,
                    'date_of_birth' => $user->date_of_birth ?? null,
                    'phone' => $user->phone ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            Log::error('Profile API Error: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch profile',
                'errors' => (object)[
                    'server' => 'Internal server error'
                ]
            ], 500);
        }
    }

    
    /**
     * Update Profile Picture/Logo (Single API for both Users and Companies)
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
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Single field for both
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
                // Get usertype from User model (student/professional)
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
                
                $authenticatedUser->image = $fileName;
                $authenticatedUser->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully',
                    'data' => [
                        'user_type' => $userType, // student or professional
                        'usertype' => $userType, // Added for consistency
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
    
}