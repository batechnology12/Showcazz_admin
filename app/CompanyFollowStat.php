<?php
// app/CompanyFollowStat.php

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
    
    /**
     * Increment followers count
     */
    public function incrementFollowers()
    {
        $this->followers_count++;
        $this->last_updated = now();
        $this->save();
    }
    
    /**
     * Decrement followers count
     */
    public function decrementFollowers()
    {
        if ($this->followers_count > 0) {
            $this->followers_count--;
        }
        $this->last_updated = now();
        $this->save();
    }
    
    /**
     * Get or create follow stat for company
     */
    public static function getOrCreateForCompany($companyId)
    {
        $stat = self::firstOrCreate(
            ['company_id' => $companyId],
            ['followers_count' => 0, 'last_updated' => now()]
        );
        return $stat;
    }
}