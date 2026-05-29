@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Subscription Reports</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Subscription Reports</h3>

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
                                <a href="{{ route('admin.subscriptions.reports', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => 'yesterday']) }}" class="btn {{ $period == 'yesterday' ? 'btn-success' : 'btn-default' }} btn-sm">Yesterday</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => 'last_month']) }}" class="btn {{ $period == 'last_month' ? 'btn-success' : 'btn-default' }} btn-sm">Last Month</a>
                                <a href="{{ route('admin.subscriptions.reports', ['period' => 'this_year']) }}" class="btn {{ $period == 'this_year' ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-download"></i>
                            <span class="caption-subject font-dark sbold uppercase">Export Reports</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <a href="{{ route('admin.subscriptions.reports.export') }}?period={{ $period }}&type=revenue" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Export Revenue Report
                        </a>
                        <a href="{{ route('admin.subscriptions.reports.export') }}?period={{ $period }}&type=packages" class="btn btn-info">
                            <i class="fa fa-file-excel-o"></i> Export Package Performance
                        </a>
                        <a href="{{ route('admin.subscriptions.reports.export') }}?period={{ $period }}&type=subscriptions" class="btn btn-warning">
                            <i class="fa fa-file-excel-o"></i> Export Subscription Trends
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Report -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-money"></i>
                            <span class="caption-subject font-dark sbold uppercase">Revenue Summary</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="well well-sm text-center">
                                    <h3>{{ $revenue_data['formatted_total_revenue'] }}</h3>
                                    <p>Total Revenue</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="well well-sm text-center">
                                    <h3>{{ $revenue_data['total_transactions'] }}</h3>
                                    <p>Total Transactions</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="well well-sm text-center">
                                    <h3>{{ $revenue_data['formatted_avg_transaction_value'] }}</h3>
                                    <p>Average Transaction Value</p>
                                </div>
                            </div>
                        </div>

                        <h4>Revenue by Payment Method</h4>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Transactions</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revenue_data['by_method'] as $method)
                                <tr>
                                    <td>{{ ucfirst($method['method']) }}</td>
                                    <td>{{ $method['count'] }}</td>
                                    <td>{{ $method['formatted_total'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <h4>Daily Revenue</h4>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revenue_data['daily'] as $day)
                                <tr>
                                    <td>{{ $day['date'] }}</td>
                                    <td>{{ $day['formatted_revenue'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Performance -->
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
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Price</th>
                                    <th>Requests</th>
                                    <th>Paid</th>
                                    <th>Conversion Rate</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($package_performance as $package)
                                <tr>
                                    <td>{{ $package['title'] }}</td>
                                    <td>{{ $package['formatted_price'] }}</td>
                                    <td>{{ $package['requests'] }}</td>
                                    <td>{{ $package['paid'] }}</td>
                                    <td>{{ $package['conversion_rate'] }}%</td>
                                    <td>{{ $package['formatted_revenue'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Trends -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Subscription Trends</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>New Subscriptions</th>
                                    <th>Expired Subscriptions</th>
                                    <th>Active Subscriptions</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscription_trends as $trend)
                                <tr>
                                    <td>{{ $trend['date'] }}</td>
                                    <td>{{ $trend['new_subscriptions'] }}</td>
                                    <td>{{ $trend['expired_subscriptions'] }}</td>
                                    <td>{{ $trend['new_subscriptions'] - $trend['expired_subscriptions'] }}</td>
                                    <td>{{ $trend['formatted_revenue'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection