<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $action,
        string $modelType,
        string $modelId,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null
    ): void {
        $request = $request ?? request();

        AuditLog::create([
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'before_json' => $before,
            'after_json' => $after,
            'user_id' => Auth::id(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }
}
