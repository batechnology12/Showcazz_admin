<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\PostTypeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubcategoryController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\UniversalConnectionController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostCommentController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StaticPageController;
use App\Http\Controllers\Api\JobOpportunityController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Payment\PaymentController;


// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [RegisterController::class, 'login']);
Route::get('/areaOfIntrest', [CommonController::class, 'index']);
Route::get('/skills', [CommonController::class, 'skills']);



Route::post('/sendResetCodeEmail', [ForgotPasswordController::class, 'sendResetCodeEmail']);
 
Route::middleware('auth:sanctum')->group(function () {
    
    Route::delete('/chat/conversation/{chatSessionId}', [ChatController::class, 'deleteConversation']);
    Route::post('/chat/conversation/{chatSessionId}/restore', [ChatController::class, 'restoreConversation']);
    
    Route::prefix('notifications')->group(function () {
        Route::get('/get_all', [NotificationController::class, 'getNotifications']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::get('/{id}', [NotificationController::class, 'getNotification']);
        
        Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
        Route::post('/{id}/mark-read', [NotificationController::class, 'markSingleAsRead']);
        Route::post('/{id}/click', [NotificationController::class, 'markAsClicked']);
        
        Route::post('/archive', [NotificationController::class, 'archiveMultiple']);
        Route::post('/{id}/archive', [NotificationController::class, 'archiveNotification']);
        
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        Route::delete('/{id}', [NotificationController::class, 'deleteNotification']);
    });
    
    Route::post('connections/search_global', [UniversalConnectionController::class, 'globalSearch']);
    Route::post('/notification/toggle', [RegisterController::class, 'togglePushNotification']);
    
    Route::post('/payment/create-link', [PaymentController::class, 'createPaymentLink']);
    Route::get('/payment/status/{orderNumber}', [PaymentController::class, 'getPaymentStatus']);
    
    
    
    Route::get('/canPostJob', [SubscriptionController::class, 'canPostJob']);
    Route::get('/current-subscription', [SubscriptionController::class, 'getUserCurrentSubscription']);
    
    
    Route::prefix('packages')->group(function () {
    // Public routes - anyone can view packages
    Route::get('/employer', [SubscriptionController::class, 'getEmployerPackages']);
    Route::get('/job-seeker', [SubscriptionController::class, 'getJobSeekerPackages']);
    Route::get('/cv-search', [SubscriptionController::class, 'getCVSearchPackages']);
    Route::get('/featured', [SubscriptionController::class, 'getFeaturedPackages']);
    Route::get('/all', [SubscriptionController::class, 'getAllPackages']); // For admin
    Route::get('/{id}', [SubscriptionController::class, 'getPackageDetails']);
    
    
   
});

    
    
});

// Public callback routes
Route::get('/payment/callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');
Route::post('/payment/webhook', [PaymentController::class, 'handleWebhook'])->name('payment.webhook');

// Success/Failure pages (for web redirects)
Route::get('/payment-success', function(Request $request) {
    $orderNumber = $request->order ?? '';
    return view('payment.success', ['order_number' => $orderNumber]);
});

Route::get('/payment-failed', function(Request $request) {
    $reason = $request->reason ?? 'unknown';
    $orderNumber = $request->order ?? '';
    return view('payment.failed', ['reason' => $reason, 'order_number' => $orderNumber]);
});
 
 

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {

    
    // Company package purchase
    Route::post('/subscription/purchase', [SubscriptionController::class, 'purchasePackage']);
    Route::get('/company/package', [SubscriptionController::class, 'getCompanyPackage']);
});



Route::get('/terms', [StaticPageController::class, 'getTerms']);
Route::get('/privacy', [StaticPageController::class, 'getPrivacy']);

