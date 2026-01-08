<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{
    protected $table = 'post_likes';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    
    protected $fillable = [
        'post_id',
        'user_id',
        'created_at',
    ];

    /**
     * Get the post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who liked
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}