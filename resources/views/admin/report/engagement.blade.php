{{-- resources/views/admin/report/engagement.blade.php --}}
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
                    <span>Engagement Reports</span>
                </li>
            </ul>
            <!-- <div class="page-toolbar">
                <a href="{{ route('admin.reports.engagement.export', ['period' => $period, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div> -->
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Engagement Reports</h3>
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
                                <a href="{{ route('admin.reports.engagement', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.reports.engagement', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.engagement', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.engagement', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.engagement', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
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
                                <form action="{{ route('admin.reports.engagement') }}" method="GET" class="form-inline">
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

        <!-- Overview Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-heart"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overall_engagement['total_interactions']) }}</div>
                        <div class="desc">Total Interactions</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overall_engagement['active_users']) }}</div>
                        <div class="desc">Active Users</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-line-chart"></i></div>
                    <div class="details">
                        <div class="number">{{ $overall_engagement['avg_interactions_per_user'] }}</div>
                        <div class="desc">Avg Interactions/User</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-percent"></i></div>
                    <div class="details">
                        <div class="number">{{ $overall_engagement['engagement_rate'] }}%</div>
                        <div class="desc">Engagement Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Activity Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Activity</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="dailyActivityChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interaction Breakdown -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Interaction Breakdown</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="interactionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Average Time Spent (seconds)</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Home Page</th>
                                <td>{{ $time_spent_avg['home_page'] }} sec</td>
                            </tr>
                            <tr>
                                <th>Opportunity Page</th>
                                <td>{{ $time_spent_avg['opportunity_page'] }} sec</td>
                            </tr>
                            <tr>
                                <th>Messaging</th>
                                <td>{{ $time_spent_avg['messaging'] }} sec</td>
                            </tr>
                            <tr>
                                <th>Profile Page</th>
                                <td>{{ $time_spent_avg['profile'] }} sec</td>
                            </tr>
                            <tr>
                                <th>Search Page</th>
                                <td>{{ $time_spent_avg['search'] }} sec</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Interactors -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Interactors</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.engagement.interactions') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>User Type</th>
                                    <th>Posts</th>
                                    <th>Comments</th>
                                    <th>Likes</th>
                                    <th>Messages</th>
                                    <th>Total Interactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($top_interactors as $user)
                                <tr>
                                    <td>
                                        @if($user->image)
                                            <img src="{{ asset('user_images/' . $user->image) }}" class="img-circle" style="width: 30px; height: 30px; margin-right: 5px;">
                                        @endif
                                        {{ $user->getName() }}
                                    </td>
                                    <td><span class="label label-info">{{ ucfirst($user->usertype ?? 'user') }}</span></td>
                                    <td>{{ $user->posts_count }}</td>
                                    <td>{{ $user->comments_count }}</td>
                                    <td>{{ $user->likes_count }}</td>
                                    <td>{{ $user->messages_count }}</td>
                                    <td><strong>{{ $user->total_interactions }}</strong></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Time Spent Analysis</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <a href="{{ route('admin.reports.engagement.time-spent') }}" class="btn btn-primary">
                            View Detailed Time Spent
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-eye"></i>
                            <span class="caption-subject font-dark sbold uppercase">Page Views</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <a href="{{ route('admin.reports.engagement.page-views') }}" class="btn btn-primary">
                            View Page Views Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-comments"></i>
                            <span class="caption-subject font-dark sbold uppercase">Interactions</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <a href="{{ route('admin.reports.engagement.interactions') }}" class="btn btn-primary">
                            View Detailed Interactions
                        </a>
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
    .img-circle { border-radius: 50%; object-fit: cover; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Daily Activity Chart
    var dailyData = @json($daily_activity);
    var dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [{
                label: 'Posts',
                data: dailyData.map(item => item.posts),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Comments',
                data: dailyData.map(item => item.comments),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38, 194, 129, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Likes',
                data: dailyData.map(item => item.likes),
                borderColor: '#e7505a',
                backgroundColor: 'rgba(231, 80, 90, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Messages',
                data: dailyData.map(item => item.messages),
                borderColor: '#8775a7',
                backgroundColor: 'rgba(135, 117, 167, 0.1)',
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

    // Interaction Breakdown Chart
    var interactionData = @json($interaction_breakdown);
    var interactionCtx = document.getElementById('interactionChart').getContext('2d');
    new Chart(interactionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Likes', 'Comments', 'Shares', 'Reposts', 'Messages', 'Connections'],
            datasets: [{
                data: [
                    interactionData.likes,
                    interactionData.comments,
                    interactionData.shares,
                    interactionData.reposts,
                    interactionData.messages,
                    interactionData.connections
                ],
                backgroundColor: [
                    '#e7505a',
                    '#36c6d3',
                    '#26c281',
                    '#8775a7',
                    '#F4D03F',
                    '#5bc0de'
                ]
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