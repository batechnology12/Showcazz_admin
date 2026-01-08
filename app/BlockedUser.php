<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockedUser extends Model
{
    protected $table = 'blocked_users';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    
    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    /**
     * Get the blocker user
     */
    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }
    
    /**
     * Get the blocked user
     */
    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}