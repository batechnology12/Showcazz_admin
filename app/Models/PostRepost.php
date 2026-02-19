<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\User;
use App\Company;
use App\Post;
use App\Job;
use App\UserConnection;
use App\FavouriteCompany;
use App\UserMessage;
use App\ChatSession;
use App\PostLike;
use App\PostComment;
use App\PostShare;
use App\Models\PostRepost;
use App\PostView;
class PostRepost extends Model
{
    use HasFactory;

    protected $table = 'post_reposts';

    protected $fillable = [
        'original_post_id',
        'reposted_post_id',
        'user_id',
        'repost_comment'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the original post that was reposted
     */
    public function originalPost()
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }

    /**
     * Get the reposted post (new post created)
     */
    public function repostedPost()
    {
        return $this->belongsTo(Post::class, 'reposted_post_id');
    }

    /**
     * Get the user who reposted
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to get reposts by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get reposts of a post
     */
    public function scopeOfPost($query, $postId)
    {
        return $query->where('original_post_id', $postId);
    }

    /**
     * Check if user has already reposted
     */
    public function scopeUserHasReposted($query, $userId, $originalPostId)
    {
        return $query->where('user_id', $userId)
            ->where('original_post_id', $originalPostId)
            ->exists();
    }
}