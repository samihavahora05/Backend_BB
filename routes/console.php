<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::call(function () {
    $autoSchedule = \App\Models\BackupSetting::where('key', 'auto_schedule')->value('value');
    if ($autoSchedule !== 'true' && $autoSchedule !== '1') return;

    $scheduleType = \App\Models\BackupSetting::where('key', 'schedule_type')->value('value') ?? 'daily';
    
    $shouldRun = false;
    $now = now();
    
    if ($scheduleType === 'daily') {
        $shouldRun = true;
    } elseif ($scheduleType === 'weekly' && $now->isSunday()) {
        $shouldRun = true;
    } elseif ($scheduleType === 'monthly' && $now->day === 1) {
        $shouldRun = true;
    }

    if ($shouldRun) {
        $fileName = 'blueboxx_complete_auto_'.now()->format('Y_m_d_His').'.zip';
        $backup = \App\Models\SystemBackup::create([
            'name' => $fileName,
            'type' => 'Complete',
            'size' => 'Pending',
            'size_bytes' => 0,
            'status' => 'pending',
            'disk' => 'local',
            'file_path' => 'backups/' . $fileName,
            'created_by' => \App\Models\User::first()->id ?? 1,
        ]);
        \App\Jobs\GenerateBackupJob::dispatch($backup->id);
    }
})->dailyAt('02:00');

\Illuminate\Support\Facades\Schedule::command('app:check-integrations')->dailyAt('00:00');
