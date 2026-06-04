<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\JobTitle;
use App\JobSkill;
use App\Industry;
use App\UserConnection;
use App\UserMessage;
use App\Post;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\PostView;
use App\Models\ChatType;
use App\Models\ChatSession;
use App\Models\PostRepost;
use App\BlockedUser;
use App\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;
use ImgUploader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class RegisterController extends Controller
{
    // Status constants to match UniversalConnectionController
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    private function generateUniqueId($type = 'user', $email = null)
    {
        // Extract name from email (before @)
        $emailName = $email ? explode('@', $email)[0] : 'USR';

        // Remove special characters and numbers
        $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $emailName));

        // Take first 3 letters
        $namePart = substr($cleanName, 0, 3);
        $namePart = str_pad($namePart, 3, 'X'); // If less than 3 letters

        // Determine type letter
        $latest = User::orderBy('id', 'desc')->first();
        
        if ($type === 'company') {
            $typeLetter = 'C';
        } elseif ($type === 'professional') {
            $typeLetter = 'P';
        } else {
            $typeLetter = 'S';
        }

        // Extract last 4 digit sequence safely
        if ($latest && !empty($latest->unique_id)) {
            preg_match('/(\d{4})/', $latest->unique_id, $matches);
            $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        // Random alphabet
        $randomAlphabet = chr(rand(65, 90)); // A-Z

        // Final Unique ID
        return $namePart . $nextNumber . $randomAlphabet . $typeLetter;
    }

    // public function changePassword(Request $request)
    // {
        
    
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'email' => 'required|email',
    //             'current_password' => 'required|string',
    //             'password' => 'required|string|min:8|confirmed|different:current_password',
    //             'password_confirmation' => 'required|string|min:8',
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

    //         $email = $request->email;
    //         $currentPassword = $request->current_password;
    //         $newPassword = $request->password;

    //         // Find user
    //         $user = User::where('email', $email)->first();

    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'User not found',
    //                 'errors' => (object)[
    //                     'email' => 'User not found'
    //                 ]
    //             ], 404);
    //         }

    //         // Validate current password
    //         if (!Hash::check($currentPassword, $user->password)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Current password is incorrect',
    //                 'errors' => (object)[
    //                     'current_password' => 'Current password is incorrect'
    //                 ]
    //             ], 401);
    //         }

    //         // Update password
    //         $user->password = Hash::make($newPassword);
    //         $user->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Password reset successfully',
    //             'data' => [
    //                 'email' => $email,
    //                 'user_type' => $user->usertype,
    //                 'reset' => true
    //             ]
    //         ]);

    //     } catch (Exception $e) {
    //         Log::error('Change password failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to reset password',
    //             'errors' => (object)[
    //                 'server' => 'An error occurred'
    //             ]
    //         ], 500);
    //     }
    // }
    
    
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
    
            // Find user
            $user = User::where('email', $email)->first();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)[
                        'email' => 'User not found'
                    ]
                ], 404);
            }
    
            // Validate current password - Now returns 422 instead of 401
            if (!Hash::check($currentPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => (object)[
                        'current_password' => 'Current password is incorrect'
                    ]
                ], 422);  // Changed from 401 to 422
            }
    
            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'email' => $email,
                    'user_type' => $user->usertype,
                    'reset' => true
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Change password failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }
    
    
    public function togglePushNotification(Request $request)
    {
        try {
            $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            // Toggle push notification
            $user->push_notification = !$user->push_notification;
            $user->save();
    
            $status = $user->push_notification ? 'enabled' : 'disabled';
    
            return response()->json([
                'success' => true,
                'message' => "Push notifications {$status} successfully",
                'data' => [
                    'push_notification' => (bool) $user->push_notification
                ]
            ]);
    
        } catch (\Exception $e) {
            Log::error('Toggle push notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    public function register(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validate only basic registration fields
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'user_type' => 'required|in:student,professional,company',
                'fcm_token' => 'required|string',
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
            
            // Common user data
            $userData = [
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'unique_id' => $this->generateUniqueId($userType, $request->email),
                'usertype' => $userType,
                'visibility_control' => 'public',
                'post_visibility_control' => 'public',
                'message_visibility_control' => 'public',
                'firebase_token' => $request->fcm_token,
                'is_active' => 1,
            ];

            // Type-specific fields
            if ($userType === 'company') {
                $userData['name'] = 'New Company';
                $userData['company_name'] = 'New Company';
                $userData['company_slug'] = Str::random(10) . '-' . time();
            } else {
                $userData['first_name'] = '';
                $userData['last_name'] = '';
                $userData['name'] = 'New User';
            }
            
           

            $user = User::create($userData);
            
            $tokenName = $userType === 'company' ? 'company_auth_token' : 'auth_token';
            $token = $user->createToken($tokenName)->plainTextToken;
            
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
                        'visibility_control' => $user->visibility_control,
                        'post_visibility_control' => $user->post_visibility_control,
                        'message_visibility_control' => $user->message_visibility_control,
                        'profile_completed' => false,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'role' => $userType === 'company' ? 'company' : $userType
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            $user = Auth::user();
            
            if (!$user || $user->usertype === 'company') {
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
                // 'specialization' => 'nullable|array',
                // 'specialization.*' => 'exists:job_skills,id',
                'portfolio_website' => 'nullable|url|max:255',
                // 'area_of_interest_ids' => 'nullable|array',
                // 'area_of_interest_ids.*' => 'exists:job_titles,id',
                'visibility_control' => 'sometimes|in:public,private',
                'headline' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'start_year' => 'nullable|string|max:10',
                'end_year' => 'nullable|string|max:10',
                'currently_pursuing' => 'nullable|boolean',
                'location' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
                'website' => [
                    'nullable',
                    'max:255',
                    'regex:/^https:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/'
                ],
                
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
            
            // Phone validation
            if ($request->has('phone') && !empty($request->phone)) {
                $phone = $request->phone;
            
                if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => (object)[
                            'phone' => 'Please enter a valid 10-digit Indian mobile number.'
                        ]
                    ], 422);
                }
            
                $userExists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();
            
                if ($userExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => (object)[
                            'phone' => 'This phone number is already registered.'
                        ]
                    ], 422);
                }
            }

            DB::beginTransaction();

            try {
                // Update user basic info
                $user->first_name = $request->full_name;
                $user->name = $request->full_name;
                
                // Update other fields
                $user->college_name = $request->college_name;
                $user->company_website = $request->website;
                $user->school_name = $request->school_name;
                $user->degree = $request->degree;
                $user->start_year = $request->start_year;
                $user->end_year = $request->end_year;
                $user->currently_pursuing = $request->currently_pursuing;
                $user->course_duration = $request->course_duration;
                $user->location = $request->location;
                $user->date_of_birth = $request->date_of_birth;
                $user->portfolio_website = $request->website;
                
                // Professional-specific fields
                if ($user->usertype == 'professional' || $user->usertype == 'student') {
                    if (!empty($request->specialization) && is_array($request->specialization)) {
                        $user->specialization = implode(',', $request->specialization);
                    } else {
                        $user->specialization = null;
                    }
                    
                    
                }
                
                
               

                // Common fields
                if ($request->has('visibility_control')) {
                    $user->visibility_control = $request->visibility_control;
                    $user->post_visibility_control = $request->visibility_control;
                    $user->message_visibility_control = $request->visibility_control;
                }
                $user->phone = $request->phone;

                if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                    $user->area_of_interest_id = $request->area_of_interest_ids; // PostgreSQL array
                } else {
                    $user->area_of_interest_id = [];
                }
                
                $user->headline = $request->headline;
                
                $user->save();
                DB::commit();

                // Get area of interest names for response
                $areaOfInterests = [];
                if (!empty($request->area_of_interest_ids) && is_array($request->area_of_interest_ids)) {
                    $jobTitles = JobTitle::whereIn('id', $request->area_of_interest_ids)
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
                    
                    $jobSkills = JobSkill::whereIn('id', $request->specialization)
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
                            'post_visibility_control' => $user->post_visibility_control ?? 'public',
                            'message_visibility_control' => $user->message_visibility_control ?? 'public',
                            'college_name' => $user->college_name,
                            'school_name' => $user->school_name,
                            'degree' => $user->degree,
                            'course_duration' => $user->course_duration,
                            'headline' => $user->headline,
                            'phone' => $user->phone,
                            'portfolio_website' => $user->portfolio_website,
                            'location' => $user->location,
                            'date_of_birth' => $user->date_of_birth,
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
            Log::error('Complete user profile failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            $user = Auth::user();
            
            if (!$user || $user->usertype !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type or not authenticated'
                ], 400);
            }

           
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255', // Owner/Contact name
                'description' => 'required|string|max:255', // Company name - required
             'website' => [
                    'required',
                    'max:255',
                    'regex:/^https:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/'
                ],
                'linkedin_url' => [
                    'required',
                    'url',
                    'max:255',
                    'regex:/^(https?:\/\/)?(www\.)?linkedin\.com\/company\/[a-zA-Z0-9\-_%]+\/?$/i'
                ],
                'gst_number' => 'nullable|string|max:100',
                'upi_id' => [
                    'required',
                    'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/'
                ],
                'visibility_control' => 'sometimes|in:public,private',
                'location' => 'nullable|string|max:255',
                'industry_id' => 'nullable|exists:industries,id',
                'phone' => 'nullable|string|max:20',
                'established_in' => 'nullable|string|max:12',
                'no_of_employees' => 'nullable|string|max:15',
            ], [
                'name.required' => 'Owner/Contact name is required',
                'name.string' => 'Owner/Contact name must be a valid string',
                'name.max' => 'Owner/Contact name cannot exceed 255 characters',
                
                'description.required' => 'Company name is required', // Changed message
                'description.string' => 'Company name must be a valid string',
                'description.max' => 'Company name cannot exceed 255 characters',
                
                'website.required' => 'Website URL is required',
                'website.regex' => 'Please enter a valid website URL starting with https:// (e.g., https://example.com)',
                
                'linkedin_url.required' => 'LinkedIn company page URL is required',
                'linkedin_url.url' => 'Please enter a valid LinkedIn URL',
                'linkedin_url.regex' => 'Please enter a valid LinkedIn company page URL (e.g., https://www.linkedin.com/company/company-name)',
                
                'upi_id.required' => 'UPI ID is required',
                'upi_id.regex' => 'Please enter a valid UPI ID (e.g., shop@okhdfcbank or merchant@paytm)',
                
                'visibility_control.in' => 'Visibility must be either public or private',
                'industry_id.exists' => 'Selected industry does not exist',
                
                'phone.max' => 'Phone number cannot exceed 20 characters',
                'established_in.max' => 'Established year cannot exceed 12 characters',
                'no_of_employees.max' => 'Number of employees cannot exceed 15 characters',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Phone validation
            if ($request->has('phone') && !empty($request->phone)) {
                $phone = $request->phone;
            
                if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => (object)[
                            'phone' => 'Please enter a valid 10-digit Indian mobile number.'
                        ]
                    ], 422);
                }
            
                $phoneExists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();
            
                if ($phoneExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => (object)[
                            'phone' => 'This phone number is already registered.'
                        ]
                    ], 422);
                }
            }

            // Update company details
            $user->first_name = $request->description;
            $user->company_name = $request->description;
            $user->name = $request->name;
            $user->company_website = $request->website;
            $user->linkedin_url = $request->linkedin_url;
            $user->gst_number = $request->gst_number;
            $user->upi_id = $request->upi_id;
            $user->company_description = $request->description;
            $user->phone = $request->phone;
            $user->company_location = $request->location;
            $user->company_industry_id = $request->industry_id;
            $user->company_established_in = $request->established_in;
            $user->company_no_of_employees = $request->no_of_employees;
            
            // Update visibility settings if provided
            if ($request->has('visibility_control')) {
                $user->visibility_control = $request->visibility_control;
                $user->post_visibility_control = $request->visibility_control;
                $user->message_visibility_control = $request->visibility_control;
            }
            
            // Update slug with actual company name
            $user->company_slug = Str::slug($request->company_name . '-' . $user->id);
            $user->is_company_profile_completed = true;
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Company profile completed successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->company_name,
                        'email' => $user->email,
                        'unique_id' => $user->unique_id,
                        'usertype' => 'company',
                        'slug' => $user->company_slug,
                        'visibility_control' => $user->visibility_control,
                        'post_visibility_control' => $user->post_visibility_control ?? 'public',
                        'message_visibility_control' => $user->message_visibility_control ?? 'public',
                        'website' => $user->company_website,
                        'linkedin_url' => $user->linkedin_url,
                        'description' => $user->company_description,
                        'location' => $user->company_location,
                        'phone' => $user->phone,
                        'industry_id' => $user->company_industry_id,
                        'logo' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : null,
                        'profile_completed' => true,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Complete company profile failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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

            if ($user->usertype === 'company') {
                // Check if company profile is completed
                $requiredFields = ['company_name', 'company_slug'];
                foreach ($requiredFields as $field) {
                    if (empty($user->$field)) {
                        $missingFields[] = $field;
                    }
                }
                $isCompleted = empty($missingFields);
            } else {
                // Check if user profile is completed
                $requiredFields = ['first_name'];
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
                    'user_type' => $user->usertype,
                    'visibility' => [
                        'visibility_control' => $user->visibility_control ?? 'public',
                        'post_visibility_control' => $user->post_visibility_control ?? 'public',
                        'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Check profile completion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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

            if ($user->usertype === 'company') {
                $response['data'] = $this->getCompanyEditData($user);
            } else {
                $response['data'] = $this->getUserEditData($user);
            }

            return response()->json($response);

        } catch (Exception $e) {
            Log::error('Get edit profile data failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
    private function getCompanyEditData($user)
    {
        // Get all industries for dropdown
        $industries = Industry::where('is_active', 1)
            ->select('id', 'industry')
            ->orderBy('industry')
            ->get();

        // Parse area_of_interest_id
        $areaOfInterestIds = [];
        if (!empty($user->area_of_interest_id)) {
            if (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
                $ids = trim($user->area_of_interest_id, '{}');
                $areaOfInterestIds = array_map('intval', explode(',', $ids));
            } elseif (is_string($user->area_of_interest_id)) {
                $areaOfInterestIds = array_map('intval', explode(',', $user->area_of_interest_id));
            } elseif (is_array($user->area_of_interest_id)) {
                $areaOfInterestIds = $user->area_of_interest_id;
            }
        }

        return [
            'user_type' => 'company',
            'basic_info' => [
                'company_name' => $user->company_name ?? '',
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'website' => $user->company_website ?? '',
                'linkedin_url' => $user->linkedin_url ?? '',
                'description' => $user->company_description ?? '',
                'location' => $user->company_location ?? $user->location ?? '',
                'visibility_control' => $user->visibility_control ?? 'public',
                'post_visibility_control' => $user->post_visibility_control ?? 'public',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
                'established_in' => $user->company_established_in ?? '',
                'no_of_employees' => $user->company_no_of_employees ?? '',
            ],
            'business_info' => [
                'gst_number' => $user->gst_number ?? '',
                'upi_id' => $user->upi_id ?? '',
                'industry_id' => $user->company_industry_id ?? '',
            ],
            'images' => [
                'logo' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                         ($user->image ? asset('user_images/' . $user->image) : null),
            ],
            'dropdowns' => [
                'industries' => $industries,
                'area_of_interests' => $areaOfInterestIds,
            ],
            'profile_completion' => $this->calculateProfileCompletion($user),
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

        $areaOfInterestIds = [];
        if (!empty($user->area_of_interest_id)) {
            if (is_array($user->area_of_interest_id)) {
                $areaOfInterestIds = array_map('intval', $user->area_of_interest_id);
            } elseif (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
                $ids = trim($user->area_of_interest_id, '{}');
                $areaOfInterestIds = array_map('intval', explode(',', $ids));
            } elseif (is_string($user->area_of_interest_id)) {
                $areaOfInterestIds = array_map('intval', explode(',', $user->area_of_interest_id));
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
                'post_visibility_control' => $user->post_visibility_control ?? 'public',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
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
                'area_of_interests' => $areaOfInterestIds,
                'specializations' => $specializationIds,
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

            if ($user->usertype === 'company') {
                $result = $this->updateCompanyProfile($user, $request);
            } else {
                $result = $this->updateUserProfile($user, $request);
            }

            DB::commit();

            return response()->json($result);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Update profile failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
    private function updateCompanyProfile($user, $request)
    {
        $validator = Validator::make($request->all(), [
            // Basic Info
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'website' => [
                    'required',
                    'max:255',
                    'regex:/^https:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/'
                ],
            'linkedin_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'visibility_control' => 'sometimes|in:public,private',
            'post_visibility_control' => 'sometimes|in:public,private',
            'message_visibility_control' => 'sometimes|in:public,private',
            
            // Business Info
            'gst_number' => 'nullable|string|max:100',
            'upi_id' => 'nullable|string|max:100',
            'industry_id' => 'nullable|exists:industries,id',
            'established_in' => 'nullable|string|max:12',
            'no_of_employees' => 'nullable|string|max:15',
            
            // Area of Interests
            'area_of_interests' => 'nullable|array',
            'area_of_interests.*' => 'exists:job_titles,id',
            
            // Images
            'logo' => 'nullable|string',
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
        $user->company_name = $request->company_name;
        $user->name = $request->company_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->company_website = $request->website;
        $user->linkedin_url = $request->linkedin_url;
        $user->company_description = $request->description;
        $user->company_location = $request->location;
        $user->company_industry_id = $request->industry_id;
        $user->company_established_in = $request->established_in;
        $user->company_no_of_employees = $request->no_of_employees;

        // Update visibility settings if provided
        if ($request->has('visibility_control')) {
            $user->visibility_control = $request->visibility_control;
        }
        if ($request->has('post_visibility_control')) {
            $user->post_visibility_control = $request->post_visibility_control;
        }
        if ($request->has('message_visibility_control')) {
            $user->message_visibility_control = $request->message_visibility_control;
        }

        // Update business info
        $user->gst_number = $request->gst_number;
        $user->upi_id = $request->upi_id;

        // Handle area of interests
        if ($request->has('area_of_interests')) {
            if (!empty($request->area_of_interests) && is_array($request->area_of_interests)) {
                $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interests)) . '}';
                $user->area_of_interest_id = DB::raw("'" . $arrayLiteral . "'");
            } else {
                $user->area_of_interest_id = DB::raw("'{}'");
            }
        }

        // Handle logo update
        if ($request->has('logo') && !empty($request->logo)) {
            $this->handleImageUpload($user, $request->logo, 'company_logo', 'company_logos');
        } elseif ($request->get('remove_logo', false)) {
            $this->deleteImage($user->company_logo, 'company_logos');
            $user->company_logo = null;
        }

        // Update slug if name changed
        if ($user->isDirty('company_name')) {
            $user->company_slug = Str::slug($request->company_name . '-' . $user->id);
        }

        $user->save();

        return [
            'success' => true,
            'message' => 'Company profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->company_name,
                    'email' => $user->email,
                    'usertype' => 'company',
                    'logo' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : null,
                    'slug' => $user->company_slug,
                    'visibility_control' => $user->visibility_control,
                    'post_visibility_control' => $user->post_visibility_control,
                    'message_visibility_control' => $user->message_visibility_control,
                    'profile_completed' => $this->isProfileComplete($user),
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
            'post_visibility_control' => 'sometimes|in:public,private',
            'message_visibility_control' => 'sometimes|in:public,private',
            
            // Education Info
            'college_name' => 'nullable|string|max:255',
            'school_name' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:255',
            'course_duration' => 'nullable|string|max:100',
            'start_year' => 'nullable|string|max:10',
            'end_year' => 'nullable|string|max:10',
            'currently_pursuing' => 'nullable|boolean',
            
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
        $user->start_year = $request->start_year;
        $user->end_year = $request->end_year;
        $user->currently_pursuing = $request->currently_pursuing;

        // Update visibility settings if provided
        if ($request->has('visibility_control')) {
            $user->visibility_control = $request->visibility_control;
        }
        if ($request->has('post_visibility_control')) {
            $user->post_visibility_control = $request->post_visibility_control;
        }
        if ($request->has('message_visibility_control')) {
            $user->message_visibility_control = $request->message_visibility_control;
        }

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
        if ($request->has('area_of_interests')) {
            if (!empty($request->area_of_interests) && is_array($request->area_of_interests)) {
                $arrayLiteral = '{' . implode(',', array_map('intval', $request->area_of_interests)) . '}';
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
                    'visibility_control' => $user->visibility_control,
                    'post_visibility_control' => $user->post_visibility_control,
                    'message_visibility_control' => $user->message_visibility_control,
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

  
    
    
    public function login(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'fcm_token' => 'nullable|string', // make optional if not always required
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
    
            // Find user
            $user = User::where('email', $request->email)->first();
    
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => (object)[
                        'email' => 'These credentials do not match our records.'
                    ]
                ], 401);
            }
    
            // Check active status
            if ($user->is_active != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive',
                    'errors' => (object)[
                        'account' => 'Your account is not active. Please contact admin.'
                    ]
                ], 403);
            }
    
            // Update FCM token if provided
            if ($request->filled('fcm_token') && $user->firebase_token !== $request->fcm_token) {
                $user->firebase_token = $request->fcm_token;
                $user->save();
            }
    
            // Create token
            try {
                $tokenName = $user->usertype === 'company' ? 'company_auth_token' : 'auth_token';
                $token = $user->createToken($tokenName)->plainTextToken;
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
    
            // Profile completion
            $isProfileCompleted = $this->checkProfileCompletionStatus($user);
    
            // User data
            $userData = [
                'id' => $user->id,
                'name' => $user->usertype === 'company'
                    ? ($user->company_name ?? $user->name)
                    : $user->getName(),
                'email' => $user->email,
                'unique_id' => $user->unique_id,
                'usertype' => $user->usertype,
                'slug' => $user->company_slug,
                'email_verified_at' => $user->email_verified_at,
                'profile_image' => $user->usertype === 'company'
                    ? ($user->company_logo ? asset('company_logos/' . $user->company_logo) : null)
                    : ($user->image ? asset('user_images/' . $user->image) : null),
                'headline' => $user->headline ?? null,
                'location' => $user->usertype === 'company'
                    ? ($user->company_location ?? $user->location)
                    : $user->location,
                'visibility_control' => $user->visibility_control ?? 'public',
                'post_visibility_control' => $user->post_visibility_control ?? 'public',
                'message_visibility_control' => $user->message_visibility_control ?? 'public',
                'is_profile_completed' => $isProfileCompleted,
            ];
    
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userData,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'role' => $user->usertype,
                    'profile_completed' => $isProfileCompleted,
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Login API Error', [
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
     * Check Profile Completion Helper
     */
    private function checkProfileCompletionStatus($user)
    {
        try {
            if ($user->usertype === 'company') {
                return !empty($user->company_name) && !empty($user->company_slug);
            } else {
                return !empty($user->first_name);
            }
        } catch (Exception $e) {
            Log::error('Error checking profile completion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout API
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                $user->currentAccessToken()->delete();
                
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
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            // Update last activity
            $user->last_activity = now();
            $user->save();

            // =======================
            // BASIC PROFILE DATA
            // =======================
            if ($user->usertype === 'company') {
                $profileData = [
                    'id' => $user->id,
                    'name' => $user->company_name ?? $user->name,
                    'email' => $user->email,
                    'unique_id' => $user->unique_id,
                    'usertype' => 'company',
                    'slug' => $user->company_slug,
                    'email_verified_at' => $user->email_verified_at,
                    'profile_image' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : null,
                    'industry' => $user->getIndustry('industry') ?? null,
                    'location' => $user->company_location ?? $user->location,
                    'description' => $user->company_description ?? null,
                    'website' => $user->company_website ?? null,
                    'phone' => $user->phone ?? null,
                    'linkedin_url' => $user->linkedin_url ?? null,
                    'gst_number' => $user->gst_number ?? null,
                    'upi_id' => $user->upi_id ?? null,
                    'established_in' => $user->company_established_in ?? null,
                    'no_of_employees' => $user->company_no_of_employees ?? null,
                    'visibility_control' => $user->visibility_control ?? 'public',
                    'post_visibility_control' => $user->post_visibility_control ?? 'public',
                    'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    'last_activity' => $user->last_activity,
                    'last_activity_formatted' => $user->last_activity ? Carbon::parse($user->last_activity)->diffForHumans() : null,
                    'is_online' => $this->isUserOnline($user->last_activity),
                    'push_notification' => $user->push_notification,
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
                    'college_name' => $user->college_name ?? null,
                    'school_name' => $user->school_name ?? null,
                    'degree' => $user->degree ?? null,
                    'course_duration' => $user->course_duration ?? null,
                    'portfolio_website' => $user->portfolio_website ?? null,
                    'gst_number' => $user->gst_number ?? null,
                    'upi_id' => $user->upi_id ?? null,
                    'visibility_control' => $user->visibility_control ?? 'public',
                    'post_visibility_control' => $user->post_visibility_control ?? 'public',
                    'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    'last_activity' => $user->last_activity,
                    'last_activity_formatted' => $user->last_activity ? Carbon::parse($user->last_activity)->diffForHumans() : null,
                    'is_online' => $this->isUserOnline($user->last_activity),
                    'push_notification' => $user->push_notification,
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

            if ($user->usertype !== 'company') {
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

    /**
     * Check if user is online
     */
    // private function isUserOnline($lastActivity)
    // {
    //     if (!$lastActivity) return false;
    //     $lastActivityTime = $lastActivity instanceof Carbon ? $lastActivity : Carbon::parse($lastActivity);
    //     return $lastActivityTime->diffInMinutes(now()) < 5;
    // }
    
    private function isUserOnline($lastActivity)
    {
        if (!$lastActivity) return false;
    
        $lastActivityTime = $lastActivity instanceof Carbon ? $lastActivity : Carbon::parse($lastActivity);
    
        return $lastActivityTime->diffInSeconds(now()) < 10;
    }

    // ==================================================
    // PROFILE COMPLETION
    // ==================================================
    private function calculateProfileCompletion($user)
    {
        if ($user->usertype === 'company') {
            $fields = ['company_name','unique_id','visibility_control','email','company_description','company_website','company_logo'];
        } else {
            $fields = ['first_name','email','visibility_control','college_name','school_name','degree','image'];
        }

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

  
    private function getProfileStats($user)
    {
        $likes = PostLike::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where('is_active', true)
              ->where('is_published', true);
        })->count();
    
        $shares = PostShare::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where('is_active', true)
              ->where('is_published', true);
        })->count();
    
        $worthDiscussing = $this->getWorthDiscussingCount($user);
    
        return [
            'likes' => $this->formatNumber($likes),
            'likes_raw' => $likes,
            'worth_discussing' => $worthDiscussing,
            'shares' => $this->formatNumber($shares),
            'shares_raw' => $shares
        ];
    }


   
    private function getUserPostsWithStats($user)
    {
        return Post::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('post_type_id', 1)
            ->latest()
            ->get()
            ->map(function ($post) use ($user) {
                $images = $post->images ? json_decode($post->images, true) : [];
    
                $postWorthDiscussing = $this->getPostWorthDiscussingCount($post, $user);
                
                $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
                $isRepost = !is_null($repostRecord);
                
                $repostInfo = null;
                if ($isRepost && $repostRecord) {
                    $originalPost = Post::find($repostRecord->original_post_id);
                    
                    if ($originalPost) {
                        $originalAuthor = $this->getEntityData($originalPost->user_id);
                        
                        $repostInfo = [
                            'repost_record_id' => $repostRecord->id,
                            'original_post_id' => $originalPost->id,
                            'original_post_title' => $originalPost->title,
                            'original_post_created_at' => $originalPost->created_at->diffForHumans(),
                            'original_author' => [
                                'id' => $originalAuthor['id'] ?? null,
                                'name' => $originalAuthor['name'] ?? 'Unknown User',
                                'usertype' => $originalAuthor['usertype'] ?? 'unknown',
                                'image' => $originalAuthor['image'] ?? null,
                            ],
                            'repost_comment' => $repostRecord->repost_comment,
                            'reposted_at' => $post->created_at->diffForHumans(),
                        ];
                    }
                } else {
                    $reposts = PostRepost::where('original_post_id', $post->id)->count();
                    $repostInfo = [
                        'total_reposts' => $reposts,
                    ];
                }
    
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'short_description' => $post->short_description,
                    'category' => optional($post->category)->name,
                    'subcategory' => optional($post->subcategory)->name,
                    'is_job_post' => $post->is_job_post ?? false,
                    'is_repost' => $isRepost,
                    'job_id' => $post->job_id,
                    'repost_info' => $repostInfo,
                    'stats' => [
                        'views' => $post->views_count ?? 0,
                        'likes' => $post->likes_count ?? 0,
                        'comments' => $post->comments_count ?? 0,
                        'shares' => $post->shares_count ?? 0,
                        'worth_discussing' => $postWorthDiscussing,
                    ],
                    'image' => $images[0] ?? null,
                    'image_url' => isset($images[0]) ? asset('post_images/' . $images[0]) : null,
                    'created_at' => $post->created_at,
                    'created_at_formatted' => $post->created_at->diffForHumans(),
                ];
            });
    }
    

    /**
     * Get entity data by ID
     */
    private function getEntityData($id)
    {
        if (!$id) {
            return null;
        }

        $user = User::find($id);
        if ($user) {
            return [
                'id' => $user->id,
                'name' => $user->usertype === 'company' ? ($user->company_name ?? $user->name) : $user->getName(),
                'usertype' => $user->usertype ?? 'user',
                'image' => $user->usertype === 'company' 
                    ? ($user->company_logo ? asset('company_logos/' . $user->company_logo) : null)
                    : ($user->image ? asset('user_images/' . $user->image) : null),
            ];
        }

        return null;
    }
    
    
    /**
     * Get worth discussing count for user's profile stats
     * Counts unique users per post (based on messages, not sessions)
     */
    private function getWorthDiscussingCount($user)
    {
        // Get all posts created by this user
        $userPostIds = Post::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_published', true)
            ->pluck('id')
            ->toArray();
        
        if (empty($userPostIds)) {
            return 0;
        }
        
        $totalWorthDiscussing = 0;
        
        foreach ($userPostIds as $postId) {
            // Get unique users who have discussed this specific post
            $uniqueUsersForPost = DB::table('user_messages')
                ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
                ->where('chat_types.slug', 'worth_discussing')
                ->where('user_messages.listing_id', $postId)  // Based on POST ID
                ->where(function($q) use ($user) {
                    $q->where('user_messages.from_id', $user->id)
                      ->orWhere('user_messages.to_id', $user->id);
                })
                ->select(DB::raw('CASE 
                    WHEN user_messages.from_id = ' . $user->id . ' THEN user_messages.to_id 
                    ELSE user_messages.from_id 
                END as other_user_id'))
                ->distinct()
                ->get()
                ->pluck('other_user_id')
                ->unique()
                ->count();
            
            $totalWorthDiscussing += $uniqueUsersForPost;
        }
        
        return $totalWorthDiscussing;
    }
    
    /**
     * Get worth discussing count for a specific post
     * Counts unique users who have discussed this post (based on messages, not sessions)
     */
    private function getPostWorthDiscussingCount_new($post, $user)
    {
        // Get unique users who have discussed this specific post
        $uniqueUsersForPost = DB::table('user_messages')
            ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
            ->where('chat_types.slug', 'worth_discussing')
            ->where('user_messages.listing_id', $post->id)  // Based on POST ID
            ->where(function($q) use ($user) {
                $q->where('user_messages.from_id', $user->id)
                  ->orWhere('user_messages.to_id', $user->id);
            })
            ->select(DB::raw('CASE 
                WHEN user_messages.from_id = ' . $user->id . ' THEN user_messages.to_id 
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
     * Get worth discussing count for a specific post
     */
    private function getPostWorthDiscussingCount($post, $user)
    {
        return ChatSession::where('post_id', $post->id)
            ->whereNotNull('worth_discussing_point_id')
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('user1_id', $user->id)
                  ->orWhere('user2_id', $user->id);
            })
            ->count();
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
            ->take(100)
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
    // private function getConnectionStats($user)
    // {
    //     if ($user->usertype === 'company') {
    //         $followers = UserConnection::where('following_id', $user->id)
    //             ->where('status', self::STATUS_ACCEPTED)
    //             ->count();

    //         return [
    //             'followers' => $this->formatNumber($followers),
    //             'followers_raw' => $followers
    //         ];
    //     }

    //     $following = UserConnection::where('follower_id', $user->id)
    //         ->where('status', self::STATUS_ACCEPTED)
    //         ->count();

    //     $followers = UserConnection::where('following_id', $user->id)
    //         ->where('status', self::STATUS_ACCEPTED)
    //         ->count();

    //     return [
    //         'following' => $this->formatNumber($following),
    //         'following_raw' => $following,
    //         'followers' => $this->formatNumber($followers),
    //         'followers_raw' => $followers
    //     ];
    // }
    
    private function getConnectionStats($user)
    {
        if ($user->usertype === 'company') {
            $followers = UserConnection::where('following_id', $user->id)
                ->where('status', self::STATUS_ACCEPTED)
                ->count();
    
            return [
                'followers' => $this->formatNumber($followers),
                'followers_raw' => $followers,
                'following' => '0',
                'following_raw' => 0,  // Add this
                'following_count' => 0,  // Add this for consistency
            ];
        }
    
        $following = UserConnection::where('follower_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
    
        $followers = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
    
        return [
            'following' => $this->formatNumber($following),
            'following_raw' => $following,
            'followers' => $this->formatNumber($followers),
            'followers_raw' => $followers,
            'following_count' => $following,  // Add this for consistency
            'followers_count' => $followers,  // Add this for consistency
        ];
    }

    // ==================================================
    // OPPORTUNITIES
    // ==================================================
    private function getOpportunityStats($user)
    {
        if ($user->usertype === 'company') {
            $jobs = Job::where('company_id', $user->id)
                ->where('is_active', true)->count();

            return [
                'job_posts' => $jobs,
                'active_jobs' => $jobs
            ];
        }
        
        return [
            'recommended_jobs' => Job::where('is_active', true)->count(),
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

   
    
    public function updateProfilePicture(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            ], [
                'image.required' => 'Please select an image file',
                'image.image' => 'The file must be a valid image',
                'image.mimes' => 'The image must be a JPEG, PNG, JPG, or GIF file',
                'image.max' => 'The image size must not exceed 10MB',
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
    
            // ✅ Watermark closure using TTF font
             $addWatermark = function($imagePath) {
                try {
                    $user = Auth::user();
                   // $watermarkText = "showcazz #" . $user->unique_id;
                    $watermarkText = $user->unique_id;
                    $watermarkPadding = 20;
    
                    // Font cached in /tmp (allowed by open_basedir)
                    $fontPath = '/tmp/DejaVuSans-Bold.ttf';
    
                    // Delete if corrupt/incomplete
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
    
                    if (!file_exists($imagePath) || !is_readable($imagePath)) {
                        Log::error('Watermark Error: Image not found: ' . $imagePath);
                        return false;
                    }
    
                    list($width, $height, $type) = getimagesize($imagePath);
    
                    switch ($type) {
                        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($imagePath); break;
                        case IMAGETYPE_PNG:  $image = imagecreatefrompng($imagePath);  break;
                        case IMAGETYPE_GIF:  $image = imagecreatefromgif($imagePath);  break;
                        case IMAGETYPE_WEBP: $image = imagecreatefromwebp($imagePath); break;
                        default:
                            Log::error('Watermark Error: Unsupported image type.');
                            return false;
                    }
    
                    if (!$image) return false;
    
                    // Convert PNG to truecolor
                    if ($type === IMAGETYPE_PNG) {
                        $trueColor = imagecreatetruecolor($width, $height);
                        imagealphablending($trueColor, false);
                        imagesavealpha($trueColor, true);
                        imagecopy($trueColor, $image, 0, 0, 0, 0, $width, $height);
                        imagedestroy($image);
                        $image = $trueColor;
                    }
    
                    imagealphablending($image, true);
    
                    // Light/semi-transparent white text + subtle shadow
                    $textColor   = imagecolorallocatealpha($image, 255, 255, 255, 80);
                    $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 100);
    
                    // Font size = 5% of image width, minimum 24px
                    $fontSize = max(10, (int) round($width * 0.03));
    
                    $bbox = imagettfbbox($fontSize, 0, $fontPath, $watermarkText);
                    if ($bbox === false) {
                        Log::error('Watermark Error: imagettfbbox failed.');
                        unlink($fontPath);
                        return false;
                    }
    
                    $textHeight = abs($bbox[5] - $bbox[1]);
    
                    // ✅ Position: left side, bottom
                    $x = $watermarkPadding;
                    $y = $height - $watermarkPadding;
    
                    imagettftext($image, $fontSize, 0, $x + 2, $y + 2, $shadowColor, $fontPath, $watermarkText);
                    imagettftext($image, $fontSize, 0, $x,     $y,     $textColor,   $fontPath, $watermarkText);
    
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
            };
    
            // Handle based on user type
            if ($user->usertype === 'company') {
                $image = $request->file('image');
                
                if ($user->company_logo) {
                    $this->deleteImage($user->company_logo, 'company_logos');
                }
                
                $fileName = ImgUploader::UploadImage(
                    'company_logos', 
                    $image, 
                    $user->company_name ?? $user->name, 
                    300, 
                    300, 
                    false
                );
                
                $imagePath = public_path('company_logos/' . $fileName);
                if (file_exists($imagePath)) {
                    $addWatermark($imagePath);
                }
                
                $user->company_logo = $fileName;
                $user->save();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Company logo updated successfully',
                    'data' => [
                        'user_type' => 'company',
                        'image_url' => asset('company_logos/' . $fileName),
                        'name' => $user->company_name ?? $user->name,
                        'usertype' => 'company'
                    ]
                ]);
    
            } else {
                if ($user->image) {
                    $this->deleteImage($user->image, 'user_images');
                }
                
                $image = $request->file('image');
                $fileName = ImgUploader::UploadImage(
                    'user_images', 
                    $image, 
                    $user->getName(), 
                    300, 
                    300, 
                    false
                );
                
                $imagePath = public_path('user_images/' . $fileName);
                if (file_exists($imagePath)) {
                    $addWatermark($imagePath);
                }
                
                $user->image = $fileName;
                $user->save();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully',
                    'data' => [
                        'user_type' => $user->usertype,
                        'usertype' => $user->usertype,
                        'image_url' => asset('user_images/' . $fileName),
                        'name' => $user->getName()
                    ]
                ]);
            }
    
        } catch (Exception $e) {
            Log::error('Update profile picture failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
     * Get all profile details
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
    
            $response = [
                'success' => true,
                'message' => 'Profile details retrieved successfully',
                'data' => []
            ];
    
            if ($user->usertype === 'company') {
                $response['data'] = $this->getCompanyCommonDetails($user);
            } else {
                $response['data'] = $this->getUserCommonDetails($user);
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
            if (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
                $ids = trim($user->area_of_interest_id, '{}');
                $ids = array_map('intval', explode(',', $ids));
            } elseif (is_string($user->area_of_interest_id)) {
                $ids = array_map('intval', explode(',', $user->area_of_interest_id));
            } elseif (is_array($user->area_of_interest_id)) {
                $ids = $user->area_of_interest_id;
            } else {
                $ids = [];
            }
            
            if (!empty($ids)) {
                $jobTitles = JobTitle::whereIn('id', $ids)
                    ->where('is_active', 1)
                    ->get(['id', 'job_title']);
                
                $areaOfInterests = $jobTitles->pluck('job_title')->toArray();
            }
        }
    
        // Get specialization/skills as array of names
        $skills = [];
        if (!empty($user->specialization)) {
            $skillIds = array_map('intval', explode(',', $user->specialization));
            
            if (!empty($skillIds)) {
                $jobSkills = JobSkill::whereIn('id', $skillIds)
                    ->where('is_active', 1)
                    ->get(['id', 'job_skill']);
                
                $skills = $jobSkills->pluck('job_skill')->toArray();
            }
        }
    
        $commonData = [
            'full_name' => $user->getName() ?? '',
            'unique_id' => $user->unique_id ?? '',
            'role' => $user->usertype ?? 'professional',
            'college_name' => $user->college_name ?? '',
            'school_name' => $user->school_name ?? '',
            'degree' => $user->degree ?? '',
            'course_duration' => $user->course_duration ?? '',
            'skills' => $skills,
            'area_of_interests' => $areaOfInterests,
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
            'visibility_control' => $user->visibility_control ?? 'public',
            'post_visibility_control' => $user->post_visibility_control ?? 'public',
            'message_visibility_control' => $user->message_visibility_control ?? 'public',
        ];
    
        if ($user->usertype === 'student') {
            $commonData['currently_pursuing'] = !empty($user->course_duration) && strpos($user->course_duration, 'Present') !== false;
        } elseif ($user->usertype === 'professional') {
            $commonData['job_title'] = $user->headline ?? '';
            $commonData['specialization'] = $user->specialization ?? '';
            $commonData['portfolio_website'] = $user->portfolio_website ?? '';
            $commonData['gst_number'] = $user->gst_number ?? '';
            $commonData['upi_id'] = $user->upi_id ?? '';
            $commonData['address'] = $user->address ?? $user->location ?? '';
        }
    
        return $commonData;
    }

    /**
     * Get company common details
     */
    private function getCompanyCommonDetails($user)
    {
        $industry = $user->company_industry_id ? Industry::find($user->company_industry_id) : null;
        
        return [
            'company_name' => $user->company_name ?? '',
            'unique_id' => $user->unique_id ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'website' => $user->company_website ?? '',
            'linkedin_url' => $user->linkedin_url ?? '',
            'gst_number' => $user->gst_number ?? '',
            'upi_id' => $user->upi_id ?? '',
            'address' => $user->company_location ?? $user->location ?? '',
            'description' => $user->company_description ?? '',
            'logo' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : null,
            'industry' => $industry ? $industry->industry : '',
            'visibility_control' => $user->visibility_control ?? 'public',
            'post_visibility_control' => $user->post_visibility_control ?? 'public',
            'message_visibility_control' => $user->message_visibility_control ?? 'public',
        ];
    }

    private function findUserById($id)
    {
        return User::find($id);
    }

    /**
     * Get user's connection IDs (accepted connections)
     */
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

    /**
     * Get blocked user IDs
     */
    private function getBlockedUserIds($userId)
    {
        // From UserConnection table (users I blocked)
        $blockedFromConnection = UserConnection::where('follower_id', $userId)
            ->where('status', self::STATUS_BLOCKED)
            ->pluck('following_id')
            ->toArray();
        
        // From BlockedUser table if it exists
        $blockedFromBlockedTable = [];
        if (class_exists('\App\BlockedUser')) {
            $blockedFromBlockedTable = \App\BlockedUser::where('blocker_id', $userId)
                ->pluck('blocked_id')
                ->toArray();
        }
        
        return array_unique(array_merge($blockedFromConnection, $blockedFromBlockedTable));
    }

    /**
     * Check if user can view profile based on visibility
     */
    private function canViewProfile($currentUser, $targetUser)
    {
        if ($currentUser->id == $targetUser->id) {
            return true;
        }
        
        $visibility = $targetUser->visibility_control ?? 'public';
        
        if ($visibility == 'public') {
            return true;
        }
        
        if ($visibility == 'private') {
            $connectionStatus = $this->checkIfConnected($currentUser, $targetUser->id);
            return in_array($connectionStatus, ['accepted', 'following']);
        }
        
        return false;
    }

    /**
     * Check if users are connected
     */
     private function checkIfConnected($currentUser, $targetId)
    {
        
        
       
        if (!$targetId) {
            return 'none';
        }
    
        if ($currentUser->id == $targetId) {
            return 'self';
        }
    
        // Check block status
        if (BlockedUser::isBlocked($currentUser->id, $targetId)) {
            return 'blocked';
        }
    
        if (BlockedUser::isBlocked($targetId, $currentUser->id)) {
            return 'blocked_by_them';
        }
    
        $connection = UserConnection::where(function ($query) use ($currentUser, $targetId) {
                $query->where('follower_id', $currentUser->id)
                      ->where('following_id', $targetId);
            })
            ->orWhere(function ($query) use ($currentUser, $targetId) {
                $query->where('following_id', $currentUser->id)
                      ->where('follower_id', $targetId);
            })
            ->first();
            
       
    
        if ($connection) {
    
            // If current user sent request
            if ($connection->follower_id == $currentUser->id) {
                return $connection->status;
            }
    
            // If target user sent request
            if ($connection->status == self::STATUS_ACCEPTED) {
                return 'accepted';
            }
    
            if ($connection->status == self::STATUS_PENDING) {
                return 'pending_from_them';
            }
        }
    
        return 'none';
    }
   

    /**
     * Get user profile by ID (for viewing other users' profiles)
     */
    public function getUserProfileById(Request $request, $id)
    {
        
        
        try {
            $currentUser = Auth::user();
           
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'post_type' => 'nullable|in:all,job,general',
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
    
            $postType = $request->get('post_type', 'all');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 100);
    
            $targetUser = $this->findUserById($id);
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user_id' => 'User not found']
                ], 404);
            }

            // Check if user is blocked
            $excludedUserIds = $this->getBlockedUserIds($currentUser->id);
            if (in_array($targetUser->id, $excludedUserIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot view this profile',
                    'errors' => (object)['profile' => 'You have blocked this user']
                ], 403);
            }
    
            $canViewProfile = $this->canViewProfile($currentUser, $targetUser);
            
            if (!$canViewProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'This profile is private',
                    'errors' => (object)['profile' => 'You are not authorized to view this profile'],
                    'data' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->usertype === 'company' ? ($targetUser->company_name ?? $targetUser->name) : $targetUser->getName(),
                        'usertype' => $targetUser->usertype,
                        'visibility_control' => $targetUser->visibility_control ?? 'private',
                        'is_private' => true,
                        'connection_status' => $this->checkIfConnected($currentUser, $targetUser->id),
                    ]
                ], 403);
            }
    

            $connectionStatus = $this->checkIfConnected($currentUser, $targetUser->id);
            $mutualConnections = $this->getMutualConnectionsForProfile($currentUser->id, $targetUser->id);
            $connectionIds = $this->getUserConnections($currentUser->id);
    
            if ($targetUser->usertype === 'company') {
                $profileData = $this->formatCompanyProfileForView($targetUser, $currentUser, $connectionStatus, $mutualConnections, $postType, $page, $perPage, $connectionIds);
            } else {
                $profileData = $this->formatUserProfileForView($targetUser, $currentUser, $connectionStatus, $mutualConnections, $postType, $page, $perPage, $connectionIds);
            }
            
            
            $chatSession = $this->getChatSessionWithUser($currentUser->id, $targetUser->id);

            $profileData['is_message'] = !is_null($chatSession);
            $profileData['chat_session'] = $chatSession;
    
            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => $profileData
            ]);
    
        } catch (\Exception $e) {
            
            
            
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    
    
    public function getUserPostById(Request $request, $userId, $postId)
    {
        
       
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'post_type' => 'nullable|in:all,job,general',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'user_posts_type' => 'nullable|in:all,job,general',
                'user_posts_page' => 'nullable|integer|min:1',
                'user_posts_per_page' => 'nullable|integer|min:1|max:50',
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
    
            $targetUser = $this->findUserById($userId);
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
    
            // Check if current user can view this user's profile
            $canViewProfile = $this->canViewProfile($currentUser, $targetUser);
            
            if (!$canViewProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'This profile is private',
                    'errors' => (object)['profile' => 'You are not authorized to view this profile']
                ], 403);
            }
    
            // Check if user is blocked
            $excludedUserIds = $this->getBlockedUserIds($currentUser->id);
            if (in_array($userId, $excludedUserIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot view content from this user'
                ], 403);
            }
    
            // Get connection status between current user and target user
            $connectionStatus = $this->checkIfConnected($currentUser, $targetUser->id);
            
            // Get mutual connections
            $mutualConnections = $this->getMutualConnectionsForProfile($currentUser->id, $targetUser->id);
    
            // Get the post
            $post = Post::where('id', $postId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->where('post_type_id', 1)
                ->where('is_published', true)
                ->with(['category', 'subcategory', 'postType'])
                ->withCount(['likes', 'comments', 'shares', 'views'])
                ->first();
    
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }
    
            // Check post visibility
            $postVisibility = $targetUser->post_visibility_control ?? 'public';
            $connectionIds = $this->getUserConnections($currentUser->id);
            
            if ($postVisibility === 'private' && !in_array($userId, $connectionIds) && $currentUser->id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'This post is private',
                    'errors' => (object)['post' => 'You are not authorized to view this post']
                ], 403);
            }
    
            // Record view
            PostView::firstOrCreate(
                [
                    'post_id' => $post->id,
                    'user_id' => $currentUser->id,
                ],
                ['ip_address' => $request->ip()]
            );
    
            // Check if liked
            $isLiked = PostLike::where('post_id', $post->id)
                ->where('user_id', $currentUser->id)
                ->exists();
    
            // Get worth discussing count
            $worthDiscussing = $this->getPostWorthDiscussingCount_new($post, $currentUser);
    
            // Format images
            $images = $post->images ? json_decode($post->images, true) : [];
            $formattedImages = array_map(function($img) {
                return asset('post_images/' . $img);
            }, $images);
            
            // ✅ NEW: Format files
            $files = $post->files ? json_decode($post->files, true) : [];
            $formattedFiles = array_map(function($file) {
                $filePath = public_path('post_files/' . $file);
                $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                
                return [
                    'name' => $file,
                    'url' => asset('post_files/' . $file),
                    'size' => $this->formatFileSize($fileSize),
                    'size_bytes' => $fileSize,
                    'extension' => $fileExtension,
                    'icon' => $this->getFileIcon($fileExtension),
                ];
            }, $files);
    
            // Determine post type
            $postType = 'general';
            $jobType = null;
            $jobTypeLabel = null;
            $isJobPost = false;
            
            if (in_array($post->category_id, [5, 6, 7])) {
                $isJobPost = true;
                $postType = 'job';
                if ($post->category_id == 5) {
                    $jobType = 'mini_mission';
                    $jobTypeLabel = 'Mini Mission';
                } elseif ($post->category_id == 6) {
                    $jobType = 'internship';
                    $jobTypeLabel = 'Internship';
                } elseif ($post->category_id == 7) {
                    $jobType = 'fresherrole';
                    $jobTypeLabel = 'Fresher Role';
                }
            }
    
            // Job details
            $jobDetails = null;
            if ($isJobPost) {
                $skillsRequired = [];
                if ($post->skills_required) {
                    $skillIds = is_array($post->skills_required) ? $post->skills_required : 
                        (json_decode($post->skills_required, true) ?: []);
                    
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
    
                $jobDetails = [
                    'role_type' => $post->role_type,
                    'work_mode' => $post->work_mode,
                    'experience_required' => $post->experience_required,
                    'salary_range' => $post->salary_range,
                    'company_name' => $post->company_name,
                    'job_location' => $post->job_location,
                    'application_deadline' => $post->application_deadline,
                    'application_deadline_formatted' => $post->application_deadline ? 
                        Carbon::parse($post->application_deadline)->format('d M Y') : null,
                    'skills_required' => $skillsRequired,
                ];
                
                if ($post->category_id == 5) {
                    $jobDetails['deliverables'] = $post->deliverables;
                    $jobDetails['timeline_start'] = $post->timeline_start;
                    $jobDetails['timeline_end'] = $post->timeline_end;
                } elseif ($post->category_id == 6) {
                    $jobDetails['stipend_amount'] = $post->stipend_amount;
                    $jobDetails['stipend_currency'] = $post->stipend_currency;
                    $jobDetails['convertible_to_full_time'] = $post->convertible_to_full_time;
                    $jobDetails['internship_duration'] = $post->internship_duration;
                }
            }
    
            // ============================================
            // REPOST HANDLING - Check if this post is a repost
            // ============================================
            $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
            $isRepost = !is_null($repostRecord);
    
            $repostInfo = null;
            $repostComment = null;
            $repostCount = 0;
    
            if ($isRepost && $repostRecord) {
                // Get the repost comment
                $repostComment = $repostRecord->repost_comment;
                
                // Get the original post
                $originalPost = Post::find($repostRecord->original_post_id);
                
                if ($originalPost) {
                    $originalAuthor = $this->getEntityData($originalPost->user_id);
                    
                    // Format original post images
                    $originalImages = $originalPost->images ? json_decode($originalPost->images, true) : [];
                    $formattedOriginalImages = array_map(function($img) {
                        return asset('post_images/' . $img);
                    }, $originalImages);
                    
                    // ✅ NEW: Format original post files
                    $originalFiles = $originalPost->files ? json_decode($originalPost->files, true) : [];
                    $formattedOriginalFiles = array_map(function($file) {
                        $filePath = public_path('post_files/' . $file);
                        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                        
                        return [
                            'name' => $file,
                            'url' => asset('post_files/' . $file),
                            'size' => $this->formatFileSize($fileSize),
                            'size_bytes' => $fileSize,
                            'extension' => $fileExtension,
                            'icon' => $this->getFileIcon($fileExtension),
                        ];
                    }, $originalFiles);
                    
                    $repostInfo = [
                        'repost_record_id' => $repostRecord->id,
                        'repost_comment' => $repostComment,
                        'reposted_at' => $repostRecord->created_at,
                        'reposted_at_formatted' => $repostRecord->created_at->diffForHumans(),
                        'original_post' => [
                            'id' => $originalPost->id,
                            'title' => $originalPost->title,
                            'content' => $originalPost->content,
                            'short_description' => $originalPost->short_description,
                            'category_id' => $originalPost->category_id,
                            'category' => optional($originalPost->category)->name,
                            'subcategory_id' => $originalPost->subcategory_id,
                            'subcategory' => optional($originalPost->subcategory)->name,
                            'created_at' => $originalPost->created_at,
                            'created_at_formatted' => $originalPost->created_at->diffForHumans(),
                            'images' => $formattedOriginalImages,
                            'files' => $formattedOriginalFiles,
                            'has_images' => !empty($formattedOriginalImages),
                            'has_files' => !empty($formattedOriginalFiles),
                            'thumbnail' => !empty($formattedOriginalImages) ? $formattedOriginalImages[0] : 
                                          (!empty($formattedOriginalFiles) ? $formattedOriginalFiles[0]['icon'] : null),
                            'stats' => [
                                'views' => $originalPost->views_count ?? 0,
                                'likes' => $originalPost->likes_count ?? 0,
                                'comments' => $originalPost->comments_count ?? 0,
                                'shares' => $originalPost->shares_count ?? 0,
                                'reposts' => $originalPost->repost_count ?? 0,
                            ],
                            'author' => $originalAuthor,
                        ],
                    ];
                }
            } else {
                // Count how many times this post has been reposted
                $repostCount = PostRepost::where('original_post_id', $post->id)->count();
            }
    
            // ============================================
            // GET USER'S OTHER POSTS
            // ============================================
            $userPostsType = $request->get('user_posts_type', 'all');
            $userPostsPage = $request->get('user_posts_page', 1);
            $userPostsPerPage = $request->get('user_posts_per_page', 100);
            
            $userPostsData = $this->getUserPostsWithFilter(
                $targetUser->id, 
                $currentUser, 
                $userPostsType, 
                $userPostsPage, 
                $userPostsPerPage, 
                $connectionIds
            );
    
            // Determine if current user is following the author
            $isFollowing = false;
            $isFollower = false;
            
            if ($targetUser->usertype === 'company') {
                $isFollowing = UserConnection::where('follower_id', $currentUser->id)
                    ->where('following_id', $targetUser->id)
                    ->where('status', self::STATUS_ACCEPTED)
                    ->exists();
            } else {
                if ($connectionStatus == self::STATUS_ACCEPTED) {
                    $isFollowing = true;
                    $isFollower = true;
                } elseif ($connectionStatus == 'accepted') {
                    $isFollowing = true;
                } elseif ($connectionStatus == 'pending_from_them') {
                    $isFollower = true;
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => [
                    'post' => [
                        'id' => $post->id,
                        'title' => $post->title,
                        'content' => $post->content,
                        'short_description' => $post->short_description,
                        'category_id' => $post->category_id,
                        'category' => optional($post->category)->name,
                        'subcategory_id' => $post->subcategory_id,
                        'subcategory' => optional($post->subcategory)->name,
                        'post_type' => $postType,
                        'job_type' => $jobType,
                        'job_type_label' => $jobTypeLabel,
                        'is_job_post' => $isJobPost,
                        
                        // Repost related fields
                        'is_repost' => $isRepost,
                        'repost_comment' => $repostComment,
                        'repost_info' => $repostInfo,
                        'repost_count' => $repostCount,
                        
                        // Job details
                        'job_details' => $jobDetails,
                        
                        // Media
                        'images' => $formattedImages,
                        'files' => $formattedFiles,
                        'has_images' => !empty($formattedImages),
                        'has_files' => !empty($formattedFiles),
                        'thumbnail' => !empty($formattedImages) ? $formattedImages[0] : 
                                      (!empty($formattedFiles) ? $formattedFiles[0]['icon'] : null),
                        
                        // Stats
                        'stats' => [
                            'views' => $post->views_count ?? 0,
                            'likes' => $post->likes_count ?? 0,
                            'comments' => $post->comments_count ?? 0,
                            'shares' => $post->shares_count ?? 0,
                            'reposts' => $repostCount,
                            'worth_discussing' => $worthDiscussing,
                        ],
                        'is_liked' => $isLiked,
                        'created_at' => $post->created_at,
                        'created_at_formatted' => $post->created_at->diffForHumans(),
                    ],
                    'author' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->usertype === 'company' ? ($targetUser->company_name ?? $targetUser->name) : $targetUser->getName(),
                        'usertype' => $targetUser->usertype,
                        'image' => $targetUser->usertype === 'company' 
                            ? ($targetUser->company_logo ? asset('company_logos/' . $targetUser->company_logo) : null)
                            : ($targetUser->image ? asset('user_images/' . $targetUser->image) : null),
                        'headline' => $targetUser->headline ?? null,
                        'location' => $targetUser->location ?? ($targetUser->usertype === 'company' ? $targetUser->company_location : null),
                        'visibility_control' => $targetUser->visibility_control ?? 'public',
                    ],
                    'connection' => [
                        'status' => $connectionStatus,
                        'is_following' => $isFollowing,
                        'is_follower' => $isFollower,
                        'can_follow' => !in_array($connectionStatus, [self::STATUS_ACCEPTED, 'following', self::STATUS_PENDING, self::STATUS_BLOCKED, 'blocked_by_them']),
                        'can_unfollow' => in_array($connectionStatus, [self::STATUS_ACCEPTED, 'following']),
                        'can_accept' => $connectionStatus == 'pending_from_them',
                        'can_reject' => $connectionStatus == 'pending_from_them',
                        'can_block' => !in_array($connectionStatus, [self::STATUS_BLOCKED, 'blocked_by_them']),
                        'can_unblock' => in_array($connectionStatus, [self::STATUS_BLOCKED, 'blocked_by_them']),
                    ],
                    'mutual_connections' => $mutualConnections,
                    'user_posts' => [
                        'posts' => $userPostsData['posts'],
                        'pagination' => [
                            'current_page' => $userPostsData['current_page'],
                            'per_page' => $userPostsData['per_page'],
                            'total' => $userPostsData['total_count'],
                            'last_page' => $userPostsData['last_page'],
                        ],
                        'filters' => $userPostsData['filters'],
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Get user post failed', [
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
     * Format file size to human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Get file icon based on extension
     */
    private function getFileIcon($extension)
    {
        $extension = strtolower($extension);
        
        $icons = [
            'pdf' => asset('icons/pdf-icon.png'),
            'doc' => asset('icons/doc-icon.png'),
            'docx' => asset('icons/doc-icon.png'),
            'xls' => asset('icons/excel-icon.png'),
            'xlsx' => asset('icons/excel-icon.png'),
            'ppt' => asset('icons/ppt-icon.png'),
            'pptx' => asset('icons/ppt-icon.png'),
            'txt' => asset('icons/txt-icon.png'),
            'zip' => asset('icons/zip-icon.png'),
            'rar' => asset('icons/zip-icon.png'),
            'jpg' => asset('icons/image-icon.png'),
            'jpeg' => asset('icons/image-icon.png'),
            'png' => asset('icons/image-icon.png'),
            'gif' => asset('icons/image-icon.png'),
            'mp4' => asset('icons/video-icon.png'),
            'mp3' => asset('icons/audio-icon.png'),
        ];
        
        return $icons[$extension] ?? asset('icons/file-icon.png');
    }
    
    

  
    /**
     * Get mutual connections for profile view - FIXED
     */
    private function getMutualConnectionsForProfile($currentUserId, $targetUserId)
    {
        // Get users that current user follows (accepted)
        $currentUserFollowing = UserConnection::where('follower_id', $currentUserId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        // Get users that follow current user (accepted)
        $currentUserFollowers = UserConnection::where('following_id', $currentUserId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        // All connections of current user (both following and followers)
        $currentUserConnections = array_unique(array_merge($currentUserFollowing, $currentUserFollowers));
        
        // Get users that target user follows (accepted)
        $targetUserFollowing = UserConnection::where('follower_id', $targetUserId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        // Get users that follow target user (accepted)
        $targetUserFollowers = UserConnection::where('following_id', $targetUserId)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        // All connections of target user (both following and followers)
        $targetUserConnections = array_unique(array_merge($targetUserFollowing, $targetUserFollowers));
        
        // Find mutual connections (people who are connected to BOTH users)
        $mutualIds = array_intersect($currentUserConnections, $targetUserConnections);
        
        // Remove current user and target user from mutual list (just to be safe)
        $mutualIds = array_diff($mutualIds, [$currentUserId, $targetUserId]);
        
        $mutualConnections = [];
        if (!empty($mutualIds)) {
            $users = User::whereIn('id', $mutualIds)
                ->where('is_active', 1)
                ->select('id', 'first_name', 'last_name', 'name', 'company_name', 'usertype', 'headline', 'image', 'company_logo')
                ->limit(15)
                ->get();
            
            foreach ($users as $user) {
                // Get proper name based on user type
                $userName = '';
                if ($user->usertype === 'company') {
                    $userName = $user->company_name ?? $user->name ?? 'Unknown Company';
                } else {
                    $userName = $user->getName();
                }
                
                // Get proper image based on user type
                $userImage = null;
                if ($user->usertype === 'company') {
                    $userImage = $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                                 ($user->image ? asset('user_images/' . $user->image) : null);
                } else {
                    $userImage = $user->image ? asset('user_images/' . $user->image) : null;
                }
                
                $mutualConnections[] = [
                    'id' => $user->id,
                    'name' => $userName,
                    'usertype' => $user->usertype ?? 'user',
                    'headline' => $user->headline,
                    'image' => $userImage,
                ];
            }
        }
    
        return [
            'count' => count($mutualIds),
            'connections' => $mutualConnections,
            'has_more' => count($mutualIds) > count($mutualConnections),
        ];
    }

   
    
    
    private function getUserPostsWithFilter($userId, $currentUser, $type, $page, $perPage, $connectionIds)
    {
        $query = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with(['category', 'subcategory'])
            ->withCount(['likes', 'comments', 'shares', 'views'])
            ->whereNotIn('category_id', [5, 6, 7])
            ->orderBy('created_at', 'desc');
        
        $posts = $query->paginate($perPage, ['*'], 'page', $page);
        
        $formattedPosts = $posts->map(function($post) use ($currentUser, $connectionIds) {
            // Format images
            $images = $post->images ? json_decode($post->images, true) : [];
            $formattedImages = array_map(function($img) {
                return asset('post_images/' . $img);
            }, $images);
            
            // Format files for user posts
            $files = $post->files ? json_decode($post->files, true) : [];
            $formattedFiles = [];
            
            if (!empty($files)) {
                foreach ($files as $file) {
                    $filePath = public_path('post_files/' . $file);
                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                    $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                    
                    $formattedFiles[] = [
                        'name' => $file,
                        'url' => asset('post_files/' . $file),
                        'size' => $this->formatFileSize($fileSize),
                        'size_bytes' => $fileSize,
                        'extension' => $fileExtension,
                        'icon' => $this->getFileIcon($fileExtension),
                        'exists' => file_exists($filePath)
                    ];
                }
            }
            
            $isLiked = PostLike::where('post_id', $post->id)
                ->where('user_id', $currentUser->id)
                ->exists();
            
            // Check if this post is a repost
            $repostRecord = PostRepost::where('reposted_post_id', $post->id)->first();
            $isRepost = !is_null($repostRecord);
            
            // ✅ ADDED: Get worth discussing count for this post
            // Count unique users who have discussed this post with the post owner
            $worthDiscussing = DB::table('user_messages')
                ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
                ->where('chat_types.slug', 'worth_discussing')
                ->where('user_messages.listing_id', $post->id)  // Based on POST ID
                ->where(function($q) use ($post) {
                    // The post owner is either sender or receiver
                    $q->where('user_messages.from_id', $post->user_id)
                      ->orWhere('user_messages.to_id', $post->user_id);
                })
                ->select(DB::raw('CASE 
                    WHEN user_messages.from_id = ' . $post->user_id . ' THEN user_messages.to_id 
                    ELSE user_messages.from_id 
                END as other_user_id'))
                ->distinct()
                ->get()
                ->pluck('other_user_id')
                ->unique()
                ->count();
            
            $formatted = [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'short_description' => $post->short_description,
                'category_id' => $post->category_id,
                'category' => optional($post->category)->name,
                'subcategory_id' => $post->subcategory_id,
                'subcategory' => optional($post->subcategory)->name,
                'post_type' => 'general',
                'job_type' => null,
                'job_type_label' => null,
                'is_job_post' => in_array($post->category_id, [5, 6, 7]),
                'images' => $formattedImages,
                'files' => $formattedFiles,
                'has_images' => !empty($formattedImages),
                'has_files' => !empty($formattedFiles),
                'thumbnail' => !empty($formattedImages) ? $formattedImages[0] : 
                              (!empty($formattedFiles) ? $formattedFiles[0]['icon'] : null),
                'stats' => [
                    'views' => $post->views_count ?? 0,
                    'likes' => $post->likes_count ?? 0,
                    'comments' => $post->comments_count ?? 0,
                    'shares' => $post->shares_count ?? 0,
                    'reposts' => $post->repost_count ?? 0,
                    'worth_discussing' => $worthDiscussing,  // ✅ ADDED
                ],
                'is_liked' => $isLiked,
                'created_at' => $post->created_at,
                'created_at_formatted' => $post->created_at->diffForHumans(),
            ];
            
            // Add repost info if it's a repost
            if ($isRepost && $repostRecord) {
                $formatted['is_repost'] = true;
                $formatted['repost_comment'] = $repostRecord->repost_comment;
                $formatted['repost_info'] = [
                    'repost_record_id' => $repostRecord->id,
                    'repost_comment' => $repostRecord->repost_comment,
                    'reposted_at' => $repostRecord->created_at,
                    'reposted_at_formatted' => $repostRecord->created_at->diffForHumans(),
                ];
            } else {
                $formatted['is_repost'] = false;
                $formatted['repost_comment'] = null;
                $formatted['repost_info'] = null;
            }
            
            return $formatted;
        });
        
        // Get available counts
        $jobPostsCount = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->whereIn('category_id', [5, 6, 7])
            ->count();
        
        $miniMissionsCount = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('category_id', 5)
            ->count();
        
        $internshipsCount = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->where('category_id', 6)
            ->count();
        
        $generalPostsCount = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->whereNotIn('category_id', [5, 6, 7])
            ->count();
        
        $totalCount = Post::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->count();
        
        return [
            'posts' => $formattedPosts,
            'current_page' => $posts->currentPage(),
            'per_page' => $posts->perPage(),
            'total_count' => $posts->total(),
            'last_page' => $posts->lastPage(),
            'filters' => [
                'post_type' => $type,
                'available' => [
                    'job_posts' => $jobPostsCount,
                    'mini_missions' => $miniMissionsCount,
                    'internships' => $internshipsCount,
                    'general_posts' => $generalPostsCount,
                    'total' => $totalCount,
                ]
            ]
        ];
    }
    
    
    private function getChatSessionWithUser($currentUserId, $targetUserId)
    {
        $generalChatType = \App\Models\ChatType::where('slug', 'general')->first();
        
       
      
        if (!$generalChatType) {
            return null;
        }
    
        // $session = \App\Models\ChatSession::where(function ($q) use ($currentUserId, $targetUserId) {
        //         $q->where('user1_id', $currentUserId)->where('user2_id', $targetUserId);
        //     })
        //     ->orWhere(function ($q) use ($currentUserId, $targetUserId) {
        //         $q->where('user1_id', $targetUserId)->where('user2_id', $currentUserId);
        //     })
        //     ->where('chat_type_id', $generalChatType->id) // ← only general
        //   //  ->where('is_active', true)
        //     ->orderBy('last_message_at', 'desc')
        //     ->first();
        
        $session = \App\Models\ChatSession::where(function ($q) use ($currentUserId, $targetUserId) {
            $q->where(function ($q2) use ($currentUserId, $targetUserId) {
                    $q2->where('user1_id', $currentUserId)
                       ->where('user2_id', $targetUserId);
                })
                ->orWhere(function ($q2) use ($currentUserId, $targetUserId) {
                    $q2->where('user1_id', $targetUserId)
                       ->where('user2_id', $currentUserId);
                });
        })
        ->where('chat_type_id', $generalChatType->id) // ✅ now applies to BOTH
        ->orderBy('last_message_at', 'desc')
        ->first();
    
        if (!$session) {
            return null;
        }
    
        return [
            'session_id'     => $session->id,
            'chat_type_id'   => $session->chat_type_id,
            'chat_type'      => 'general',
            'last_message'   => $session->last_message,
            'last_message_at'=> $session->last_message_at,
        ];
    }

  
  
    
    private function formatUserProfileForView($user, $currentUser, $connectionStatus, $mutualConnections, $postType = 'all', $page = 1, $perPage = 100, $connectionIds = [])
    {
        $postsData = $this->getUserPostsWithFilter($user->id, $currentUser, $postType, $page, $perPage, $connectionIds);
        $connectionStats = $this->getConnectionStats($user);
        
        // Get the full profile stats including likes, worth discussing, and shares
        $profileStats = $this->getProfileStats($user);
        
        // ✅ Check if user is blocked by current user
        $isBlockedByCurrentUser = BlockedUser::where('blocker_id', $currentUser->id)
            ->where('blocked_id', $user->id)
            ->exists();
        
        // ✅ Check if current user is blocked by the target user
        $isBlockedByTargetUser = BlockedUser::where('blocker_id', $user->id)
            ->where('blocked_id', $currentUser->id)
            ->exists();
        
        // Optional: Check if blocked via UserConnection table
        $isBlockedViaConnection = UserConnection::where('follower_id', $currentUser->id)
            ->where('following_id', $user->id)
            ->where('status', self::STATUS_BLOCKED)
            ->exists();
        
        $isBlockedByTargetViaConnection = UserConnection::where('follower_id', $user->id)
            ->where('following_id', $currentUser->id)
            ->where('status', self::STATUS_BLOCKED)
            ->exists();
        
        // Combine block checks
        $isBlockedByCurrentUser = $isBlockedByCurrentUser || $isBlockedViaConnection;
        $isBlockedByTargetUser = $isBlockedByTargetUser || $isBlockedByTargetViaConnection;
        
        $areaOfInterests = [];
        if (!empty($user->area_of_interest_id)) {
            if (is_string($user->area_of_interest_id) && strpos($user->area_of_interest_id, '{') === 0) {
                $ids = trim($user->area_of_interest_id, '{}');
                $ids = array_map('intval', explode(',', $ids));
            } elseif (is_string($user->area_of_interest_id)) {
                $ids = array_map('intval', explode(',', $user->area_of_interest_id));
            } elseif (is_array($user->area_of_interest_id)) {
                $ids = $user->area_of_interest_id;
            } else {
                $ids = [];
            }
            
            if (!empty($ids)) {
                $jobTitles = JobTitle::whereIn('id', $ids)
                    ->where('is_active', 1)
                    ->get(['id', 'job_title']);
                
                $areaOfInterests = $jobTitles->pluck('job_title')->toArray();
            }
        }
    
        $skills = [];
        if (!empty($user->specialization)) {
            $skillIds = array_map('intval', explode(',', $user->specialization));
            
            if (!empty($skillIds)) {
                $jobSkills = JobSkill::whereIn('id', $skillIds)
                    ->where('is_active', 1)
                    ->get(['id', 'job_skill']);
                
                $skills = $jobSkills->pluck('job_skill')->toArray();
            }
        }
    
        $isFollowing = false;
        $isFollower = false;
        
        if ($connectionStatus == self::STATUS_ACCEPTED) {
            $isFollowing = true;
            $isFollower = true;
        } elseif ($connectionStatus == 'accepted') {
            $isFollowing = true;
        } elseif ($connectionStatus == self::STATUS_PENDING) {
            $isFollowing = false;
        } elseif ($connectionStatus == 'pending_from_them') {
            $isFollower = true;
        }
    
        return [
            'id' => $user->id,
            'name' => $user->getName(),
            'email' => $user->email,
            'unique_id' => $user->unique_id,
            'usertype' => $user->usertype ?? 'professional',
            'profile_image' => $user->image ? asset('user_images/' . $user->image) : null,
            'cover_image' => $user->cover_image ? asset('user_images/' . $user->cover_image) : null,
            'headline' => $user->headline ?? null,
            'location' => $user->location ?? null,
            'summary' => $user->getProfileSummary('summary') ?? null,
            'industry' => $user->getIndustry('industry') ?? null,
            'date_of_birth' => $user->date_of_birth ?? null,
            'phone' => $user->phone ?? null,
            'visibility_control' => $user->visibility_control ?? 'public',
            'post_visibility_control' => $user->post_visibility_control ?? 'public',
            'message_visibility_control' => $user->message_visibility_control ?? 'public',
            
            'college_name' => $user->college_name ?? null,
            'school_name' => $user->school_name ?? null,
            'degree' => $user->degree ?? null,
            'course_duration' => $user->course_duration ?? null,
            'currently_pursuing' => !empty($user->course_duration) && strpos($user->course_duration, 'Present') !== false,
            
            'portfolio_website' => $user->portfolio_website ?? null,
            'gst_number' => $user->gst_number ?? null,
            'upi_id' => $user->upi_id ?? null,
            
            'skills' => $skills,
            'area_of_interests' => $areaOfInterests,
            
            'mutual_connections' => $mutualConnections,
            
            'connection_status' => [
                'status' => $connectionStatus,
                'is_following' => $isFollowing,
                'is_follower' => $isFollower,
                'is_blocked' => $isBlockedByCurrentUser,
                'is_blocked_by_them' => $isBlockedByTargetUser,
                'can_follow' => !in_array($connectionStatus, [self::STATUS_ACCEPTED, 'following', self::STATUS_PENDING, self::STATUS_BLOCKED, 'blocked_by_them']) 
                    && !$isBlockedByCurrentUser 
                    && !$isBlockedByTargetUser,
                'can_unfollow' => in_array($connectionStatus, [self::STATUS_ACCEPTED, 'following']) 
                    && !$isBlockedByCurrentUser 
                    && !$isBlockedByTargetUser,
                'can_accept' => $connectionStatus == 'pending_from_them' 
                    && !$isBlockedByCurrentUser 
                    && !$isBlockedByTargetUser,
                'can_reject' => $connectionStatus == 'pending_from_them' 
                    && !$isBlockedByCurrentUser 
                    && !$isBlockedByTargetUser,
                'can_block' => !in_array($connectionStatus, [self::STATUS_BLOCKED, 'blocked_by_them']) 
                    && !$isBlockedByCurrentUser,
                'can_unblock' => in_array($connectionStatus, [self::STATUS_BLOCKED, 'blocked_by_them']) 
                    || $isBlockedByCurrentUser,
            ],
            
            'stats' => [
                'posts_count' => $postsData['total_count'],
                'followers_count' => $connectionStats['followers_raw'],
                'following_count' => $connectionStats['following_raw'],
                'likes_received' => $this->getUserLikesReceived($user->id),
                // Add the missing stats from getProfileStats
                'likes' => $profileStats['likes'] ?? 0,
                'worth_discussing' => $profileStats['worth_discussing'] ?? 0,
                'shares' => $profileStats['shares'] ?? 0,
                // Also include raw values if needed
                'likes_raw' => $profileStats['likes_raw'] ?? 0,
                'shares_raw' => $profileStats['shares_raw'] ?? 0,
            ],
            
            'posts' => $postsData['posts'],
            'posts_pagination' => [
                'current_page' => $postsData['current_page'],
                'per_page' => $postsData['per_page'],
                'total' => $postsData['total_count'],
                'last_page' => $postsData['last_page'],
            ],
            'posts_filters' => $postsData['filters'],
            
            'is_own_profile' => $currentUser->id == $user->id,
        ];
    }

    /**
     * Get likes received by user
     */
    private function getUserLikesReceived($userId)
    {
        return PostLike::whereHas('post', function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('is_active', true)
              ->where('is_published', true);
        })->count();
    }

   
    
    private function formatCompanyProfileForView($user, $currentUser, $connectionStatus, $mutualConnections, $postType = 'all', $page = 1, $perPage = 10, $connectionIds = [])
    {
        $postsData = $this->getUserPostsWithFilter($user->id, $currentUser, $postType, $page, $perPage, $connectionIds);
        
        // Add connection stats for company (similar to user profile)
        $connectionStats = $this->getConnectionStats($user);
        
        // Get the full profile stats including likes, worth discussing, and shares
        $profileStats = $this->getProfileStats($user);
        
        $followersCount = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
        
        $companyFollowers = UserConnection::where('following_id', $user->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('follower_id')
            ->toArray();
        
        $currentUserFollowing = UserConnection::where('follower_id', $currentUser->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->pluck('following_id')
            ->toArray();
        
        $mutualFollowerIds = array_intersect($companyFollowers, $currentUserFollowing);
        
        $mutualFollowers = [];
        if (!empty($mutualFollowerIds)) {
            $users = User::whereIn('id', $mutualFollowerIds)
                ->where('is_active', 1)
                ->select('id', 'first_name', 'last_name', 'headline', 'image')
                ->limit(15)
                ->get();
            
            foreach ($users as $follower) {
                $mutualFollowers[] = [
                    'id' => $follower->id,
                    'name' => $follower->getName(),
                    'headline' => $follower->headline,
                    'image' => $follower->image ? asset('user_images/' . $follower->image) : null,
                ];
            }
        }
        
        // ✅ Check if user is blocked by current user
        $isBlockedByCurrentUser = BlockedUser::where('blocker_id', $currentUser->id)
            ->where('blocked_id', $user->id)
            ->exists();
        
        // ✅ Check if current user is blocked by the company
        $isBlockedByCompany = BlockedUser::where('blocker_id', $user->id)
            ->where('blocked_id', $currentUser->id)
            ->exists();
        
        $isFollowing_stats = $this->checkIfConnected($currentUser, $user->id);
        
        $isFollowing = UserConnection::where(function ($query) use ($currentUser, $user) {
            $query->where('follower_id', $currentUser->id)
                  ->where('following_id', $user->id);
        })
        ->orWhere(function ($query) use ($currentUser, $user) {
            $query->where('follower_id', $user->id)
                  ->where('following_id', $currentUser->id);
        })
        ->where('status', self::STATUS_ACCEPTED)
        ->exists();
    
        return [
            'id' => $user->id,
            'name' => $user->company_name ?? $user->name,
            'email' => $user->email,
            'unique_id' => $user->unique_id,
            'usertype' => 'company',
            'slug' => $user->company_slug,
            'logo' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : null,
            'industry' => $user->getIndustry('industry') ?? null,
            'location' => $user->company_location ?? $user->location ?? null,
            'description' => $user->company_description ?? null,
            'website' => $user->company_website ?? null,
            'phone' => $user->phone ?? null,
            'linkedin_url' => $user->linkedin_url ?? null,
            'gst_number' => $user->gst_number ?? null,
            'upi_id' => $user->upi_id ?? null,
            'visibility_control' => $user->visibility_control ?? 'public',
            'post_visibility_control' => $user->post_visibility_control ?? 'public',
            'message_visibility_control' => $user->message_visibility_control ?? 'public',
            'established_in' => $user->company_established_in ?? null,
            'no_of_employees' => $user->company_no_of_employees ?? null,
            
            'mutual_followers' => [
                'count' => count($mutualFollowerIds),
                'list' => $mutualFollowers,
                'has_more' => count($mutualFollowerIds) > count($mutualFollowers),
            ],
            
            'connection_status' => [
                'status' => $isFollowing_stats,
                'is_following' => $isFollowing,
                'is_blocked' => $isBlockedByCurrentUser, // ✅ Added: whether current user blocked this company
                'is_blocked_by_them' => $isBlockedByCompany, // ✅ Added: whether company blocked current user
                'can_follow' => !$isFollowing && !$isBlockedByCurrentUser && !$isBlockedByCompany,
                'can_unfollow' => $isFollowing && !$isBlockedByCurrentUser && !$isBlockedByCompany,
                'can_block' => !$isBlockedByCurrentUser && !$isBlockedByCompany,
                'can_unblock' => $isBlockedByCurrentUser,
            ],
            
            'stats' => [
                'posts_count' => $postsData['total_count'],
                'followers_count' => $connectionStats['followers_raw'],
                'following_count' => $connectionStats['following_raw'],
                'likes_received' => $this->getUserLikesReceived($user->id),
                // Add the missing stats from getProfileStats
                'likes' => $profileStats['likes'] ?? 0,
                'worth_discussing' => $profileStats['worth_discussing'] ?? 0,
                'shares' => $profileStats['shares'] ?? 0,
                // Also include raw values if needed
                'likes_raw' => $profileStats['likes_raw'] ?? 0,
                'shares_raw' => $profileStats['shares_raw'] ?? 0,
            ],
            
            'posts' => $postsData['posts'],
            'posts_pagination' => [
                'current_page' => $postsData['current_page'],
                'per_page' => $postsData['per_page'],
                'total' => $postsData['total_count'],
                'last_page' => $postsData['last_page'],
            ],
            'posts_filters' => $postsData['filters'],
            
            'is_own_profile' => $currentUser->id == $user->id,
        ];
    }
   

    /**
     * Check if company is blocked by user
     */
    private function isCompanyBlocked($userId, $companyId)
    {
        return UserConnection::where('follower_id', $userId)
            ->where('following_id', $companyId)
            ->where('status', self::STATUS_BLOCKED)
            ->exists();
    }

    /**
     * Check if profile is complete
     */
    private function isProfileComplete($user)
    {
        if ($user->usertype === 'company') {
            return !empty($user->company_name) && !empty($user->company_slug);
        } else {
            return !empty($user->first_name);
        }
    }

    public function updateVisibility(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'visibility_control' => 'sometimes|in:public,private',
                'post_visibility_control' => 'sometimes|in:public,private',
                'message_visibility_control' => 'sometimes|in:public,private',
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
    
            $updatedFields = [];
            
            if ($request->has('visibility_control')) {
                $user->visibility_control = $request->visibility_control;
                $updatedFields[] = 'visibility_control';
            }
            
            if ($request->has('post_visibility_control')) {
                $user->post_visibility_control = $request->post_visibility_control;
                $updatedFields[] = 'post_visibility_control';
            }
            
            if ($request->has('message_visibility_control')) {
                $user->message_visibility_control = $request->message_visibility_control;
                $updatedFields[] = 'message_visibility_control';
            }
    
            if (empty($updatedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No visibility settings provided to update',
                    'errors' => (object)[
                        'fields' => 'Please provide at least one visibility setting'
                    ]
                ], 400);
            }
    
            $user->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Visibility settings updated successfully',
                'data' => [
                    'visibility_control' => $user->visibility_control,
                    'post_visibility_control' => $user->post_visibility_control ?? 'public',
                    'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    'updated_fields' => $updatedFields,
                    'user_type' => $user->usertype
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Update visibility failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update visibility settings',
                'errors' => (object)[
                    'server' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get Current Visibility Settings
     */
    public function getVisibility(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Visibility settings retrieved successfully',
                'data' => [
                    'visibility_control' => $user->visibility_control ?? 'public',
                    'post_visibility_control' => $user->post_visibility_control ?? 'public',
                    'message_visibility_control' => $user->message_visibility_control ?? 'public',
                    'user_type' => $user->usertype
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Get visibility failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get visibility settings',
                'errors' => (object)[
                    'server' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Check if user has worth discussing conversations
     */
    public function hasWorthDiscussing(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
    
            $worthDiscussingCount = $this->getWorthDiscussingCount($user);
    
            return response()->json([
                'success' => true,
                'data' => [
                    'has_worth_discussing' => $worthDiscussingCount > 0,
                    'count' => $worthDiscussingCount
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Has worth discussing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check worth discussing status',
                'errors' => (object)[
                    'server' => $e->getMessage()
                ]
            ], 500);
        }
    }
}