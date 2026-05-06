<?php

namespace App\Mail;

use App\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewInquiryAlert extends Mailable
{
    use Queueable, SerializesModels;

    // Create a public variable to hold the data
    public $inquiry; 

    // When the email is created, catch the database data
    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    // Build the actual email
    public function build()
    {
        return $this->subject('🚨 New Website Lead: ' . $this->inquiry->customer_name)
                    ->view('emails.new_inquiry'); 
    }
}