Route::prefix('post-types')->group(function () {
    Route::get('/', [PostTypeController::class, 'index']);
    Route::get('/dropdown', [PostTypeController::class, 'dropdown']);
    Route::get('/{id}', [PostTypeController::class, 'show']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/dropdown', [CategoryController::class, 'dropdown']);
    Route::get('/by-post-type/{postTypeId}', [CategoryController::class, 'byPostType']);
});

Route::prefix('subcategories')->group(function () {
    Route::get('/', [SubcategoryController::class, 'index']);
    Route::get('/dropdown', [SubcategoryController::class, 'dropdown']);
    Route::get('/by-category/{categoryId}', [SubcategoryController::class, 'byCategory']);
});


// Forgot Password Routes
Route::prefix('password')->group(function () {
    Route::post('/send-code', [ForgotPasswordController::class, 'sendResetCode']);
    Route::post('/verify-code', [ForgotPasswordController::class, 'verifyCode']);
    Route::post('/reset', [ForgotPasswordController::class, 'resetPassword']);
});



Route::middleware('auth:sanctum')->group(function() {
    
     Route::get('/user/profile/{id}', [RegisterController::class, 'getUserProfileById']);
     Route::get('/user/{userId}/post/{postId}', [RegisterController::class, 'getUserPostById']);
     
     
     Route::post('updateVisibility', [RegisterController::class, 'updateVisibility']);
     Route::get('getVisibility', [RegisterController::class, 'getVisibility']);
    
    // Get all opportunities (mini_mission & internship) with filters
    Route::get('/opportunities', [JobOpportunityController::class, 'getOpportunities']);
    
    
    Route::get('/chat_check_user', [ChatController::class, 'getOrCheckChatWithUser']);
    // Get single opportunity details
    Route::get('/opportunities/{id}', [JobOpportunityController::class, 'getOpportunityDetails']);
    
    // Get application questions for an opportunity
    Route::get('/opportunities/{id}/questions', [JobOpportunityController::class, 'getApplicationQuestions']);
    
    // Apply to an opportunity
    Route::post('/opportunities/{id}/apply', [JobOpportunityController::class, 'applyToOpportunity']);
    
    // Get opportunities posted by current user
    Route::get('/my-posted-opportunities', [JobOpportunityController::class, 'getPostedOpportunities']);
    Route::post('/jobs/{id}/status', [JobOpportunityController::class, 'changejobStatus']);
    // Get opportunities applied by current user
    Route::get('/my-applied-opportunities', [JobOpportunityController::class, 'getAppliedOpportunities']);
});



// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    
   

    Route::get('/dashboard', [DashboardController::class, 'getDashboard']);
    //auth
    Route::post('/logout', [RegisterController::class, 'logout']);
    Route::get('/profile', [RegisterController::class, 'profile']);
    Route::post('/changePassword', [RegisterController::class, 'changePassword']);
    Route::get('/profileAlldetails', [RegisterController::class, 'profileAlldetails']);
    
    Route::get('/profile/edit', [RegisterController::class, 'getEditProfileData']);
    Route::post('/update-profile-picture', [RegisterController::class, 'updateProfilePicture']);
    Route::post('/complete-user-profile', [RegisterController::class, 'completeUserProfile']);
    Route::post('/complete-company-profile', [RegisterController::class, 'completeCompanyProfile']);
    Route::get('/check-profile-completion', [RegisterController::class, 'checkProfileCompletion']);
   
    //connections
    Route::get('/suggestions', [UniversalConnectionController::class, 'getSuggestions']);
    Route::get('/mutual-connections', [UniversalConnectionController::class, 'getMutualConnections']);
    Route::post('/connection-action', [UniversalConnectionController::class, 'handleConnection']);
    Route::get('/connections', [UniversalConnectionController::class, 'getAllConnections']);
    Route::get('/getAllConnections_with_profile', [UniversalConnectionController::class, 'getAllConnections_with_profile']);
    
    Route::get('/connection-status', [UniversalConnectionController::class, 'checkStatus']);
    Route::get('/pending-requests', [UniversalConnectionController::class, 'getPendingRequests']);
    
    Route::get('/block-list', [UniversalConnectionController::class, 'getBlockList']);
    Route::delete('/block-list/{id}', [UniversalConnectionController::class, 'unblockFromList']);

    //post
    Route::post('/posts', [PostController::class, 'createPost']);
    // Get posts with filters
    Route::get('/posts', [PostController::class, 'getPosts']);
    // Single post operations
    
    
    Route::put('/posts/{id}/repost-comment', [PostController::class, 'updateRepostComment']);
    
    
    Route::get('/getPost_job/{id}', [PostController::class, 'getPost_job']);
    Route::get('/posts/{id}', [PostController::class, 'getPost']);
    Route::post('/posts/{id}', [PostController::class, 'updatePost']);
    Route::delete('/posts/{id}', [PostController::class, 'deletePost']);
    // Post interactions
    Route::post('/posts/{id}/like', [PostController::class, 'toggleLike']);
    Route::post('/posts/{id}/comments', [PostController::class, 'addComment']);
    Route::post('/posts/{id}/share', [PostController::class, 'sharePost']);
    // Stats
    Route::get('/posts/{id}/stats', [PostController::class, 'getPostStats']);
    Route::get('/my-posts', [PostController::class, 'getUserPosts']);
    Route::get('/users/{userId}/posts', [PostController::class, 'getPostsByUserId']);
    
    
    //updatePost
    Route::get('/posts/{id}/edit', [PostController::class, 'editPostDetails']);
    Route::put('/posts/{id}', [PostController::class, 'updatePost']);

    //comments
    Route::get('/{id}/comments', [PostCommentController::class, 'getComments']);
    Route::post('/{id}/comments', [PostCommentController::class, 'addComment']); 
    Route::put('/comments/{id}', [PostCommentController::class, 'updateComment']);
    Route::delete('/comments/{id}', [PostCommentController::class, 'deleteComment']);
    
    
    //rePost
    Route::post('/posts/{id}/repost', [PostController::class, 'repostPost']);
    Route::post('/posts/{id}/getPostLikers', [PostController::class, 'getPostLikers']);
    
    
    // Route::get('/reposts/{id}', [PostController::class, 'getRepost']);
    // Route::get('/posts/{postId}/reposts', [PostController::class, 'getPostReposts']);
    // Route::get('/user/reposts', [PostController::class, 'getUserReposts']);
    // Route::delete('/reposts/{id}', [PostController::class, 'deleteRepost']);

    // Comment likes
    Route::post('/comments/{id}/like', [PostCommentController::class, 'toggleLike']);
    Route::get('/comments/{id}/likes', [PostCommentController::class, 'getCommentLikes']);

    Route::prefix('chat')->group(function () {
        // Worth discussing points
        Route::get('/worth-discussing-points', [ChatController::class, 'getWorthDiscussingPoints']);
        
        // Initialize chats
        Route::post('/posts/{postId}/initiate', [ChatController::class, 'initializeChatFromPost']);
        Route::post('/general/initiate', [ChatController::class, 'initializeGeneralChat']);
        
        // Message operations
        Route::post('/send', [ChatController::class, 'sendMessage']);
        // Route::get('/messages/{chatSessionId}', [ChatController::class, 'getMessages']);
        // Route::get('/messages', [ChatController::class, 'getMessages']); // Alternative with query param
        
        // Conversations
        // Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::get('/conversations', [ChatController::class, 'getConversations_new']);
        Route::get('/messages/{chatSessionId}', [ChatController::class, 'getMessages_new']);
        
        Route::get('/conversations_new', [ChatController::class, 'getConversations_new']);
        Route::get('/messages_new/{chatSessionId}', [ChatController::class, 'getMessages_new']);
        
        // Message actions
        Route::post('/mark-read', [ChatController::class, 'markMessagesAsRead']);
        Route::delete('/message/{messageId}', [ChatController::class, 'deleteMessage']);
        
        // Chat session actions
        Route::post('/session/{chatSessionId}/close', [ChatController::class, 'closeChatSession']);
    });
   
});

