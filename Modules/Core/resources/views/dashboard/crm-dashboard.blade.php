@extends('core::components.layouts.master')
@section('title', 'Dashboard | RoomGate')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @php
    $invoiceChart = $stats['invoice_chart'] ?? ['labels' => [], 'paid' => [], 'unpaid' => [], 'overdue' => [], 'total' => []];
    $contractsChart = $stats['contracts_chart'] ?? ['labels' => [], 'new' => [], 'renewal' => []];
  @endphp
  <script>
    window.RoomGateInvoiceCharts = {
      labels: @json($invoiceChart['labels']),
      paid: @json(array_map(fn ($v) => round($v / 100, 2), $invoiceChart['paid'])),
      unpaid: @json(array_map(fn ($v) => round($v / 100, 2), $invoiceChart['unpaid'])),
      overdue: @json(array_map(fn ($v) => round($v / 100, 2), $invoiceChart['overdue'])),
      total: @json(array_map(fn ($v) => round($v / 100, 2), $invoiceChart['total'])),
    };
    window.RoomGateContractCharts = {
      labels: @json($contractsChart['labels']),
      new: @json($contractsChart['new']),
      renewal: @json($contractsChart['renewal']),
    };
  </script>
              <div class="row g-6">
                @php
                  $rentCollected = number_format(($stats['rent_collected_cents'] ?? 0) / 100, 2);
                  $rentDue = number_format(($stats['rent_due_cents'] ?? 0) / 100, 2);
                  $occupancyRate = number_format($stats['occupancy_rate'] ?? 0, 1);
                  $rentChangePct = number_format($stats['rent_change_pct'] ?? 0, 1);
                  $paidCents = number_format(($stats['invoice_paid_cents'] ?? 0) / 100, 2);
                  $unpaidCents = number_format(($stats['invoice_unpaid_cents'] ?? 0) / 100, 2);
                  $overdueCents = number_format(($stats['invoice_overdue_cents'] ?? 0) / 100, 2);
                  $totalCents = number_format(($stats['invoice_total_cents'] ?? 0) / 100, 2);
                @endphp

                <!-- Properties -->
                <div class="col-xxl-2 col-md-3 col-6">
                  <div class="card h-100">
                    <div class="card-header pb-3">
                      <h5 class="card-title mb-1">Properties</h5>
                      <p class="card-subtitle">Total</p>
                    </div>
                    <div class="card-body">
                      <div id="ordersLastWeek"></div>
                      <div class="d-flex justify-content-between align-items-center gap-3">
                        <h4 class="mb-0">{{ $stats['properties'] ?? 0 }}</h4>
                        <small class="text-success">{{ $occupancyRate }}%</small>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Rooms -->
                <div class="col-xxl-2 col-md-3 col-6">
                  <div class="card h-100">
                    <div class="card-header pb-0">
                      <h5 class="card-title mb-1">Rooms</h5>
                      <p class="card-subtitle">Total</p>
                    </div>
                    <div id="salesLastYear"></div>
                    <div class="card-body pt-0">
                      <div class="d-flex justify-content-between align-items-center mt-3 gap-3">
                        <h4 class="mb-0">{{ $stats['rooms'] ?? 0 }}</h4>
                        <small class="text-success">{{ $stats['occupied_rooms'] ?? 0 }} occupied</small>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Active Leases -->
                <div class="col-xxl-2 col-md-3 col-6">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="badge p-2 bg-label-danger mb-3 rounded">
                        <i class="icon-base ti tabler-credit-card icon-28px"></i>
                      </div>
                      <h5 class="card-title mb-1">Active Leases</h5>
                      <p class="card-subtitle ">Current</p>
                      <p class="text-heading mb-3 mt-1">{{ $stats['active_contracts'] ?? 0 }}</p>
                      <div>
                        <span class="badge bg-label-danger">Rent due ${{ $rentDue }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Overdue Invoices -->
                <div class="col-xxl-2 col-md-3 col-6">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="badge p-2 bg-label-success mb-3 rounded">
                        <i class="icon-base ti tabler-credit-card icon-28px"></i>
                      </div>
                      <h5 class="card-title mb-1">Overdue Invoices</h5>
                      <p class="card-subtitle ">Needs attention</p>
                      <p class="text-heading mb-3 mt-1">{{ $stats['overdue_invoices'] ?? 0 }}</p>
                      <div>
                        <span class="badge bg-label-success">${{ $rentCollected }} collected</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Rent Collected -->
                <div class="col-xxl-4 col-xl-5 col-md-6 col-sm-8 col-12 mb-md-0 order-xxl-0 order-2">
                  <div class="card pb-xxl-3">
                    <div class="card-body row">
                      <div class="d-flex flex-column col-4">
                        <div class="card-title mb-auto">
                          <h5 class="mb-2 text-nowrap">Rent Collected</h5>
                          <p class="mb-0">This month</p>
                        </div>
                        <div class="chart-statistics">
                          <h3 class="card-title mb-1">${{ $rentCollected }}</h3>
                          <span class="badge bg-label-success">{{ $rentChangePct }}%</span>
                        </div>
                      </div>
                      <div id="revenueGrowth" class="col-8"></div>
                    </div>
                  </div>
                </div>

                <!-- Earning Reports Tabs-->
                <div class="col-xxl-8 col-12">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                      <div class="card-title m-0">
                        <h5 class="mb-1">Earning Reports</h5>
                        <p class="card-subtitle">Yearly Invoice Overview</p>
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1"
                          type="button"
                          id="earningReportsTabsId"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsTabsId">
                          <a class="dropdown-item" href="javascript:void(0);">View More</a>
                          <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <ul class="nav nav-tabs widget-nav-tabs pb-8 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
                        <li class="nav-item">
                          <a
                            href="javascript:void(0);"
                            class="nav-link btn active d-flex flex-column align-items-center justify-content-center"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-orders-id"
                            aria-controls="navs-orders-id"
                            aria-selected="true">
                            <div class="badge bg-label-secondary rounded p-2">
                              <i class="icon-base ti tabler-shopping-cart icon-md"></i>
                            </div>
                            <h6 class="tab-widget-title mb-0 mt-2">Paid</h6>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a
                            href="javascript:void(0);"
                            class="nav-link btn d-flex flex-column align-items-center justify-content-center"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-sales-id"
                            aria-controls="navs-sales-id"
                            aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-2">
                              <i class="icon-base ti tabler-chart-bar-popular icon-md"></i>
                            </div>
                            <h6 class="tab-widget-title mb-0 mt-2">Unpaid</h6>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a
                            href="javascript:void(0);"
                            class="nav-link btn d-flex flex-column align-items-center justify-content-center"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-profit-id"
                            aria-controls="navs-profit-id"
                            aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-2">
                              <i class="icon-base ti tabler-currency-dollar icon-md"></i>
                            </div>
                            <h6 class="tab-widget-title mb-0 mt-2">Overdue</h6>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a
                            href="javascript:void(0);"
                            class="nav-link btn d-flex flex-column align-items-center justify-content-center"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-income-id"
                            aria-controls="navs-income-id"
                            aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-2">
                              <i class="icon-base ti tabler-chart-pie-2 icon-md"></i>
                            </div>
                            <h6 class="tab-widget-title mb-0 mt-2">Total</h6>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a
                            href="javascript:void(0);"
                            class="nav-link btn d-flex align-items-center justify-content-center disabled"
                            role="tab"
                            data-bs-toggle="tab"
                            aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-2">
                              <i class="icon-base ti tabler-plus icon-md"></i>
                            </div>
                          </a>
                        </li>
                      </ul>
                      <div class="tab-content p-0 ms-0 ms-sm-2">
                        <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
                          <div id="earningReportsTabsOrders"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-sales-id" role="tabpanel">
                          <div id="earningReportsTabsSales"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
                          <div id="earningReportsTabsProfit"></div>
                        </div>
                        <div class="tab-pane fade" id="navs-income-id" role="tabpanel">
                          <div id="earningReportsTabsIncome"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Sales last 6 months -->
                <div class="col-xl-4 col-md-6 order-xxl-0 order-1">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between pb-4">
                      <div class="card-title mb-0">
                        <h5 class="mb-1">Sales</h5>
                        <p class="card-subtitle">Last 6 Months</p>
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1"
                          type="button"
                          id="salesLastMonthMenu"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="salesLastMonthMenu">
                          <a class="dropdown-item" href="javascript:void(0);">View More</a>
                          <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <div id="salesLastMonth"></div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/js/dashboards-crm.js"></script>
@endpush
