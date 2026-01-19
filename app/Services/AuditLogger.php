<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        ?Request $request = null
    ): void {
        $request = $request ?? request();
        $before = $this->sanitizePayload($before);
        $after = $this->sanitizePayload($after);

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
}
