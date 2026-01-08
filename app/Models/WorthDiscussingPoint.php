<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorthDiscussingPoint extends Model
{
    protected $table = 'worth_discussing_points';
    public $timestamps = true;
    
    protected $fillable = [
        'title',
        'description',
        'icon',
        'slug',
        'is_active',
        'sort_order'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get messages with this point
     */
    public function messages()
    {
        return $this->hasMany(UserMessage::class, 'worth_discussing_point_id');
    }
    
    /**
     * Scope to get only active points
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}