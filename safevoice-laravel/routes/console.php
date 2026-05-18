<?php

use Illuminate\Support\Facades\Schedule;

// Auto-reject expired PI payment deadlines — runs every hour
Schedule::command('safevoice:auto-reject')->hourly();
