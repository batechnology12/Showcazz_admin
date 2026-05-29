{{-- resources/views/admin/report/performance.blade.php --}}
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
                    <a href="{{ route('admin.reports.dashboard') }}">Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Platform Performance</span>
                </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Platform Performance</h3>
        <!-- END PAGE TITLE -->

        <!-- Period Selector -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-calendar"></i>
                            <span class="caption-subject font-dark sbold uppercase">Time Period</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <a href="{{ route('admin.reports.performance', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.performance', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.performance', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.performance', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.reports.performance', ['period' => 'this_year']) }}" class="btn {{ $period == 'this_year' ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customDateSection">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>From:</strong> {{ $date_range['start']->format('d M Y') }}
                            </div>
                            <div class="col-md-4">
                                <strong>To:</strong> {{ $date_range['end']->format('d M Y') }}
                            </div>
                            <div class="col-md-4">
                                <strong>Period:</strong> {{ ucfirst(str_replace('_', ' ', $period)) }}
                            </div>
                        </div>

                        <div id="customDateSection" class="collapse {{ $period == 'custom' ? 'in' : '' }} margin-top-20">
                            <div class="well">
                                <form action="{{ route('admin.reports.performance') }}" method="GET" class="form-inline">
                                    <input type="hidden" name="period" value="custom">
                                    <div class="form-group">
                                        <label>Start Date:</label>
                                        <input type="date" name="start_date" class="form-control input-sm" value="{{ request('start_date', $date_range['start_formatted']) }}">
                                    </div>
                                    <div class="form-group margin-left-10">
                                        <label>End Date:</label>
                                        <input type="date" name="end_date" class="form-control input-sm" value="{{ request('end_date', $date_range['end_formatted']) }}">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm margin-left-10">Apply Filter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth Metrics -->
        <div class="row">
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Growth</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Current Period</th>
                                <td>{{ number_format($growth_metrics['users']['current']) }}</td>
                            </tr>
                            <tr>
                                <th>Previous Period</th>
                                <td>{{ number_format($growth_metrics['users']['previous']) }}</td>
                            </tr>
                            <tr>
                                <th>Growth Rate</th>
                                <td>
                                    <span class="label label-{{ $growth_metrics['users']['growth'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $growth_metrics['users']['growth'] }}%
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-file-text"></i>
                            <span class="caption-subject font-dark sbold uppercase">Post Growth</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Current Period</th>
                                <td>{{ number_format($growth_metrics['posts']['current']) }}</td>
                            </tr>
                            <tr>
                                <th>Previous Period</th>
                                <td>{{ number_format($growth_metrics['posts']['previous']) }}</td>
                            </tr>
                            <tr>
                                <th>Growth Rate</th>
                                <td>
                                    <span class="label label-{{ $growth_metrics['posts']['growth'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $growth_metrics['posts']['growth'] }}%
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-heart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Engagement Growth</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Current Period</th>
                                <td>{{ number_format($growth_metrics['engagement']['current']) }}</td>
                            </tr>
                            <tr>
                                <th>Previous Period</th>
                                <td>{{ number_format($growth_metrics['engagement']['previous']) }}</td>
                            </tr>
                            <tr>
                                <th>Growth Rate</th>
                                <td>
                                    <span class="label label-{{ $growth_metrics['engagement']['growth'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $growth_metrics['engagement']['growth'] }}%
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Activity Metrics -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Activity</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Daily Active Users</th>
                                <td>{{ number_format($user_activity['daily_active']) }}</td>
                            </tr>
                            <tr>
                                <th>Weekly Active Users</th>
                                <td>{{ number_format($user_activity['weekly_active']) }}</td>
                            </tr>
                            <tr>
                                <th>Monthly Active Users</th>
                                <td>{{ number_format($user_activity['monthly_active']) }}</td>
                            </tr>
                            <tr>
                                <th>Retention Rate</th>
                                <td>{{ $user_activity['retention_rate'] }}%</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Content Metrics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Total Posts</th>
                                <td>{{ number_format($content_metrics['total_posts']) }}</td>
                            </tr>
                            <tr>
                                <th>New Posts</th>
                                <td>{{ number_format($content_metrics['new_posts']) }}</td>
                            </tr>
                            <tr>
                                <th>Total Jobs</th>
                                <td>{{ number_format($content_metrics['total_jobs']) }}</td>
                            </tr>
                            <tr>
                                <th>New Jobs</th>
                                <td>{{ number_format($content_metrics['new_jobs']) }}</td>
                            </tr>
                            <tr>
                                <th>Posts with Media</th>
                                <td>{{ number_format($content_metrics['posts_with_media']) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement & Retention -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-heart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Engagement Rate</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row text-center">
                            <div class="col-md-12">
                                <h1 style="font-size: 48px;">{{ $engagement_rate }}%</h1>
                                <p>Overall Engagement Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Retention Rate</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row text-center">
                            <div class="col-md-12">
                                <h1 style="font-size: 48px;">{{ $retention_rate }}%</h1>
                                <p>User Retention Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Growth Trends</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="growthChart" height="300"></canvas>
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
    // Growth Chart
    $.ajax({
        url: '{{ route("admin.reports.performance.growth", ["period" => $period, "start_date" => request("start_date"), "end_date" => request("end_date")]) }}',
        success: function(response) {
            if (response.success) {
                var ctx = document.getElementById('growthChart').getContext('2d');
                var data = response.data;
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.period),
                        datasets: [{
                            label: 'Users',
                            data: data.map(item => item.users),
                            borderColor: '#36c6d3',
                            backgroundColor: 'rgba(54, 198, 211, 0.1)',
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Posts',
                            data: data.map(item => item.posts),
                            borderColor: '#26c281',
                            backgroundColor: 'rgba(38, 194, 129, 0.1)',
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Jobs',
                            data: data.map(item => item.jobs),
                            borderColor: '#8775a7',
                            backgroundColor: 'rgba(135, 117, 167, 0.1)',
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Applications',
                            data: data.map(item => item.applications),
                            borderColor: '#F4D03F',
                            backgroundColor: 'rgba(244, 208, 63, 0.1)',
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }
    });
});
</script>
@endpush