<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostShare extends Model
{
    protected $table = 'post_shares';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'post_id',
        'user_id',
        'shared_to_user_id',
        'ip_address',
    ];

    /**
     * Get the post that was shared
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get the user who shared
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user to whom post was shared
     */
    public function sharedToUser()
    {
        return $this->belongsTo(User::class, 'shared_to_user_id');
    }
}