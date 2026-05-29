<?php
// app/Models/ChatSession.php

namespace App\Models;

use App\Post;
use App\User;
use App\Company;
use App\UserMessage;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $table = 'chat_sessions';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'user1_id',
        'user2_id',
        'post_id',
        'deleted_by',
        'chat_type_id',
        'worth_discussing_point_id',
        'last_message_at',
        'last_message',
        'unread_count',
        'user_deleted_at',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get user1 (maps to users table)
     */
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }
    
    /**
     * Get user2 (maps to users table)
     */
    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }
    
    /**
     * Get post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
    
    /**
     * Get chat type
     */
    public function chatType()
    {
        return $this->belongsTo(ChatType::class, 'chat_type_id');
    }
    
    /**
     * Get worth discussing point
     */
    public function worthDiscussingPoint()
    {
        return $this->belongsTo(WorthDiscussingPoint::class, 'worth_discussing_point_id');
    }
    
    /**
     * Get all messages in this session
     */
    public function messages()
    {
        return $this->hasMany(UserMessage::class, 'chat_session_id', 'id')
                    ->orderBy('created_at', 'asc');
    }
    
    /**
     * Get the user1 entity (checks both User and Company tables)
     */
    public function getUser1Entity()
    {
        return $this->getEntity($this->user1_id);
    }
    
    /**
     * Get the user2 entity (checks both User and Company tables)
     */
    public function getUser2Entity()
    {
        return $this->getEntity($this->user2_id);
    }
    
    /**
     * Helper to get entity from either User or Company table
     */
    private function getEntity($id)
    {
        if (!$id) {
            return null;
        }
        
        // Check Company first
        $company = Company::find($id);
        if ($company) {
            return $company;
        }
        
        // Then check User
        return User::find($id);
    }
    
    /**
     * Get other user in chat (returns the actual model - either User or Company)
     */
    public function getOtherUser($currentUserId)
    {
        $otherUserId = ($this->user1_id == $currentUserId) ? $this->user2_id : $this->user1_id;
        
        if (!$otherUserId) {
            return null;
        }
        
        // First check if it's a company
        $company = Company::find($otherUserId);
        if ($company) {
            return $company;
        }
        
        // Then check if it's a user
        return User::find($otherUserId);
    }
    
    /**
     * Get other user ID
     */
    public function getOtherUserId($currentUserId)
    {
        return ($this->user1_id == $currentUserId) ? $this->user2_id : $this->user1_id;
    }
    
    /**
     * Check if a user is part of this chat
     */
    public function hasUser($userId)
    {
        return $this->user1_id == $userId || $this->user2_id == $userId;
    }
    
    /**
     * Get participant info (formatted for API responses)
     */
    public function getParticipantInfo($userId)
    {
        $otherUser = $this->getOtherUser($userId);
        
        if (!$otherUser) {
            return null;
        }
        
        // Determine if it's a company or user
        $isCompany = $otherUser instanceof Company;
        
        // Get name
        $name = '';
        if ($isCompany) {
            $name = $otherUser->name;
        } else {
            $firstName = $otherUser->first_name ?? '';
            $lastName = $otherUser->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $name = $name ?: ($otherUser->name ?? 'Unknown User');
        }
        
        return [
            'id' => $otherUser->id,
            'name' => $name,
            'email' => $otherUser->email,
            'type' => $isCompany ? 'company' : ($otherUser->usertype ?? 'user'),
            'entity_type' => $isCompany ? 'company' : 'user',
            'image' => $isCompany 
                ? ($otherUser->logo ? asset('company_logos/' . $otherUser->logo) : null)
                : ($otherUser->image ? asset('user_images/' . $otherUser->image) : null),
        ];
    }
    
    /**
     * Generate session ID
     */
    public static function generateId($user1Id, $user2Id, $postId = null, $chatTypeId = null)
    {
        $base = "{$user1Id}_{$user2Id}";
        if ($postId) {
            $base .= "_{$postId}";
        }
        if ($chatTypeId) {
            $base .= "_{$chatTypeId}";
        }
        return md5($base);
    }
}