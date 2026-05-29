<?php
// app/Models/RazorpayWebhookLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazorpayWebhookLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'razorpay_webhook_logs';

    protected $fillable = [
        'event_type',
        'razorpay_payment_id',
        'razorpay_order_id',
        'payload',
        'signature',
        'processed',
        'created_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'created_at' => 'datetime'
    ];
}