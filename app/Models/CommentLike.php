<?php

namespace App\Models;
use App\User;
use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    protected $table = 'comment_likes';
    public $timestamps = true;
    
    protected $fillable = [
        'comment_id',
        'user_id',
    ];

    /**
     * Get the comment
     */
    public function comment()
    {
        return $this->belongsTo(PostComment::class, 'comment_id');
    }

    /**
     * Get the user who liked
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}