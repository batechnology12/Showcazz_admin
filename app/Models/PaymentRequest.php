<?php
// app/Models/PaymentRequest.php

namespace App\Models;
use App\User;
use App\Package;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'user_id',
        'package_id',
        'order_number',
        'razorpay_payment_link_id',
        'razorpay_order_id',
        'payment_link_url',
        'name',
        'email',
        'phone',
        'amount',
        'currency',
        'status',
        'request_payload',
        'notes',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'amount' => 'float',
        'request_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who made the request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package being purchased
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the transaction for this request
     */
    public function transaction()
    {
        return $this->hasOne(PaymentTransaction::class, 'payment_request_id');
    }

    /**
     * Check if payment is completed
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }
}