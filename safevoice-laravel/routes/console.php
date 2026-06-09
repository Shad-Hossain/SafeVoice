<?php

use Illuminate\Support\Facades\Schedule;

// Auto-reject expired PI payment deadlines — runs every hour
Schedule::command('safevoice:auto-reject')->hourly();

// Auto-expire pending PI assignments after 48h — runs every hour
Schedule::command('safevoice:expire-pi-assignments')->hourly();

// Evidence deadline processing — runs daily at midnight
// → 30-day expired: auto-suspend user
// → 7-day expired: send PI notification to user
// → 2-month suspended: auto-reactivate account
Schedule::command('safevoice:process-evidence-deadlines')->dailyAt('00:00');


use App\Console\Commands\ExpireLegalCaseOffers;

Schedule::command('legal:expire-offers')->hourly();

// Expire instant legal requests past 30-min deadline — runs every minute
Schedule::command('legal:expire-instant')->everyMinute();
