<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    protected $table = 'user_connections';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_BLOCKED = 'blocked';
    
    protected $fillable = [
        'follower_id',
        'following_id',
        'status',
        'created_at',
        'updated_at'
    ];
    
    // Get follower user
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
    
    // Get following user
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}