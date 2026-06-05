<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Package;
use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;
use App\Models\RazorpayWebhookLog;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    /**
     * Create payment link for package purchase
     */
    public function createPaymentLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$validator->errors()->toArray()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $package = Package::findOrFail($request->package_id);
            
            // Generate unique order number
            $orderNumber = 'ORD-' . strtoupper(uniqid() . Str::random(6));

            // Create payment request record
            $paymentRequest = PaymentRequest::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'order_number' => $orderNumber,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'amount' => $package->package_price,
                'currency' => 'INR',
                'status' => 'pending',
                'request_payload' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $keyId = env('RAZORPAY_KEY');
            $keySecret = env('RAZORPAY_SECRET');
            $razorpay = new Api($keyId, $keySecret);

            // Create Razorpay payment link
            $paymentLink = $razorpay->paymentLink->create([
                'amount' => (int)($package->package_price * 100), // amount in paise
                'currency' => 'INR',
                'accept_partial' => false,
                'reference_id' => $paymentRequest->order_number,
                'description' => 'Purchase of ' . $package->package_title,
                'customer' => [
                    'name' => $request->name,
                    'email' => $request->email,
                    'contact' => $request->phone
                ],
                'notify' => [
                    'sms' => true,
                    'email' => true
                ],
                'callback_url' => route('payment.callback'),
                'callback_method' => 'get',
                'reminder_enable' => true
            ]);

            // Update payment request
            $paymentRequest->update([
                'razorpay_payment_link_id' => $paymentLink['id'],
                'payment_link_url' => $paymentLink['short_url']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment link created successfully',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'order_number' => $paymentRequest->order_number,
                    'amount' => $package->package_price,
                    'payment_link' => $paymentLink['short_url'],
                    'expires_at' => $paymentLink['expire_by'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment link creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment link creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Payment callback handler
     */
    public function paymentCallback(Request $request)
    {
        try {
            $paymentLinkId = $request->razorpay_payment_link_id;
            $paymentLinkStatus = $request->razorpay_payment_link_status;
            $razorpayPaymentId = $request->razorpay_payment_id;
            $razorpaySignature = $request->razorpay_signature;

            $paymentRequest = PaymentRequest::where('razorpay_payment_link_id', $paymentLinkId)->first();

            if (!$paymentRequest) {
                Log::error('Payment request not found', ['payment_link_id' => $paymentLinkId]);
                return redirect('/payment-failed?reason=invalid_request');
            }

            if ($paymentLinkStatus === 'paid') {
                DB::beginTransaction();

                try {
                    $paymentRequest->update([
                        'status' => 'paid',
                        'razorpay_order_id' => $request->razorpay_order_id
                    ]);

                    PaymentTransaction::create([
                        'payment_request_id' => $paymentRequest->id,
                        'razorpay_payment_id' => $razorpayPaymentId,
                        'razorpay_order_id' => $request->razorpay_order_id,
                        'razorpay_signature' => $razorpaySignature,
                        'payment_method' => $request->razorpay_payment_method ?? 'unknown',
                        'amount' => $paymentRequest->amount,
                        'currency' => $paymentRequest->currency,
                        'status' => 'success',
                        'payment_response' => $request->all(),
                        'created_at' => now()
                    ]);

                    $this->activatePackageForUser($paymentRequest);
                    
                    DB::commit();

                    return redirect('/api/payment-success?order=' . $paymentRequest->order_number);
                    
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            $paymentRequest->update(['status' => 'failed']);
            
            PaymentTransaction::create([
                'payment_request_id' => $paymentRequest->id,
                'razorpay_payment_id' => $razorpayPaymentId ?? 'N/A',
                'razorpay_order_id' => $request->razorpay_order_id ?? 'N/A',
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'status' => 'failed',
                'payment_response' => $request->all(),
                'created_at' => now()
            ]);

            return redirect('/api/payment-failed?order=' . $paymentRequest->order_number);

        } catch (Exception $e) {
            Log::error('Payment callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect('/api/payment-failed?reason=verification_failed');
        }
    }

    /**
     * Webhook handler for Razorpay events
     */
    public function handleWebhook(Request $request)
    { 
        Log::info('handleWebhook innn');
        
        $payload = $request->all();
        Log::info('handleWebhook request received', $payload);
        
        $signature = $request->header('x-razorpay-signature');

        // Log webhook
        $webhookLog = RazorpayWebhookLog::create([
            'event_type' => $payload['event'] ?? 'unknown',
            'razorpay_payment_id' => $payload['payload']['payment']['entity']['id'] ?? null,
            'razorpay_order_id' => $payload['payload']['order']['entity']['id'] ?? null,
            'payload' => $payload,
            'signature' => $signature,
            'processed' => false,
            'created_at' => now()
        ]);

        try {
            // Process based on event type
            switch ($payload['event']) {
                case 'payment_link.paid':
                    $this->processPaymentLinkPaid($payload);
                    break;
                    
                case 'payment.failed':
                    $this->processPaymentFailed($payload);
                    break;
                    
                case 'payment_link.cancelled':
                    $this->processPaymentLinkCancelled($payload);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event', ['event' => $payload['event']]);
            }

            $webhookLog->update(['processed' => true]);

            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'webhook_id' => $webhookLog->id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($payload),
            env('RAZORPAY_WEBHOOK_SECRET')
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid webhook signature');
        }

        return true;
    }

    /**
     * Process payment_link.paid webhook
     */
    private function processPaymentLinkPaid($payload)
    {
        $paymentLinkId = $payload['payload']['payment_link']['entity']['id'];
        $paymentId = $payload['payload']['payment']['entity']['id'];

        $paymentRequest = PaymentRequest::where('razorpay_payment_link_id', $paymentLinkId)->first();

        if (!$paymentRequest) {
            throw new Exception('Payment request not found');
        }

        if ($paymentRequest->status === 'paid') {
            return; // Already processed
        }

        DB::transaction(function () use ($paymentRequest, $paymentId, $payload) {
            $paymentRequest->update([
                'status' => 'paid',
                'razorpay_order_id' => $payload['payload']['order']['entity']['id'] ?? null
            ]);

            PaymentTransaction::create([
                'payment_request_id' => $paymentRequest->id,
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $payload['payload']['order']['entity']['id'] ?? 'N/A',
                'payment_method' => $payload['payload']['payment']['entity']['method'] ?? 'unknown',
                'amount' => $payload['payload']['payment']['entity']['amount'] / 100,
                'currency' => $payload['payload']['payment']['entity']['currency'] ?? 'INR',
                'status' => 'success',
                'payment_response' => $payload,
                'created_at' => now()
            ]);

            $this->activatePackageForUser($paymentRequest);
        });
    }

    /**
     * Process payment.failed webhook
     */
    private function processPaymentFailed($payload)
    {
        $paymentId = $payload['payload']['payment']['entity']['id'];

        $transaction = PaymentTransaction::where('razorpay_payment_id', $paymentId)->first();

        if ($transaction) {
            $transaction->update(['status' => 'failed']);
            
            if ($transaction->paymentRequest) {
                $transaction->paymentRequest->update(['status' => 'failed']);
            }
        }
    }

    /**
     * Process payment_link.cancelled webhook
     */
    private function processPaymentLinkCancelled($payload)
    {
        $paymentLinkId = $payload['payload']['payment_link']['entity']['id'];

        $paymentRequest = PaymentRequest::where('razorpay_payment_link_id', $paymentLinkId)->first();

        if ($paymentRequest && $paymentRequest->status === 'pending') {
            $paymentRequest->update(['status' => 'cancelled']);
        }
    }

    /**
     * Activate package for user (company users only)
     */
    private function activatePackageForUser($paymentRequest)
    {
        $user = $paymentRequest->user;
        
        // Check if user is a company
        if ($user && $user->usertype === 'company') {
            $package = $paymentRequest->package;
            
            $user->package_id = $package->id;
            $user->package_start_date = now();
            $user->package_end_date = now()->addDays($package->package_num_days);
            $user->jobs_quota = $package->package_num_listings;
            $user->availed_jobs_quota = 0;
            $user->save();

            Log::info('Package activated for company user', [
                'user_id' => $user->id,
                'company_name' => $user->company_name ?? $user->name,
                'package_id' => $package->id,
                'payment_request_id' => $paymentRequest->id
            ]);
        } else {
            Log::info('Payment completed for non-company user', [
                'user_id' => $user->id,
                'usertype' => $user->usertype ?? 'unknown',
                'payment_request_id' => $paymentRequest->id
            ]);
        }
    }

    /**
     * Get payment status by order number
     */
    public function getPaymentStatus($orderNumber)
    {
        try {
            $paymentRequest = PaymentRequest::with(['package', 'transaction', 'user'])
                ->where('order_number', $orderNumber)
                ->first();

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request not found'
                ], 404);
            }

            $userData = null;
            if ($paymentRequest->user) {
                $user = $paymentRequest->user;
                $userData = [
                    'id' => $user->id,
                    'name' => $user->usertype === 'company' 
                        ? ($user->company_name ?? $user->name) 
                        : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'usertype' => $user->usertype,
                    'email' => $user->email
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status retrieved',
                'data' => [
                    'order_number' => $paymentRequest->order_number,
                    'status' => $paymentRequest->status,
                    'amount' => $paymentRequest->amount,
                    'formatted_amount' => '₹ ' . number_format($paymentRequest->amount, 0),
                    'user' => $userData,
                    'package' => [
                        'id' => $paymentRequest->package->id,
                        'title' => $paymentRequest->package->package_title,
                        'listings' => $paymentRequest->package->package_num_listings
                    ],
                    'transaction' => $paymentRequest->transaction ? [
                        'payment_id' => $paymentRequest->transaction->razorpay_payment_id,
                        'payment_method' => $paymentRequest->transaction->payment_method,
                        'paid_at' => $paymentRequest->transaction->created_at,
                        'paid_at_formatted' => $paymentRequest->transaction->created_at ? 
                            Carbon::parse($paymentRequest->transaction->created_at)->format('d M Y, h:i A') : null
                    ] : null,
                    'created_at' => $paymentRequest->created_at,
                    'created_at_formatted' => $paymentRequest->created_at ? 
                        Carbon::parse($paymentRequest->created_at)->format('d M Y, h:i A') : null
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get payment status failed', [
                'error' => $e->getMessage(),
                'order_number' => $orderNumber
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Get payment history for authenticated user
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $payments = PaymentRequest::with(['package', 'transaction'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $formattedPayments = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'order_number' => $payment->order_number,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'formatted_amount' => '₹ ' . number_format($payment->amount, 0),
                    'package' => $payment->package ? [
                        'id' => $payment->package->id,
                        'title' => $payment->package->package_title
                    ] : null,
                    'transaction' => $payment->transaction ? [
                        'payment_id' => $payment->transaction->razorpay_payment_id,
                        'payment_method' => $payment->transaction->payment_method,
                        'status' => $payment->transaction->status
                    ] : null,
                    'created_at' => $payment->created_at,
                    'created_at_formatted' => $payment->created_at ? 
                        Carbon::parse($payment->created_at)->format('d M Y, h:i A') : null
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved',
                'data' => [
                    'payments' => $formattedPayments,
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                        'last_page' => $payments->lastPage()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get payment history failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment history',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }

    /**
     * Verify payment signature
     */
    public function verifyPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$validator->errors()->toArray()
                ], 422);
            }

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'errors' => (object)['server' => 'Invalid signature']
            ], 400);
        }
    }

    /**
     * Create payment order for App SDK
     */
    public function createOrderForApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => (object)$validator->errors()->toArray()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $package = Package::findOrFail($request->package_id);
            
            // Generate unique order number
            $orderNumber = 'ORD-' . strtoupper(uniqid() . Str::random(6));

            // Create payment request record
            $paymentRequest = PaymentRequest::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'order_number' => $orderNumber,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'amount' => $package->package_price,
                'currency' => 'INR',
                'status' => 'pending',
                'request_payload' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $keyId = env('RAZORPAY_KEY');
            $keySecret = env('RAZORPAY_SECRET');
            $razorpay = new Api($keyId, $keySecret);

            // Create Razorpay Order
            $orderData = [
                'receipt'         => $paymentRequest->order_number,
                'amount'          => (int)($package->package_price * 100), // amount in paise
                'currency'        => 'INR',
                'payment_capture' => 1 // auto capture
            ];

            $razorpayOrder = $razorpay->order->create($orderData);

            // Update payment request with order ID
            $paymentRequest->update([
                'razorpay_order_id' => $razorpayOrder['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment order created successfully',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'order_number' => $paymentRequest->order_number,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'amount' => $package->package_price,
                    'currency' => 'INR',
                    'key_id' => $keyId,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment from App SDK and update status
     */
    public function verifyAppPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$validator->errors()->toArray()
                ], 422);
            }

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            // Verify signature
            $this->razorpay->utility->verifyPaymentSignature($attributes);

            // Find payment request
            $paymentRequest = PaymentRequest::where('razorpay_order_id', $request->razorpay_order_id)->first();

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request not found'
                ], 404);
            }

            if ($paymentRequest->status === 'paid') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already verified'
                ]);
            }

            DB::beginTransaction();

            try {
                $paymentRequest->update([
                    'status' => 'paid',
                ]);

                PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'payment_method' => $request->payment_method ?? 'app',
                    'amount' => $paymentRequest->amount,
                    'currency' => $paymentRequest->currency,
                    'status' => 'success',
                    'payment_response' => $request->all(),
                    'created_at' => now()
                ]);

                $this->activatePackageForUser($paymentRequest);
                
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully and package activated'
                ]);
                
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Payment signature verification failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            // Update status to failed
            $paymentRequest = PaymentRequest::where('razorpay_order_id', $request->razorpay_order_id)->first();
            if ($paymentRequest) {
                $paymentRequest->update(['status' => 'failed']);
                
                PaymentTransaction::create([
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'amount' => $paymentRequest->amount,
                    'currency' => $paymentRequest->currency,
                    'status' => 'failed',
                    'payment_response' => $request->all(),
                    'created_at' => now()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed',
                'errors' => (object)['server' => 'Invalid signature']
            ], 400);

        } catch (Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'errors' => (object)['server' => 'An error occurred']
            ], 500);
        }
    }
}