<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyFollowStat extends Model
{
    protected $table = 'company_follow_stats';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    protected $fillable = [
        'company_id',
        'followers_count',
        'last_updated'
    ];
    
    protected $casts = [
        'last_updated' => 'datetime',
    ];
    
    /**
     * Get the company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}