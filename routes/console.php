<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue housekeeping: finished batches and failed jobs older than a week are pruned every night.
Schedule::command('queue:prune-batches --hours=48 --unfinished=72 --cancelled=72')->daily()->onOneServer();
Schedule::command('queue:prune-failed --hours=168')->daily()->onOneServer();

// Accounts an admin deleted are anonymized once the 30-day retention window closes.
Schedule::command('users:anonymize-expired')->daily()->onOneServer();

// Event analytics: yesterday and today are rebuilt hourly so late events are counted, partitions are prepared daily,
// and partitions past the 13-month retention window are dropped monthly.
Schedule::command('events:aggregate')->hourly()->onOneServer();
Schedule::command('events:partitions')->daily()->onOneServer();
Schedule::command('events:prune')->monthlyOn(1, '03:30')->onOneServer();

// Notifications: read ones are kept 90 days, unread ones 180.
Schedule::command('notifications:prune')->daily()->onOneServer();
