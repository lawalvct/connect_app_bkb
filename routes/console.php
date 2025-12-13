<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Note: All scheduled tasks are now defined in bootstrap/app.php
// using Laravel 12's withSchedule() method for better organization
