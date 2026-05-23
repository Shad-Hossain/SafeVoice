<?php

use Illuminate\Support\Facades\Schedule;

// Auto-reject expired PI payment deadlines — runs every hour
Schedule::command('safevoice:auto-reject')->hourly();

// Auto-expire pending PI assignments after 48h — runs every hour
Schedule::command('safevoice:expire-pi-assignments')->hourly();