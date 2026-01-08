<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\API\PostTypeController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\SubcategoryController;
use App\Http\Controllers\API\CommonController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\UniversalConnectionController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PostCommentController;
use App\Http\Controllers\Api\ChatController;

// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [RegisterController::class, 'login']);
Route::get('/areaOfIntrest', [CommonController::class, 'index']);
Route::get('/skills', [CommonController::class, 'skills']);

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


// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {

    //auth
    Route::post('/logout', [RegisterController::class, 'logout']);
    Route::get('/profile', [RegisterController::class, 'profile']);
    Route::post('/update-profile-picture', [RegisterController::class, 'updateProfilePicture']);
    Route::post('/complete-user-profile', [RegisterController::class, 'completeUserProfile']);
    Route::post('/complete-company-profile', [RegisterController::class, 'completeCompanyProfile']);
    Route::get('/check-profile-completion', [RegisterController::class, 'checkProfileCompletion']);
   
    //connections
    Route::get('/suggestions', [UniversalConnectionController::class, 'getSuggestions']);
    Route::get('/mutual-connections', [UniversalConnectionController::class, 'getMutualConnections']);
    Route::post('/connection-action', [UniversalConnectionController::class, 'handleConnection']);
    Route::get('/connections', [UniversalConnectionController::class, 'getAllConnections']);
    Route::get('/connection-status', [UniversalConnectionController::class, 'checkStatus']);
    Route::get('/pending-requests', [UniversalConnectionController::class, 'getPendingRequests']);

    //post
    Route::post('/posts', [PostController::class, 'createPost']);
    // Get posts with filters
    Route::get('/posts', [PostController::class, 'getPosts']);
    // Single post operations
    Route::get('/posts/{id}', [PostController::class, 'getPost']);
    Route::put('/posts/{id}', [PostController::class, 'updatePost']);
    Route::delete('/posts/{id}', [PostController::class, 'deletePost']);
    // Post interactions
    Route::post('/posts/{id}/like', [PostController::class, 'toggleLike']);
    Route::post('/posts/{id}/comments', [PostController::class, 'addComment']);
    Route::post('/posts/{id}/share', [PostController::class, 'sharePost']);
    // Stats
    Route::get('/posts/{id}/stats', [PostController::class, 'getPostStats']);
    Route::get('/my-posts', [PostController::class, 'getUserPosts']);
    Route::get('/users/{userId}/posts', [PostController::class, 'getPostsByUserId']);

    //comments
    Route::get('/{id}/comments', [PostCommentController::class, 'getComments']);
    Route::post('/{id}/comments', [PostCommentController::class, 'addComment']); 
    Route::put('/comments/{id}', [PostCommentController::class, 'updateComment']);
    Route::delete('/comments/{id}', [PostCommentController::class, 'deleteComment']);

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
        Route::get('/messages/{chatSessionId}', [ChatController::class, 'getMessages']);
        Route::get('/messages', [ChatController::class, 'getMessages']); // Alternative with query param
        
        // Conversations
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        
        // Message actions
        Route::post('/mark-read', [ChatController::class, 'markMessagesAsRead']);
        Route::delete('/message/{messageId}', [ChatController::class, 'deleteMessage']);
        
        // Chat session actions
        Route::post('/session/{chatSessionId}/close', [ChatController::class, 'closeChatSession']);
    });
   
});