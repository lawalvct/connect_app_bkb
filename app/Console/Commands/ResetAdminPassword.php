<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $admin = Admin::first();
        } else {
            $admin = Admin::where('email', $email)->first();
        }

        if (!$admin) {
            $this->error('Admin not found');
            return;
        }

        $admin->password = bcrypt('admin123');
        $admin->otp_code = null;
        $admin->otp_expires_at = null;
        $admin->save();

        $this->info('Admin password reset successfully!');
        $this->info('Email: ' . $admin->email);
        $this->info('Password: admin123');
    }
}
