@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Subscription Dashboard</span>
                </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Subscription Management Dashboard</h3>
        <!-- END PAGE TITLE -->

        @include('flash::message')

        <!-- Period Selector -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-calendar"></i>
                            <span class="caption-subject font-dark sbold uppercase">Date Range</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => 'yesterday']) }}" class="btn {{ $period == 'yesterday' ? 'btn-success' : 'btn-default' }} btn-sm">Yesterday</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => 'last_month']) }}" class="btn {{ $period == 'last_month' ? 'btn-success' : 'btn-default' }} btn-sm">Last Month</a>
                                <a href="{{ route('admin.subscriptions.dashboard', ['period' => 'this_year']) }}" class="btn {{ $period == 'this_year' ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customRangeSection">
                                    <i class="fa fa-calendar-plus-o"></i> Custom
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>From:</strong> {{ $date_range['start']->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>To:</strong> {{ $date_range['end']->format('d M Y') }}
                            </div>
                        </div>

                        {{-- Custom Range Form --}}
                        <div id="customRangeSection" class="collapse {{ $period == 'custom' ? 'in' : '' }}" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <form id="customRangeForm" action="{{ route('admin.subscriptions.dashboard') }}" method="GET" class="form-inline">
                                <input type="hidden" name="period" value="custom">
                                <div class="form-group margin-right-10">
                                    <label for="start_date" class="margin-right-10">Start Date</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" 
                                               id="start_date" 
                                               name="start_date" 
                                               class="form-control input-sm" 
                                               value="{{ $period == 'custom' ? $date_range['start']->format('Y-m-d') : '' }}"
                                               max="{{ date('Y-m-d') }}"
                                               required>
                                    </div>
                                </div>
                                <div class="form-group margin-right-10">
                                    <label for="end_date" class="margin-right-10">End Date</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" 
                                               id="end_date" 
                                               name="end_date" 
                                               class="form-control input-sm" 
                                               value="{{ $period == 'custom' ? $date_range['end']->format('Y-m-d') : '' }}"
                                               max="{{ date('Y-m-d') }}"
                                               required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fa fa-check"></i> Apply
                                </button>
                                <a href="{{ route('admin.subscriptions.dashboard') }}" class="btn btn-sm btn-default">
                                    <i class="fa fa-times"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $overview['packages']['total'] }}</div>
                        <div class="desc">Total Packages</div>
                        <small>{{ $overview['packages']['active'] }} Active</small>
                    </div>
                    <a class="more" href="{{ route('admin.subscriptions.packages') }}">
                        View Details <i class="m-icon-swapright m-icon-white"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $overview['requests']['total'] }}</div>
                        <div class="desc">Payment Requests</div>
                        <small>{{ $overview['requests']['pending'] }} Pending | {{ $overview['requests']['paid'] }} Paid</small>
                    </div>
                    <a class="more" href="{{ route('admin.subscriptions.requests') }}">
                        View Details <i class="m-icon-swapright m-icon-white"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual">
                        <i class="fa fa-exchange"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $overview['transactions']['total'] }}</div>
                        <div class="desc">Transactions</div>
                        <small>{{ $overview['transactions']['formatted_total_revenue'] }} Total</small>
                    </div>
                    <a class="more" href="{{ route('admin.subscriptions.transactions') }}">
                        View Details <i class="m-icon-swapright m-icon-white"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-building"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $overview['companies']['with_package'] }}</div>
                        <div class="desc">Companies with Package</div>
                        <small>{{ $overview['companies']['adoption_rate'] }}% Adoption Rate</small>
                    </div>
                    <a class="more" href="{{ route('admin.subscriptions.companies') }}">
                        View Details <i class="m-icon-swapright m-icon-white"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Monthly Revenue</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Packages by Revenue</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="packageChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Recent Payment Requests</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.subscriptions.requests') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>User</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_requests as $request)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.subscriptions.requests.view', $request->id) }}">
                                            {{ $request->order_number }}
                                        </a>
                                    </td>
                                    <td>{{ $request->user_name }}</td>
                                    <td>{{ $request->package->package_title ?? 'N/A' }}</td>
                                    <td>₹ {{ number_format($request->amount, 2) }}</td>
                                    <td>
                                        @if($request->status == 'paid')
                                            <span class="label label-success">Paid</span>
                                        @elseif($request->status == 'pending')
                                            <span class="label label-warning">Pending</span>
                                        @elseif($request->status == 'failed')
                                            <span class="label label-danger">Failed</span>
                                        @else
                                            <span class="label label-default">{{ ucfirst($request->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $request->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No recent payment requests</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Recent Transactions</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.subscriptions.transactions') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>User</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_transactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.subscriptions.transactions.view', $transaction->id) }}">
                                            {{ substr($transaction->razorpay_payment_id, 0, 10) }}...
                                        </a>
                                    </td>
                                    <td>{{ $transaction->user_name }}</td>
                                    <td>{{ $transaction->package_title }}</td>
                                    <td>₹ {{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No recent transactions</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Stats -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cubes"></i>
                            <span class="caption-subject font-dark sbold uppercase">Package Performance</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Price</th>
                                    <th>Purchases</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($package_stats as $stat)
                                <tr>
                                    <td>{{ $stat['title'] }}</td>
                                    <td>{{ $stat['formatted_price'] }}</td>
                                    <td>{{ $stat['purchases'] }}</td>
                                    <td>{{ $stat['formatted_revenue'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No package data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
<style>
    .dashboard-stat .details .number { font-size: 28px; }
    .dashboard-stat .details .desc { font-size: 14px; }
    .dashboard-stat .details small { font-size: 11px; color: #fff; opacity: 0.8; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Revenue Chart
    var revenueData = @json($monthly_revenue);
    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.month),
            datasets: [{
                label: 'Revenue (₹)',
                data: revenueData.map(item => item.revenue),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹ ' + value.toLocaleString();
                        }
                    }
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return 'Revenue: ₹ ' + tooltipItem.yLabel.toLocaleString();
                    }
                }
            }
        }
    });

    // Package Chart
    var packageData = @json($top_packages);
    var packageCtx = document.getElementById('packageChart').getContext('2d');
    new Chart(packageCtx, {
        type: 'doughnut',
        data: {
            labels: packageData.map(item => item.title),
            datasets: [{
                data: packageData.map(item => item.revenue),
                backgroundColor: [
                    '#36c6d3', '#5bc0de', '#f0ad4e', '#5cb85c', '#d9534f'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.labels[tooltipItem.index] || '';
                        var value = data.datasets[0].data[tooltipItem.index];
                        return label + ': ₹ ' + value.toLocaleString();
                    }
                }
            }
        }
    });

    // Custom Range Form Submission
    $('#customRangeForm').on('submit', function(e) {
        e.preventDefault();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date');
                return;
            }
            
            var url = $(this).attr('action') + '?period=custom&start_date=' + startDate + '&end_date=' + endDate;
            window.location.href = url;
        }
    });
});
</script>
@endpush