<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    protected $table = 'post_views';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
    ];

    /**
     * Get the post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who viewed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}