<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * A lead captured by the on-site scripted chatbot. See create_chat_leads_table.
 */
class ChatLead extends Model
{
    protected $fillable = [
        'session_id', 'name', 'company_name', 'location', 'phone', 'email', 'intent', 'product',
        'quantity', 'message', 'transcript', 'page_url', 'status', 'notified',
    ];

    protected $casts = [
        'transcript' => 'array',
        'notified'   => 'boolean',
    ];

    /** A lead is "callable" once we have a phone number. */
    public function isCallable(): bool
    {
        return !empty($this->phone);
    }
}
