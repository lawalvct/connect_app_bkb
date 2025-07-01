<?php

namespace App\Console\Commands;

use App\Jobs\SendAdExpiryRemindersJob;
use Illuminate\Console\Command;

class SendAdReminders extends Command
{
    protected $signature = 'ads:send-reminders';
    protected $description = 'Send expiry reminders for advertisements';

    public function handle()
    {
        SendAdExpiryRemindersJob::dispatch();
        $this->info('Ad expiry reminders job dispatched');
    }
}
