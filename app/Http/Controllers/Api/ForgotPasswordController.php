<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ForgotPasswordController extends Controller
{
    /**
     * SendGrid API configuration
     */
    private $sendGridApiKey = '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';
    private $sendGridUrl = 'https://api.sendgrid.com/v3/mail/send';
    private $fromEmail = 'info@showcazz.com';
    private $fromName = 'Showcazz';

    /**
     * 1. Send Verification Code API
     */
    public function sendResetCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }

            $email = $request->email;
            
            // Check if email exists in users table only
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found',
                    'errors' => (object)[
                        'email' => 'The provided email address is not registered'
                    ]
                ], 404);
            }

            // Determine user type and name
            $userType = $user->usertype ?? 'user';
            $userName = $user->usertype === 'company' 
                ? ($user->company_name ?? $user->name ?? 'User')
                : $user->getName();

            // Generate 4-digit verification code
            $verificationCode = rand(1000, 9999);
            
            // Store in cache for 10 minutes
            $cacheKey = 'reset_code_' . $email;
            Cache::put($cacheKey, [
                'code' => $verificationCode,
                'email' => $email,
                'created_at' => Carbon::now()
            ], now()->addMinutes(10));

            // Send email with the code using SendGrid
            $emailSent = $this->sendResetCodeEmail($email, $verificationCode, $userName, $userType);
            
            if (!$emailSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email',
                    'errors' => (object)[
                        'email' => 'Unable to send verification email. Please try again.'
                    ]
                ], 500);
            }
            
            // Response data
            $responseData = [
                'email' => $email,
                'expires_in' => 10
            ];
            
            // Only include code in local/development environment
            if (app()->environment('local', 'development')) {
                $responseData['debug_code'] = $verificationCode;
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully to your email',
                'data' => $responseData
            ]);

        } catch (Exception $e) {
            Log::error('Send reset code failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code',
                'errors' => (object)[
                    'server' => 'An error occurred while sending verification code'
                ]
            ], 500);
        }
    }

    /**
     * Send reset code email using SendGrid cURL
     */
    private function sendResetCodeEmail($email, $code, $userName, $userType)
    {
        try {
            $appName = config('app.name', 'Showcazz');
            
            // Create HTML email content
            $htmlContent = $this->getEmailTemplate($code, $userName, $userType, $appName);
            
            // Prepare SendGrid payload
            $payload = [
                "personalizations" => [
                    [
                        "to" => [
                            ["email" => $email]
                        ],
                        "subject" => "Password Reset Request - " . $appName
                    ]
                ],
                "from" => [
                    "email" => $this->fromEmail,
                    "name" => $this->fromName
                ],
                "content" => [
                    [
                        "type" => "text/html",
                        "value" => $htmlContent
                    ]
                ]
            ];

            // Make cURL request to SendGrid
            $ch = curl_init($this->sendGridUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->sendGridApiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                Log::error('SendGrid cURL Error: ' . curl_error($ch));
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            // Check if SendGrid accepted the request (2xx status code)
            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info('SendGrid email sent successfully', ['email' => $email, 'code' => $code]);
                return true;
            } else {
                Log::error('SendGrid API Error', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'email' => $email
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            Log::error('SendGrid Exception: ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get HTML email template
     */
    private function getEmailTemplate($code, $userName, $userType, $appName)
    {
        $year = date('Y');
        $loginUrl = config('app.frontend_url', 'https://showcazz.com') . '/login';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .code-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 8px;
            text-align: center;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
        }
        .info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info p {
            margin: 5px 0;
        }
        .warning {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 20px;
            padding: 10px;
            background-color: #fdeaea;
            border-radius: 5px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666666;
            border-top: 1px solid #e0e0e0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 20px;
        }
        .button:hover {
            opacity: 0.9;
        }
        hr {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello <strong>{$userName}</strong>,
            </div>
            
            <p>We received a request to reset the password for your <strong></strong> account. Use the verification code below to complete the password reset process:</p>
            
            <div class="code-box">
                {$code}
            </div>
            
            <div class="info">
                <p><strong>📋 Details:</strong></p>
                <p>• Account Type: <strong>{$userType}</strong></p>
                <p>• Code Expires: <strong>10 minutes</strong></p>
               
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Important Security Notice:</strong></p>
                <p>• Never share this code with anyone</p>
                <p>• Our team will never ask for this code</p>
                <p>• If you didn't request this, please ignore this email</p>
            </div>
            
            <p>To complete your password reset, enter this verification code on the password reset page. You'll then be able to create a new password.</p>
            
           
            <hr>
            
            <p style="color: #666666; font-size: 14px;">
                <strong>Didn't request this?</strong><br>
                If you didn't request a password reset, please secure your account by logging in and changing your password immediately, or contact our support team.
            </p>
        </div>
        
        <div class="footer">
            <p>© {$year} . All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 2. Verify Code API
     */
    public function verifyCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'code' => 'required|string|size:4',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }

            $email = $request->email;
            $code = $request->code;

            // Get code from cache
            $cacheKey = 'reset_code_' . $email;
            $cachedData = Cache::get($cacheKey);

            if (!$cachedData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code expired or invalid',
                    'errors' => (object)[
                        'code' => 'The verification code has expired. Please request a new one.'
                    ]
                ], 400);
            }

            // Check if code matches
            if ((string)$cachedData['code'] !== (string)$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code',
                    'errors' => (object)[
                        'code' => 'The verification code is incorrect. Please try again.'
                    ]
                ], 400);
            }

            // Code is valid - generate a temporary verification token
            $verificationToken = md5($email . time() . rand(1000, 9999));
            
            // Store verification status in cache with short expiry
            $verifyKey = 'verified_' . $email;
            Cache::put($verifyKey, true, now()->addMinutes(15));

            return response()->json([
                'success' => true,
                'message' => 'Code verified successfully',
                'data' => [
                    'email' => $email,
                    'verified' => true,
                    'verification_token' => $verificationToken
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Verify code failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code',
                'errors' => (object)[
                    'server' => 'An error occurred while verifying the code'
                ]
            ], 500);
        }
    }

    /**
     * 3. Resend Verification Code API
     */
    public function resendCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);
    
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }
    
            $email = $request->email;
            
            // Check if email exists in users table only
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found',
                    'errors' => (object)[
                        'email' => 'The provided email address is not registered'
                    ]
                ], 404);
            }

            // Determine user type and name
            $userType = $user->usertype ?? 'user';
            $userName = $user->usertype === 'company' 
                ? ($user->company_name ?? $user->name ?? 'User')
                : $user->getName();

            // Check rate limiting
            $rateLimitKey = 'rate_limit_' . $email;
            $lastRequestTime = Cache::get($rateLimitKey);
            
            if ($lastRequestTime && Carbon::parse($lastRequestTime)->diffInMinutes(now()) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests',
                    'errors' => (object)[
                        'email' => 'Please wait at least 2 minutes before requesting another code.'
                    ]
                ], 429);
            }

            // Generate new 4-digit verification code
            $verificationCode = rand(1000, 9999);
            
            // Store in cache for 10 minutes
            $cacheKey = 'reset_code_' . $email;
            Cache::put($cacheKey, [
                'code' => $verificationCode,
                'email' => $email,
                'created_at' => Carbon::now()
            ], now()->addMinutes(10));

            // Update rate limit
            Cache::put($rateLimitKey, Carbon::now(), now()->addMinutes(10));

            // Send email with the code using SendGrid
            $emailSent = $this->sendResetCodeEmail($email, $verificationCode, $userName, $userType);
            
            if (!$emailSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email',
                    'errors' => (object)[
                        'email' => 'Unable to send verification email. Please try again.'
                    ]
                ], 500);
            }
            
            $responseData = [
                'email' => $email,
                'expires_in' => 10
            ];
            
            // Only include code in local/development environment
            if (app()->environment('local', 'development')) {
                $responseData['debug_code'] = $verificationCode;
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code resent successfully',
                'data' => $responseData
            ]);

        } catch (Exception $e) {
            Log::error('Resend code failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification code',
                'errors' => (object)[
                    'server' => 'An error occurred while resending the code'
                ]
            ], 500);
        }
    }

    /**
     * 4. Reset Password API
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'verification_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }

            $email = $request->email;
            $newPassword = $request->password;

            // Optional: Verify that the code was verified
            $verifyKey = 'verified_' . $email;
            $isVerified = Cache::get($verifyKey);
            
            // You can uncomment this if you want to enforce verification before reset
            // if (!$isVerified) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Email not verified',
            //         'errors' => (object)[
            //             'email' => 'Please verify your email with the code first'
            //         ]
            //     ], 400);
            // }

            // Find user in users table only
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)[
                        'email' => 'User not found'
                    ]
                ], 404);
            }
    
            // Validate current password
            if ($user) {
                if (!Hash::check($currentPassword, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => (object)[
                            'current_password' => 'Current password is incorrect'
                        ]
                    ], 401);
                }
            } else {
                if (!Hash::check($currentPassword, $company->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => (object)[
                            'current_password' => 'Current password is incorrect'
                        ]
                    ], 401);
                }
            }
    
            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Clear all cache keys for this email
            Cache::forget('reset_code_' . $email);
            Cache::forget('verified_' . $email);
            Cache::forget('rate_limit_' . $email);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'email' => $email,
                    'user_type' => $user->usertype,
                    'reset' => true
                ]
            ]);
    
        } catch (Exception $e) {
            Log::error('Reset password failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'errors' => (object)[
                    'server' => 'An error occurred while resetting password'
                ]
            ], 500);
        }
    }

    /**
     * 5. Check Email Existence API
     */
    public function checkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[$field] = $messages[0];
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => (object)$errors
                ], 422);
            }

            $email = $request->email;
            
            $user = User::where('email', $email)->first();
            
            $exists = !is_null($user);
            $userType = $user ? $user->usertype : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $email,
                    'exists' => $exists,
                    'user_type' => $userType
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Check email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check email',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }

    /**
     * Alternative: Send email using Laravel HTTP Client instead of cURL
     */
    private function sendEmailWithHttpClient($email, $code, $userName, $userType)
    {
        try {
            $appName = 'Showcazz';
            $htmlContent = $this->getEmailTemplate($code, $userName, $userType, $appName);
            
            $payload = [
                "personalizations" => [
                    [
                        "to" => [
                            ["email" => $email]
                        ],
                        "subject" => "Password Reset Request - " . $appName
                    ]
                ],
                "from" => [
                    "email" => $this->fromEmail,
                    "name" => $this->fromName
                ],
                "content" => [
                    [
                        "type" => "text/html",
                        "value" => $htmlContent
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->sendGridApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->sendGridUrl, $payload);

            if ($response->successful()) {
                Log::info('SendGrid email sent successfully', ['email' => $email]);
                return true;
            } else {
                Log::error('SendGrid API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'email' => $email
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            Log::error('SendGrid HTTP Client Exception: ' . $e->getMessage(), [
                'email' => $email
            ]);
            return false;
        }
    }
}