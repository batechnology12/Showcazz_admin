<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DocumentsUpload extends Mailable
{

    use Queueable,
        SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
       if($this->data['is_admin']){
           $recipientAddress = $this->data['email'];
           $recipientName = $this->data['full_name'];
           if($this->data['company']->is_active == 1){
                $subject = 'Account Approved Start Posting Jobs';
           }elseif($this->data['status'] !=1){
                $subject = 'Resubmit Job Posting Request Declined';
           }else{
                $subject = 'Registration Verification in Progress';
           }
          
       }else{
           $recipientAddress = config('mail.recieve_to.address');
           $recipientName = config('mail.recieve_to.name');
           $subject = 'New Employer Registration Approval Required';
       }        
       
        
       return $this->from([
        'address' => config('mail.recieve_to.address'),
        'name' => config('mail.recieve_to.name'),
    ])
    
    ->to($recipientAddress, $recipientName)
    ->replyTo($this->data['email'], $this->data['full_name'])
    ->subject($subject)
    ->view('emails.send_document_message')
    ->with($this->data);
    }

}
