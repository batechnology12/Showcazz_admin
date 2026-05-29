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
                    <span>Notification Dashboard</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Push Notification Dashboard</h3>

        @include('flash::message')

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bolt"></i>
                            <span class="caption-subject font-dark sbold uppercase">Quick Actions</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <a href="{{ route('admin.notifications.send.form') }}" class="btn btn-primary">
                            <i class="fa fa-send"></i> Send New Notification
                        </a>
                        <a href="{{ route('admin.notifications.history') }}" class="btn btn-info">
                            <i class="fa fa-history"></i> View History
                        </a>
                        <a href="{{ route('admin.notifications.stats') }}" class="btn btn-success">
                            <i class="fa fa-bar-chart"></i> Statistics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual">
                        <i class="fa fa-bell"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['total_sent'] }}</div>
                        <div class="desc">Total Notifications</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ number_format($stats['total_recipients']) }}</div>
                        <div class="desc">Total Recipients</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['success_rate'] }}%</div>
                        <div class="desc">Success Rate</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['notifications_today'] }}</div>
                        <div class="desc">Today</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Coverage -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Coverage</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th>Total Users</th>
                                <td>{{ number_format($userStats['total_users']) }}</td>
                                <td rowspan="6" style="width: 150px; text-align: center; vertical-align: middle;">
                                    <div class="easy-pie-chart" data-percent="{{ $userStats['fcm_coverage'] }}">
                                        <span class="percent">{{ $userStats['fcm_coverage'] }}%</span>
                                    </div>
                                    <div>FCM Coverage</div>
                                </td>
                            </tr>
                            <tr>
                                <th>With FCM Token</th>
                                <td>{{ number_format($userStats['with_fcm_token']) }}</td>
                            </tr>
                            <tr>
                                <th>Companies</th>
                                <td>{{ number_format($userStats['companies']) }}</td>
                            </tr>
                            <tr>
                                <th>Students</th>
                                <td>{{ number_format($userStats['students']) }}</td>
                            </tr>
                            <tr>
                                <th>Professionals</th>
                                <td>{{ number_format($userStats['professionals']) }}</td>
                            </tr>
                            <tr>
                                <th>Subscribed</th>
                                <td>{{ number_format($userStats['subscribed']) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Recent Notifications</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.notifications.history') }}" class="btn btn-circle btn-default btn-sm">
                                <i class="fa fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Recipients</th>
                                    <th>Success</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentNotifications as $notification)
                                <tr>
                                    <td>
                                                  <a href="{{ route('admin.notifications.view', $notification->id) }}">
                                            {{ \Illuminate\Support\Str::limit($notification->title, 30) }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="label label-info">{{ ucfirst($notification->target_type) }}</span>
                                    </td>
                                    <td>{{ number_format($notification->total_recipients) }}</td>
                                    <td>
                                        <div class="progress" style="margin-bottom: 0; height: 15px;">
                                            <div class="progress-bar progress-bar-success" 
                                                 style="width: {{ $notification->total_recipients > 0 ? ($notification->success_count / $notification->total_recipients) * 100 : 0 }}%">
                                                {{ $notification->success_count }}/{{ $notification->total_recipients }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No notifications sent yet</td>
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
<link rel="stylesheet" href="{{ asset('assets/global/plugins/easypiechart/jquery.easypiechart.css') }}">
<style>
    .easy-pie-chart {
        display: inline-block;
        position: relative;
        width: 100px;
        height: 100px;
        margin: 10px auto;
    }
    .easy-pie-chart .percent {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 18px;
        font-weight: bold;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/global/plugins/easypiechat/jquery.easypiechart.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('.easy-pie-chart').easyPieChart({
        barColor: '#36c6d3',
        trackColor: '#f5f5f5',
        scaleColor: false,
        lineWidth: 10,
        lineCap: 'round',
        size: 100,
        animate: 1000
    });
});
</script>
@endpush