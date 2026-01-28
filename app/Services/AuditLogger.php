<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function log(
        string $action,
        string $modelType,
        string $modelId,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
        ?int $tenantId = null
    ): void {
        $request = $request ?? request();
        $before = $this->sanitizePayload($before);
        $after = $this->sanitizePayload($after);
        $tenantId = $tenantId ?? $this->resolveTenantId($modelType, $modelId, $before, $after);

        $payload = [
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
        ];

        if ($this->auditLogsHaveTenantColumn()) {
            $payload['tenant_id'] = $tenantId;
        }

        AuditLog::create($payload);
    }

    private function sanitizePayload(?array $payload): ?array
    {
        if (!$payload) {
            return $payload;
        }

        return $this->scrubArray($payload);
    }

    private function scrubArray(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $payload[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->scrubArray($value);
            }
        }

        return $payload;
    }

    private function resolveTenantId(string $modelType, string $modelId, ?array $before, ?array $after): ?int
    {
        $tenantId = data_get($after, 'tenant_id') ?? data_get($before, 'tenant_id');
        if ($tenantId) {
            return (int) $tenantId;
        }

        if ($modelType === Tenant::class && is_numeric($modelId)) {
            return (int) $modelId;
        }

        return null;
    }

    private function auditLogsHaveTenantColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('audit_logs', 'tenant_id');
        }

        return $hasColumn;
    }
}
