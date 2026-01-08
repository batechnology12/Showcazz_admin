<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatType extends Model
{
    protected $table = 'chat_types';
    public $timestamps = true;
    
    protected $fillable = ['name', 'slug', 'description'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get all messages of this type
     */
    public function messages()
    {
        return $this->hasMany(UserMessage::class, 'chat_type_id');
    }
}