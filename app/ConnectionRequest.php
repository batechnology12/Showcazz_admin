<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConnectionRequest extends Model
{
    protected $table = 'connection_requests';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    
    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
        'message',
        'created_at',
        'updated_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the requester user
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
    
    /**
     * Get the receiver user
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}