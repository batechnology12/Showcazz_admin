{{-- resources/views/admin/report/post-categories.blade.php --}}
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
                    <span>Posts by Category</span>
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
        <h3 class="page-title">Posts by Category</h3>
        <!-- END PAGE TITLE -->

        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Category Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="categoryChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bar-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Category Engagement</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="categoryEngagementChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-tags"></i>
                            <span class="caption-subject font-dark sbold uppercase">Category Details</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Total Posts</th>
                                        <th>Total Views</th>
                                        <th>Total Likes</th>
                                        <th>Total Comments</th>
                                        <th>Avg Views/Post</th>
                                        <th>Engagement Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $category)
                                    @php
                                        $avgViews = $category->post_count > 0 ? round($category->total_views / $category->post_count, 1) : 0;
                                        $engagementRate = $category->total_views > 0 ? round(($category->total_likes + $category->total_comments) / $category->total_views * 100, 2) : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td class="text-center"><span class="badge badge-primary">{{ number_format($category->post_count) }}</span></td>
                                        <td class="text-center">{{ number_format($category->total_views) }}</td>
                                        <td class="text-center">{{ number_format($category->total_likes) }}</td>
                                        <td class="text-center">{{ number_format($category->total_comments) }}</td>
                                        <td class="text-center">{{ $avgViews }}</td>
                                        <td class="text-center">{{ $engagementRate }}%</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No categories found</td>
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
    // Category Distribution Chart
    var categoryData = @json($categories);
    var categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryData.map(item => item.name),
            datasets: [{
                data: categoryData.map(item => item.post_count),
                backgroundColor: [
                    '#36c6d3', '#e7505a', '#26c281', '#8775a7', 
                    '#F4D03F', '#5bc0de', '#f0ad4e', '#d9534f'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // Category Engagement Chart
    var engagementCtx = document.getElementById('categoryEngagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'bar',
        data: {
            labels: categoryData.map(item => item.name),
            datasets: [{
                label: 'Views',
                data: categoryData.map(item => item.total_views),
                backgroundColor: '#36c6d3'
            }, {
                label: 'Likes',
                data: categoryData.map(item => item.total_likes),
                backgroundColor: '#e7505a'
            }, {
                label: 'Comments',
                data: categoryData.map(item => item.total_comments),
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