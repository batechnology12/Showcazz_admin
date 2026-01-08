<?php

namespace App\Models;

use App\Post;
use App\User;
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
        'chat_type_id',
        'worth_discussing_point_id',
        'last_message_at',
        'last_message',
        'unread_count',
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
     * Get user1
     */
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }
    
    /**
     * Get user2
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
     * Get other user in chat
     */
    public function getOtherUser($currentUserId)
    {
        if ($this->user1_id == $currentUserId) {
            return $this->user2;
        }
        return $this->user1;
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