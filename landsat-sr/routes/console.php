<?php

use App\Console\Commands\SendEmail;
use Illuminate\Support\Facades\Schedule;
use App\Models\Observation;
use App\Jobs\DownloadData;
use Carbon\Carbon;

Schedule::call(function () {
    Observation::where('status', 'pending')
        ->where('overpass_time', '<=', Carbon::now()->subHours(24))
        ->get()
        ->each(fn(Observation $observation) => DownloadData::dispatch($observation));
})->everyMinute();

Schedule::command(SendEmail::class)->everyminute();
