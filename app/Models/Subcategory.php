<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 
        'icon', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function postType()
    {
        return $this->hasOneThrough(
            PostType::class,
            Category::class,
            'id', // Foreign key on categories table
            'id', // Foreign key on post_types table
            'category_id', // Local key on subcategories table
            'post_type_id' // Local key on categories table
        );
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'subcategory_id');
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get subcategories by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

}