<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('core::dashboard.dashboard');
    }

    public function crmDashboard()
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $prevStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $yearStart = $now->copy()->startOfYear();
        $yearEnd = $now->copy()->endOfYear();
        $year = (int) $now->format('Y');

        $propertiesCount = Property::count();
        $roomsCount = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $activeContracts = Contract::where('status', 'active')->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();

        $rentCollectedThis = (int) Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->sum('paid_cents');
        $rentCollectedPrev = (int) Invoice::whereBetween('issue_date', [$prevStart, $prevEnd])->sum('paid_cents');

        $rentDueCents = (int) Invoice::whereColumn('total_cents', '>', 'paid_cents')
            ->sum(DB::raw('total_cents - paid_cents'));

        $rentChangePct = $rentCollectedPrev > 0
            ? (($rentCollectedThis - $rentCollectedPrev) / $rentCollectedPrev) * 100
            : 0;

        $occupancyRate = $roomsCount > 0 ? ($occupiedRooms / $roomsCount) * 100 : 0;

        $yearInvoices = Invoice::whereBetween('issue_date', [$yearStart, $yearEnd]);
        $paidCents = (clone $yearInvoices)->where('status', 'paid')->sum('total_cents');
        $unpaidCents = (clone $yearInvoices)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum(DB::raw('total_cents - paid_cents'));
        $overdueCents = (clone $yearInvoices)
            ->where('status', 'overdue')
            ->sum(DB::raw('total_cents - paid_cents'));
        $totalCents = (clone $yearInvoices)
            ->whereNotIn('status', ['draft', 'void'])
            ->sum('total_cents');

        $paidCount = (clone $yearInvoices)->where('status', 'paid')->count();
        $unpaidCount = (clone $yearInvoices)->whereIn('status', ['sent', 'partial', 'overdue'])->count();
        $overdueCount = (clone $yearInvoices)->where('status', 'overdue')->count();
        $totalCount = (clone $yearInvoices)->whereNotIn('status', ['draft', 'void'])->count();

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $paidSeries = [];
        $unpaidSeries = [];
        $overdueSeries = [];
        $totalSeries = [];

        foreach (range(1, 6) as $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = Carbon::create($year, $month, 1)->endOfMonth();

            $monthScope = Invoice::whereBetween('issue_date', [$start, $end]);
            $paidSeries[] = (int) (clone $monthScope)->where('status', 'paid')->sum('total_cents');
            $unpaidSeries[] = (int) (clone $monthScope)->whereIn('status', ['sent', 'partial', 'overdue'])
                ->sum(DB::raw('total_cents - paid_cents'));
            $overdueSeries[] = (int) (clone $monthScope)->where('status', 'overdue')
                ->sum(DB::raw('total_cents - paid_cents'));
            $totalSeries[] = (int) (clone $monthScope)->whereNotIn('status', ['draft', 'void'])->sum('total_cents');
        }

        $lastSixStart = $now->copy()->subMonthsNoOverflow(5)->startOfMonth();
        $lastSixLabels = [];
        $newContractsSeries = [];
        $renewalSeries = [];

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $lastSixStart->copy()->addMonths($i)->startOfMonth();
            $monthEnd = $lastSixStart->copy()->addMonths($i)->endOfMonth();
            $lastSixLabels[] = $monthStart->format('M');

            $monthContracts = Contract::whereBetween('start_date', [$monthStart, $monthEnd]);
            $newContractsSeries[] = (int) (clone $monthContracts)->whereNull('previous_contract_id')->count();
            $renewalSeries[] = (int) (clone $monthContracts)->whereNotNull('previous_contract_id')->count();
        }

        return view('core::dashboard.crm-dashboard', [
            'stats' => [
                'properties' => $propertiesCount,
                'rooms' => $roomsCount,
                'occupied_rooms' => $occupiedRooms,
                'active_contracts' => $activeContracts,
                'overdue_invoices' => $overdueInvoices,
                'rent_due_cents' => $rentDueCents,
                'rent_collected_cents' => $rentCollectedThis,
                'rent_change_pct' => $rentChangePct,
                'occupancy_rate' => $occupancyRate,
                'invoice_paid_cents' => (int) $paidCents,
                'invoice_unpaid_cents' => (int) $unpaidCents,
                'invoice_overdue_cents' => (int) $overdueCents,
                'invoice_total_cents' => (int) $totalCents,
                'invoice_paid_count' => $paidCount,
                'invoice_unpaid_count' => $unpaidCount,
                'invoice_overdue_count' => $overdueCount,
                'invoice_total_count' => $totalCount,
                'invoice_chart' => [
                    'labels' => $monthLabels,
                    'paid' => $paidSeries,
                    'unpaid' => $unpaidSeries,
                    'overdue' => $overdueSeries,
                    'total' => $totalSeries,
                ],
                'contracts_chart' => [
                    'labels' => $lastSixLabels,
                    'new' => $newContractsSeries,
                    'renewal' => $renewalSeries,
                ],
            ],
        ]);
    }

    public function accessRoles()
    {
        return view('core::dashboard.app-access-roles');
    }

    public function accessPermission()
    {
        return view('core::dashboard.app-access-permission');
    }

    public function userList()
    {
        return view('core::dashboard.app-user-list');
    }

    public function userViewAccount()
    {
        return view('core::dashboard.app-user-view-account');
    }

    public function userViewBilling()
    {
        return view('core::dashboard.app-user-view-billing');
    }

    public function userViewConnections()
    {
        return view('core::dashboard.app-user-view-connections');
    }

    public function userViewNotifications()
    {
        return redirect()->route('core.notifications.index');
    }

    public function userViewSecurity()
    {
        return view('core::dashboard.app-user-view-security');
    }

    public function invoiceList()
    {
        return view('core::dashboard.app-invoice-list');
    }

    public function invoiceAdd()
    {
        return view('core::dashboard.app-invoice-add');
    }

    public function invoiceEdit()
    {
        return view('core::dashboard.app-invoice-edit');
    }

    public function invoicePreview()
    {
        return view('core::dashboard.app-invoice-preview');
    }

    public function invoicePrint()
    {
        return view('core::dashboard.app-invoice-print');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('core::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('core::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('core::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
