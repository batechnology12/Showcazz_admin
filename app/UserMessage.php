<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;
use App\Post;
use App\Models\ChatType;
use App\Models\WorthDiscussingPoint;
use App\Models\ChatSession;
class UserMessage extends Model
{
    use SoftDeletes;
    
    protected $table = 'user_messages';
    public $timestamps = true;
    
    protected $fillable = [
        'listing_id',
        'listing_title',
        'from_id',
        'to_id',
        'to_email',
        'to_name',
        'from_name',
        'from_email',
        'from_phone',
        'message_txt',
        'subject',
        'chat_type_id',
        'chat_session_id',
        'worth_discussing_point_id',
        'status',
        'is_read',
        'read_at',
        'message_type',
        'attachments'
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'attachments' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    protected $dates = ['created_at', 'updated_at', 'read_at', 'deleted_at'];
    
    /**
     * Get the sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
    
    /**
     * Get the receiver
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_id');
    }
    
    /**
     * Get the post if message is related to a post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'listing_id');
    }
    
    /**
     * Get the chat type
     */
    public function chatType()
    {
        return $this->belongsTo(ChatType::class, 'chat_type_id');
    }
    
    /**
     * Get the worth discussing point
     */
    public function worthDiscussingPoint()
    {
        return $this->belongsTo(WorthDiscussingPoint::class, 'worth_discussing_point_id');
    }
    
    /**
     * Get chat session
     */
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id', 'id');
    }
    
    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    
    /**
     * Scope for messages from a specific user
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('from_id', $userId);
    }
    
    /**
     * Scope for messages to a specific user
     */
    public function scopeToUser($query, $userId)
    {
        return $query->where('to_id', $userId);
    }
    
    /**
     * Scope for chat type
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('chat_type_id', $typeId);
    }
    
    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }
}