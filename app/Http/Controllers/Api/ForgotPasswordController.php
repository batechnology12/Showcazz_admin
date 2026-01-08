<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class ForgotPasswordController extends Controller
{
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
            
            // Check if email exists
            $user = User::where('email', $email)->first();
            $company = Company::where('email', $email)->first();
            
            if (!$user && !$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found',
                    'errors' => (object)[
                        'email' => 'The provided email address is not registered'
                    ]
                ], 404);
            }

            // Generate 4-digit verification code
            $verificationCode = rand(1000, 9999);
            
            // Store in cache for 10 minutes
            $cacheKey = 'reset_code_' . $email;
            Cache::put($cacheKey, [
                'code' => $verificationCode,
                'email' => $email,
                'created_at' => Carbon::now()
            ], now()->addMinutes(10));

            // In production, send email here
            // Mail::to($email)->send(new ResetPasswordCodeMail($verificationCode));
            
            // For now, return code in response
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully',
                'data' => [
                    'email' => $email,
                    'code' => $verificationCode, // Remove this in production
                    'expires_in' => 10
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
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
                        'code' => 'The code has expired or is invalid'
                    ]
                ], 400);
            }

            // Check if code matches
            if ($cachedData['code'] != $code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code',
                    'errors' => (object)[
                        'code' => 'The code is incorrect'
                    ]
                ], 400);
            }

            // Code is valid
            return response()->json([
                'success' => true,
                'message' => 'Code verified successfully',
                'data' => [
                    'email' => $email,
                    'verified' => true
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }

    /**
     * 3. Reset Password API
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
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
            $password = $request->password;

           
            // Find user/company
            $user = User::where('email', $email)->first();
            $company = Company::where('email', $email)->first();

            if (!$user && !$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => (object)[
                        'email' => 'User not found'
                    ]
                ], 404);
            }

            // Update password
            if ($user) {
                $user->password = Hash::make($password);
                $user->save();
                $userType = 'user';
            } else {
                $company->password = Hash::make($password);
                $company->save();
                $userType = 'company';
            }

           

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'email' => $email,
                    'user_type' => $userType,
                    'reset' => true
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'errors' => (object)[
                    'server' => 'An error occurred'
                ]
            ], 500);
        }
    }
}