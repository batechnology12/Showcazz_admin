<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    public function show()
    {
        return view('auth.verify');
    }

    public function verify(Request $request)
    {
        $user = $request->user();

        // Check if the URL is a valid verification URL
        if ($request->route('id') == $user->getKey() &&
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            // Mark the user's email as verified
            $user->markEmailAsVerified();

            event(new Verified($user));

            return redirect('/home')->with('verified', true);
        }

        return redirect('/login')->with('error', 'Invalid verification URL');
    }
    
    
    public function resend(Request $request)
    {
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('resent', true);
    }
    
    
    
}