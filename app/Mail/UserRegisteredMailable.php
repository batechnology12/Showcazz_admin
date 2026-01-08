<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMailable extends Mailable
{

    use SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
{
    $recipientAddress = config('mail.recieve_to.address');
    $recipientName = config('mail.recieve_to.name');

    return $this->from([
        'address' => $recipientAddress,
        'name' => $recipientName,
    ])
    ->to($recipientAddress, $recipientName)
    ->subject('Job Seeker "' . $this->user->name . '" has been registered on "' . config('app.name'))
    ->view('emails.user_registered_message')
    ->with([
        'name' => $this->user->name,
        'email' => $this->user->email,
        'link' => route('user.profile', $this->user->id),
        'link_admin' => route('edit.user', ['id' => $this->user->id])
    ]);
}


}
