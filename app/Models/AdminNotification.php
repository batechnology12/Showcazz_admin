<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Admin;

class AdminNotification extends Model
{
    use HasFactory;

    protected $table = 'admin_notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'target_users',
        'target_type',
        'total_recipients',
        'success_count',
        'failed_count',
        'response_data',
        'sent_by',
    ];

    protected $casts = [
        'target_users' => 'array',
        'response_data' => 'array',
    ];

    public function sentBy()
    {
        return $this->belongsTo(Admin::class, 'sent_by');
    }
}