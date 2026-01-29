<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class TemplateController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $this->requireTemplateManager($currentTenant);

        $templates = MessageTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('key')
            ->get();

        return view('core::dashboard.message-templates', compact('templates'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireTemplateManager($currentTenant);

        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:120',
                Rule::unique('message_templates', 'key')->where('tenant_id', $tenant->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'in:email,sms,whatsapp,push'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template = MessageTemplate::create([
            'tenant_id' => $tenant->id,
            'key' => $validated['key'],
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $auditLogger->log('created', MessageTemplate::class, (string) $template->id, null, $template->toArray(), $request, $tenant->id);

        return back()->with('status', 'Template created.');
    }

    public function update(Request $request, string $tenant, MessageTemplate $template, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $current = $this->requireTemplateManager($currentTenant);
        if ((int) $template->tenant_id !== (int) $current->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'in:email,sms,whatsapp,push'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $before = $template->toArray();
        $template->update([
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $auditLogger->log('updated', MessageTemplate::class, (string) $template->id, $before, $template->toArray(), $request, $current->id);

        return back()->with('status', 'Template updated.');
    }

    public function destroy(Request $request, string $tenant, MessageTemplate $template, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $current = $this->requireTemplateManager($currentTenant);
        if ((int) $template->tenant_id !== (int) $current->id) {
            abort(404);
        }

        $before = $template->toArray();
        $template->delete();
        $auditLogger->log('deleted', MessageTemplate::class, (string) $template->id, $before, null, $request, $current->id);

        return back()->with('status', 'Template deleted.');
    }

    public function test(Request $request, string $tenant, MessageTemplate $template, NotificationService $notifications, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $current = $this->requireTemplateManager($currentTenant);
        if ((int) $template->tenant_id !== (int) $current->id) {
            abort(404);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if ($template->channel !== 'email') {
            return back()->with('warning', 'Only email templates can be tested right now.');
        }

        $data = $this->sampleData($validated['email'], $current->name);
        $message = $notifications->queue($template->key, $current, null, $data, [
            'to_address' => $validated['email'],
            'channel' => 'email',
            'dedupe_key' => 'template-test-'.$template->id.'-'.md5($validated['email']),
        ]);

        $auditLogger->log('sent_test', MessageTemplate::class, (string) $template->id, null, [
            'to' => $validated['email'],
            'message_id' => $message?->id,
        ], $request, $current->id);

        return back()->with('status', 'Test message queued.');
    }

    private function requireTemplateManager(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $role = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->value('role');

        if (! in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }

        return $tenant;
    }

    private function sampleData(string $email, string $tenantName): array
    {
        return [
            'recipient_name' => $email,
            'tenant_name' => $tenantName,
            'request_id' => 123,
            'title' => 'Sample request',
            'priority' => 'medium',
            'status' => 'open',
            'comment' => 'Sample comment',
            'invoice_number' => 'INV-2026-0001',
            'amount' => '99.00',
            'currency' => 'USD',
            'period_end' => now()->addMonth()->format('Y-m-d'),
            'trial_end' => now()->addDays(7)->format('Y-m-d'),
            'grace_end' => now()->addDays(7)->format('Y-m-d'),
        ];
    }
}
