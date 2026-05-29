<?php
// app/Models/OtpVerification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'email',
        'otp',
        'purpose',
        'expires_at',
        'attempts',
        'is_verified',
        'metadata'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'attempts' => 'integer',
        'metadata' => 'array'
    ];
}