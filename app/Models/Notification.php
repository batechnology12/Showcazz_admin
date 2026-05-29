<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'from_user_id',
        'type',
        'title',
        'body',
        'data',
        'screen',
        'action_payload',
        'is_read',
        'read_at',
        'is_clicked',
        'clicked_at',
        'status'
    ];

    protected $casts = [
        'data' => 'array',
        'action_payload' => 'array',
        'is_read' => 'boolean',
        'is_clicked' => 'boolean',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper methods
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
        return $this;
    }

    public function markAsClicked()
    {
        if (!$this->is_clicked) {
            $this->is_clicked = true;
            $this->clicked_at = now();
            $this->save();
        }
        return $this;
    }

    public function archive()
    {
        $this->status = 'archived';
        $this->save();
        return $this;
    }

    // Format for API response
    public function formatForApi()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'screen' => $this->screen,
            'data' => $this->data,
            'action_payload' => $this->action_payload,
            'is_read' => $this->is_read,
            'is_clicked' => $this->is_clicked,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'created_at_formatted' => $this->created_at ? $this->created_at->format('d M Y, h:i A') : null,
            'created_at_diff' => $this->created_at ? $this->formatTimeDiff($this->created_at) : null,
            'from_user' => $this->fromUser ? [
                'id' => $this->fromUser->id,
                'name' => $this->fromUser->getName(),
                'usertype' => $this->fromUser->usertype,
                'image' => $this->fromUser->usertype === 'company' 
                    ? ($this->fromUser->company_logo ? asset('company_logos/' . $this->fromUser->company_logo) : null)
                    : ($this->fromUser->image ? asset('user_images/' . $this->fromUser->image) : null)
            ] : null
        ];
    }

    private function formatTimeDiff($dateTime)
    {
        if (!$dateTime) {
            return null;
        }

        $now = now();
        $diffInSeconds = $now->diffInSeconds($dateTime);
        $diffInMinutes = $now->diffInMinutes($dateTime);
        $diffInHours = $now->diffInHours($dateTime);
        $diffInDays = $now->diffInDays($dateTime);

        if ($diffInSeconds < 60) {
            return $diffInSeconds <= 5 ? 'Just now' : $diffInSeconds . ' seconds ago';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' ' . ($diffInMinutes == 1 ? 'minute ago' : 'minutes ago');
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ' . ($diffInHours == 1 ? 'hour ago' : 'hours ago');
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' ' . ($diffInDays == 1 ? 'day ago' : 'days ago');
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' ' . ($weeks == 1 ? 'week ago' : 'weeks ago');
        } elseif ($diffInDays < 365) {
            $months = floor($diffInDays / 30);
            return $months . ' ' . ($months == 1 ? 'month ago' : 'months ago');
        } else {
            $years = floor($diffInDays / 365);
            return $years . ' ' . ($years == 1 ? 'year ago' : 'years ago');
        }
    }
}