<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\B2BPortalController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('b2b:sync-daily-summary {--month=}', function () {
    $month = $this->option('month');

    $result = app(B2BPortalController::class)->syncDailySummaryForAllUsers($month ?: null);

    $this->info('B2B summary synced.');
    $this->line('Month: ' . $result['month']);
    $this->line('Processed users: ' . $result['processed_users']);
})->purpose('Sync monthly B2B AM point summary for all b2b users');

Schedule::command('b2b:sync-daily-summary')
    ->everyMinute()
    ->withoutOverlapping();
