<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobPostedMailableFront extends Mailable
{

    use SerializesModels;

    public $job;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company = $this->job->getCompany();
        $recipientAddress = $company->email;
        $recipientName = $company->name;
    
        return $this->to($recipientAddress, $recipientName)
            ->subject('Job Vacancy Submission Pending Approval')
            ->view('emails.job_posted_message_front')
            ->with([
                'name' => $company->name,
                'link' => route('job.detail', [$this->job->slug]),
                'link_admin' => route('edit.job', ['id' => $this->job->id])
            ]);
    }


}
