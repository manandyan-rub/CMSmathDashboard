<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    DB::table('content_workflows')
        ->where('state', 'published')
        ->whereNull('published_at')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->update([
            'published_at' => now(),
            'updated_at' => now(),
        ]);
})->everyMinute()->name('cms-publish-scheduled-content')->withoutOverlapping();
