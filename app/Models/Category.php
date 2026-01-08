<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_type_id', 'name', 'slug', 'description', 
        'icon', 'color', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // public function postType()
    // {
    //     return $this->belongsTo(PostType::class);
    // }

    // public function subcategories()
    // {
    //     return $this->hasMany(Subcategory::class);
    // }


    public function postType()
    {
        return $this->belongsTo(PostType::class, 'post_type_id');
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id')
                    ->where('is_active', true)
                    ->orderBy('name');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function activeSubcategories()
    {
        return $this->hasMany(Subcategory::class)->where('is_active', true);
    }

     public function scopeByPostType($query, $postTypeId)
    {
        return $query->where('post_type_id', $postTypeId);
    }
}