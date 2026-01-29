<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAttachment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Modules\Core\App\Services\CurrentTenant;

class MaintenanceAttachmentController extends Controller
{
    public function show(Request $request, string $tenant, MaintenanceAttachment $attachment, CurrentTenant $currentTenant, AuditLogger $auditLogger)
    {
        $current = $currentTenant->getOrFail();
        if ((int) $attachment->tenant_id !== (int) $current->id) {
            abort(404);
        }

        $path = public_path($attachment->file_path);
        if (!is_file($path)) {
            abort(404);
        }

        $auditLogger->log(
            'downloaded',
            MaintenanceAttachment::class,
            (string) $attachment->id,
            null,
            ['path' => $attachment->file_path, 'size' => $attachment->file_size_bytes],
            $request,
            $current->id
        );

        return response()->download($path);
    }
}
