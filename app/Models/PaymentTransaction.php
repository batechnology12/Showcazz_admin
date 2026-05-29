<?php
// app/Models/PaymentTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'payment_request_id',
        'razorpay_payment_id',
        'razorpay_order_id',
        'razorpay_signature',
        'payment_method',
        'amount',
        'currency',
        'status',
        'payment_response',
        'created_at'
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_response' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Get the payment request for this transaction
     */
    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}