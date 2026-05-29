{{-- resources/views/admin/report/posts.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<style>
    .dashboard-stat {
    display: block;
    margin-bottom: 25px;
    overflow: hidden;
    border-radius: 4px;
    height: 15vh;
}
</style>
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
                    <span>Post Reports</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.posts.export', ['period' => $period, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Post Reports</h3>
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
                                <a href="{{ route('admin.reports.posts', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.reports.posts', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.posts', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.posts', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.posts', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.reports.posts', ['period' => 'last_month']) }}" class="btn {{ $period == 'last_month' ? 'btn-success' : 'btn-default' }} btn-sm">Last Month</a>
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
                                <form action="{{ route('admin.reports.posts') }}" method="GET" class="form-inline">
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

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($post_stats['total']) }}</div>
                        <div class="desc">Total Posts</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-plus-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($post_stats['new']) }}</div>
                        <div class="desc">New Posts</div>
                        <small>in this period</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($post_stats['published']) }}</div>
                        <div class="desc">Published</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-pencil-square-o"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($post_stats['drafts']) }}</div>
                        <div class="desc">Drafts</div>
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
                            <span class="caption-subject font-dark sbold uppercase">Post Trend</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="postTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Category Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Posts -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Popular Posts</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.posts.popular') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                    <th>Comments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($popular_posts as $post)
                                <tr>
                                    <td>{{ $post->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}">
                                            {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                        </a>
                                    </td>
                                    <td>{{ $post->user->name ?? 'Unknown' }}</td>
                                    <td>{{ $post->category->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($post->views_count) }}</td>
                                    <td>{{ number_format($post->likes_count) }}</td>
                                    <td>{{ number_format($post->comments_count) }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}" class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No posts found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Metrics -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-heart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Engagement Metrics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($engagement_metrics['total_views']) }}</h3>
                                <small>Total Views</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($engagement_metrics['unique_viewers']) }}</h3>
                                <small>Unique Viewers</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ $engagement_metrics['avg_views_per_post'] }}</h3>
                                <small>Avg Views/Post</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ $engagement_metrics['likes_per_view'] }}%</h3>
                                <small>Likes per View</small>
                            </div>
                        </div>
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
    // Post Trend Chart
    var trendData = @json($post_trend);
    var trendCtx = document.getElementById('postTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.date),
            datasets: [{
                label: 'Posts',
                data: trendData.map(item => item.posts),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Likes',
                data: trendData.map(item => item.likes),
                borderColor: '#e7505a',
                backgroundColor: 'rgba(231, 80, 90, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Comments',
                data: trendData.map(item => item.comments),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38, 194, 129, 0.1)',
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

    // Category Distribution Chart
    var categoryData = @json($category_distribution);
    var categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.name),
            datasets: [{
                data: categoryData.map(item => item.count),
                backgroundColor: ['#36c6d3', '#e7505a', '#26c281', '#8775a7', '#F4D03F', '#5bc0de']
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