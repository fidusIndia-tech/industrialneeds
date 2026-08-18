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
 * The chatbot widget keeps its own recipient (CHATBOT_LEAD_EMAIL, see
 * config/chatbot.php) so bot chatter can be routed away from real enquiries.
 */

return [

    'notify_email' => env('LEAD_NOTIFY_EMAIL', 'sales@fidusindia.com'),

];
