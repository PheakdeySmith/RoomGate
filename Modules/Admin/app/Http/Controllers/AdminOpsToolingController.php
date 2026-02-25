<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBakongPaymentWebhook;
use App\Models\WebhookEvent;
use App\Support\EnforcesOptionalPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminOpsToolingController extends Controller
{
    use EnforcesOptionalPermission;

    public function index(Request $request)
    {
        $this->enforceOptionalPermission($request, 'ops_tooling.manage');

        $webhookEvents = WebhookEvent::query()
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $failedJobsCount = (int) DB::table('failed_jobs')->count();
        $jobsCount = (int) DB::table('jobs')->count();
        $queueConnection = (string) config('queue.default', 'sync');
        $cacheStore = (string) config('cache.default', 'file');

        $dbBackupDir = storage_path('backups/db');
        $uploadBackupDir = storage_path('backups/uploads');
        $latestDbBackup = $this->latestFileInfo($dbBackupDir);
        $latestUploadBackup = $this->latestFileInfo($uploadBackupDir);

        $healthChecks = [
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'db_ok' => $this->checkDatabase(),
            'cache_ok' => $this->checkCache(),
            'queue_connection' => $queueConnection,
            'failed_jobs_count' => $failedJobsCount,
            'queued_jobs_count' => $jobsCount,
            'scheduler_hint' => 'Run `php artisan schedule:run` every minute in production.',
            'queue_worker_hint' => 'Run `php artisan queue:work` with retries.',
        ];

        return view('admin::dashboard.ops-tooling', compact(
            'webhookEvents',
            'failedJobsCount',
            'jobsCount',
            'queueConnection',
            'cacheStore',
            'latestDbBackup',
            'latestUploadBackup',
            'healthChecks'
        ));
    }

    public function replayWebhook(Request $request, WebhookEvent $webhookEvent): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'ops_tooling.manage');

        if ($webhookEvent->provider === 'bakong') {
            ProcessBakongPaymentWebhook::dispatch($webhookEvent->id);
            $webhookEvent->update(['status' => 'received', 'last_error' => null, 'processed_at' => null]);

            return back()->with('status', 'Bakong webhook replay queued.');
        }

        return back()->with('warning', 'Replay is currently supported for Bakong events only.');
    }

    public function retryFailedJobs(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'ops_tooling.manage');

        Artisan::call('queue:retry', ['id' => 'all']);

        return back()->with('status', 'Failed jobs retry command executed.');
    }

    private function latestFileInfo(string $dir): ?array
    {
        if (!File::isDirectory($dir)) {
            return null;
        }

        $files = collect(File::files($dir))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $file = $files->first();

        return [
            'name' => $file->getFilename(),
            'path' => $file->getPathname(),
            'size_bytes' => $file->getSize(),
            'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('select 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('ops:health:test', 'ok', 5);
            return Cache::get('ops:health:test') === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }
}
