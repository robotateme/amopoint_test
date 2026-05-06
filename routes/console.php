<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('jokes:fetch')->everyFiveMinutes();
