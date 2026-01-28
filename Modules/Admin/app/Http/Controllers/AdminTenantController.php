<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\InAppNotification;
use App\Models\OutboundMessage;
use App\Models\AuditLog;
use App\Models\Property;
use App\Models\Room;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\UtilityProvider;
use App\Models\UtilityMeter;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\InAppNotificationService;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTenantController extends Controller
{
    public function index()
    {
        $plans = Plan::query()->orderBy('name')->get();

        return view('admin::dashboard.app-tenant-list', [
            'plans' => $plans,
        ]);
    }

    public function show(Tenant $tenant)
    {
        return redirect()->route('admin.tenants.account', $tenant);
    }

    public function account(Tenant $tenant, PlanGate $planGate)
    {
        $context = $this->tenantContext($tenant, $planGate);

        return view('admin::dashboard.tenant-view-account', $context);
    }

    public function security(Tenant $tenant, PlanGate $planGate)
    {
        $context = $this->tenantContext($tenant, $planGate);
        return view('admin::dashboard.tenant-view-security', $context);
    }

    public function billing(Tenant $tenant, PlanGate $planGate)
    {
        $context = $this->tenantContext($tenant, $planGate);

        $context['subscriptionInvoices'] = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $context['subscriptionPayments'] = SubscriptionPayment::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin::dashboard.tenant-view-billing', $context);
    }

    public function notifications(Tenant $tenant, PlanGate $planGate)
    {
        $context = $this->tenantContext($tenant, $planGate);

        $context['outboundMessages'] = OutboundMessage::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $context['inAppNotifications'] = InAppNotification::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin::dashboard.tenant-view-notifications', $context);
    }

    public function connections(Tenant $tenant, PlanGate $planGate)
    {
        $context = $this->tenantContext($tenant, $planGate);

        $context['utilityProviders'] = UtilityProvider::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->limit(50)
            ->get();

        $context['utilityMeters'] = UtilityMeter::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin::dashboard.tenant-view-connections', $context);
    }

    public function membersData(Request $request, Tenant $tenant): JsonResponse
    {
        $query = $tenant->users();

        $total = $query->count();
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('tenant_users.role', 'like', "%{$search}%")
                    ->orWhere('tenant_users.status', 'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $orderColumn = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir = data_get($request->all(), 'order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderMap = [
            1 => 'users.name',
            2 => 'tenant_users.role',
            3 => 'tenant_users.status',
        ];
        if (isset($orderMap[$orderColumn])) {
            $query->orderBy($orderMap[$orderColumn], $orderDir);
        } else {
            $query->orderBy('users.name');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $members = $query->skip($start)->take($length)->get();

        $statusMap = [
            'active' => 'bg-label-success',
            'invited' => 'bg-label-warning',
            'disabled' => 'bg-label-secondary',
        ];

        $data = $members->map(function (User $member) use ($tenant, $statusMap) {
            $statusClass = $statusMap[$member->pivot->status] ?? 'bg-label-secondary';
            $toggleLabel = $member->pivot->status === 'disabled' ? 'Enable' : 'Disable';
            $toggleIcon = $member->pivot->status === 'disabled' ? 'tabler-player-play' : 'tabler-player-stop';
            $toggleConfirm = $member->pivot->status === 'disabled'
                ? 'Enable this member?'
                : 'Disable this member?';

            $toggleForm = '<form method="POST" action="' . route('admin.tenants.members.toggle-status', [$tenant, $member]) . '" data-confirm="' . e($toggleConfirm) . '" class="d-inline">'
                . csrf_field()
                . '<button type="submit" class="btn btn-icon btn-text-warning rounded-pill waves-effect" title="' . $toggleLabel . '">'
                . '<i class="icon-base ti ' . $toggleIcon . ' icon-md"></i>'
                . '</button></form>';

            $resetForm = '<form method="POST" action="' . route('admin.tenants.members.reset-password', [$tenant, $member]) . '" data-confirm="Send password reset email?" class="d-inline">'
                . csrf_field()
                . '<button type="submit" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="Reset Password">'
                . '<i class="icon-base ti tabler-key icon-md"></i>'
                . '</button></form>';

            $editButton = '<button class="btn btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="modal" data-bs-target="#editMemberModal" data-member-id="' . $member->id . '" data-member-name="' . e($member->name) . '" data-member-email="' . e($member->email) . '" data-member-role="' . e($member->pivot->role) . '" data-member-status="' . e($member->pivot->status) . '">'
                . '<i class="icon-base ti tabler-edit icon-md"></i>'
                . '</button>';

            $deleteForm = '<form method="POST" action="' . route('admin.tenants.members.destroy', [$tenant, $member]) . '" data-confirm="Remove this member?" class="d-inline">'
                . csrf_field()
                . method_field('DELETE')
                . '<button type="submit" class="btn btn-icon btn-text-danger rounded-pill waves-effect" title="Remove">'
                . '<i class="icon-base ti tabler-trash icon-md"></i>'
                . '</button></form>';

            return [
                '',
                '<div class="d-flex flex-column"><span class="text-heading">' . e($member->name) . '</span><small class="text-body-secondary">' . e($member->email) . '</small></div>',
                ucfirst($member->pivot->role),
                '<span class="badge ' . $statusClass . '">' . ucfirst($member->pivot->status) . '</span>',
                '<div class="d-flex align-items-center">' . $editButton . $toggleForm . $resetForm . $deleteForm . '</div>',
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function exportMembers(Tenant $tenant)
    {
        $members = $tenant->users()
            ->select('users.name', 'users.email', 'tenant_users.role', 'tenant_users.status')
            ->orderBy('users.name')
            ->get();

        $filename = 'tenant-' . $tenant->id . '-members.csv';

        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Role', 'Status']);
            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->name,
                    $member->email,
                    $member->pivot->role,
                    $member->pivot->status,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function invoicesData(Request $request, Tenant $tenant): JsonResponse
    {
        $query = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->select('id', 'invoice_number', 'status', 'total_cents', 'currency_code', 'due_date');

        $total = $query->count();
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $orderColumn = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir = data_get($request->all(), 'order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderMap = [
            1 => 'invoice_number',
            2 => 'status',
            3 => 'total_cents',
            4 => 'due_date',
        ];
        if (isset($orderMap[$orderColumn])) {
            $query->orderBy($orderMap[$orderColumn], $orderDir);
        } else {
            $query->orderByDesc('created_at');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $invoices = $query->skip($start)->take($length)->get();

        $statusMap = [
            'draft' => 'bg-label-secondary',
            'sent' => 'bg-label-info',
            'paid' => 'bg-label-success',
            'partial' => 'bg-label-warning',
            'overdue' => 'bg-label-danger',
            'void' => 'bg-label-secondary',
        ];

        $data = $invoices->map(function (Invoice $invoice) use ($statusMap, $tenant) {
            $statusClass = $statusMap[$invoice->status] ?? 'bg-label-secondary';
            $amount = number_format(($invoice->total_cents ?? 0) / 100, 2);
            $currency = $invoice->currency_code ?: ($tenant->default_currency ?? 'USD');

            $viewUrl = route('admin.invoices.index', ['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id]);
            $printUrl = route('admin.invoices.print', $invoice);

            $markPaidForm = '';
            if ($invoice->status !== 'paid') {
                $markPaidForm = '<form method="POST" action="' . route('admin.invoices.update-status', $invoice) . '" data-confirm="Mark this invoice as paid?" class="d-inline">'
                    . csrf_field()
                    . method_field('PATCH')
                    . '<input type="hidden" name="status" value="paid" />'
                    . '<button type="submit" class="btn btn-icon btn-text-success rounded-pill waves-effect" title="Mark Paid">'
                    . '<i class="icon-base ti tabler-check icon-md"></i>'
                    . '</button></form>';
            }

            $actions = '<a href="' . $viewUrl . '" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="View">'
                . '<i class="icon-base ti tabler-eye icon-md"></i>'
                . '</a>'
                . '<a href="' . $printUrl . '" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="Print" target="_blank" rel="noopener">'
                . '<i class="icon-base ti tabler-printer icon-md"></i>'
                . '</a>'
                . $markPaidForm;

            return [
                '',
                e($invoice->invoice_number),
                '<span class="badge ' . $statusClass . '">' . ucfirst($invoice->status) . '</span>',
                $amount . ' ' . e($currency),
                optional($invoice->due_date)->format('Y-m-d') ?? '-',
                $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function exportInvoices(Tenant $tenant)
    {
        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'tenant-' . $tenant->id . '-invoices.csv';

        return response()->streamDownload(function () use ($invoices, $tenant) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice #', 'Status', 'Total', 'Paid', 'Balance', 'Due Date']);
            foreach ($invoices as $invoice) {
                $currency = $invoice->currency_code ?: ($tenant->default_currency ?? 'USD');
                $total = ($invoice->total_cents ?? 0) / 100;
                $paid = ($invoice->paid_cents ?? 0) / 100;
                $balance = ($invoice->total_cents ?? 0) - ($invoice->paid_cents ?? 0);
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->status,
                    $currency . ' ' . number_format($total, 2),
                    $currency . ' ' . number_format($paid, 2),
                    $currency . ' ' . number_format($balance / 100, 2),
                    optional($invoice->due_date)->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function activityData(Request $request, Tenant $tenant): JsonResponse
    {
        $query = AuditLog::query()->with('user');
        $query->where(function ($q) use ($tenant) {
            if (Schema::hasColumn('audit_logs', 'tenant_id')) {
                $q->where('tenant_id', $tenant->id);
            }
            $q->orWhere(function ($sub) use ($tenant) {
                $sub->where('model_type', Tenant::class)
                    ->where('model_id', (string) $tenant->id)
                    ->orWhere(function ($nested) use ($tenant) {
                        $nested->where('model_type', 'tenant_users')
                            ->where('model_id', 'like', $tenant->id . ':%');
                    });
            });
        });

        $actionFilter = $request->input('filter_action');
        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        $modelFilter = $request->input('filter_model');
        if ($modelFilter) {
            $modelMap = [
                'tenant' => Tenant::class,
                'tenant_users' => 'tenant_users',
                'property' => Property::class,
                'room' => Room::class,
                'contract' => Contract::class,
                'invoice' => Invoice::class,
                'utility_provider' => UtilityProvider::class,
                'utility_meter' => UtilityMeter::class,
            ];
            if (isset($modelMap[$modelFilter])) {
                $query->where('model_type', $modelMap[$modelFilter]);
            }
        }

        $dateFrom = $request->input('filter_date_from');
        $dateTo = $request->input('filter_date_to');
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $total = $query->count();
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $filtered = $query->count();

        $orderColumn = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir = data_get($request->all(), 'order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderMap = [
            1 => 'action',
            2 => 'model_type',
            3 => 'user_id',
            4 => 'url',
            5 => 'created_at',
        ];
        if (isset($orderMap[$orderColumn])) {
            $query->orderBy($orderMap[$orderColumn], $orderDir);
        } else {
            $query->orderByDesc('created_at');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $logs = $query->skip($start)->take($length)->get();

        $data = $logs->map(function (AuditLog $log) {
            $modelLabel = $log->model_type === 'tenant_users'
                ? 'Tenant Member'
                : class_basename($log->model_type);
            $userLabel = $log->user
                ? e($log->user->name)
                : 'System';

            return [
                '',
                ucfirst($log->action),
                e($modelLabel),
                $userLabel,
                $log->url ? e($log->url) : '-',
                $log->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function exportActivity(Request $request, Tenant $tenant)
    {
        $query = AuditLog::query()->with('user');
        $query->where(function ($q) use ($tenant) {
            if (Schema::hasColumn('audit_logs', 'tenant_id')) {
                $q->where('tenant_id', $tenant->id);
            }
            $q->orWhere(function ($sub) use ($tenant) {
                $sub->where('model_type', Tenant::class)
                    ->where('model_id', (string) $tenant->id)
                    ->orWhere(function ($nested) use ($tenant) {
                        $nested->where('model_type', 'tenant_users')
                            ->where('model_id', 'like', $tenant->id . ':%');
                    });
            });
        });

        $actionFilter = $request->input('action');
        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        $modelFilter = $request->input('model');
        if ($modelFilter) {
            $modelMap = [
                'tenant' => Tenant::class,
                'tenant_users' => 'tenant_users',
                'property' => Property::class,
                'room' => Room::class,
                'contract' => Contract::class,
                'invoice' => Invoice::class,
                'utility_provider' => UtilityProvider::class,
                'utility_meter' => UtilityMeter::class,
            ];
            if (isset($modelMap[$modelFilter])) {
                $query->where('model_type', $modelMap[$modelFilter]);
            }
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->orderByDesc('created_at')->get();
        $filename = 'tenant-' . $tenant->id . '-activity.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['When', 'Action', 'Model', 'User', 'Email', 'URL']);
            foreach ($logs as $log) {
                $modelLabel = $log->model_type === 'tenant_users'
                    ? 'Tenant Member'
                    : class_basename($log->model_type);
                fputcsv($handle, [
                    optional($log->created_at)->format('Y-m-d H:i'),
                    $log->action,
                    $modelLabel,
                    $log->user?->name,
                    $log->user?->email,
                    $log->url,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function data(): JsonResponse
    {
        if (!Schema::hasTable('tenants')) {
            return response()->json(['data' => []]);
        }

        $tenants = Tenant::query()
            ->with([
                'users' => function ($query) {
                    $query->wherePivot('role', 'owner');
                },
                'subscriptions.plan',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Tenant $tenant) {
                $statusMap = [
                    'active' => 2,
                    'suspended' => 3,
                    'closed' => 3,
                ];
                $owner = $tenant->users->first();
                $subscription = $tenant->subscriptions->sortByDesc('current_period_end')->first();
                $planName = $subscription?->plan?->name ?? '—';
                $planId = $subscription?->plan_id;

                return [
                    'id' => $tenant->id,
                    'full_name' => $tenant->name,
                    'email' => $owner?->email ?? ($tenant->slug ? $tenant->slug . '@roomgate.test' : '-'),
                    'avatar' => null,
                    'role' => 'Owner',
                    'current_plan' => $planName,
                    'current_plan_id' => $planId,
                    'billing' => $subscription?->provider ? ucfirst($subscription->provider) : 'Manual',
                    'status' => $statusMap[$tenant->status] ?? 3,
                    'status_raw' => $tenant->status,
                    'action' => '',
                ];
            })
            ->values();

        return response()->json(['data' => $tenants]);
    }

    public function store(Request $request, AuditLogger $auditLogger, InAppNotificationService $inApp): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        return DB::transaction(function () use ($validated, $request, $auditLogger, $inApp) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug ?: Str::random(8);
            $suffix = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $plan = Plan::query()->findOrFail($validated['plan_id']);
            $now = now();
            $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'status' => 'active',
                'default_currency' => $plan->currency_code,
                'timezone' => 'UTC',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['owner_email'],
                'password' => $validated['owner_password'],
                'status' => 'active',
                'platform_role' => 'tenant',
            ]);

            if (method_exists($user, 'assignRole')) {
                $guardName = config('auth.defaults.guard', 'web');
                Role::firstOrCreate([
                    'name' => 'owner',
                    'guard_name' => $guardName,
                ]);
                $user->assignRole('owner');
            }

            DB::table('tenant_users')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
                'provider' => 'manual',
            ]);

            $auditLogger->log('created', Tenant::class, (string) $tenant->id, null, $tenant->toArray(), $request);
            $this->notifyPlatformAdmins($inApp, 'New tenant created', $tenant->name.' was created.');

            return back()->with('status', 'Tenant created.');
        });
    }

    public function update(Request $request, Tenant $tenant, AuditLogger $auditLogger, InAppNotificationService $inApp): RedirectResponse
    {
        $owner = $tenant->users()->wherePivot('role', 'owner')->first();
        $ownerId = $owner?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,suspended,closed'],
            'plan_id' => ['required', 'exists:plans,id'],
            'owner_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $request, $auditLogger, $inApp) {
            $before = $tenant->toArray();

            $tenant->fill([
                'name' => $validated['name'],
                'status' => $validated['status'],
            ]);
            $tenant->save();

            if ($owner) {
                if (!empty($validated['owner_email'])) {
                    $owner->email = $validated['owner_email'];
                }
                if (!empty($validated['owner_password'])) {
                    $owner->password = $validated['owner_password'];
                }
                $owner->save();
            }

            $plan = Plan::query()->findOrFail($validated['plan_id']);
            $now = now();
            $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();
            $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();

            if ($subscription) {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                ]);
            } else {
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'auto_renew' => true,
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                    'provider' => 'manual',
                ]);
            }

            $auditLogger->log('updated', Tenant::class, (string) $tenant->id, $before, $tenant->toArray(), $request);
            if (($before['status'] ?? null) !== $tenant->status) {
                $this->notifyPlatformAdmins(
                    $inApp,
                    'Tenant status changed',
                    $tenant->name.' is now '.ucfirst($tenant->status).'.'
                );
            }

            return back()->with('status', 'Tenant updated.');
        });
    }

    public function destroy(Request $request, Tenant $tenant, AuditLogger $auditLogger, InAppNotificationService $inApp): RedirectResponse
    {
        return DB::transaction(function () use ($tenant, $request, $auditLogger, $inApp) {
            $before = $tenant->toArray();

            $tenant->users()->detach();
            $tenant->subscriptions()->update(['deleted_at' => now()]);
            $tenant->delete();

            $auditLogger->log('deleted', Tenant::class, (string) $tenant->id, $before, null, $request);
            $this->notifyPlatformAdmins($inApp, 'Tenant deleted', $tenant->name.' was deleted.');

            return back()->with('status', 'Tenant deleted.');
        });
    }

    private function notifyPlatformAdmins(InAppNotificationService $inApp, string $title, string $body): void
    {
        if (!method_exists(User::class, 'role')) {
            return;
        }

        $admins = User::role(['platform_admin', 'admin'])->get();
        foreach ($admins as $admin) {
            $inApp->create($admin, $title, $body, [
                'type' => 'info',
                'icon' => 'tabler-bell',
                'link_url' => route('admin.tenants.index'),
            ]);
        }
    }

    public function storeMember(Request $request, Tenant $tenant, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $currentCount = $tenant->users()->count();
        if (! $planGate->canCreate($tenant, 'tenant_users_max', $currentCount)) {
            return back()->withErrors(['plan' => 'This tenant has reached the user limit for their plan.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:owner,admin,staff,tenant'],
            'status' => ['required', 'in:active,invited,disabled'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $request, $auditLogger) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => 'active',
                'platform_role' => 'tenant',
            ]);

            $tenant->users()->attach($user->id, [
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            $auditLogger->log('created', 'tenant_users', (string) $tenant->id . ':' . $user->id, null, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ], $request);

            return back()->with('status', 'Tenant member added.');
        });
    }

    public function updateMember(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $ownerId = $user->id;
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:owner,admin,staff,tenant'],
            'status' => ['required', 'in:active,invited,disabled'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $user, $request, $auditLogger, $pivot) {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ];

            if ($pivot->role === 'owner' && $validated['role'] !== 'owner') {
                $ownerCount = DB::table('tenant_users')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'owner')
                    ->count();
                if ($ownerCount <= 1) {
                    return back()->withErrors(['role' => 'At least one owner is required.']);
                }
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = $validated['password'];
            }
            $user->save();

            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->update([
                    'role' => $validated['role'],
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

            $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, $before, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ], $request);

            return back()->with('status', 'Tenant member updated.');
        });
    }

    public function destroyMember(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            $ownerCount = DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->count();
            if ($ownerCount <= 1) {
                return back()->withErrors(['owner' => 'At least one owner is required.']);
            }
        }

        return DB::transaction(function () use ($tenant, $user, $request, $auditLogger, $pivot) {
            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->delete();

            $auditLogger->log('deleted', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ], null, $request);

            return back()->with('status', 'Tenant member removed.');
        });
    }

    public function updateStatus(Request $request, Tenant $tenant, AuditLogger $auditLogger, InAppNotificationService $inApp): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended,closed'],
        ]);

        return DB::transaction(function () use ($tenant, $validated, $request, $auditLogger, $inApp) {
            $before = $tenant->toArray();
            $tenant->status = $validated['status'];
            $tenant->save();

            $auditLogger->log('updated', Tenant::class, (string) $tenant->id, $before, $tenant->toArray(), $request, $tenant->id);
            if (($before['status'] ?? null) !== $tenant->status) {
                $this->notifyPlatformAdmins(
                    $inApp,
                    'Tenant status changed',
                    $tenant->name.' is now '.ucfirst($tenant->status).'.'
                );
            }

            return back()->with('status', 'Tenant status updated.');
        });
    }

    public function resetOwnerPassword(Request $request, Tenant $tenant, AuditLogger $auditLogger): RedirectResponse
    {
        $owner = $tenant->users()->wherePivot('role', 'owner')->first();
        if (!$owner) {
            return back()->withErrors(['owner' => 'Owner not found.']);
        }

        $status = Password::sendResetLink(['email' => $owner->email]);
        $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $owner->id, [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'action' => 'owner_password_reset_requested',
        ], null, $request);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Owner reset link sent.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function toggleMemberStatus(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        $newStatus = $pivot->status === 'disabled' ? 'active' : 'disabled';

        if ($pivot->role === 'owner' && $newStatus === 'disabled') {
            $activeOwners = DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->where('status', 'active')
                ->count();
            if ($activeOwners <= 1) {
                return back()->withErrors(['owner' => 'At least one active owner is required.']);
            }
        }

        return DB::transaction(function () use ($tenant, $user, $request, $auditLogger, $pivot, $newStatus) {
            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

            $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ], [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $newStatus,
            ], $request);

            return back()->with('status', 'Member status updated.');
        });
    }

    public function resetMemberPassword(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'password_reset_requested',
        ], null, $request);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Password reset link sent.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    private function tenantContext(Tenant $tenant, PlanGate $planGate): array
    {
        $tenant->load([
            'users' => function ($query) {
                $query->wherePivot('role', 'owner')->orderBy('name');
            },
            'subscriptions.plan',
        ]);

        $owner = $tenant->users->first();
        $subscription = $planGate->getActiveSubscription($tenant);
        $plan = $subscription?->plan;
        $planLimits = $planGate->getPlanLimits($subscription);

        $stats = [
            'properties' => Property::query()->where('tenant_id', $tenant->id)->count(),
            'rooms' => Room::query()->where('tenant_id', $tenant->id)->count(),
            'contracts' => Contract::query()->where('tenant_id', $tenant->id)->where('status', 'active')->count(),
            'open_invoices' => Invoice::query()->where('tenant_id', $tenant->id)->whereIn('status', ['sent', 'overdue'])->count(),
        ];
        $invoiceTotals = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('COALESCE(SUM(total_cents),0) as total_cents, COALESCE(SUM(paid_cents),0) as paid_cents')
            ->first();
        $overdueBalance = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->selectRaw('COALESCE(SUM(COALESCE(total_cents,0) - COALESCE(paid_cents,0)),0) as balance')
            ->value('balance');
        $occupancyRate = $stats['rooms'] > 0
            ? round(($stats['contracts'] / $stats['rooms']) * 100, 1)
            : 0;

        return [
            'tenant' => $tenant,
            'owner' => $owner,
            'subscription' => $subscription,
            'plan' => $plan,
            'planLimits' => $planLimits,
            'stats' => $stats,
            'kpis' => [
                'total_invoiced_cents' => (int) ($invoiceTotals->total_cents ?? 0),
                'total_paid_cents' => (int) ($invoiceTotals->paid_cents ?? 0),
                'overdue_balance_cents' => (int) ($overdueBalance ?? 0),
                'occupancy_rate' => $occupancyRate,
            ],
        ];
    }
}
