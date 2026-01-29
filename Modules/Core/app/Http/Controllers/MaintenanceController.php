<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceComment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceStatusEvent;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AuditLogger;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class MaintenanceController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [MaintenanceRequest::class, $tenant->id]);

        $requests = MaintenanceRequest::query()
            ->with(['createdBy', 'assignedTo', 'property', 'room'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('requested_at')
            ->get();

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('room_number')
            ->get();

        $members = User::query()
            ->whereHas('tenants', function ($query) use ($tenant) {
                $query->where('tenants.id', $tenant->id)
                    ->whereIn('tenant_users.role', ['owner', 'admin', 'staff'])
                    ->where('tenant_users.status', 'active');
            })
            ->orderBy('name')
            ->get();

        return view('core::dashboard.maintenance', compact('requests', 'properties', 'rooms', 'members'));
    }

    public function show(string $tenant, MaintenanceRequest $maintenanceRequest, CurrentTenant $currentTenant)
    {
        $current = $currentTenant->getOrFail();
        if ((int) $maintenanceRequest->tenant_id !== (int) $current->id) {
            abort(404);
        }

        $this->authorize('view', $maintenanceRequest);

        $maintenanceRequest->load([
            'createdBy',
            'assignedTo',
            'property',
            'room',
            'statusEvents' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'comments' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'attachments' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'workOrders' => function ($query) {
                $query->orderByDesc('id');
            },
            'statusEvents.changedBy',
            'comments.user',
        ]);

        return view('core::dashboard.maintenance-show', [
            'tenant' => $current,
            'requestModel' => $maintenanceRequest,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger, InAppNotificationService $inApp, NotificationService $notifications, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [MaintenanceRequest::class, $tenant->id]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('tenant_id', $tenant->id)],
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id)],
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('tenant_users', 'user_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereIn('role', ['owner', 'admin', 'staff'])
                        ->where('status', 'active');
                }),
            ],
        ]);

        $requestModel = DB::transaction(function () use ($validated, $tenant, $auditLogger, $request) {
            $payload = array_merge($validated, [
                'tenant_id' => $tenant->id,
                'created_by_user_id' => $request->user()->id,
                'status' => 'open',
                'requested_at' => now(),
            ]);

            $model = MaintenanceRequest::create($payload);

            MaintenanceStatusEvent::create([
                'tenant_id' => $tenant->id,
                'maintenance_request_id' => $model->id,
                'changed_by_user_id' => $request->user()->id,
                'from_status' => null,
                'to_status' => 'open',
                'note' => 'Request created',
                'created_at' => now(),
            ]);

            $auditLogger->log('created', MaintenanceRequest::class, (string) $model->id, null, $model->toArray(), $request, $tenant->id);

            return $model;
        });

        $admins = $tenant->users()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->wherePivot('status', 'active')
            ->get();

        foreach ($admins as $admin) {
            $inApp->create($admin, 'New maintenance request', $requestModel->title, [
                'tenant_id' => $tenant->id,
                'type' => 'info',
                'icon' => 'tabler-tool',
                'link_url' => route('core.maintenance.index', ['tenant' => $tenant->slug]),
                'dedupe_key' => 'maintenance-request-created-'.$requestModel->id.'-user-'.$admin->id,
            ]);

            $notifications->queue('maintenance_request_created', $tenant, $admin, [
                'tenant_name' => $tenant->name,
                'request_id' => $requestModel->id,
                'title' => $requestModel->title,
                'priority' => $requestModel->priority,
                'status' => $requestModel->status,
            ], [
                'to_address' => $admin->email,
                'channel' => 'email',
                'dedupe_key' => 'maintenance-request-created-email-'.$requestModel->id.'-user-'.$admin->id,
                'metadata' => ['maintenance_request_id' => $requestModel->id],
            ]);
        }

        return back()->with('status', "Maintenance request #{$requestModel->id} created.");
    }

    public function update(Request $request, string $tenant, MaintenanceRequest $maintenanceRequest, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $maintenanceRequest);

        $validated = $request->validate([
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('tenant_users', 'user_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereIn('role', ['owner', 'admin', 'staff'])
                        ->where('status', 'active');
                }),
            ],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $before = $maintenanceRequest->toArray();
        $maintenanceRequest->update($validated);
        $auditLogger->log('updated', MaintenanceRequest::class, (string) $maintenanceRequest->id, $before, $maintenanceRequest->toArray(), $request, $tenant->id);

        return back()->with('status', 'Maintenance request updated.');
    }

    public function updateStatus(Request $request, string $tenant, MaintenanceRequest $maintenanceRequest, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $maintenanceRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed,cancelled'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $maintenanceRequest->toArray();
        $fromStatus = $maintenanceRequest->status;
        $toStatus = $validated['status'];

        $updates = ['status' => $toStatus];
        if ($fromStatus === 'open' && $toStatus === 'in_progress' && !$maintenanceRequest->first_response_at) {
            $updates['first_response_at'] = now();
        }
        if ($toStatus === 'resolved') {
            $updates['resolved_at'] = now();
        }
        if ($toStatus === 'closed') {
            $updates['closed_at'] = now();
        }

        DB::transaction(function () use ($maintenanceRequest, $updates, $tenant, $validated, $fromStatus, $auditLogger, $request) {
            $maintenanceRequest->update($updates);

            MaintenanceStatusEvent::create([
                'tenant_id' => $tenant->id,
                'maintenance_request_id' => $maintenanceRequest->id,
                'changed_by_user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'created_at' => now(),
            ]);

            $auditLogger->log('updated', MaintenanceRequest::class, (string) $maintenanceRequest->id, $fromStatus ? ['status' => $fromStatus] : null, ['status' => $validated['status']], $request, $tenant->id);
        });

        $this->notifyStatusChange($maintenanceRequest, $tenant, $validated['status'], $request);

        return back()->with('status', 'Maintenance status updated.');
    }

    public function comment(Request $request, string $tenant, MaintenanceRequest $maintenanceRequest, AuditLogger $auditLogger, CurrentTenant $currentTenant, InAppNotificationService $inApp, NotificationService $notifications): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $maintenanceRequest);

        $validated = $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $comment = MaintenanceComment::create([
            'tenant_id' => $tenant->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_internal' => (bool) ($validated['is_internal'] ?? false),
            'created_at' => now(),
        ]);

        $auditLogger->log('created', MaintenanceComment::class, (string) $comment->id, null, $comment->toArray(), $request, $tenant->id);

        $this->notifyComment($maintenanceRequest, $comment, $tenant, $inApp, $notifications, $request);

        return back()->with('status', 'Comment added.');
    }

    public function attachment(Request $request, string $tenant, MaintenanceRequest $maintenanceRequest, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $maintenanceRequest);

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:5120'],
            'comment_id' => ['nullable', Rule::exists('maintenance_comments', 'id')->where('maintenance_request_id', $maintenanceRequest->id)],
        ]);
        $validator->validate();

        $file = $request->file('file');
        $filename = uniqid('maintenance_', true).'.'.$file->getClientOriginalExtension();
        $baseDir = public_path('uploads/private/maintenance/'.$tenant->id.'/'.$maintenanceRequest->id);
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        $file->move($baseDir, $filename);
        $path = 'uploads/private/maintenance/'.$tenant->id.'/'.$maintenanceRequest->id.'/'.$filename;

        $attachment = MaintenanceAttachment::create([
            'tenant_id' => $tenant->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'comment_id' => $request->integer('comment_id') ?: null,
            'uploaded_by_user_id' => $request->user()->id,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size_bytes' => $file->getSize(),
            'created_at' => now(),
        ]);

        $auditLogger->log('uploaded', MaintenanceAttachment::class, (string) $attachment->id, null, $attachment->toArray(), $request, $tenant->id);

        return back()->with('status', 'Attachment uploaded.');
    }

    public function storeWorkOrder(Request $request, string $tenant, MaintenanceRequest $maintenanceRequest, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $current = $currentTenant->getOrFail();
        $this->authorize('update', $maintenanceRequest);

        $validated = $request->validate([
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'scheduled_for' => ['nullable', 'date'],
            'cost_cents' => ['nullable', 'integer', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['required', 'in:created,scheduled,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $current->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'vendor_name' => $validated['vendor_name'] ?? null,
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'completed_at' => ($validated['status'] ?? '') === 'completed' ? now() : null,
            'cost_cents' => $validated['cost_cents'] ?? null,
            'currency_code' => $validated['currency_code'] ?? ($current->default_currency ?? 'USD'),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $auditLogger->log('created', WorkOrder::class, (string) $workOrder->id, null, $workOrder->toArray(), $request, $current->id);

        return back()->with('status', 'Work order created.');
    }

    private function notifyStatusChange(MaintenanceRequest $requestModel, $tenant, string $status, Request $request): void
    {
        $recipients = collect();
        $creator = $requestModel->createdBy;
        if ($creator) {
            $recipients->push($creator);
        }
        if ($requestModel->assignedTo) {
            $recipients->push($requestModel->assignedTo);
        }
        $admins = $tenant->users()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->wherePivot('status', 'active')
            ->get();
        $recipients = $recipients->merge($admins)->unique('id');

        $inApp = app(InAppNotificationService::class);
        $notifications = app(NotificationService::class);

        foreach ($recipients as $user) {
            $inApp->create($user, 'Maintenance status updated', $requestModel->title, [
                'tenant_id' => $tenant->id,
                'type' => 'info',
                'icon' => 'tabler-tool',
                'link_url' => route('core.maintenance.show', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel->id]),
                'dedupe_key' => 'maintenance-status-'.$requestModel->id.'-'.$status.'-user-'.$user->id,
            ]);

            $notifications->queue('maintenance_request_status_changed', $tenant, $user, [
                'tenant_name' => $tenant->name,
                'request_id' => $requestModel->id,
                'title' => $requestModel->title,
                'status' => $status,
            ], [
                'to_address' => $user->email,
                'channel' => 'email',
                'dedupe_key' => 'maintenance-status-email-'.$requestModel->id.'-'.$status.'-user-'.$user->id,
                'metadata' => ['maintenance_request_id' => $requestModel->id],
            ]);
        }
    }

    private function notifyComment(MaintenanceRequest $requestModel, MaintenanceComment $comment, $tenant, InAppNotificationService $inApp, NotificationService $notifications, Request $request): void
    {
        $recipients = collect();
        $creator = $requestModel->createdBy;
        if ($creator) {
            $recipients->push($creator);
        }
        if ($requestModel->assignedTo) {
            $recipients->push($requestModel->assignedTo);
        }
        $admins = $tenant->users()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->wherePivot('status', 'active')
            ->get();
        $recipients = $recipients->merge($admins)->unique('id');

        foreach ($recipients as $user) {
            $inApp->create($user, 'Maintenance comment added', $requestModel->title, [
                'tenant_id' => $tenant->id,
                'type' => 'info',
                'icon' => 'tabler-message',
                'link_url' => route('core.maintenance.show', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel->id]),
                'dedupe_key' => 'maintenance-comment-'.$requestModel->id.'-'.$comment->id.'-user-'.$user->id,
            ]);

            $notifications->queue('maintenance_request_comment_added', $tenant, $user, [
                'tenant_name' => $tenant->name,
                'request_id' => $requestModel->id,
                'title' => $requestModel->title,
                'comment' => $comment->body,
            ], [
                'to_address' => $user->email,
                'channel' => 'email',
                'dedupe_key' => 'maintenance-comment-email-'.$requestModel->id.'-'.$comment->id.'-user-'.$user->id,
                'metadata' => ['maintenance_request_id' => $requestModel->id],
            ]);
        }
    }
}
