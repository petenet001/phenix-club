<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:refresh-status')
    ->dailyAt('00:05')
    ->timezone('Africa/Brazzaville')
    ->withoutOverlapping();
