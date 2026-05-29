<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Package;
use App\User;
use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    
    public function getEmployerPackages(Request $request)
    {
        try {
            $user = Auth::user(); // Get the authenticated user
            
            $packages = Package::employerPackages()
                ->where('status', true)
                ->orderBy('package_price')
                ->get()
                ->map(function ($package) use ($user) {
                    // Check if user is subscribed to this package
                    $isSubscribed = false;
                    
                    if ($user && $user->usertype === 'company') {
                        // User is subscribed if they have this package_id and it's active
                        $isSubscribed = ($user->package_id == $package->id) && 
                                        $user->package_end_date && 
                                        Carbon::parse($user->package_end_date)->isFuture() &&
                                        ($user->jobs_quota - $user->availed_jobs_quota) > 0;
                    }
                    
                    // Parse features into array if it's a string
                    $features = [];
                    if (!empty($package->package_features)) {
                        if (is_array($package->package_features)) {
                            $features = $package->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $package->package_features));
                            $features = array_filter($features, function($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }
                    
                    return [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'duration_text' => $package->duration_text,
                        'listings' => $package->package_num_listings,
                        'features' => $features,
                        'is_popular' => (bool)$package->is_popular,
                        'badge_text' => $package->badge_text,
                        'detail_compare' => 'This is the best plan for you',
                        'currency' => $package->currency,
                        'is_subscribed' => $isSubscribed,
                    ];
                });
    
            return response()->json([
                'success' => true,
                'message' => 'Employer packages retrieved successfully',
                'data' => $packages
            ]);
    
        } catch (Exception $e) {
            Log::error('Get employer packages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get all job seeker packages
     */
    public function getJobSeekerPackages(Request $request)
    {
        try {
            $packages = Package::jobSeekerPackages()
                ->where('status', true)
                ->get()
                ->map(function ($package) {
                    // Parse features into array if it's a string
                    $features = [];
                    if (!empty($package->package_features)) {
                        if (is_array($package->package_features)) {
                            $features = $package->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $package->package_features));
                            $features = array_filter($features, function($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }
                    
                    return [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'duration_text' => $package->duration_text,
                        'listings' => $package->package_num_listings,
                        'features' => $features,
                        'currency' => $package->currency
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Job seeker packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (Exception $e) {
            Log::error('Get job seeker packages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get all CV search packages
     */
    public function getCVSearchPackages(Request $request)
    {
        try {
            $packages = Package::CVSearchPackages()
                ->where('status', true)
                ->get()
                ->map(function ($package) {
                    // Parse features into array if it's a string
                    $features = [];
                    if (!empty($package->package_features)) {
                        if (is_array($package->package_features)) {
                            $features = $package->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $package->package_features));
                            $features = array_filter($features, function($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }
                    
                    return [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'duration_text' => $package->duration_text,
                        'listings' => $package->package_num_listings,
                        'features' => $features,
                        'currency' => $package->currency
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'CV search packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (Exception $e) {
            Log::error('Get CV search packages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get featured packages
     */
    public function getFeaturedPackages(Request $request)
    {
        try {
            $packages = Package::featuredPackages()
                ->where('status', true)
                ->get()
                ->map(function ($package) {
                    // Parse features into array if it's a string
                    $features = [];
                    if (!empty($package->package_features)) {
                        if (is_array($package->package_features)) {
                            $features = $package->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $package->package_features));
                            $features = array_filter($features, function($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }
                    
                    return [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'duration_text' => $package->duration_text,
                        'listings' => $package->package_num_listings,
                        'features' => $features,
                        'currency' => $package->currency
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Featured packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (Exception $e) {
            Log::error('Get featured packages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get all packages (for admin)
     */
    public function getAllPackages(Request $request)
    {
        try {
            $packages = Package::orderBy('package_for')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('package_for');

            return response()->json([
                'success' => true,
                'message' => 'All packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (Exception $e) {
            Log::error('Get all packages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get package details by ID
     */
    public function getPackageDetails($id)
    {
        try {
            $package = Package::find($id);

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not found',
                    'errors' => (object)['package' => 'Package not found']
                ], 404);
            }

            // Parse features into array if it's a string
            $features = [];
            if (!empty($package->package_features)) {
                if (is_array($package->package_features)) {
                    $features = $package->package_features;
                } else {
                    $features = explode("\n", str_replace("\r", "", $package->package_features));
                    $features = array_filter($features, function($f) {
                        return trim($f) !== '';
                    });
                    $features = array_values($features);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Package details retrieved',
                'data' => [
                    'id' => $package->id,
                    'title' => $package->package_title,
                    'subtitle' => $package->package_subtitle,
                    'price' => (float)$package->package_price,
                    'formatted_price' => $package->formatted_price,
                    'duration_days' => $package->package_num_days,
                    'duration_text' => $package->duration_text,
                    'listings' => $package->package_num_listings,
                    'features' => $features,
                    'is_popular' => (bool)$package->is_popular,
                    'badge_text' => $package->badge_text,
                    'package_for' => $package->package_for,
                    'detail_compare' => 'This is the best plan for you',
                    'currency' => $package->currency
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get package details failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve package details',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Purchase a package (simplified - just updates user's package_id)
     */
    public function purchasePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
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

            if (!$user || $user->usertype !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only companies can purchase packages',
                    'errors' => (object)['user' => 'Only company accounts can purchase packages']
                ], 403);
            }

            $package = Package::find($request->package_id);

            // Update user with new package details
            $user->package_id = $package->id;
            $user->package_start_date = now();
            $user->package_end_date = now()->addDays($package->package_num_days);
            $user->jobs_quota = $package->package_num_listings;
            $user->availed_jobs_quota = 0;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Package purchased successfully',
                'data' => [
                    'user_id' => $user->id,
                    'company_name' => $user->company_name ?? $user->name,
                    'package' => [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'listings' => $package->package_num_listings
                    ],
                    'start_date' => $user->package_start_date,
                    'start_date_formatted' => $user->package_start_date ? $user->package_start_date->format('d M Y') : null,
                    'end_date' => $user->package_end_date,
                    'end_date_formatted' => $user->package_end_date ? $user->package_end_date->format('d M Y') : null,
                    'total_listings' => $user->jobs_quota,
                    'used_listings' => $user->availed_jobs_quota,
                    'remaining_listings' => $user->jobs_quota - $user->availed_jobs_quota
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Purchase package failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase package',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get user's current package info (for company users)
     */
    public function getUserPackage(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated',
                    'errors' => (object)['user' => 'Not authenticated']
                ], 401);
            }

            // Only companies have packages, but we'll return null for other types
            if ($user->usertype !== 'company') {
                return response()->json([
                    'success' => true,
                    'message' => 'Package info not applicable for this user type',
                    'data' => [
                        'has_package' => false,
                        'user_type' => $user->usertype,
                        'package' => null
                    ]
                ]);
            }

            $package = null;
            if ($user->package_id) {
                $package = Package::find($user->package_id);
            }

            // IMPORTANT FIX: Count job posts that should count against quota
            // This should be posts with post_type_id == 2 (Job Posts) OR category_id == 5 or 6
            $jobPostsCount = Post::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('post_type_id', 2) // Job post type
                          ->orWhereIn('category_id', [5, 6, 7]); // Mini Mission or Internship categories
                })
                ->count();

            $isActive = $user->package_end_date && 
                       Carbon::parse($user->package_end_date)->isFuture();

            // Check if user has exceeded their limit based on availed_jobs_quota
            $hasExceededLimit = $user->availed_jobs_quota >= $user->jobs_quota;
            $needsUpgrade = $hasExceededLimit || !$isActive;
            
            // Check if user can post another job
            $canPost = $isActive && ($user->jobs_quota - $user->availed_jobs_quota) > 0;

            $daysRemaining = 0;
            if ($isActive && $user->package_end_date) {
                $daysRemaining = now()->diffInDays(Carbon::parse($user->package_end_date), false);
            }

            // Calculate usage percentage based on availed_jobs_quota, NOT jobPostsCount
            $totalListings = $user->jobs_quota ?? 0;
            $usedListings = $user->availed_jobs_quota ?? 0;
            $usagePercentage = $totalListings > 0 ? round(($usedListings / $totalListings) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'message' => 'User package retrieved',
                'data' => [
                    'has_package' => !is_null($package),
                    'user_type' => $user->usertype,
                    'company_name' => $user->company_name ?? $user->name,
                    'package' => $package ? [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'features' => $package->package_features,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'listings' => $package->package_num_listings
                    ] : null,
                    'start_date' => $user->package_start_date,
                    'start_date_formatted' => $user->package_start_date ? 
                        Carbon::parse($user->package_start_date)->format('d M Y') : null,
                    'end_date' => $user->package_end_date,
                    'end_date_formatted' => $user->package_end_date ? 
                        Carbon::parse($user->package_end_date)->format('d M Y') : null,
                    'total_listings' => $totalListings,
                    'used_listings' => $usedListings,
                    'job_posts_count' => $jobPostsCount,
                    'remaining_listings' => $totalListings - $usedListings,
                    'can_post' => $canPost,
                    'is_active' => $isActive,
                    'days_remaining' => $daysRemaining,
                    'expiry_status' => $isActive ? 'active' : 'expired',
                    'has_exceeded_limit' => $hasExceededLimit,
                    'needs_upgrade' => $needsUpgrade,
                    'usage_percentage' => $usagePercentage,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get user package failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get package info',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get user's current subscription with transaction history
     * Shows current active plan and all past purchases
     */
    public function getUserCurrentSubscription(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // ============================================
            // CURRENT PLAN DETAILS
            // ============================================
            $currentPlan = null;
            $hasActivePlan = false;
            $canUpgrade = false;
            $daysRemaining = 0;
            $usagePercentage = 0;
            $needsUpgrade = false;
            $hasExceededLimit = false;

            if ($user->package_id) {
                $package = Package::find($user->package_id);
                
                if ($package) {
                    // IMPORTANT FIX: Count job posts that should count against quota
                    $jobPostsCount = Post::where('user_id', $user->id)
                        ->where(function($query) {
                            $query->where('post_type_id', 2)
                                  ->orWhereIn('category_id', [5, 6, 7]);
                        })
                        ->count();
                    
                    // Check if plan is active
                    $isActive = $user->package_end_date && 
                               Carbon::parse($user->package_end_date)->isFuture();

                    $daysRemaining = $isActive ? now()->diffInDays(Carbon::parse($user->package_end_date), false) : 0;
                    
                    // Calculate usage percentage based on availed_jobs_quota
                    $totalListings = $user->jobs_quota ?? 0;
                    $usedListings = $user->availed_jobs_quota ?? 0;
                    $usagePercentage = $totalListings > 0 ? round(($usedListings / $totalListings) * 100, 2) : 0;

                    // Check if user has exceeded their limit
                    $hasExceededLimit = $usedListings >= $totalListings;
                    $needsUpgrade = $hasExceededLimit || !$isActive;
                    
                    // Check if user can post another job
                    $canPost = $isActive && ($totalListings - $usedListings) > 0;

                    // Parse features into array
                    $features = [];
                    if (!empty($package->package_features)) {
                        if (is_array($package->package_features)) {
                            $features = $package->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $package->package_features));
                            $features = array_filter($features, function ($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }

                    $currentPlan = [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'price' => (float)$package->package_price,
                        'formatted_price' => $package->formatted_price,
                        'duration_days' => $package->package_num_days,
                        'duration_text' => $package->duration_text,
                        'total_listings' => $totalListings,
                        'used_listings' => $usedListings,
                        'job_posts_count' => $jobPostsCount,
                        'remaining_listings' => $totalListings - $usedListings,
                        'can_post' => $canPost,
                        'usage_percentage' => $usagePercentage,
                        'features' => $features,
                        'start_date' => $user->package_start_date,
                        'start_date_formatted' => $user->package_start_date ? 
                            Carbon::parse($user->package_start_date)->format('d M Y') : null,
                        'end_date' => $user->package_end_date,
                        'end_date_formatted' => $user->package_end_date ? 
                            Carbon::parse($user->package_end_date)->format('d M Y') : null,
                        'is_active' => $isActive,
                        'days_remaining' => $daysRemaining,
                        'status' => $isActive ? 'active' : 'expired',
                        'has_exceeded_limit' => $hasExceededLimit,
                        'needs_upgrade' => $needsUpgrade,
                    ];

                    $hasActivePlan = $isActive;

                    // Check if user can upgrade (has active plan and there are higher tier packages)
                    if ($isActive) {
                        // Get all employer packages priced higher than current
                        $higherPackages = Package::employerPackages()
                            ->where('package_price', '>', $package->package_price)
                            ->where('status', true)
                            ->count();
                        
                        $canUpgrade = $higherPackages > 0;
                    }
                }
            }

            // ============================================
            // TRANSACTION HISTORY
            // ============================================
            $paymentRequests = PaymentRequest::with(['package', 'transaction'])
                ->where('user_id', $user->id)
                ->where('status','paid')
                ->orderBy('created_at', 'desc')
                ->get();

            $transactionHistory = $paymentRequests->map(function ($payment) {
                $status = $payment->status;
                $statusColor = 'red';
                $statusBadge = 'Failed';
                
                if ($status === 'paid') {
                    $statusColor = 'green';
                    $statusBadge = 'Paid';
                } elseif ($status === 'pending') {
                    $statusColor = 'orange';
                    $statusBadge = 'Pending';
                } elseif ($status === 'cancelled') {
                    $statusColor = 'gray';
                    $statusBadge = 'Cancelled';
                }

                // Parse features into array for display
                $packageFeatures = [];

                if ($payment->package && $payment->package->package_features) {
                
                    $featuresData = $payment->package->package_features;
                
                    if (is_array($featuresData)) {
                        $packageFeatures = $featuresData;
                    } else {
                        $packageFeatures = explode("\n", str_replace("\r", "", $featuresData));
                        $packageFeatures = array_filter($packageFeatures, function ($f) {
                            return trim($f) !== '';
                        });
                        $packageFeatures = array_values($packageFeatures);
                    }
                }

                return [
                    'id' => $payment->id,
                    'order_number' => $payment->order_number,
                    'package' => $payment->package ? [
                        'id' => $payment->package->id,
                        'title' => $payment->package->package_title,
                        'subtitle' => $payment->package->package_subtitle,
                        'price' => (float)$payment->package->package_price,
                        'formatted_price' => $payment->package->formatted_price,
                        'duration_days' => $payment->package->package_num_days,
                        'duration_text' => $payment->package->duration_text,
                        'listings' => $payment->package->package_num_listings,
                        'features' => $packageFeatures,
                    ] : null,
                    'amount' => $payment->amount,
                    'formatted_amount' => '₹ ' . number_format($payment->amount, 0),
                    'currency' => $payment->currency,
                    'status' => $status,
                    'status_badge' => $statusBadge,
                    'status_color' => $statusColor,
                    'payment_method' => $payment->transaction ? $payment->transaction->payment_method : null,
                    'transaction_id' => $payment->transaction ? $payment->transaction->razorpay_payment_id : null,
                    'razorpay_order_id' => $payment->razorpay_order_id,
                    'payment_link' => $payment->payment_link_url,
                    'created_at' => $payment->created_at,
                    'created_at_formatted' => $payment->created_at ? 
                        Carbon::parse($payment->created_at)->format('d M Y, h:i A') : null,
                    'created_at_date' => $payment->created_at ? 
                        Carbon::parse($payment->created_at)->format('d M Y') : null,
                    'created_at_time' => $payment->created_at ? 
                        Carbon::parse($payment->created_at)->format('h:i A') : null,
                ];
            })->values();

            // Separate active and expired transactions
            $activeTransactions = $transactionHistory->filter(function ($transaction) {
                return $transaction['status'] === 'paid';
            })->values();

            // ============================================
            // RECOMMENDED UPGRADE PLANS
            // ============================================
            $recommendedPlans = [];
            
            if ($hasActivePlan && $user->package_id) {
                $currentPackage = Package::find($user->package_id);
                
                if ($currentPackage) {
                    // Get next tier packages (higher price)
                    $nextTierPackages = Package::employerPackages()
                        ->where('package_price', '>', $currentPackage->package_price)
                        ->where('status', true)
                        ->orderBy('package_price')
                        ->limit(3)
                        ->get();
                    
                    foreach ($nextTierPackages as $package) {
                        // Parse features into array
                        $features = [];

                        if ($package->package_features) {
                        
                            $featureData = $package->package_features;
                        
                            if (is_array($featureData)) {
                                $features = $featureData;
                            } else {
                                $features = explode("\n", str_replace("\r", "", $featureData));
                                $features = array_filter($features, function ($f) {
                                    return trim($f) !== '';
                                });
                                $features = array_values($features);
                            }
                        }
                        

                        $recommendedPlans[] = [
                            'id' => $package->id,
                            'title' => $package->package_title,
                            'subtitle' => $package->package_subtitle,
                            'price' => (float)$package->package_price,
                            'formatted_price' => $package->formatted_price,
                            'duration_days' => $package->package_num_days,
                            'duration_text' => $package->duration_text,
                            'listings' => $package->package_num_listings,
                            'features' => $features,
                            'additional_cost' => '₹ ' . number_format($package->package_price - $currentPackage->package_price, 0),
                            'is_popular' => (bool)$package->is_popular,
                            'badge_text' => $package->badge_text,
                        ];
                    }
                }
            } else {
                // If no active plan, show most popular employer package
                $popularPackage = Package::employerPackages()
                    ->where('is_popular', true)
                    ->where('status', true)
                    ->first();
                
                if (!$popularPackage) {
                    $popularPackage = Package::employerPackages()
                        ->where('status', true)
                        ->orderBy('package_price')
                        ->first();
                }
                
                if ($popularPackage) {
                    // Parse features into array
                    $features = [];

                    if (!empty($popularPackage->package_features)) {
                        if (is_array($popularPackage->package_features)) {
                            $features = $popularPackage->package_features;
                        } else {
                            $features = explode("\n", str_replace("\r", "", $popularPackage->package_features));
                            $features = array_filter($features, function ($f) {
                                return trim($f) !== '';
                            });
                            $features = array_values($features);
                        }
                    }
                    
                    $recommendedPlans[] = [
                        'id' => $popularPackage->id,
                        'title' => $popularPackage->package_title,
                        'subtitle' => $popularPackage->package_subtitle,
                        'price' => (float)$popularPackage->package_price,
                        'formatted_price' => $popularPackage->formatted_price,
                        'duration_days' => $popularPackage->package_num_days,
                        'duration_text' => $popularPackage->duration_text,
                        'listings' => $popularPackage->package_num_listings,
                        'features' => $features,
                        'is_popular' => (bool)$popularPackage->is_popular,
                        'badge_text' => $popularPackage->badge_text,
                    ];
                }
            }

            // ============================================
            // SUBSCRIPTION SUMMARY STATS
            // ============================================
            $totalSpent = PaymentTransaction::whereIn('payment_request_id', function($query) use ($user) {
                    $query->select('id')
                        ->from('payment_requests')
                        ->where('user_id', $user->id)
                        ->where('status', 'paid');
                })
                ->where('status', 'success')
                ->sum('amount');

            $totalPurchases = PaymentRequest::where('user_id', $user->id)
                ->where('status', 'paid')
                ->count();

            $firstPurchase = PaymentRequest::where('user_id', $user->id)
                ->where('status', 'paid')
                ->orderBy('created_at', 'asc')
                ->first();

            $lastPurchase = PaymentRequest::where('user_id', $user->id)
                ->where('status', 'paid')
                ->orderBy('created_at', 'desc')
                ->first();

            // Format user name
            $userName = $user->usertype === 'company' 
                ? ($user->company_name ?? $user->name) 
                : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return response()->json([
                'success' => true,
                'message' => 'User subscription details retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $userName,
                        'email' => $user->email,
                        'usertype' => $user->usertype,
                    ],
                    'current_plan' => $currentPlan,
                    'has_active_plan' => $hasActivePlan,
                    'can_upgrade' => $canUpgrade,
                    'needs_upgrade' => $needsUpgrade,
                    'subscription_summary' => [
                        'total_spent' => $totalSpent,
                        'formatted_total_spent' => '₹ ' . number_format($totalSpent, 0),
                        'total_purchases' => $totalPurchases,
                        'first_purchase_date' => $firstPurchase ? $firstPurchase->created_at : null,
                        'first_purchase_date_formatted' => $firstPurchase ? 
                            Carbon::parse($firstPurchase->created_at)->format('d M Y') : null,
                        'last_purchase_date' => $lastPurchase ? $lastPurchase->created_at : null,
                        'last_purchase_date_formatted' => $lastPurchase ? 
                            Carbon::parse($lastPurchase->created_at)->format('d M Y') : null,
                    ],
                    'transaction_history' => $transactionHistory,
                    'active_transactions' => $activeTransactions,
                    'recommended_plans' => $recommendedPlans,
                    'debug' => [
                        'user_id' => $user->id,
                        'package_id' => $user->package_id,
                        'has_package' => !is_null($user->package_id),
                        'job_posts_count' => Post::where('user_id', $user->id)
                            ->where(function($query) {
                                $query->where('post_type_id', 2)
                                      ->orWhereIn('category_id', [5, 6, 7]);
                            })->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get user current subscription failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get subscription details',
                'errors' => (object)['server' => 'An error occurred: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Use a listing (call this when company posts a job)
     */
    public function useListing($userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Only companies use listings
            if ($user->usertype !== 'company') {
                return [
                    'success' => false,
                    'message' => 'Only company accounts can use listings'
                ];
            }

            // Check if package exists and is active
            if (!$user->package_id) {
                return [
                    'success' => false,
                    'message' => 'No package purchased'
                ];
            }

            $isActive = $user->package_end_date && 
                       Carbon::parse($user->package_end_date)->isFuture() && 
                       ($user->jobs_quota - $user->availed_jobs_quota) > 0;

            if (!$isActive) {
                return [
                    'success' => false,
                    'message' => 'No active package or insufficient listings'
                ];
            }

            $user->availed_jobs_quota += 1;
            $user->save();

            // Check if user can still post another job
            $canPost = ($user->jobs_quota - $user->availed_jobs_quota) > 0;

            return [
                'success' => true,
                'message' => 'Listing used successfully',
                'remaining_listings' => $user->jobs_quota - $user->availed_jobs_quota,
                'used_listings' => $user->availed_jobs_quota,
                'total_listings' => $user->jobs_quota,
                'can_post' => $canPost,
                'has_exceeded_limit' => $user->availed_jobs_quota >= $user->jobs_quota,
                'needs_upgrade' => $user->availed_jobs_quota >= $user->jobs_quota
            ];

        } catch (Exception $e) {
            Log::error('Use listing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to use listing: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get package usage history (for company users)
     */
    public function getPackageHistory(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->usertype !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only companies can view package history',
                    'errors' => (object)['user' => 'Invalid user type']
                ], 403);
            }

            // Count job posts that count against quota
            $jobPostsCount = Post::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('post_type_id', 2)
                          ->orWhereIn('category_id', [5, 6, 7]);
                })
                ->count();

            $package = $user->package_id ? Package::find($user->package_id) : null;
            
            $hasExceededLimit = $user->availed_jobs_quota >= $user->jobs_quota;
            $needsUpgrade = $hasExceededLimit || ($user->package_end_date && Carbon::parse($user->package_end_date)->isPast());
            
            // Check if user can post another job
            $canPost = $user->package_end_date && 
                       Carbon::parse($user->package_end_date)->isFuture() && 
                       ($user->jobs_quota - $user->availed_jobs_quota) > 0;

            return response()->json([
                'success' => true,
                'message' => 'Package history retrieved',
                'data' => [
                    'current_package' => $package ? [
                        'id' => $package->id,
                        'title' => $package->package_title,
                        'subtitle' => $package->package_subtitle,
                        'purchased_at' => $user->package_start_date,
                        'purchased_at_formatted' => $user->package_start_date ? 
                            Carbon::parse($user->package_start_date)->format('d M Y') : null,
                        'expires_at' => $user->package_end_date,
                        'expires_at_formatted' => $user->package_end_date ? 
                            Carbon::parse($user->package_end_date)->format('d M Y') : null,
                        'total_listings' => $user->jobs_quota ?? 0,
                        'used_listings' => $user->availed_jobs_quota ?? 0,
                        'job_posts_count' => $jobPostsCount,
                        'remaining_listings' => ($user->jobs_quota ?? 0) - ($user->availed_jobs_quota ?? 0),
                        'can_post' => $canPost,
                        'has_exceeded_limit' => $hasExceededLimit,
                        'needs_upgrade' => $needsUpgrade,
                    ] : null,
                    'history' => [] // Add history from a separate table if needed
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get package history failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get package history',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Check if company can post a job (has available listing)
     */
    public function canPostJob(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->usertype !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only companies can post jobs',
                    'errors' => (object)['user' => 'Invalid user type']
                ], 403);
            }

            $hasPackage = !is_null($user->package_id);
            $isActive = $user->package_end_date && 
                       Carbon::parse($user->package_end_date)->isFuture();
            $hasListings = ($user->jobs_quota - $user->availed_jobs_quota) > 0;

            $canPost = $hasPackage && $isActive && $hasListings;
            $hasExceededLimit = $user->availed_jobs_quota >= $user->jobs_quota;

            $message = '';
            if (!$hasPackage) {
                $message = 'No package purchased';
            } elseif (!$isActive) {
                $message = 'Package expired';
            } elseif (!$hasListings) {
                $message = 'No listings remaining';
            }

            // Count job posts for informational purposes
            $jobPostsCount = Post::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('post_type_id', 2)
                          ->orWhereIn('category_id', [5, 6, 7]);
                })
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Job posting eligibility checked',
                'data' => [
                    'can_post' => $canPost,
                    'has_package' => $hasPackage,
                    'package_active' => $isActive,
                    'has_listings' => $hasListings,
                    'remaining_listings' => ($user->jobs_quota ?? 0) - ($user->availed_jobs_quota ?? 0),
                    'total_listings' => $user->jobs_quota ?? 0,
                    'used_listings' => $user->availed_jobs_quota ?? 0,
                    'job_posts_count' => $jobPostsCount,
                    'has_exceeded_limit' => $hasExceededLimit,
                    'needs_upgrade' => $hasExceededLimit || !$isActive,
                    'message' => $message ?: 'You can post a job'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Can post job check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check eligibility',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get all available packages grouped by type
     */
    public function getAllPackagesGrouped(Request $request)
    {
        try {
            $user = Auth::user();
            
            $packages = [
                'employer' => Package::employerPackages()
                    ->where('status', true)
                    ->orderBy('package_price')
                    ->get()
                    ->map(function ($package) use ($user) {
                        return $this->formatPackage($package, $user);
                    }),
                'job_seeker' => Package::jobSeekerPackages()
                    ->where('status', true)
                    ->get()
                    ->map(function ($package) use ($user) {
                        return $this->formatPackage($package, $user);
                    }),
                'cv_search' => Package::CVSearchPackages()
                    ->where('status', true)
                    ->get()
                    ->map(function ($package) use ($user) {
                        return $this->formatPackage($package, $user);
                    }),
                'featured' => Package::featuredPackages()
                    ->where('status', true)
                    ->get()
                    ->map(function ($package) use ($user) {
                        return $this->formatPackage($package, $user);
                    }),
            ];

            return response()->json([
                'success' => true,
                'message' => 'All packages retrieved successfully',
                'data' => $packages
            ]);

        } catch (Exception $e) {
            Log::error('Get all packages grouped failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Format package data consistently
     */
    private function formatPackage($package, $user = null)
    {
        // Check if user is subscribed to this package
        $isSubscribed = false;
        
        if ($user && $user->usertype === 'company') {
            $isSubscribed = ($user->package_id == $package->id) && 
                            $user->package_end_date && 
                            Carbon::parse($user->package_end_date)->isFuture() &&
                            ($user->jobs_quota - $user->availed_jobs_quota) > 0;
        }

        // Parse features into array
        $features = [];
        if (!empty($package->package_features)) {
            if (is_array($package->package_features)) {
                $features = $package->package_features;
            } else {
                $features = explode("\n", str_replace("\r", "", $package->package_features));
                $features = array_filter($features, function($f) {
                    return trim($f) !== '';
                });
                $features = array_values($features);
            }
        }

        return [
            'id' => $package->id,
            'title' => $package->package_title,
            'subtitle' => $package->package_subtitle,
            'price' => (float)$package->package_price,
            'formatted_price' => $package->formatted_price,
            'duration_days' => $package->package_num_days,
            'duration_text' => $package->duration_text,
            'listings' => $package->package_num_listings,
            'features' => $features,
            'is_popular' => (bool)$package->is_popular,
            'badge_text' => $package->badge_text,
            'package_for' => $package->package_for,
            'currency' => $package->currency,
            'sort_order' => $package->sort_order,
            'is_active' => (bool)$package->status,
            'is_subscribed' => $isSubscribed,
        ];
    }
}