@component('mail::message')
One last step!
@component('mail::button', ['url' => route('email-verification.check', $user->verification_token) . '?email=' . urlencode($user->email) ])
Click here to verify your account
@endcomponent
<p style="margin-top:10px; font-family: Helvetica, Arial, sans-serif;font-size: 14px; color: #333;">                               
Warm regards, <br>
Warm regards, <br>
{{ $siteSetting->site_name }} Team 
</p>

@endcomponent
