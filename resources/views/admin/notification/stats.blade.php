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
                    <a href="{{ route('admin.notifications.dashboard') }}">Notification Dashboard</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Statistics</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Notification Statistics</h3>

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
                                <a href="{{ route('admin.notifications.stats', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.notifications.stats', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.notifications.stats', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.notifications.stats', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.notifications.stats', ['period' => 'last_month']) }}" class="btn {{ $period == 'last_month' ? 'btn-success' : 'btn-default' }} btn-sm">Last Month</a>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>From:</strong> {{ $dateRange['start']->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>To:</strong> {{ $dateRange['end']->format('d M Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-bell"></i></div>
                    <div class="details">
                        <div class="number">{{ $stats['overview']['total_notifications'] }}</div>
                        <div class="desc">Notifications Sent</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($stats['overview']['total_recipients']) }}</div>
                        <div class="desc">Total Recipients</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ $stats['overview']['success_rate'] }}%</div>
                        <div class="desc">Success Rate</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-times-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($stats['overview']['total_failed']) }}</div>
                        <div class="desc">Failed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bar-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Notification Volume</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="volumeChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Success Rate Trend</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="successChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Type -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Notifications by Type</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="typeChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-table"></i>
                            <span class="caption-subject font-dark sbold uppercase">Type Breakdown</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Count</th>
                                    <th>Recipients</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['by_type'] as $type)
                                <tr>
                                    <td><span class="label label-info">{{ ucfirst($type->target_type ?: 'unknown') }}</span></td>
                                    <td>{{ $type->count }}</td>
                                    <td>{{ number_format($type->recipients) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Stats Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-calendar"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Statistics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Notifications</th>
                                    <th>Recipients</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['daily_stats'] as $day)
                                <tr>
                                    <td>{{ $day['date'] }}</td>
                                    <td>{{ $day['count'] }}</td>
                                    <td>{{ number_format($day['recipients']) }}</td>
                                    <td><span class="label label-success">{{ $day['success'] }}</span></td>
                                    <td><span class="label label-danger">{{ $day['failed'] }}</span></td>
                                    <td>
                                        <div class="progress" style="margin-bottom: 0;">
                                            <div class="progress-bar progress-bar-success" 
                                                 style="width: {{ $day['recipients'] > 0 ? ($day['success'] / $day['recipients']) * 100 : 0 }}%">
                                                {{ $day['recipients'] > 0 ? round(($day['success'] / $day['recipients']) * 100, 2) : 0 }}%
                                            </div>
                                        </div>
                                    </td>
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

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Volume Chart
    var volumeData = @json($stats['daily_stats']);
    var volumeCtx = document.getElementById('volumeChart').getContext('2d');
    new Chart(volumeCtx, {
        type: 'bar',
        data: {
            labels: volumeData.map(item => item.date),
            datasets: [{
                label: 'Notifications',
                data: volumeData.map(item => item.count),
                backgroundColor: '#36c6d3'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, stepSize: 1 }
            }
        }
    });

    // Success Rate Chart
    var successData = @json($stats['success_rate']);
    var successCtx = document.getElementById('successChart').getContext('2d');
    new Chart(successCtx, {
        type: 'line',
        data: {
            labels: successData.map(item => item.date),
            datasets: [{
                label: 'Success Rate %',
                data: successData.map(item => item.rate),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38, 194, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });

    // Type Chart
    var typeData = @json($stats['by_type']);
    var typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'pie',
        data: {
            labels: typeData.map(item => item.target_type ? ucfirst(item.target_type) : 'Unknown'),
            datasets: [{
                data: typeData.map(item => item.count),
                backgroundColor: ['#36c6d3', '#5bc0de', '#f0ad4e', '#5cb85c', '#d9534f', '#8775a7']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    function ucfirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
</script>
@endpush