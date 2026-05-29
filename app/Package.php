<?php
// app/Models/Package.php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'package_title',
        'package_subtitle',
        'package_price',
        'package_num_days',
        'package_num_listings',
        'package_for',
        'package_features',
        'is_popular',
        'sort_order',
        'badge_text',
        'currency'
    ];

    protected $casts = [
        'package_price' => 'float',
        'package_num_days' => 'integer',
        'package_num_listings' => 'integer',
        'package_features' => 'array',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get packages for employers (job posting)
     */
    public function scopeEmployerPackages($query)
    {
        return $query->where('package_for', 'employer')
                    ->orderBy('sort_order');
    }

    /**
     * Get packages for job seekers
     */
    public function scopeJobSeekerPackages($query)
    {
        return $query->where('package_for', 'job_seeker')
                    ->orderBy('sort_order');
    }

    /**
     * Get CV search packages
     */
    public function scopeCVSearchPackages($query)
    {
        return $query->where('package_for', 'cv_search')
                    ->orderBy('sort_order');
    }

    /**
     * Get featured packages
     */
    public function scopeFeaturedPackages($query)
    {
        return $query->where('package_for', 'make_featured')
                    ->orderBy('sort_order');
    }

    /**
     * Format price with currency
     */
    public function getFormattedPriceAttribute()
    {
        $symbols = [
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];
        
        $symbol = $symbols[$this->currency] ?? $this->currency;
        
        return $symbol . ' ' . number_format($this->package_price, 0);
    }

    /**
     * Get duration text
     */
    public function getDurationTextAttribute()
    {
        $days = $this->package_num_days;
        
        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . ' ' . ($years > 1 ? 'Years' : 'Year');
        } elseif ($days >= 30) {
            $months = floor($days / 30);
            return $months . ' ' . ($months > 1 ? 'Months' : 'Month');
        } else {
            return $days . ' ' . ($days > 1 ? 'Days' : 'Day');
        }
    }

    /**
     * Get payment requests for this package
     */
    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
    }
}