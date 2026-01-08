<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIfVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if the user is authenticated using the 'company' guard
        if (Auth::guard('company')->check()) {
            // Check if the user is verified
            if (!Auth::guard('company')->user()->hasVerifiedEmail()) {
                // Redirect to the email verification page
                return redirect()->route('company.verification.notice');
            }
        } else {
            // Redirect to login if not authenticated
            return redirect()->route('company.login'); // Replace with your company login route
        }

        return $next($request);
    }
}