Route::get('/migrate-old-images', function () {
    try {
        $posts = \Illuminate\Support\Facades\DB::table('posts')
            ->whereNotNull('images')
            ->orWhereNotNull('files')
            ->get();
            
        $migratedImages = 0;
        $migratedFiles = 0;
        $missingImages = 0;
        $missingFiles = 0;
        $results = [];

        foreach ($posts as $post) {
            // Process Images
            if (!empty($post->images)) {
                $images = json_decode($post->images, true);
                if (is_string($images)) $images = json_decode($images, true);
                if (is_array($images)) {
                    foreach ($images as $img) {
                        $localPath = public_path('post_images/' . $img);
                        if (file_exists($localPath)) {
                            \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_images', new \Illuminate\Http\File($localPath), $img, 'public');
                            $migratedImages++;
                            $results[] = "Migrated Image: " . $img;
                        } else {
                            $missingImages++;
                        }
                    }
                }
            }

            // Process Files
            if (!empty($post->files)) {
                $files = json_decode($post->files, true);
                if (is_string($files)) $files = json_decode($files, true);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        $localPath = public_path('post_files/' . $file);
                        if (file_exists($localPath)) {
                            \Illuminate\Support\Facades\Storage::disk('do')->putFileAs('post_files', new \Illuminate\Http\File($localPath), $file, 'public');
                            $migratedFiles++;
                            $results[] = "Migrated File: " . $file;
                        } else {
                            $missingFiles++;
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Migration completed successfully!',
            'stats' => [
                'total_posts_checked' => count($posts),
                'migrated_images' => $migratedImages,
                'migrated_files' => $migratedFiles,
                'missing_local_images' => $missingImages,
                'missing_local_files' => $missingFiles,
            ],
            'details' => $results
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/debug-images', function () {
    $dir = public_path('post_images');
    $files = is_dir($dir) ? array_diff(scandir($dir), array('.', '..')) : [];
    
    $posts = \Illuminate\Support\Facades\DB::table('posts')->whereNotNull('images')->limit(5)->get();
    $dbPathsChecked = [];
    
    foreach ($posts as $post) {
        $images = json_decode($post->images, true);
        if (is_string($images)) $images = json_decode($images, true);
        if (is_array($images)) {
            foreach ($images as $img) {
                $localPath = public_path('post_images/' . $img);
                $dbPathsChecked[] = [
                    'image_name' => $img,
                    'path_checked' => $localPath,
                    'exists' => file_exists($localPath)
                ];
            }
        }
    }

    return response()->json([
        'public_path_post_images' => $dir,
        'is_directory' => is_dir($dir),
        'files_in_directory_sample' => array_slice(array_values($files), 0, 10),
        'db_paths_checked' => $dbPathsChecked
    ]);
});