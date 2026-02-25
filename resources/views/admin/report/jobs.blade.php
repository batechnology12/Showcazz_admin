{{-- resources/views/admin/report/jobs.blade.php --}}
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
                    <span>Job Reports</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.jobs.export', ['period' => $period]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Job Reports</h3>
        <!-- END PAGE TITLE -->

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
                                <a href="{{ route('admin.reports.jobs', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.reports.jobs', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.jobs', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.jobs', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.jobs', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.reports.jobs', ['period' => 'this_year']) }}" class="btn {{ $period == 'this_year' ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>
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

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-briefcase"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['total']) }}</div>
                        <div class="desc">Total Jobs</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-plus-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['new']) }}</div>
                        <div class="desc">New Jobs</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-tasks"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['mini_missions']) }}</div>
                        <div class="desc">Mini Missions</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-graduation-cap"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['internships']) }}</div>
                        <div class="desc">Internships</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['active']) }}</div>
                        <div class="desc">Active Jobs</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat info">
                    <div class="visual"><i class="fa fa-clock-o"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($job_stats['expired']) }}</div>
                        <div class="desc">Expired</div>
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
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Job Posting Trend</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="jobTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Jobs by Type</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="jobsByTypeChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Stats -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Application Statistics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($application_stats['total']) }}</h3>
                                <small>Total Applications</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($application_stats['new']) }}</h3>
                                <small>New Applications</small>
                                <div>in this period</div>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ $application_stats['avg_per_job'] }}</h3>
                                <small>Avg per Job</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($application_stats['unique_applicants']) }}</h3>
                                <small>Unique Applicants</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Jobs -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Jobs by Applications</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.jobs.posted') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All Jobs
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applications</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($top_jobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.jobs.show', $job->id) }}">
                                            {{ \Illuminate\Support\Str::limit($job->title, 50) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($job->category_id == 5)
                                            <span class="label label-primary">Mini Mission</span>
                                        @else
                                            <span class="label label-success">Internship</span>
                                        @endif
                                    </td>
                                    <td>{{ $job->company_name ?? 'N/A' }}</td>
                                    <td>{{ $job->job_location ?? 'N/A' }}</td>
                                    <td class="text-center"><span class="badge badge-warning">{{ $job->applications_count }}</span></td>
                                    <td>{{ number_format($job->views_count) }}</td>
                                    <td>{{ number_format($job->likes_count) }}</td>
                                    <td>
                                        <a href="{{ route('admin.jobs.show', $job->id) }}" class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No jobs found</td>
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
    .dashboard-stat .details .number { font-size: 24px; }
    .badge-warning { background-color: #f0ad4e; color: white; padding: 3px 6px; border-radius: 3px; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Job Trend Chart
    var trendData = @json($job_trend);
    var trendCtx = document.getElementById('jobTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.date),
            datasets: [{
                label: 'Jobs Posted',
                data: trendData.map(item => item.posted),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Applications',
                data: trendData.map(item => item.applications),
                borderColor: '#e7505a',
                backgroundColor: 'rgba(231, 80, 90, 0.1)',
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

    // Jobs by Type Chart
    var typeCtx = document.getElementById('jobsByTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Mini Missions', 'Internships'],
            datasets: [{
                data: [{{ $jobs_by_type['mini_missions'] }}, {{ $jobs_by_type['internships'] }}],
                backgroundColor: ['#36c6d3', '#26c281']
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