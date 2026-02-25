{{-- resources/views/admin/report/users.blade.php --}}
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
                    <span>User Reports</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.users.export', ['period' => $period]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">User Reports</h3>
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
                                <a href="{{ route('admin.reports.users', ['period' => 'today']) }}" class="btn {{ $period == 'today' ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.reports.users', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.users', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.users', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.users', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
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
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($user_stats['total']) }}</div>
                        <div class="desc">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-user-plus"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($user_stats['new']) }}</div>
                        <div class="desc">New Users</div>
                        <small>in this period</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($user_stats['active']) }}</div>
                        <div class="desc">Active Users</div>
                        <small>in this period</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($user_stats['with_posts']) }}</div>
                        <div class="desc">Users with Posts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Type Distribution -->
        

        <!-- Engagement Metrics -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-heart"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Engagement Metrics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($user_engagement['avg_posts_per_user'], 1) }}</h3>
                                <small>Avg Posts per User</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($user_engagement['avg_comments_per_user'], 1) }}</h3>
                                <small>Avg Comments per User</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($user_engagement['avg_likes_per_user'], 1) }}</h3>
                                <small>Avg Likes per User</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3>{{ number_format($user_stats['verified']) }}</h3>
                                <small>Verified Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active & Inactive Users -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-check-circle text-success"></i>
                            <span class="caption-subject font-dark sbold uppercase">Active Users</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.users.active') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Posts</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($active_users as $user)
                                <tr>
                                    <td>{{ $user->getName() }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->posts_count ?? 0 }}</td>
                                    <td>{{ $user->comments_count ?? 0 }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center">No active users found</td></tr>
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
                            <i class="fa fa-exclamation-triangle text-warning"></i>
                            <span class="caption-subject font-dark sbold uppercase">Inactive Users (90+ days)</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.users.inactive') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inactive_users as $user)
                                <tr>
                                    <td>{{ $user->getName() }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                    <td>{{ $user->last_login_at ? $user->last_login_at->format('d M Y') : 'Never' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center">No inactive users found</td></tr>
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
    // User Type Chart
    var userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
    new Chart(userTypeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Professionals', 'Companies'],
            datasets: [{
                data: [
                    {{ $user_types['students'] }},
                    {{ $user_types['professionals'] }},
                    {{ $user_types['companies'] }}
                ],
                backgroundColor: ['#36c6d3', '#26c281', '#8775a7']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // User Growth Chart
    var userGrowthData = @json($user_growth);
    var userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: userGrowthData.map(item => item.month),
            datasets: [{
                label: 'Students',
                data: userGrowthData.map(item => item.students),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Professionals',
                data: userGrowthData.map(item => item.professionals),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38, 194, 129, 0.1)',
                fill: false,
                tension: 0.4
            }, {
                label: 'Companies',
                data: userGrowthData.map(item => item.companies),
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
});
</script>
@endpush