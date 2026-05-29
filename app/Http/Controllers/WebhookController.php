<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;
use App\Models\RazorpayWebhookLog;

class WebhookController extends Controller
{

    public function razorpayWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret = config('services.razorpay.webhook_secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if ($expectedSignature !== $signature) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature'
            ], 400);
        }
        $data = json_decode($payload, true);
        RazorpayWebhookLog::create([
            'event_type' => $data['event'],
            'razorpay_payment_id' => $data['payload']['payment']['entity']['id'] ?? null,
            'razorpay_order_id' => $data['payload']['payment']['entity']['order_id'] ?? null,
            'payload' => $data,
            'signature' => $signature,
            'processed' => 0
        ]);
        if ($data['event'] == 'payment.captured') {
            $payment = $data['payload']['payment']['entity'];
            $paymentRequest = PaymentRequest::where(
                'razorpay_order_id',
                $payment['order_id']
            )->first();
            if ($paymentRequest) {
                PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => $payment['id'],
                    'razorpay_order_id' => $payment['order_id'],
                    'payment_method' => $payment['method'],
                    'amount' => $payment['amount'] / 100,
                    'currency' => $payment['currency'],
                    'status' => 'success',
                    'payment_response' => $payment
                ]);
                $paymentRequest->update([
                    'status' => 'paid'
                ]);
            }
        }
        if ($data['event'] == 'payment.failed') {
            $payment = $data['payload']['payment']['entity'];
            $paymentRequest = PaymentRequest::where(
                'razorpay_order_id',
                $payment['order_id']
            )->first();
            if ($paymentRequest) {
                PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => $payment['id'],
                    'razorpay_order_id' => $payment['order_id'],
                    'payment_method' => $payment['method'] ?? null,
                    'amount' => $payment['amount'] / 100,
                    'currency' => $payment['currency'],
                    'status' => 'failed',
                    'payment_response' => $payment
                ]);
                $paymentRequest->update([
                    'status' => 'failed'
                ]);
            }

        }
        return response()->json([
            'status' => true,
            'message' => 'Webhook processed successfully'
        ]);
    }
}