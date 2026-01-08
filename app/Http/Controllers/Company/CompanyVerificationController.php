<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Jrean\UserVerification\Traits\VerifiesUsers;
use Jrean\UserVerification\Facades\UserVerification;

class CompanyVerificationController extends Controller
{
    /**
     * Show the email verification notice.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function show()
    {
        return view('company.verify');
    }
    
    public function resend(Request $request)
    {
        if (Auth::guard('company')->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }
        
        $company = Auth::guard('company')->user();
        
        UserVerification::generate($company);
        UserVerification::send($company, 'Company Verification', config('mail.recieve_to.address'), config('mail.recieve_to.name'));

       // Auth::guard('company')->user()->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}
