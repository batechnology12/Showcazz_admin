<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
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
     * Initialize chat from post (called from post page)
     */
    public function initializeChatFromPost(Request $request, $postId)
    {
        try {
            $user = Auth::user();
            
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

            // Get the post
            $post = Post::with('user')->find($postId);
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                    'errors' => (object)['post' => 'Post not found or deleted']
                ], 404);
            }

            // Check if post is active and published
            if (!$post->is_active || !$post->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post is not available',
                    'errors' => (object)['post' => 'Post is not published or inactive']
                ], 400);
            }

            // Prevent self-messaging (can't chat with yourself about your own post)
            if ($user->id == $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot initiate chat about your own post',
                    'errors' => (object)['user' => 'Cannot chat with yourself about your own post']
                ], 400);
            }

            // For job application, verify it's a job post
            if ($request->chat_type == 'job_application' && !$post->is_job_post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid job post',
                    'errors' => (object)['post' => 'This post is not a job post']
                ], 400);
            }

            // For worth discussing, verify it's NOT a job post and has appropriate category
            if ($request->chat_type == 'worth_discussing') {
                if ($post->is_job_post) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Worth discussing is not available for job posts',
                        'errors' => (object)['post' => 'Use job application for job posts']
                    ], 400);
                }
                
                // Verify worth discussing point exists
                $point = WorthDiscussingPoint::find($request->worth_discussing_point_id);
                if (!$point) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid discussion point',
                        'errors' => (object)['worth_discussing_point_id' => 'Discussion point not found']
                    ], 404);
                }
            }

            // Get chat type
            $chatType = ChatType::where('slug', $request->chat_type)->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chat type',
                    'errors' => (object)['chat_type' => 'Chat type not found']
                ], 404);
            }

            // Get post author (receiver)
            $receiver = $post->user;

            // Generate chat session ID
            $sessionId = $this->generateChatSessionId(
                $user->id,
                $receiver->id,
                $post->id,
                $chatType->id
            );

            DB::beginTransaction();

            try {
                // Create or update chat session
                $chatSession = ChatSession::updateOrCreate(
                    [
                        'id' => $sessionId
                    ],
                    [
                        'user1_id' => min($user->id, $receiver->id),
                        'user2_id' => max($user->id, $receiver->id),
                        'post_id' => $post->id,
                        'chat_type_id' => $chatType->id,
                        'worth_discussing_point_id' => $request->worth_discussing_point_id ?? null,
                        'last_message_at' => now(),
                        'last_message' => Str::limit($request->initial_message, 100),
                        'unread_count' => DB::raw('unread_count + 1'),
                        'is_active' => true
                    ]
                );

                // Generate subject based on chat type and post
                $subject = $this->generateChatSubject($request->chat_type, $post, $request->worth_discussing_point_id);

                // Create the initial message
                $message = UserMessage::create([
                    'listing_id' => $post->id,
                    'listing_title' => $post->title,
                    'from_id' => $user->id,
                    'to_id' => $receiver->id,
                    'to_email' => $receiver->email,
                    'to_name' => $receiver->name,
                    'from_name' => $user->name,
                    'from_email' => $user->email,
                    'from_phone' => $user->phone,
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

                // Load relationships
                $message->load(['sender', 'receiver', 'post', 'chatType', 'worthDiscussingPoint']);

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
                throw $e;
            }

        } catch (Exception $e) {
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
            
            $validator = Validator::make($request->all(), [
                'to_user_id' => 'required|exists:users,id',
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

            // Get chat type (general)
            $chatType = ChatType::where('slug', 'general')->first();
            if (!$chatType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat type not found',
                    'errors' => (object)['chat_type' => 'General chat type not configured']
                ], 404);
            }


           

            // Get receiver
            $receiver = User::find($request->to_user_id);
 
            // Generate chat session ID
            $sessionId = $this->generateChatSessionId(
                $user->id,
                $receiver->id,
                null,
                $chatType->id
            );

            DB::beginTransaction();

            try {
                // Create or update chat session
                $chatSession = ChatSession::updateOrCreate(
                    [
                        'id' => $sessionId
                    ],
                    [
                        'user1_id' => min($user->id, $receiver->id),
                        'user2_id' => max($user->id, $receiver->id),
                        'post_id' => null,
                        'chat_type_id' => $chatType->id,
                        'worth_discussing_point_id' => null,
                        'last_message_at' => now(),
                        'last_message' => Str::limit($request->initial_message, 100),
                        //'unread_count' => DB::raw('unread_count + 1'),
                        'is_active' => true
                    ]
                );

                // Create the initial message
                $message = UserMessage::create([
                    'listing_id' => null,
                    'listing_title' => null,
                    'from_id' => $user->id,
                    'to_id' => $receiver->id,
                    'to_email' => $receiver->email,
                    'to_name' => $receiver->name,
                    'from_name' => $user->name,
                    'from_email' => $user->email,
                    'from_phone' => $user->phone,
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

            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
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

            // Get the other user
            $otherUserId = ($chatSession->user1_id == $user->id) 
                ? $chatSession->user2_id 
                : $chatSession->user1_id;
                
            $receiver = User::find($otherUserId);

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
                    'to_email' => $receiver->email,
                    'to_name' => $receiver->name,
                    'from_name' => $user->name,
                    'from_email' => $user->email,
                    'from_phone' => $user->phone,
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
            $chatSession->update(['unread_count' => 0]);

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

            dd($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get user's conversations
     */
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'chat_type' => 'nullable|in:general,job_application,worth_discussing'
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

            // Filter by chat type if specified
            if ($request->chat_type) {
                $chatType = ChatType::where('slug', $request->chat_type)->first();
                if ($chatType) {
                    $query->where('chat_type_id', $chatType->id);
                }
            }

            // Get conversations with pagination
            $conversations = $query->orderBy('last_message_at', 'desc')
                                 ->paginate($perPage, ['*'], 'page', $page);

            // Format conversations
            $formattedConversations = $conversations->map(function ($session) use ($user) {
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
                        'current_page' => $conversations->currentPage(),
                        'per_page' => $conversations->perPage(),
                        'total' => $conversations->total(),
                        'last_page' => $conversations->lastPage()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversations',
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
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
                'email' => $message->sender->email,
                'image' => $message->sender->image ? asset('user_images/' . $message->sender->image) : null,
                'usertype' => $message->sender->usertype
            ] : null,
            'receiver' => $message->receiver ? [
                'id' => $message->receiver->id,
                'name' => $message->receiver->name,
                'email' => $message->receiver->email,
                'image' => $message->receiver->image ? asset('user_images/' . $message->receiver->image) : null,
                'usertype' => $message->receiver->usertype
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
    private function formatChatSessionResponse($session, $currentUser)
    {
        $otherUser = $this->getOtherUserInSession($session, $currentUser->id);
        $lastMessage = $session->messages->first();
        
        $formatted = [
            'session_id' => $session->id,
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'image' => $otherUser->image ? asset('user_images/' . $otherUser->image) : null,
                'usertype' => $otherUser->usertype
            ] : null,
            'unread_count' => $session->unread_count,
            'last_message_at' => $session->last_message_at,
            'created_at' => $session->created_at,
            'is_active' => $session->is_active
        ];

        // Add post info if available
        if ($session->post) {
            $formatted['post'] = [
                'id' => $session->post->id,
                'title' => $session->post->title,
                'is_job_post' => $session->post->is_job_post
            ];
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