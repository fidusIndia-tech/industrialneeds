<?php

namespace App\Mail;

use App\Model\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Admin alert when a customer submits a quote request (RFQ MVP, PR 1).
 * Mirrors NewInquiryAlert; sent best-effort (caller wraps in try/catch).
 */
class QuoteRequested extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function build()
    {
        return $this->subject('🧾 New Quote Request: ' . $this->quote->customer_name)
                    ->view('emails.quote_requested');
    }
}
