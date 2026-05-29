{{-- resources/views/admin/report/post-engagement.blade.php --}}
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
                    <a href="{{ route('admin.reports.posts') }}">Post Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Post Engagement</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.posts') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Post Reports
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Post Engagement Analysis</h3>
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
                                <a href="{{ route('admin.reports.posts.engagement', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.posts.engagement', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.posts.engagement', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.posts.engagement', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customDateSection">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div id="customDateSection" class="collapse {{ $period == 'custom' ? 'in' : '' }}">
                            <div class="well">
                                <form action="{{ route('admin.reports.posts.engagement') }}" method="GET" class="form-inline">
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

        <!-- Engagement Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bar-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Engagement</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="dailyEngagementChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Engagement by Post Type</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="engagementByTypeChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Engaged Posts -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Engaged Posts</span>
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
                                    <th>Shares</th>
                                    <th>Engagement Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($top_engaged_posts as $post)
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
                                    <td>{{ number_format($post->shares_count) }}</td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ number_format($post->views_count + ($post->likes_count * 2) + ($post->comments_count * 3)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}" class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">No posts found</td>
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
    .badge-primary { background-color: #3598dc; color: white; padding: 3px 6px; border-radius: 3px; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Daily Engagement Chart
    var dailyData = @json($daily_engagement);
    var dailyCtx = document.getElementById('dailyEngagementChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [{
                label: 'Likes',
                data: dailyData.map(item => item.likes),
                borderColor: '#e7505a',
                backgroundColor: 'rgba(231, 80, 90, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Comments',
                data: dailyData.map(item => item.comments),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Shares',
                data: dailyData.map(item => item.shares),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38, 194, 129, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Views',
                data: dailyData.map(item => item.views),
                borderColor: '#8775a7',
                backgroundColor: 'rgba(135, 117, 167, 0.1)',
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

    // Engagement by Type Chart
    var typeData = @json($engagement_by_type);
    var typeCtx = document.getElementById('engagementByTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: typeData.map(item => item.name),
            datasets: [{
                label: 'Views',
                data: typeData.map(item => item.total_views),
                backgroundColor: '#36c6d3'
            }, {
                label: 'Likes',
                data: typeData.map(item => item.total_likes),
                backgroundColor: '#e7505a'
            }, {
                label: 'Comments',
                data: typeData.map(item => item.total_comments),
                backgroundColor: '#26c281'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
@endpush