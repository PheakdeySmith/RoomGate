<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\BusinessSetting;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('roomgate:backup-db', function () {
    $connection = config('database.default');
    $db = config("database.connections.{$connection}");
    $backupDir = storage_path('backups/db');
    File::ensureDirectoryExists($backupDir);

    $timestamp = now()->format('Ymd_His');

    if (($db['driver'] ?? null) === 'sqlite') {
        $source = $db['database'] ?? database_path('database.sqlite');
        if (!File::exists($source)) {
            $this->error('SQLite database file not found.');
            return 1;
        }
        $target = $backupDir . DIRECTORY_SEPARATOR . "db_{$timestamp}.sqlite";
        File::copy($source, $target);
        $this->info("SQLite backup created: {$target}");
        return 0;
    }

    if (($db['driver'] ?? null) === 'mysql') {
        $filename = "db_{$timestamp}.sql";
        $target = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $command = [
            'mysqldump',
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? 3306),
            '--user=' . ($db['username'] ?? ''),
            '--password=' . ($db['password'] ?? ''),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $db['database'] ?? '',
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('mysqldump failed: ' . $process->getErrorOutput());
            return 1;
        }

        File::put($target, $process->getOutput());
        $this->info("MySQL backup created: {$target}");
        return 0;
    }

    $meta = [
        'driver' => $db['driver'] ?? 'unknown',
        'database' => $db['database'] ?? null,
        'created_at' => now()->toISOString(),
        'note' => 'No native dump handler for this driver.',
    ];
    $target = $backupDir . DIRECTORY_SEPARATOR . "db_{$timestamp}.json";
    File::put($target, json_encode($meta, JSON_PRETTY_PRINT));
    $this->warn("Fallback DB metadata backup created: {$target}");
    return 0;
})->purpose('Create database backup under storage/backups/db');

Artisan::command('roomgate:backup-uploads', function () {
    $sourceDir = public_path('uploads');
    $targetDir = storage_path('backups/uploads');
    File::ensureDirectoryExists($targetDir);

    if (!File::isDirectory($sourceDir)) {
        $this->warn('Uploads directory not found. Nothing to backup.');
        return 0;
    }

    $timestamp = now()->format('Ymd_His');
    $zipPath = $targetDir . DIRECTORY_SEPARATOR . "uploads_{$timestamp}.zip";

    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        $this->error('Unable to create uploads archive.');
        return 1;
    }

    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir));
    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }
        $path = $file->getRealPath();
        if (!$path) {
            continue;
        }
        $relativePath = Str::replaceFirst($sourceDir . DIRECTORY_SEPARATOR, '', $path);
        $zip->addFile($path, $relativePath);
    }

    $zip->close();
    $this->info("Uploads backup created: {$zipPath}");
    return 0;
})->purpose('Create uploads zip backup under storage/backups/uploads');

Artisan::command('roomgate:backup-all', function () {
    $dbCode = Artisan::call('roomgate:backup-db');
    $this->output->write(Artisan::output());

    $uploadsCode = Artisan::call('roomgate:backup-uploads');
    $this->output->write(Artisan::output());

    return ($dbCode === 0 && $uploadsCode === 0) ? 0 : 1;
})->purpose('Run DB and uploads backups');

Artisan::command('roomgate:cron-heartbeat', function () {
    $settings = BusinessSetting::current();
    $settings->putMetaValue('cron.last_heartbeat_at', now()->toDateTimeString());
    $settings->save();
    Cache::forget('business_settings:current');

    $this->info('Cron heartbeat updated.');
    return 0;
})->purpose('Update cron heartbeat for health monitoring');

Schedule::command('roomgate:cron-heartbeat')->everyMinute();
Schedule::command('rent:generate-invoices')->dailyAt('01:00');
Schedule::command('rent:send-due-soon-reminders')->dailyAt('00:30');
Schedule::command('rent:send-overdue-reminders')->dailyAt('02:00');
Schedule::command('notifications:send-queued')->everyFiveMinutes();
Schedule::command('notifications:retry-failed')->hourly();
Schedule::command('subscriptions:renew')->dailyAt('03:00');
Schedule::command('subscriptions:send-trial-ending')->dailyAt('00:45');
Schedule::command('rooms:reconcile-status')->dailyAt('02:30');
Schedule::command('roomgate:backup-all')->dailyAt('04:00');
