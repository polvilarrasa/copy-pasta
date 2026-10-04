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
