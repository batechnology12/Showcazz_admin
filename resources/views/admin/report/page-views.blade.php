{{-- resources/views/admin/report/page-views.blade.php --}}
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
                    <a href="{{ route('admin.reports.engagement') }}">Engagement Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Page Views</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.engagement') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Engagement
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Page Views Report</h3>
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
                                <a href="{{ route('admin.reports.engagement.page-views', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.engagement.page-views', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.engagement.page-views', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customDateSection">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div id="customDateSection" class="collapse {{ $period == 'custom' ? 'in' : '' }}">
                            <div class="well">
                                <form action="{{ route('admin.reports.engagement.page-views') }}" method="GET" class="form-inline">
                                    <input type="hidden" name="period" value="custom">
                                    <div class="form-group">
                                        <label>Start Date:</label>
                                        <input type="date" name="start_date" class="form-control input-sm" value="{{ request('start_date', isset($date_range) ? $date_range['start_formatted'] : '') }}">
                                    </div>
                                    <div class="form-group margin-left-10">
                                        <label>End Date:</label>
                                        <input type="date" name="end_date" class="form-control input-sm" value="{{ request('end_date', isset($date_range) ? $date_range['end_formatted'] : '') }}">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm margin-left-10">Apply Filter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-eye"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($pageViews['total_views']) }}</div>
                        <div class="desc">Total Page Views</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($pageViews['unique_viewers']) }}</div>
                        <div class="desc">Unique Viewers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number">
                            @if($pageViews['unique_viewers'] > 0)
                                {{ round($pageViews['total_views'] / $pageViews['unique_viewers'], 1) }}
                            @else
                                0
                            @endif
                        </div>
                        <div class="desc">Views per User</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Views Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Views Trend</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="dailyViewsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Views by Page Type -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Views by Page Type</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="viewsByPageChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-table"></i>
                            <span class="caption-subject font-dark sbold uppercase">Page View Details</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Page Type</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Percentage</th>
                            </tr>
                            <tr>
                                <td><i class="fa fa-file-text text-info"></i> Posts</td>
                                <td class="text-center">{{ number_format($pageViews['views_by_page']['posts']) }}</td>
                                <td class="text-center">
                                    @if($pageViews['total_views'] > 0)
                                        {{ round($pageViews['views_by_page']['posts'] / $pageViews['total_views'] * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fa fa-briefcase text-success"></i> Jobs</td>
                                <td class="text-center">{{ number_format($pageViews['views_by_page']['jobs']) }}</td>
                                <td class="text-center">
                                    @if($pageViews['total_views'] > 0)
                                        {{ round($pageViews['views_by_page']['jobs'] / $pageViews['total_views'] * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fa fa-user text-warning"></i> Profiles</td>
                                <td class="text-center">{{ number_format($pageViews['views_by_page']['profiles']) }}</td>
                                <td class="text-center">
                                    @if($pageViews['total_views'] > 0)
                                        {{ round($pageViews['views_by_page']['profiles'] / $pageViews['total_views'] * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </td>
                            </tr>
                            <tr class="active">
                                <th>Total</th>
                                <th class="text-center">{{ number_format($pageViews['total_views']) }}</th>
                                <th class="text-center">100%</th>
                            </tr>
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
    // Daily Views Chart
    var dailyData = @json($pageViews['daily_views']);
    var dailyCtx = document.getElementById('dailyViewsChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [{
                label: 'Page Views',
                data: dailyData.map(item => item.views),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Views by Page Type Chart
    var viewsByPageCtx = document.getElementById('viewsByPageChart').getContext('2d');
    new Chart(viewsByPageCtx, {
        type: 'doughnut',
        data: {
            labels: ['Posts', 'Jobs', 'Profiles'],
            datasets: [{
                data: [
                    {{ $pageViews['views_by_page']['posts'] }},
                    {{ $pageViews['views_by_page']['jobs'] }},
                    {{ $pageViews['views_by_page']['profiles'] }}
                ],
                backgroundColor: ['#36c6d3', '#26c281', '#F4D03F']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });
});
</script>
@endpush