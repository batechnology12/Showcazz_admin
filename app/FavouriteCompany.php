<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FavouriteCompany extends Model
{
    protected $table = 'favourites_company';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';
    
    
    protected $fillable = [
        'user_id',
        'company_slug',
        'company_id',
        'user_type',
        'status',
        'created_at',
        'updated_at'
    ];
    
    // Get user who follows
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Get company being followed
      public function follower()
    {
        if ($this->user_type === 'company') {
            return $this->belongsTo(Company::class, 'user_id');
        }
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get the company that is favourited
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    
    /**
     * Scope to get favourites for a user with type
     */
    public function scopeForUser($query, $userId, $userType = 'user')
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }
    
    /**
     * Scope to get pending requests for a company
     */
    public function scopePendingForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId)
                     ->where('status', self::STATUS_PENDING);
    }
    
    /**
     * Check if user follows company
     */
    public static function isFollowing($userId, $companyId, $userType = 'user')
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('company_id', $companyId)
            ->where('status', self::STATUS_ACCEPTED)
            ->exists();
    }
    
    /**
     * Check if there's a pending request
     */
    public static function hasPendingRequest($userId, $companyId, $userType = 'user')
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('company_id', $companyId)
            ->where('status', self::STATUS_PENDING)
            ->exists();
    }
    
    /**
     * Get company follower count (accepted only)
     */
    public static function getFollowerCount($companyId)
    {
        return self::where('company_id', $companyId)
            ->where('status', self::STATUS_ACCEPTED)
            ->count();
    }
}