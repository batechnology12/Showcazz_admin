<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostTag extends Model
{
    protected $table = 'post_tags';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at'];
    
    protected $fillable = [
        'post_id',
        'tagged_user_id',
        'tagged_company_id',
        'created_at',
    ];

    /**
     * Get the post
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get tagged user
     */
    public function taggedUser()
    {
        return $this->belongsTo(User::class, 'tagged_user_id');
    }

    /**
     * Get tagged company
     */
    public function taggedCompany()
    {
        return $this->belongsTo(Company::class, 'tagged_company_id');
    }
}