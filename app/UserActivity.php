<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $table = 'user_activities';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    
    // Activity types
    const TYPE_FOLLOW = 'follow';
    const TYPE_UNFOLLOW = 'unfollow';
    const TYPE_BLOCK = 'block';
    const TYPE_UNBLOCK = 'unblock';
    const TYPE_ACCEPT = 'accept';
    const TYPE_REJECT = 'reject';
    
    protected $fillable = [
        'user_id',
        'activity_type',
        'target_user_id',
        'metadata',
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];
    
    /**
     * Get the user who performed the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get the target user
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetCompany()
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }
}