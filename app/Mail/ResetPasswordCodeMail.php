<?php
// app/Mail/ResetPasswordCodeMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $userName;
    public $userType;
    public $appName;
    public $expiryMinutes;

    public function __construct($code, $userName, $userType)
    {
        $this->code = $code;
        $this->userName = $userName;
        $this->userType = $userType;
        $this->appName = config('app.name', 'Showcazz');
        $this->expiryMinutes = 10;
    }

    public function build()
    {
        
      
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Password Reset Request - ' . $this->appName)
                    ->view('emails.reset-password');
    }
}