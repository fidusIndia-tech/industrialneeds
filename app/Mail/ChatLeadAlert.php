<?php

namespace App\Mail;

use App\Model\ChatLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sales alert for a chatbot lead. Sent when a phone number is captured (engagementOnly=false),
 * or — if enabled — the moment a visitor opens the chat (engagementOnly=true).
 */
class ChatLeadAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $engagementOnly;

    public function __construct(ChatLead $lead, bool $engagementOnly = false)
    {
        $this->lead = $lead;
        $this->engagementOnly = $engagementOnly;
    }

    public function build()
    {
        $subject = $this->engagementOnly
            ? '👀 Visitor opened the chat — ' . ($this->lead->page_url ?: 'site')
            : '📞 New website lead — call ' . ($this->lead->name ?: 'now') . ' (' . $this->lead->phone . ')';

        return $this->subject($subject)->view('emails.chat_lead_alert');
    }
}
