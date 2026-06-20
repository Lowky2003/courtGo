<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'mail:test {email : The address to send the test message to}';

    protected $description = 'Send a test email to verify the mailer is configured';

    public function handle(): int
    {
        $to = $this->argument('email');

        try {
            Mail::raw('If you can read this, CourtGo email delivery is working.', function ($message) use ($to) {
                $message->to($to)->subject('CourtGo test email');
            });
        } catch (\Throwable $e) {
            $this->error('Could not send: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Test email sent to '.$to.' via the "'.config('mail.default').'" mailer.');

        if (config('mail.default') === 'log') {
            $this->line('MAIL_MAILER=log — the email was written to storage/logs/laravel.log, not delivered.');
        }

        return self::SUCCESS;
    }
}
