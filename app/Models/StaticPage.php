<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaticPage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'static_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_type',
        'title',
        'content',
        'meta_description',
        'meta_keywords',
        'version',
        'is_active',
        'published_at',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_at'
    ];

    /**
     * Scope a query to only include active pages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include specific page type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    /**
     * Scope a query to get latest version of a page type.
     */
    public function scopeLatestVersion($query, $type)
    {
        return $query->where('page_type', $type)
            ->orderBy('version', 'desc')
            ->limit(1);
    }

    /**
     * Get the user who created this page.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this page.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get page type label.
     */
    public function getPageTypeLabelAttribute()
    {
        $labels = [
            'terms_conditions' => 'Terms & Conditions',
            'privacy_policy' => 'Privacy Policy',
            'cookie_policy' => 'Cookie Policy',
            'about_us' => 'About Us',
            'faq' => 'FAQ'
        ];

        return $labels[$this->page_type] ?? ucfirst(str_replace('_', ' ', $this->page_type));
    }

    /**
     * Get excerpt of content.
     */
    public function getExcerptAttribute($length = 200)
    {
        return strip_tags(substr($this->content, 0, $length)) . '...';
    }
}