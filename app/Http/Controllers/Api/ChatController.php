<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use App\Post;
use App\Job;
use App\UserMessage;
use App\Models\ChatType;
use App\Models\WorthDiscussingPoint;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Get all worth discussing points
     */
    public function getWorthDiscussingPoints()
    {
        try {
            $points = WorthDiscussingPoint::active()->ordered()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Worth discussing points retrieved successfully',
                'data' => $points
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve worth discussing points',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Helper function to get entity data from either users or companies table
     */
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
                'email' => $company->email ?? null,
                'phone' => $company->phone ?? null,
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'entity_type' => 'company',
                'original_id' => $company->id
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
                        'email' => $companyRecord->email ?? $user->email,
                        'phone' => $companyRecord->phone ?? $user->phone,
                        'image' => $companyRecord->logo ? asset('company_logos/' . $companyRecord->logo) : null,
                        'entity_type' => 'company',
                        'original_id' => $companyRecord->id
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
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'entity_type' => 'user',
                'original_id' => $user->id
            ];
        }

        // Not found in either table
        return [
            'id' => $id,
            'name' => 'Unknown User',
            'usertype' => 'unknown',
            'email' => null,
            'phone' => null,
            'image' => null,
            'entity_type' => 'unknown',
            'original_id' => null
        ];
    }

    /**
     * Helper function to get user by ID (checks both tables)
     */
    private function findUserById($id)
    {
        if (!$id) {
            return null;
        }

        // Check companies table first
        $company = Company::find($id);
        if ($company) {
            return $company;
        }

        // Then check users table
        return User::find($id);
    }

    /**
     * Helper function to get user email by ID
     */
    private function getUserEmail($id)
    {
        $entity = $this->getEntityData($id);
        return $entity ? $entity['email'] : null;
    }

    /**
     * Helper function to get user name by ID
     */
    private function getUserName($id)
    {
        $entity = $this->getEntityData($id);
        return $entity ? $entity['name'] : 'Unknown User';
    }

    /**
     * Helper function to get user phone by ID
     */
    private function getUserPhone($id)
    {
        $entity = $this->getEntityData($id);
        return $entity ? $entity['phone'] : null;
    }

    /**
     * Initialize chat from post
     */
    public function initializeChatFromPost(Request $request, $postId)
    {
        try {
            $user = Auth::user();
            
            // Get current user entity data
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'chat_type' => 'required|in:job_application,worth_discussing',
                'worth_discussing_point_id' => 'required_if:chat_type,worth_discussing|exists:worth_discussing_points,id',
                'initial_message' => 'required|string|min:1|max:1000',
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

            // Get Post
            $post = Post::with('user')->find($postId);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }

            // Validate post status
            if (!$post->is_active || !$post->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is not available',
                    'errors' => (object)['post' => 'Post is not published or inactive']
                ], 400);
            }

            // Prevent self chat
            if ($user->id == $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot initiate chat about your own post',
                    'errors' => (object)['user' => 'Cannot chat with yourself']
                ], 400);
            }

            // Validate Job Post
            if ($request->chat_type == 'job_application' && !$post->is_job_post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid job post',
                    'errors' => (object)['post' => 'This post is not a job post']
                ], 400);
            }

            // Validate Worth Discussing
            if ($request->chat_type == 'worth_discussing') {
                if ($post->is_job_post) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Worth discussing is not available for job posts'
                    ], 400);
                }

                $point = WorthDiscussingPoint::find($request->worth_discussing_point_id);
                if (!$point) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid discussion point'
                    ], 404);
                }
            }

            // Get Chat Type
            $chatType = ChatType::where('slug', $request->chat_type)->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type'
                ], 404);
            }

            // Get receiver data (post owner)
            $receiverData = $this->getEntityData($post->user_id);
            if (!$receiverData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post owner not found',
                    'errors' => (object)['receiver' => 'Post owner not found']
                ], 404);
            }

            // Generate Session ID
            $sessionId = $this->generateChatSessionId(
                $user->id,
                $post->user_id,
                $post->id,
                $chatType->id
            );

            DB::beginTransaction();

            // ==============================
            // CREATE / UPDATE CHAT SESSION
            // ==============================

            $chatSession = ChatSession::find($sessionId);

            if (!$chatSession) {
                $chatSession = ChatSession::create([
                    'id' => $sessionId,
                    'user1_id' => min($user->id, $post->user_id),
                    'user2_id' => max($user->id, $post->user_id),
                    'post_id' => $post->id,
                    'chat_type_id' => $chatType->id,
                    'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                    'last_message_at' => now(),
                    'last_message' => Str::limit($request->initial_message, 100),
                    'unread_count' => 1,
                    'is_active' => true
                ]);
            } else {
                $chatSession->update([
                    'last_message_at' => now(),
                    'last_message' => Str::limit($request->initial_message, 100),
                    'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                ]);

                $chatSession->increment('unread_count');
            }

            // ==============================
            // CREATE MESSAGE
            // ==============================

            $subject = $this->generateChatSubject(
                $request->chat_type,
                $post,
                $request->worth_discussing_point_id
            );

            $message = UserMessage::create([
                'listing_id' => $post->id,
                'listing_title' => $post->title,
                'from_id' => $user->id,
                'to_id' => $post->user_id,
                'to_email' => $receiverData['email'],
                'to_name' => $receiverData['name'],
                'from_name' => $currentUserData['name'],
                'from_email' => $currentUserData['email'],
                'from_phone' => $currentUserData['phone'],
                'message_txt' => $request->initial_message,
                'subject' => $subject,
                'chat_type_id' => $chatType->id,
                'chat_session_id' => $sessionId,
                'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                'status' => 'active',
                'is_read' => false,
                'message_type' => 'text'
            ]);

            DB::commit();

            $message->load([
                'sender',
                'receiver',
                'post',
                'chatType',
                'worthDiscussingPoint'
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->chat_type == 'job_application'
                    ? 'Job application sent successfully'
                    : 'Discussion initiated successfully',
                'data' => [
                    'message' => $this->formatMessageResponse($message),
                    'session_id' => $sessionId,
                    'chat_type' => $chatType->slug,
                    'post' => [
                        'id' => $post->id,
                        'title' => $post->title,
                        'is_job_post' => $post->is_job_post
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Chat initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize chat',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Initialize general chat (not post-related)
     */
    public function initializeGeneralChat(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get current user entity data
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'to_user_id' => 'required',
                'initial_message' => 'required|string|min:1|max:1000',
                'subject' => 'nullable|string|max:200'
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

            // Prevent self-messaging
            if ($user->id == $request->to_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message to yourself',
                    'errors' => (object)['to_user_id' => 'Cannot send message to yourself']
                ], 400);
            }

            // Get receiver data (checks both tables)
            $receiverData = $this->getEntityData($request->to_user_id);
            if (!$receiverData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found',
                    'errors' => (object)['to_user_id' => 'User not found']
                ], 404);
            }

            // Get chat type (general)
            $chatType = ChatType::where('slug', 'general')->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat type not found',
                    'errors' => (object)['chat_type' => 'General chat type not configured']
                ], 404);
            }

            // Generate chat session ID
            $sessionId = $this->generateChatSessionId(
                $user->id,
                $request->to_user_id,
                null,
                $chatType->id
            );

            DB::beginTransaction();

            try {
                // Create or update chat session
                $chatSession = ChatSession::updateOrCreate(
                    ['id' => $sessionId],
                    [
                        'user1_id' => min($user->id, $request->to_user_id),
                        'user2_id' => max($user->id, $request->to_user_id),
                        'post_id' => null,
                        'chat_type_id' => $chatType->id,
                        'worth_discussing_point_id' => null,
                        'last_message_at' => now(),
                        'last_message' => Str::limit($request->initial_message, 100),
                        'is_active' => true
                    ]
                );

                // Increment unread count
                $chatSession->increment('unread_count');

                // Create the initial message
                $message = UserMessage::create([
                    'listing_id' => null,
                    'listing_title' => null,
                    'from_id' => $user->id,
                    'to_id' => $request->to_user_id,
                    'to_email' => $receiverData['email'],
                    'to_name' => $receiverData['name'],
                    'from_name' => $currentUserData['name'],
                    'from_email' => $currentUserData['email'],
                    'from_phone' => $currentUserData['phone'],
                    'message_txt' => $request->initial_message,
                    'subject' => $request->subject ?? 'General Chat',
                    'chat_type_id' => $chatType->id,
                    'chat_session_id' => $sessionId,
                    'worth_discussing_point_id' => null,
                    'status' => 'active',
                    'is_read' => false,
                    'message_type' => 'text'
                ]);

                DB::commit();

                // Load relationships
                $message->load(['sender', 'receiver', 'chatType']);

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => [
                        'message' => $this->formatMessageResponse($message),
                        'session_id' => $sessionId,
                        'chat_type' => $chatType->slug
                    ]
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('General chat initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
    
    
    
    /**
     * Check or get chat session with a specific user
     */
    public function getOrCheckChatWithUser(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'chat_type' => 'nullable|string|in:general,job_application,worth_discussing',
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
    
            $targetUserId = $request->user_id;
            $chatType = $request->chat_type ?? 'general';
    
            // Get target user data
            $targetUserData = $this->getEntityData($targetUserId);
            if (!$targetUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user_id' => 'User not found']
                ], 404);
            }
    
            // Get chat type model
            $chatTypeModel = ChatType::where('slug', $chatType)->first();
            if (!$chatTypeModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type',
                    'errors' => (object)['chat_type' => 'Chat type not found']
                ], 404);
            }
    
            // Check if a chat session exists between these two users
            $existingSession = ChatSession::where(function($query) use ($user, $targetUserId, $chatTypeModel) {
                    $query->where('user1_id', $user->id)
                          ->where('user2_id', $targetUserId)
                          ->where('chat_type_id', $chatTypeModel->id);
                })
                ->orWhere(function($query) use ($user, $targetUserId, $chatTypeModel) {
                    $query->where('user1_id', $targetUserId)
                          ->where('user2_id', $user->id)
                          ->where('chat_type_id', $chatTypeModel->id);
                })
                ->where('is_active', true)
                ->with(['messages' => function($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }])
                ->first();
    
            // If chat exists, return the session details
            if ($existingSession) {
                // Get last message
                $lastMessage = $existingSession->messages->first();
                
                // Get unread count for current user
                $unreadCount = UserMessage::where('chat_session_id', $existingSession->id)
                    ->where('to_id', $user->id)
                    ->where('is_read', false)
                    ->count();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Existing chat found',
                    'data' => [
                        'has_chat' => true,
                        'chat_session' => [
                            'session_id' => $existingSession->id,
                            'chat_type' => [
                                'id' => $chatTypeModel->id,
                                'name' => $chatTypeModel->name,
                                'slug' => $chatTypeModel->slug,
                            ],
                            'other_user' => $targetUserData,
                            'last_message' => $lastMessage ? [
                                'id' => $lastMessage->id,
                                'message' => Str::limit($lastMessage->message_txt, 100),
                                'created_at' => $lastMessage->created_at,
                                'created_at_formatted' => $lastMessage->created_at->diffForHumans(),
                            ] : null,
                            'unread_count' => $unreadCount,
                            'created_at' => $existingSession->created_at,
                            'updated_at' => $existingSession->updated_at,
                        ],
                        'can_initiate' => false,
                    ]
                ]);
            }
    
            // No chat exists - return that user can initiate a new chat
            return response()->json([
                'success' => true,
                'message' => 'No existing chat found',
                'data' => [
                    'has_chat' => false,
                    'can_initiate' => true,
                    'other_user' => $targetUserData,
                    'chat_type' => [
                        'id' => $chatTypeModel->id,
                        'name' => $chatTypeModel->name,
                        'slug' => $chatTypeModel->slug,
                    ],
                    'suggestions' => [
                        'You can start a new conversation with this user',
                        'Click the chat button to send your first message',
                    ]
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Check chat with user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to check chat status',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Send message in existing chat
     */
    public function sendMessage(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get current user entity data
            $currentUserData = $this->getEntityData($user->id);
            if (!$currentUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)['user' => 'User not found']
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'chat_session_id' => 'required|exists:chat_sessions,id',
                'message' => 'required|string|min:1|max:5000',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240' // 10MB max per file
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

            // Get chat session
            $chatSession = ChatSession::find($request->chat_session_id);
            
            if (!$chatSession->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session is inactive',
                    'errors' => (object)['session' => 'This chat session is no longer active']
                ], 400);
            }

            // Verify user is part of this chat session
            if ($chatSession->user1_id != $user->id && $chatSession->user2_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }

            // Get the other user ID
            $otherUserId = ($chatSession->user1_id == $user->id) 
                ? $chatSession->user2_id 
                : $chatSession->user1_id;
            
            // Get receiver data (checks both tables)
            $receiverData = $this->getEntityData($otherUserId);
            if (!$receiverData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found',
                    'errors' => (object)['receiver' => 'Receiver user not found']
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Handle attachments
                $attachments = [];
                if ($request->hasFile('attachments')) {
                    $uploadPath = public_path('chat_attachments');
                    
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    foreach ($request->file('attachments') as $file) {
                        $fileName = 'chat_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                        $file->move($uploadPath, $fileName);
                        
                        $attachments[] = [
                            'filename' => $file->getClientOriginalName(),
                            'path' => $fileName,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];
                    }
                }
                
                // Create message
                $message = UserMessage::create([
                    'listing_id' => $chatSession->post_id,
                    'listing_title' => $chatSession->post ? $chatSession->post->title : null,
                    'from_id' => $user->id,
                    'to_id' => $otherUserId,
                    'to_email' => $receiverData['email'],
                    'to_name' => $receiverData['name'],
                    'from_name' => $currentUserData['name'],
                    'from_email' => $currentUserData['email'],
                    'from_phone' => $currentUserData['phone'],
                    'message_txt' => $request->message,
                    'subject' => $chatSession->post ? $chatSession->post->title : 'General Chat',
                    'chat_type_id' => $chatSession->chat_type_id,
                    'chat_session_id' => $chatSession->id,
                    'worth_discussing_point_id' => $chatSession->worth_discussing_point_id,
                    'status' => 'active',
                    'is_read' => false,
                    'message_type' => !empty($attachments) ? 'file' : 'text',
                    'attachments' => !empty($attachments) ? json_encode($attachments) : null
                ]);

                // Update chat session
                $chatSession->update([
                    'last_message_at' => now(),
                    'last_message' => Str::limit($request->message, 100)
                ]);

                $chatSession->increment('unread_count');

                DB::commit();

                // Load relationships
                $message->load(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint']);

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $this->formatMessageResponse($message)
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                
                // Clean up uploaded files if message creation failed
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        @unlink(public_path('chat_attachments/' . $attachment['path']));
                    }
                }
                
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Send message failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get chat messages
     */
    public function getMessages(Request $request, $chatSessionId = null)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
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

            // If chatSessionId is provided in URL, use it
            if ($chatSessionId) {
                $sessionId = $chatSessionId;
            } else {
                // Otherwise check query parameters for backward compatibility
                $sessionId = $request->chat_session_id;
            }

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session ID is required',
                    'errors' => (object)['session' => 'Chat session ID is required']
                ], 400);
            }

            // Get chat session
            $chatSession = ChatSession::find($sessionId);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }

            // Verify user is part of this chat session
            if ($chatSession->user1_id != $user->id && $chatSession->user2_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }

            $perPage = $request->per_page ?? 50;
            $page = $request->page ?? 1;

            // Get messages for this session
            $messages = UserMessage::with(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint'])
                ->where('chat_session_id', $sessionId)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Mark messages as read (only messages sent to current user)
            UserMessage::where('chat_session_id', $sessionId)
                ->where('to_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            // Reset unread count for this session
            //$chatSession->update(['unread_count' => 0]);

            // Format messages
            $formattedMessages = $messages->map(function ($message) use ($user) {
                return $this->formatMessageResponse($message);
            });

            return response()->json([
                'success' => true,
                'message' => 'Messages retrieved successfully',
                'data' => [
                    'chat_session' => $this->formatChatSessionResponse($chatSession, $user),
                    'messages' => $formattedMessages,
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                        'last_page' => $messages->lastPage()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get messages failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get user's conversations - WITH all_job_woth filter
     */
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'chat_type' => 'nullable|string|in:all,general,job_application,worth_discussing,all_job_woth',
                'search' => 'nullable|string|min:1|max:255',
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
            $chatType = $request->chat_type;
            $searchTerm = $request->search;

            // Build query for chat sessions
            $query = ChatSession::with([
                'user1',
                'user2',
                'post',
                'chatType',
                'worthDiscussingPoint',
                'messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(1);
                }
            ])
            ->where('is_active', true)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            });

            // Apply chat type filter
            if ($chatType) {
                if ($chatType === 'all') {
                    // No filter - show all conversations including general
                    // Do nothing
                } elseif ($chatType === 'all_job_woth') {
                    // Show ONLY job_application AND worth_discussing (exclude general)
                    $chatTypeIds = ChatType::whereIn('slug', ['job_application', 'worth_discussing'])
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($chatTypeIds)) {
                        $query->whereIn('chat_type_id', $chatTypeIds);
                    }
                } elseif ($chatType === 'general' || $chatType === 'job_application' || $chatType === 'worth_discussing') {
                    // Single chat type filter
                    $chatTypeModel = ChatType::where('slug', $chatType)->first();
                    if ($chatTypeModel) {
                        $query->where('chat_type_id', $chatTypeModel->id);
                    }
                }
            }

            // Get all conversations (for search filtering)
            $allConversations = $query->orderBy('last_message_at', 'desc')->get();

            // Apply global search if provided
            if ($searchTerm) {
                $filteredConversations = $allConversations->filter(function ($session) use ($user, $searchTerm) {
                    $otherUser = $this->getOtherUserInSession($session, $user->id);
                    
                    if (!$otherUser) {
                        return false;
                    }
                    
                    // Get other user data
                    $otherUserData = $this->getEntityData($otherUser->id);
                    
                    if (!$otherUserData) {
                        return false;
                    }
                    
                    $searchTermLower = strtolower($searchTerm);
                    
                    // Search in name
                    $nameMatch = stripos($otherUserData['name'], $searchTermLower) !== false;
                    
                    // Search in user type (student, professional, company)
                    $typeMatch = stripos($otherUserData['usertype'], $searchTermLower) !== false;
                    
                    // Also search in email
                    $emailMatch = isset($otherUserData['email']) && stripos($otherUserData['email'], $searchTermLower) !== false;
                    
                    return $nameMatch || $typeMatch || $emailMatch;
                })->values();
            } else {
                $filteredConversations = $allConversations;
            }

            // Manual pagination
            $total = $filteredConversations->count();
            $offset = ($page - 1) * $perPage;
            $paginatedConversations = $filteredConversations->slice($offset, $perPage)->values();

                // Format conversations
                $formattedConversations = $paginatedConversations->map(function ($session) use ($user) {
                    return $this->formatChatSessionResponse($session, $user);
                });

            // Get total unread count
            $totalUnread = UserMessage::where('to_id', $user->id)
                ->where('is_read', false)
                ->count();

            // Get unread counts by chat type
            $unreadByType = DB::table('user_messages')
                ->join('chat_types', 'user_messages.chat_type_id', '=', 'chat_types.id')
                ->where('user_messages.to_id', $user->id)
                ->where('user_messages.is_read', false)
                ->select('chat_types.slug', DB::raw('COUNT(*) as count'))
                ->groupBy('chat_types.slug')
                ->pluck('count', 'slug');

            return response()->json([
                'success' => true,
                'message' => 'Conversations retrieved successfully',
                'data' => [
                    'conversations' => $formattedConversations,
                    'stats' => [
                        'total_unread' => $totalUnread,
                        'unread_by_type' => $unreadByType
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                    ],
                    'filters' => [
                        'chat_type' => $chatType ?? 'all',
                        'search' => $searchTerm,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get conversations failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversations',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Search users for new conversations (separate route)
     */
    public function searchUsersForChat(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'search' => 'required|string|min:1|max:255',
                'limit' => 'nullable|integer|min:1|max:50',
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

            $searchTerm = $request->search;
            $limit = $request->limit ?? 20;

            // Get existing conversations to exclude
            $existingChatUserIds = ChatSession::where(function($q) use ($user) {
                    $q->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
                })
                ->get()
                ->map(function($session) use ($user) {
                    return $session->user1_id == $user->id ? $session->user2_id : $session->user1_id;
                })
                ->toArray();

            // Add current user to exclude list
            $existingChatUserIds[] = $user->id;

            // Search in users table
            $users = User::where('is_active', 1)
                ->whereNotIn('id', $existingChatUserIds)
                ->where(function($q) use ($searchTerm) {
                    $q->where('first_name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('last_name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('usertype', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('headline', 'ILIKE', "%{$searchTerm}%");
                })
                ->limit($limit)
                ->get()
                ->map(function($userResult) {
                    return [
                        'id' => $userResult->id,
                        'name' => trim(($userResult->first_name ?? '') . ' ' . ($userResult->last_name ?? '')) ?: $userResult->name,
                        'email' => $userResult->email,
                        'usertype' => $userResult->usertype ?? 'user',
                        'image' => $userResult->image ? asset('user_images/' . $userResult->image) : null,
                        'headline' => $userResult->headline,
                        'entity_type' => 'user',
                    ];
                });

            // Search in companies table
            $companies = Company::where('is_active', 1)
                ->whereNotIn('id', $existingChatUserIds)
                ->where(function($q) use ($searchTerm) {
                    $q->where('name', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
                })
                ->limit($limit)
                ->get()
                ->map(function($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email,
                        'usertype' => 'company',
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'headline' => $company->description,
                        'entity_type' => 'company',
                        'slug' => $company->slug,
                    ];
                });

            // Combine and sort results
            $results = $users->concat($companies)->sortBy('name')->values();

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'results' => $results->take($limit),
                    'total' => $results->count(),
                    'search_term' => $searchTerm,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('User search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search users',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'message_ids' => 'required|array',
                'message_ids.*' => 'exists:user_messages,id'
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

            // Update messages
            $updatedCount = UserMessage::whereIn('id', $request->message_ids)
                ->where('to_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            // Update chat session unread counts
            $affectedSessions = UserMessage::whereIn('id', $request->message_ids)
                ->where('to_id', $user->id)
                ->pluck('chat_session_id')
                ->unique();

            foreach ($affectedSessions as $sessionId) {
                $unreadCount = UserMessage::where('chat_session_id', $sessionId)
                    ->where('to_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                    
                ChatSession::where('id', $sessionId)
                    ->update(['unread_count' => $unreadCount]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Mark messages as read failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Delete a message (soft delete for sender)
     */
    public function deleteMessage($messageId)
    {
        try {
            $user = Auth::user();
            
            $message = UserMessage::find($messageId);
            
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                    'errors' => (object)['message' => 'Message not found']
                ], 404);
            }

            // Check if user is the sender
            if ($message->from_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['message' => 'You can only delete your own messages']
                ], 403);
            }

            // Soft delete (update status)
            $message->update(['status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
                'data' => null
            ]);

        } catch (Exception $e) {
            Log::error('Delete message failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Close/end a chat session
     */
    public function closeChatSession($chatSessionId)
    {
        try {
            $user = Auth::user();
            
            $chatSession = ChatSession::find($chatSessionId);
            
            if (!$chatSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat session not found',
                    'errors' => (object)['session' => 'Chat session not found']
                ], 404);
            }

            // Verify user is part of this chat session
            if ($chatSession->user1_id != $user->id && $chatSession->user2_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => (object)['session' => 'You are not part of this chat session']
                ], 403);
            }

            // Mark session as inactive
            $chatSession->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Chat session closed successfully',
                'data' => null
            ]);

        } catch (Exception $e) {
            Log::error('Close chat session failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close chat session',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Helper: Generate chat session ID
     */
    private function generateChatSessionId($user1Id, $user2Id, $postId = null, $chatTypeId = null)
    {
        $parts = [
            min($user1Id, $user2Id),
            max($user1Id, $user2Id),
            $postId,
            $chatTypeId
        ];
        
        return md5(implode('_', array_filter($parts)));
    }

    /**
     * Helper: Generate chat subject
     */
    private function generateChatSubject($chatType, $post, $worthDiscussingPointId = null)
    {
        switch ($chatType) {
            case 'job_application':
                return "Job Application: {$post->title}";
                
            case 'worth_discussing':
                $point = WorthDiscussingPoint::find($worthDiscussingPointId);
                return $point ? "{$point->title} - {$post->title}" : "Discussion - {$post->title}";
                
            default:
                return "General Chat";
        }
    }

    /**
     * Helper: Format message response
     */
    private function formatMessageResponse($message)
    {
        $user = Auth::user();
        $attachments = $message->attachments ? json_decode($message->attachments, true) : [];
        
        // Get sender data from both tables
        $senderData = $this->getEntityData($message->from_id);
        
        // Get receiver data from both tables
        $receiverData = $this->getEntityData($message->to_id);
        
        $formatted = [
            'id' => $message->id,
            'message' => $message->message_txt,
            'subject' => $message->subject,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at,
            'message_type' => $message->message_type,
            'attachments' => array_map(function($attachment) {
                return [
                    'filename' => $attachment['filename'] ?? '',
                    'path' => asset('chat_attachments/' . ($attachment['path'] ?? '')),
                    'mime_type' => $attachment['mime_type'] ?? '',
                    'size' => $attachment['size'] ?? 0
                ];
            }, $attachments),
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
            'is_sender' => $message->from_id == $user->id,
            'sender' => $senderData ? [
                'id' => $senderData['id'],
                'name' => $senderData['name'],
                'email' => $senderData['email'],
                'image' => $senderData['image'],
                'usertype' => $senderData['usertype'],
                'entity_type' => $senderData['entity_type']
            ] : null,
            'receiver' => $receiverData ? [
                'id' => $receiverData['id'],
                'name' => $receiverData['name'],
                'email' => $receiverData['email'],
                'image' => $receiverData['image'],
                'usertype' => $receiverData['usertype'],
                'entity_type' => $receiverData['entity_type']
            ] : null,
        ];

        // Add post info if available
        if ($message->listing_id && $message->post) {
            $formatted['post'] = [
                'id' => $message->post->id,
                'title' => $message->post->title,
                'is_job_post' => $message->post->is_job_post
            ];
        }

        // Add chat type info
        if ($message->chatType) {
            $formatted['chat_type'] = [
                'id' => $message->chatType->id,
                'name' => $message->chatType->name,
                'slug' => $message->chatType->slug
            ];
        }

        // Add worth discussing point info if available
        if ($message->worthDiscussingPoint) {
            $formatted['worth_discussing_point'] = [
                'id' => $message->worthDiscussingPoint->id,
                'title' => $message->worthDiscussingPoint->title,
                'description' => $message->worthDiscussingPoint->description,
                'icon' => $message->worthDiscussingPoint->icon
            ];
        }

        return $formatted;
    }

    /**
     * Helper: Format chat session response
     */
    // private function formatChatSessionResponse($session, $currentUser)
    // {
    //     $otherUser = $this->getOtherUserInSession($session, $currentUser->id);
    //     $lastMessage = $session->messages->first();
        
    //     // Get other user data from both tables
    //     $otherUserData = $otherUser ? $this->getEntityData($otherUser->id) : null;
        
    //     $formatted = [
    //         'session_id' => $session->id,
    //         'other_user' => $otherUserData ? [
    //             'id' => $otherUserData['id'],
    //             'name' => $otherUserData['name'],
    //             'email' => $otherUserData['email'],
    //             'image' => $otherUserData['image'],
    //             'usertype' => $otherUserData['usertype'],
    //             'entity_type' => $otherUserData['entity_type']
    //         ] : null,
    //         'unread_count' => $session->unread_count,
    //         'last_message_at' => $session->last_message_at,
    //         'last_message' => $session->last_message,
    //         'created_at' => $session->created_at,
    //         'is_active' => $session->is_active
    //     ];

    //     // Add post info if available
    //     if ($session->post) {
    //         $formatted['post'] = [
    //             'id' => $session->post->id,
    //             'title' => $session->post->title,
    //             'content' => $session->post->content,
    //             'stipend_amount' => $session->post->stipend_amount,
    //             'is_job_post' => $session->post->is_job_post
    //         ];
    //     }

    //     // Add chat type info
    //     if ($session->chatType) {
    //         $formatted['chat_type'] = [
    //             'id' => $session->chatType->id,
    //             'name' => $session->chatType->name,
    //             'slug' => $session->chatType->slug
    //         ];
    //     }

    //     // Add worth discussing point info if available
    //     if ($session->worthDiscussingPoint) {
    //         $formatted['worth_discussing_point'] = [
    //             'id' => $session->worthDiscussingPoint->id,
    //             'title' => $session->worthDiscussingPoint->title
    //         ];
    //     }

    //     // Add last message preview
    //     if ($lastMessage) {
    //         $formatted['last_message'] = [
    //             'id' => $lastMessage->id,
    //             'message' => Str::limit($lastMessage->message_txt, 100),
    //             'is_sender' => $lastMessage->from_id == $currentUser->id,
    //             'created_at' => $lastMessage->created_at
    //         ];
    //     }

    //     return $formatted;
    // }
    
    
    /**
 * Helper: Format chat session response - WITH COMPLETE JOB DETAILS
 */
private function formatChatSessionResponse($session, $currentUser)
{
    $otherUser = $this->getOtherUserInSession($session, $currentUser->id);
    $lastMessage = $session->messages->first();
    
    // Get other user data from both tables
    $otherUserData = $otherUser ? $this->getEntityData($otherUser->id) : null;
    
    $formatted = [
        'session_id' => $session->id,
        'other_user' => $otherUserData ? [
            'id' => $otherUserData['id'],
            'name' => $otherUserData['name'],
            'email' => $otherUserData['email'],
            'image' => $otherUserData['image'],
            'usertype' => $otherUserData['usertype'],
            'entity_type' => $otherUserData['entity_type']
        ] : null,
        'unread_count' => $session->unread_count,
        'last_message_at' => $session->last_message_at,
        'last_message' => $session->last_message,
        'created_at' => $session->created_at,
        'is_active' => $session->is_active
    ];

    // Add detailed post info if available
    if ($session->post) {
        $post = $session->post;
        
        // Parse skills required
        $skillsRequired = [];
        if ($post->skills_required) {
            if (is_array($post->skills_required)) {
                $skillIds = $post->skills_required;
            } else {
                $skillIds = json_decode($post->skills_required, true) ?: [];
            }
            
            if (!empty($skillIds)) {
                $skills = \App\JobSkill::whereIn('id', $skillIds)
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

        // Parse tech stack
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

        $formatted['post'] = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'short_description' => $post->short_description,
            'images' => $images,
            'category_id' => $post->category_id,
            'subcategory_id' => $post->subcategory_id,
            'is_job_post' => $post->is_job_post ?? ($post->category_id == 5 || $post->category_id == 6),
            'created_at' => $post->created_at,
            'author_id' => $post->user_id,
        ];

        // Add job-specific fields based on category
        if ($post->category_id == 5 || $post->category_id == 6) {
            $formatted['post']['job_details'] = [
                'role_type' => $post->role_type,
                'work_mode' => $post->work_mode,
                'key_deliverables' => $post->key_deliverables,
                'experience_required' => $post->experience_required,
                'skills_required' => $skillsRequired,
                'tech_stack' => $techStack,
                'benefits' => $post->benefits,
                'salary_range' => $post->salary_range,
                'company_name' => $post->company_name,
                'job_location' => $post->job_location,
                'application_url' => $post->application_url,
                'application_deadline' => $post->application_deadline,
                'application_deadline_formatted' => $post->application_deadline ? 
                    \Carbon\Carbon::parse($post->application_deadline)->format('d M Y') : null,
                'is_expired' => $post->application_deadline ? 
                    \Carbon\Carbon::parse($post->application_deadline)->isPast() : false,
            ];

            // Add category-specific fields
            if ($post->category_id == 5) { // Mini Mission
                $formatted['post']['job_details']['deliverables'] = $post->deliverables;
                $formatted['post']['job_details']['timeline_start'] = $post->timeline_start;
                $formatted['post']['job_details']['timeline_end'] = $post->timeline_end;
                $formatted['post']['job_details']['type'] = 'mini_mission';
                $formatted['post']['job_details']['type_label'] = 'Mini Mission';
            } elseif ($post->category_id == 6) { // Internship
                $formatted['post']['job_details']['stipend_amount'] = $post->stipend_amount;
                $formatted['post']['job_details']['stipend_currency'] = $post->stipend_currency;
                $formatted['post']['job_details']['convertible_to_full_time'] = $post->convertible_to_full_time;
                $formatted['post']['job_details']['internship_duration'] = $post->internship_duration;
                $formatted['post']['job_details']['type'] = 'internship';
                $formatted['post']['job_details']['type_label'] = 'Internship';
            }
        }
    }

    // Add chat type info
    if ($session->chatType) {
        $formatted['chat_type'] = [
            'id' => $session->chatType->id,
            'name' => $session->chatType->name,
            'slug' => $session->chatType->slug
        ];
    }

    // Add worth discussing point info if available
    if ($session->worthDiscussingPoint) {
        $formatted['worth_discussing_point'] = [
            'id' => $session->worthDiscussingPoint->id,
            'title' => $session->worthDiscussingPoint->title
        ];
    }

    // Add last message preview
    if ($lastMessage) {
        $formatted['last_message'] = [
            'id' => $lastMessage->id,
            'message' => Str::limit($lastMessage->message_txt, 100),
            'is_sender' => $lastMessage->from_id == $currentUser->id,
            'created_at' => $lastMessage->created_at
        ];
    }

    return $formatted;
}

    /**
     * Helper: Get other user in chat session
     */
    private function getOtherUserInSession($session, $currentUserId)
    {
        if ($session->user1_id == $currentUserId) {
            return $session->user2;
        }
        return $session->user1;
    }
}