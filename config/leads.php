<?php

/**
 * Where enquiry and quote-request alerts are delivered.
 *
 * These used to be hardcoded at each Mail::to() call site, so redirecting them
 * meant a code change and a deploy. Set LEAD_NOTIFY_EMAIL in .env instead.
 *
 * Leave it empty to switch the alerts off entirely — the lead is still saved to
 * the database either way, so nothing is lost, it just is not emailed out.
 *
 * The chatbot widget falls back to this same address, so one variable moves both.
 * Set CHATBOT_LEAD_EMAIL (see config/chatbot.php) only when bot chatter should
 * land in a separate inbox from real enquiries.
 */

return [

    'notify_email' => env('LEAD_NOTIFY_EMAIL', 'sales@fidusindia.com'),

];
