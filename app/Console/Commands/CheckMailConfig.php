<?php

namespace App\Console\Commands;

use App\CPU\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Report which mail settings are actually in effect, and optionally prove them
 * by sending a real message.
 *
 * Mail on this project does NOT come from .env by default. MailConfigServiceProvider
 * reads business_settings.mail_config (Admin > Business Settings > Mail Config) and,
 * only when its status is 1, overwrites the whole mail config with it. If both that
 * and mail_config_sendgrid are switched off, Laravel silently falls back to the
 * MAIL_* values in .env instead — which is easy to misread as "SMTP is configured".
 *
 * The admin panel's test button reports a bare success/fail flag and throws the
 * exception away, so a failing SMTP account looks the same as an unconfigured one.
 * --send surfaces the actual error.
 *
 *   php artisan mail:check
 *   php artisan mail:check --send=you@example.com
 */
class CheckMailConfig extends Command
{
    protected $signature = 'mail:check
                            {--send= : Send a real test message to this address}';

    protected $description = 'Show which mail settings are in effect and optionally send a test message';

    public function handle()
    {
        $smtp = Helpers::get_business_settings('mail_config');
        $grid = Helpers::get_business_settings('mail_config_sendgrid');

        $source = null;
        if (is_array($smtp) && ($smtp['status'] ?? 0) == 1) {
            $source = 'business_settings.mail_config (SMTP)';
            $active = $smtp;
        } elseif (is_array($grid) && ($grid['status'] ?? 0) == 1) {
            $source = 'business_settings.mail_config_sendgrid';
            $active = $grid;
        }

        $this->line('');

        if ($source === null) {
            $this->warn('No mail configuration is enabled in the admin panel.');
            $this->line('  mail_config status ............ ' . $this->status($smtp));
            $this->line('  mail_config_sendgrid status ... ' . $this->status($grid));
            $this->line('');
            $this->line('Laravel is falling back to the MAIL_* values in .env:');
            $this->line('  driver ... ' . config('mail.driver'));
            $this->line('  host ..... ' . config('mail.host'));
            $this->line('  port ..... ' . config('mail.port'));
            $this->line('  username . ' . config('mail.username'));
            $this->line('  from ..... ' . config('mail.from.address'));
            $this->line('');
            $this->comment('Fix this in Admin > Business Settings > Mail Config, then set status to Active.');
        } else {
            $this->info('Active source: ' . $source);
            $this->line('  driver ..... ' . ($active['driver'] ?? '-'));
            $this->line('  host ....... ' . ($active['host'] ?? '-'));
            $this->line('  port ....... ' . ($active['port'] ?? '-'));
            $this->line('  encryption . ' . ($active['encryption'] ?? '-'));
            $this->line('  username ... ' . ($active['username'] ?? '-'));
            $this->line('  from ....... ' . ($active['email_id'] ?? '-') . ' (' . ($active['name'] ?? '-') . ')');
            $this->line('  password ... ' . (empty($active['password']) ? 'NOT SET' : 'set (' . strlen($active['password']) . ' chars)'));
        }

        $this->line('');
        $this->line('Lead alerts are delivered to:');
        $this->line('  enquiries / quotes . ' . (config('leads.notify_email') ?: 'DISABLED'));
        $this->line('  chatbot ............ ' . (config('chatbot.notify_email') ?: 'DISABLED'));
        $this->line('');

        $to = $this->option('send');
        if (!$to) {
            $this->comment('Add --send=you@example.com to send a real test message.');
            return self::SUCCESS;
        }

        $this->line("Sending a test message to {$to} ...");

        try {
            Mail::raw(
                "This is a test from industrialsupply.in.\n\n"
                . "If you are reading this, outgoing mail works and enquiry, quote\n"
                . "and chatbot alerts will be delivered.\n\nSent: " . now()->toDateTimeString(),
                function ($m) use ($to) {
                    $m->to($to)->subject('Mail test — industrialsupply.in');
                }
            );
        } catch (\Throwable $e) {
            $this->line('');
            $this->error('FAILED: ' . $e->getMessage());
            $this->line('');
            $this->comment('Common causes: wrong password or app-password, port/encryption mismatch');
            $this->comment('(587 needs tls, 465 needs ssl), or the host blocking outbound SMTP.');
            return self::FAILURE;
        }

        $this->info('Sent without error. Check the inbox, and the spam folder.');
        return self::SUCCESS;
    }

    private function status($setting)
    {
        if (!is_array($setting)) {
            return 'not configured';
        }

        return ($setting['status'] ?? 0) == 1 ? 'ACTIVE' : 'off';
    }
}
