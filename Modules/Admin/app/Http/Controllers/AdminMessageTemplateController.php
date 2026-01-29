<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class AdminMessageTemplateController extends Controller
{
    public function index()
    {
        $templates = MessageTemplate::query()
            ->whereNull('tenant_id')
            ->orderBy('key')
            ->get();

        return view('admin::dashboard.message-templates', compact('templates'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:120',
                Rule::unique('message_templates', 'key')->whereNull('tenant_id'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'in:email,sms,whatsapp,push'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template = MessageTemplate::create([
            'tenant_id' => null,
            'key' => $validated['key'],
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $auditLogger->log('created', MessageTemplate::class, (string) $template->id, null, $template->toArray(), $request);

        return back()->with('status', 'Template created.');
    }

    public function update(Request $request, MessageTemplate $template, AuditLogger $auditLogger): RedirectResponse
    {
        if ($template->tenant_id !== null) {
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

        $auditLogger->log('updated', MessageTemplate::class, (string) $template->id, $before, $template->toArray(), $request);

        return back()->with('status', 'Template updated.');
    }

    public function destroy(Request $request, MessageTemplate $template, AuditLogger $auditLogger): RedirectResponse
    {
        if ($template->tenant_id !== null) {
            abort(404);
        }

        $before = $template->toArray();
        $template->delete();
        $auditLogger->log('deleted', MessageTemplate::class, (string) $template->id, $before, null, $request);

        return back()->with('status', 'Template deleted.');
    }

    public function test(Request $request, MessageTemplate $template, NotificationService $notifications, AuditLogger $auditLogger): RedirectResponse
    {
        if ($template->tenant_id !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if ($template->channel !== 'email') {
            return back()->with('warning', 'Only email templates can be tested right now.');
        }

        $data = $this->sampleData($validated['email']);
        $message = $notifications->queue($template->key, null, null, $data, [
            'to_address' => $validated['email'],
            'channel' => 'email',
            'dedupe_key' => 'template-test-'.$template->id.'-'.md5($validated['email']),
        ]);

        $auditLogger->log('sent_test', MessageTemplate::class, (string) $template->id, null, [
            'to' => $validated['email'],
            'message_id' => $message?->id,
        ], $request);

        return back()->with('status', 'Test message queued.');
    }

    private function sampleData(string $email): array
    {
        return [
            'recipient_name' => $email,
            'tenant_name' => 'Sample Tenant',
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
