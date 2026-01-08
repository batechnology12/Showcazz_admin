<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    protected $table = 'post_comments';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'content',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who commented
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get parent comment
     */
    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_comment_id');
    }

    /**
     * Get replies to this comment
     */
    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_comment_id')
                    ->where('is_active', true)
                    ->with('user')
                    ->with('replies');
    }

    /**
     * Scope to get only active comments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


     /**
     * Get likes for this comment
     */
    public function likes()
    {
        return $this->hasMany(CommentLike::class, 'comment_id');
    }

    /**
     * Check if comment is liked by user
     */
    public function isLikedByUser($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}