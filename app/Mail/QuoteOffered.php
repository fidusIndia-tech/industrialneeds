<?php

namespace App\Mail;

use App\Model\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Customer email with the admin's price + a tokenised accept link (RFQ MVP, PR 2).
 * The accept link target (GET /quote/{token}) is implemented in PR 3; the URL is
 * built here so the email is ready the moment that route lands.
 */
class QuoteOffered extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $acceptUrl;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        $this->acceptUrl = url('/quote/' . $quote->accept_token);
    }

    public function build()
    {
        return $this->subject('Your Quote ' . ($this->quote->reference_no ?? ('#' . $this->quote->id)) . ' from IndustrialNeeds')
                    ->view('emails.quote_offered');
    }
}
