<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The application timezone is Asia/Dhaka. At 04:30 the shop is quiet.
Schedule::command('cart:delete-old-guest-carts')->dailyAt('04:30');
