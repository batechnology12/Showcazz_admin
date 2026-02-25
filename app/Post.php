<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

use App\Models\PostType;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\PostRepost;

class Post extends Model
{
    protected $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'duration_start', 'duration_end', 'event_date', 'event_end_date', 'timeline_start', 'timeline_end'];
    
    
    
    protected $fillable = [
        'user_id',
        'post_type_id',
        'category_id',
        'subcategory_id',
        'title',
        'content',
        'short_description',
        'images',
        'files',
        'tech_stack',
        'project_domain',
        'role_in_project',
        'duration_start',
        'duration_end',
        'certification_title',
        'technology_topic',
        'occasion_title',
        'message',
        'event_date',
        'event_end_date',
        'result_rank',
        'idea_title',
        'guide_title',
        'deliverables',
        'timeline_start',
        'timeline_end',
        'is_published',
        'is_active',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'repost_count',
        'is_repost',
        'original_post_id',
        'post_user_type',
    
        // ✅ Missing fields added
        'role_type',
        'work_mode',
        'key_deliverables',
        'internship_duration',
        'stipend_amount',
        'stipend_currency',
        'convertible_to_full_time',
        'ctc_amount',
        'ctc_currency',
        'application_deadline',
        'is_job_post',
        'organizer_id',
        'host_id',
        'idea_or_goal',
        'outcome_or_fun_element',
        'company_name',
        'job_location',
        'salary_range',
        'experience_required',
        'skills_required',
        'benefits',
        'application_url',
    ];
    
    protected $casts = [
        'images' => 'array',
        'files' => 'array',
        'tech_stack' => 'array',
        'is_published' => 'boolean',
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
    ];
    
    /**
     * Get the user who created the post
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }
    
    /**
     * Get the post type
     */
    public function postType()
    {
        return $this->belongsTo(PostType::class, 'post_type_id');
    }
    
    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }


    public function getDisplayTitleAttribute()
    {
        // Priority-based title logic
        return $this->idea_title
            ?? $this->guide_title
            ?? $this->certification_title
            ?? $this->occasion_title
            ?? $this->title;
    }
    
    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }
    
    /**
     * Get tags (tagged users/companies)
     */
    public function tags()
    {
        return $this->hasMany(PostTag::class, 'post_id');
    }
    
    
    
    /**
     * Get tagged users
     */
    public function taggedUsers()
    {
        return $this->belongsToMany(User::class, 'post_tags', 'post_id', 'tagged_user_id')
                    ->whereNotNull('tagged_user_id');
    }
    
    /**
     * Get tagged companies
     */
    public function taggedCompanies()
    {
        return $this->belongsToMany(Company::class, 'post_tags', 'post_id', 'tagged_company_id')
                    ->whereNotNull('tagged_company_id');
    }
    
    /**
     * Get likes
     */
    public function likes()
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }
    
    /**
     * Get comments
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class, 'post_id')
                    ->whereNull('parent_comment_id')
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc');
    }
    
    /**
     * Get all comments including replies
     */
    public function allComments()
    {
        return $this->hasMany(PostComment::class, 'post_id')
                    ->where('is_active', true);
    }
    
    /**
     * Get shares
     */
    public function shares()
    {
        return $this->hasMany(PostShare::class, 'post_id');
    }
    
    /**
     * Get views
     */
    public function views()
    {
        return $this->hasMany(PostView::class, 'post_id');
    }
    
    /**
     * Check if user liked the post
     */
    public function isLikedByUser($userId = null)
    {
        if (!$userId && auth()->check()) {
            $userId = auth()->user()->id;
        }
        
        if (!$userId) {
            return false;
        }
        
        return $this->likes()->where('user_id', $userId)->exists();
    }
    
    /**
     * Get images URLs
     */
    public function getImagesUrlsAttribute()
    {
        if (empty($this->images)) {
            return [];
        }
        
        return array_map(function($image) {
            return asset('post_images/' . $image);
        }, $this->images);
    }
    
    /**
     * Get files URLs
     */
    public function getFilesUrlsAttribute()
    {
        if (empty($this->files)) {
            return [];
        }
        
        return array_map(function($file) {
            return asset('post_files/' . $file);
        }, $this->files);
    }
    
    
    
    // Add these relationships to your Post model

    /**
     * Get the reposts of this post
     */
    public function reposts()
    {
        return $this->hasMany(PostRepost::class, 'original_post_id');
    }
    
    /**
     * Get the reposted posts (if this post is a repost)
     */
    public function repostedFrom()
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }
    
    /**
     * Check if this post is a repost
     */
    public function getIsRepostAttribute()
    {
        return !is_null($this->original_post_id);
    }
    
    /**
     * Get the original post (if this is a repost)
     */
    public function originalPost()
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }
    
    /**
     * Get the repost record if this post is a repost
     */
    public function repostRecord()
    {
        return $this->hasOne(PostRepost::class, 'reposted_post_id');
    }
    
    /**
     * Get repost count
     */
    public function getRepostCountAttribute()
    {
        return $this->reposts()->count();
    }
    
    /**
     * Get tech stack as array
     */
    public function getTechStackArrayAttribute()
    {
        if (empty($this->tech_stack)) {
            return [];
        }
        
        return $this->tech_stack;
    }
}