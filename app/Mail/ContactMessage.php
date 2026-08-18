<?php

namespace App\Mail;

use App\Model\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Alert for a contact-form submission.
 *
 * The homepage "Request a Quote" and "Quick Quote" buttons both point at the
 * contact page, so this form carries real quote enquiries — not just general
 * questions. It previously saved to the contacts table and notified nobody.
 *
 * Sent best-effort; the caller wraps it in try/catch.
 */
class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function build()
    {
        $subject = '📩 New Contact Enquiry: ' . ($this->contact->subject ?: 'No subject');

        $mail = $this->subject($subject)->view('emails.contact_message');

        // Let sales hit Reply and reach the customer directly.
        if (filter_var($this->contact->email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($this->contact->email, $this->contact->name ?: null);
        }

        return $mail;
    }
}
