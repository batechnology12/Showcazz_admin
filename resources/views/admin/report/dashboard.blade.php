{{-- resources/views/admin/report/dashboard.blade.php --}}
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
                    <span>Reports &amp; Analytics Dashboard</span>
                </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Reports &amp; Analytics Dashboard</h3>
        <!-- END PAGE TITLE -->

        @include('flash::message')

        <!-- ===== Date Range Selector ===== -->
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
                                <a href="{{ route('admin.reports.dashboard', ['period' => 'today']) }}"
                                   class="btn {{ $period == 'today'      ? 'btn-success' : 'btn-default' }} btn-sm">Today</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => 'yesterday']) }}"
                                   class="btn {{ $period == 'yesterday'  ? 'btn-success' : 'btn-default' }} btn-sm">Yesterday</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => '7_days']) }}"
                                   class="btn {{ $period == '7_days'     ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => '30_days']) }}"
                                   class="btn {{ $period == '30_days'    ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => '90_days']) }}"
                                   class="btn {{ $period == '90_days'    ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => 'this_month']) }}"
                                   class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => 'last_month']) }}"
                                   class="btn {{ $period == 'last_month' ? 'btn-success' : 'btn-default' }} btn-sm">Last Month</a>
                                <a href="{{ route('admin.reports.dashboard', ['period' => 'this_year']) }}"
                                   class="btn {{ $period == 'this_year'  ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>

                                {{-- Custom Date Range toggle --}}
                                <button type="button"
                                        id="customRangeToggle"
                                        class="btn {{ $period == 'custom' ? 'btn-warning' : 'btn-default' }} btn-sm">
                                    <i class="fa fa-calendar-o"></i> Custom
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Active range display --}}
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>From:</strong> {{ $date_range['start']->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>To:</strong> {{ $date_range['end']->format('d M Y') }}
                            </div>
                        </div>

                        {{-- Custom date picker panel --}}
                        <div id="customRangePanel" style="display:{{ $period == 'custom' ? 'block' : 'none' }}; margin-top:10px;">
                            <hr style="margin-top:12px;">
                            <form id="customRangeForm"
                                  action="{{ route('admin.reports.dashboard') }}"
                                  method="GET"
                                  class="form-inline">
                                <input type="hidden" name="period" value="custom">

                                <div class="form-group" style="margin-right:12px;">
                                    <label for="start_date" style="margin-right:6px; font-weight:600;">
                                        <i class="fa fa-calendar"></i> Start Date
                                    </label>
                                    <input type="date"
                                           id="start_date"
                                           name="start_date"
                                           class="form-control input-sm"
                                           value="{{ $period == 'custom' ? $date_range['start']->format('Y-m-d') : '' }}"
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                </div>

                                <div class="form-group" style="margin-right:12px;">
                                    <label for="end_date" style="margin-right:6px; font-weight:600;">
                                        <i class="fa fa-calendar"></i> End Date
                                    </label>
                                    <input type="date"
                                           id="end_date"
                                           name="end_date"
                                           class="form-control input-sm"
                                           value="{{ $period == 'custom' ? $date_range['end']->format('Y-m-d') : '' }}"
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                </div>

                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fa fa-search"></i> Apply
                                </button>
                                <a href="{{ route('admin.reports.dashboard', ['period' => '30_days']) }}"
                                   class="btn btn-default btn-sm" style="margin-left:6px;">
                                    <i class="fa fa-times"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Overview Cards ===== -->
        <div class="row">
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_users']) }}</div>
                        <div class="desc">Total Users</div>
                        <small>+{{ $overview['new_users'] }} new</small>
                    </div>
                    <a class="more" href="">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-building"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_companies']) }}</div>
                        <div class="desc">Companies</div>
                    </div>
                    <a class="more" href="">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_posts']) }}</div>
                        <div class="desc">Total Posts</div>
                        <small>+{{ $overview['new_posts'] }} new</small>
                    </div>
                    <a class="more" href="{{ route('admin.reports.posts') }}">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-briefcase"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_jobs']) }}</div>
                        <div class="desc">Total Jobs</div>
                        <small>{{ number_format($overview['total_applications']) }} applications</small>
                    </div>
                    <a class="more" href="{{ route('admin.reports.jobs') }}">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-heart"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_likes']) }}</div>
                        <div class="desc">Total Likes</div>
                    </div>
                    <a class="more" href="{{ route('admin.reports.engagement') }}">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-eye"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($overview['total_views']) }}</div>
                        <div class="desc">Total Views</div>
                    </div>
                    <a class="more" href="{{ route('admin.reports.engagement') }}">View Details <i class="m-icon-swapright m-icon-white"></i></a>
                </div>
            </div>
        </div>

        <!-- ===== Charts Row ===== -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Growth</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.users.growth', ['period' => 'monthly']) }}"
                               class="btn btn-circle btn-default btn-sm" id="refreshUserChart">
                                <i class="fa fa-refresh"></i> Refresh
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="userGrowthChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bar-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Post Engagement</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="postEngagementChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Platform Performance ===== -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-dashboard"></i>
                            <span class="caption-subject font-dark sbold uppercase">Platform Performance</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr><th>Response Time</th><td>{{ $platform_performance['response_time'] }}</td></tr>
                            <tr><th>Uptime</th><td>{{ $platform_performance['uptime'] }}</td></tr>
                            <tr><th>API Calls (24h)</th><td>{{ number_format($platform_performance['api_calls']) }}</td></tr>
                            <tr><th>Storage Used</th><td>{{ $platform_performance['storage_used'] }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Active Users</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h3>{{ number_format($overview['active_users']) }}</h3>
                                <small>Active Users</small>
                            </div>
                            <div class="col-md-4">
                                <h3>{{ round($overview['active_users'] / max(1, $overview['total_users']) * 100) }}%</h3>
                                <small>Engagement Rate</small>
                            </div>
                            <div class="col-md-4">
                                <h3>{{ number_format($overview['new_users']) }}</h3>
                                <small>New Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Top Content ===== -->
        <div class="row">
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Posts</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.reports.posts.popular') }}" class="btn btn-circle btn-default btn-xs">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            @forelse($top_content['top_posts'] as $post)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.posts.show', $post->id) }}">
                                        {{ \Illuminate\Support\Str::limit($post->title, 30) }}
                                    </a>
                                </td>
                                <td>{{ number_format($post->views_count) }} views</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center">No posts found</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Top Users</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            @forelse($top_content['top_users'] as $user)
                            <tr>
                                <td>
                                    @if($user->usertype === 'company')
                                        <span class="label label-sm label-success">Company</span>
                                    @else
                                        <span class="label label-sm label-info">{{ ucfirst($user->usertype) }}</span>
                                    @endif
                                    {{ $user->usertype === 'company' ? ($user->company_name ?? $user->name) : $user->getName() }}
                                </td>
                                <td>{{ number_format($user->followers_count ?? 0) }} followers</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center">No users found</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Recent Activity ===== -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Recent Activity</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="timeline">
                            @forelse($recent_activity as $activity)
                            <div class="timeline-item">
                                <div class="timeline-badge bg-{{ $activity['color'] }}">
                                    <i class="fa {{ $activity['icon'] }}"></i>
                                </div>
                                <div class="timeline-body">
                                    <div class="timeline-body-content">
                                        <span class="text-muted">{{ $activity['time'] }}</span>
                                        <p>
                                            <strong>{{ $activity['user'] }}</strong> - {{ $activity['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-info">No recent activity</div>
                            @endforelse
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
    .dashboard-stat .details .desc   { font-size: 14px; }
    .dashboard-stat .details small   { font-size: 11px; color: #fff; opacity: .8; }

    /* Timeline */
    .timeline                  { position: relative; padding: 20px 0; list-style: none; }
    .timeline-item             { position: relative; margin-bottom: 20px; }
    .timeline-badge            { position: absolute; top: 0; left: 0; width: 40px; height: 40px;
                                 border-radius: 50%; text-align: center; line-height: 40px; color: #fff; z-index: 100; }
    .timeline-body             { margin-left: 60px; padding: 15px; background: #f4f4f4; border-radius: 4px; }
    .timeline-body-content     { color: #333; }

    /* Badge colours */
    .bg-blue   { background-color: #36c6d3; }
    .bg-green  { background-color: #26c281; }
    .bg-purple { background-color: #8775a7; }
    .bg-red    { background-color: #e7505a; }
    .bg-orange { background-color: #F4D03F; }

    .label-sm { font-size: 10px; padding: 2px 5px; margin-right: 5px; }

    /* Custom range panel */
    #customRangeForm       { margin-top: 10px; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
    #customRangeForm .form-group { margin-bottom: 6px; }

    /* Datepicker z-index & theme display fix */
    .datepicker-dropdown, .datepicker {
        z-index: 99999 !important;
    }
    .datepicker > div {
        display: block;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function () {

    var today = new Date().toISOString().split('T')[0];

    /* ── Custom date-range panel toggle ── */
    var $panel  = $('#customRangePanel');
    var $toggle = $('#customRangeToggle');

    $toggle.on('click', function () {
        if ($panel.is(':visible')) {
            $panel.slideUp(250);
            $toggle.removeClass('btn-warning').addClass('btn-default');
        } else {
            $panel.slideDown(250);
            $toggle.removeClass('btn-default').addClass('btn-warning');
            
            // Pre-fill defaults if fields are empty
            if (!$('#start_date').val()) {
                var d30 = new Date();
                d30.setDate(d30.getDate() - 30);
                $('#start_date').val(d30.toISOString().split('T')[0]);
            }
            if (!$('#end_date').val()) {
                $('#end_date').val(today);
            }
        }
    });

    /* Form submit */
    $('#customRangeForm').on('submit', function(e){
        e.preventDefault();
        var startDate = $('#start_date').val();
        var endDate   = $('#end_date').val();
        
        if (!startDate || !endDate) {
            alert('Please select both Start Date and End Date.');
            return;
        }
        
        var url = $(this).attr('action')
                  + '?period=custom'
                  + '&start_date=' + startDate
                  + '&end_date='   + endDate;
        window.location.href = url;
    });

    /* ── User Growth Chart ── */
    var userGrowthData = @json($user_growth);
    var labels = userGrowthData.map(function(item) {
        var d = new Date(item.date);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    var userCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Users',
                data: userGrowthData.map(function(i){ return i.new_users; }),
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54,198,211,.1)',
                fill: true, tension: .4
            }, {
                label: 'Active Users',
                data: userGrowthData.map(function(i){ return i.active_users; }),
                borderColor: '#26c281',
                backgroundColor: 'rgba(38,194,129,.1)',
                fill: true, tension: .4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return v.toLocaleString(); } } } },
            tooltips: { callbacks: { label: function(t, d){
                return d.datasets[t.datasetIndex].label + ': ' + t.yLabel.toLocaleString();
            }}}
        }
    });

    /* ── Post Engagement Chart ── */
    var postEngagementData = @json($post_engagement);
    var postCtx = document.getElementById('postEngagementChart').getContext('2d');
    new Chart(postCtx, {
        type: 'bar',
        data: {
            labels: ['Likes', 'Comments', 'Shares', 'Reposts'],
            datasets: [{
                label: 'Engagement',
                data: [
                    postEngagementData.likes,
                    postEngagementData.comments,
                    postEngagementData.shares,
                    postEngagementData.reposts
                ],
                backgroundColor: ['#36c6d3','#26c281','#8775a7','#F4D03F']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return v.toLocaleString(); } } } },
            tooltips: { callbacks: { label: function(t, d){
                return d.datasets[t.datasetIndex].label + ': ' + t.yLabel.toLocaleString();
            }}}
        }
    });

});
</script>
@endpush