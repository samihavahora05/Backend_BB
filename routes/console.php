<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:run-auto', function () {
    $this->info('Checking auto-backup schedule...');
    $autoSchedule = \App\Models\BackupSetting::where('key', 'auto_schedule')->value('value');
    if ($autoSchedule !== 'true' && $autoSchedule !== '1' && $autoSchedule !== true && $autoSchedule !== 1) {
        $this->warn('Auto backup is disabled in settings.');
        return;
    }

    $backupType = \App\Models\BackupSetting::where('key', 'schedule_backup_type')->value('value') ?? 'Complete';
    $fileName = 'blueboxx_'.strtolower($backupType).'_auto_'.now()->format('Y_m_d_His').($backupType === 'Database' ? '.sql' : '.zip');

    $backup = \App\Models\SystemBackup::create([
        'name' => $fileName,
        'type' => $backupType,
        'size' => 'Pending',
        'size_bytes' => 0,
        'status' => 'pending',
        'disk' => 'local',
        'file_path' => 'backups/' . $fileName,
        'created_by' => \App\Models\User::first()->id ?? 1,
    ]);
    \App\Jobs\GenerateBackupJob::dispatch($backup->id);
    $this->info("Auto backup job created and dispatched: {$fileName}");
})->purpose('Trigger scheduled auto backup');

\Illuminate\Support\Facades\Schedule::call(function () {
    $autoSchedule = \App\Models\BackupSetting::where('key', 'auto_schedule')->value('value');
    if ($autoSchedule !== 'true' && $autoSchedule !== '1' && $autoSchedule !== true && $autoSchedule !== 1) return;

    $scheduleType = \App\Models\BackupSetting::where('key', 'schedule_type')->value('value') ?? 'daily';
    $scheduleDayOfWeek = strtolower(\App\Models\BackupSetting::where('key', 'schedule_day_of_week')->value('value') ?? 'sunday');
    $scheduleDayOfMonth = (int)(\App\Models\BackupSetting::where('key', 'schedule_day_of_month')->value('value') ?? 1);
    $scheduleSpecificDate = \App\Models\BackupSetting::where('key', 'schedule_specific_date')->value('value');
    $scheduleTime = \App\Models\BackupSetting::where('key', 'schedule_time')->value('value') ?? '02:00';
    $backupType = \App\Models\BackupSetting::where('key', 'schedule_backup_type')->value('value') ?? 'Complete';

    $now = Carbon::now();
    $currentTime = $now->format('H:i');

    // Check if the current time matches configured schedule time
    if ($scheduleTime && $currentTime !== $scheduleTime) {
        return;
    }

    $shouldRun = false;

    if ($scheduleType === 'daily') {
        $shouldRun = true;
    } elseif ($scheduleType === 'weekly') {
        $todayDay = strtolower($now->format('l'));
        if ($todayDay === $scheduleDayOfWeek) {
            $shouldRun = true;
        }
    } elseif ($scheduleType === 'monthly') {
        if ($now->day === $scheduleDayOfMonth || ($scheduleDayOfMonth >= 28 && $now->isLastOfMonth())) {
            $shouldRun = true;
        }
    } elseif ($scheduleType === 'specific_date' && !empty($scheduleSpecificDate)) {
        $lastRunKey = 'schedule_specific_date_last_run_' . $scheduleSpecificDate;
        $alreadyRun = \App\Models\BackupSetting::where('key', $lastRunKey)->value('value');

        if ($now->format('Y-m-d') === $scheduleSpecificDate && !$alreadyRun) {
            $shouldRun = true;
            \App\Models\BackupSetting::updateOrCreate(['key' => $lastRunKey], ['value' => 'completed']);
        }
    }

    if ($shouldRun) {
        $fileName = 'blueboxx_'.strtolower($backupType).'_auto_'.now()->format('Y_m_d_His').($backupType === 'Database' ? '.sql' : '.zip');
        $backup = \App\Models\SystemBackup::create([
            'name' => $fileName,
            'type' => $backupType,
            'size' => 'Pending',
            'size_bytes' => 0,
            'status' => 'pending',
            'disk' => 'local',
            'file_path' => 'backups/' . $fileName,
            'created_by' => \App\Models\User::first()->id ?? 1,
        ]);
        \App\Jobs\GenerateBackupJob::dispatch($backup->id);
    }
})->everyMinute();

\Illuminate\Support\Facades\Schedule::command('app:check-integrations')->dailyAt('00:00');
