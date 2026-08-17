<?php

/**
 * On-site scripted chatbot — settings + the question flow.
 * Everything here is editable without touching code: turn the bot on/off, change the alert
 * recipient, and rewrite the conversation steps.
 */

return [

    // Master on/off for the whole widget.
    'enabled' => env('CHATBOT_ENABLED', true),

    // ---- Lead alert email ----
    // Where new-lead alerts go. Set CHATBOT_LEAD_EMAIL in .env to your SALES inbox.
    // (Email only fires when a phone number is captured — see 'notify'.)
    'notify_email' => env('CHATBOT_LEAD_EMAIL', 'sales@industrialsupply.in'),

    // Also email the moment a visitor merely OPENS the chat (before any details)?
    // Off by default to avoid noise — you can only call someone once they leave a number.
    'notify_on_engagement' => env('CHATBOT_NOTIFY_ON_OPEN', false),

    // ---- Widget appearance ----
    'title'       => env('CHATBOT_TITLE', 'Industrial Needs'),
    'subtitle'    => 'Typically replies in a few minutes',
    'primary'     => '#0056b3',   // header / bubble colour
    'launcher'    => 'Chat with us',

    /**
     * The scripted conversation. Each step:
     *   key      : the ChatLead column this answer fills (null = no save, e.g. greeting)
     *   bot      : the bot's message
     *   type     : 'buttons' | 'text' | 'tel'   (input shown to the visitor)
     *   options  : for 'buttons' — the choices
     *   optional : true = a Skip control is offered
     *   validate : 'phone' to require a plausible number before continuing
     *
     * The flow runs top to bottom. The final step (type 'end') closes the chat.
     */
    'flow' => [
        [
            'key' => 'intent',
            'bot' => '👋 Hi! Welcome to Industrial Needs. What can we help you with?',
            'type' => 'buttons',
            'options' => ['Request a price', 'Check availability', 'Technical question', 'Something else'],
        ],
        [
            'key' => 'product',
            'bot' => 'Sure! Which product or part number are you interested in?',
            'type' => 'text',
        ],
        [
            'key' => 'quantity',
            'bot' => 'Roughly how many units do you need?',
            'type' => 'text',
            'optional' => true,
        ],
        [
            'key' => 'name',
            'bot' => 'Great — what’s your name?',
            'type' => 'text',
        ],
        [
            'key' => 'company_name',
            'bot' => 'Which company are you with?',
            'type' => 'text',
            'optional' => true,
        ],
        [
            'key' => 'location',
            'bot' => 'Where are you located (city / country)?',
            'type' => 'text',
            'optional' => true,
        ],
        [
            'key' => 'email',
            'bot' => 'Your email address?',
            'type' => 'text',
            'optional' => true,
        ],
        [
            'key' => 'phone',
            'bot' => 'And the best phone / WhatsApp number for our team to call you back?',
            'type' => 'tel',
            'validate' => 'phone',
        ],
        [
            'key' => 'message',
            'bot' => 'Anything else you’d like to add?',
            'type' => 'text',
            'optional' => true,
        ],
        [
            'key' => null,
            'type' => 'end',
            'bot' => 'Thank you! 🎯 Our team has your details and will call you shortly.',
        ],
    ],
];
