<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Both every minute; each command decides for itself what is due. On the server
 * they run from /etc/cron.d/undilot — never from the www-data crontab, which
 * belongs to another application on the same box.
 */
Schedule::command('events:update-statuses')->everyMinute();
Schedule::command('voting:send-reminder')->everyMinute();
