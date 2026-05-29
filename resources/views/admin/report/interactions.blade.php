{{-- resources/views/admin/report/interactions.blade.php --}}
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
                    <span>Interactions Report</span>
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
        <h3 class="page-title">User Interactions Report</h3>
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
                                <a href="{{ route('admin.reports.engagement.interactions', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.engagement.interactions', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.engagement.interactions', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customDateSection">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div id="customDateSection" class="collapse {{ $period == 'custom' ? 'in' : '' }}">
                            <div class="well">
                                <form action="{{ route('admin.reports.engagement.interactions') }}" method="GET" class="form-inline">
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
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-heart"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_likes']) }}</div>
                        <div class="desc">Likes</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-comment"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_comments']) }}</div>
                        <div class="desc">Comments</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-share-alt"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_shares']) }}</div>
                        <div class="desc">Shares</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-retweet"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_reposts']) }}</div>
                        <div class="desc">Reposts</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-envelope"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_messages']) }}</div>
                        <div class="desc">Messages</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat info">
                    <div class="visual"><i class="fa fa-link"></i></div>
                    <div class="details">
                        <div class="number">{{ number_format($data['total_connections']) }}</div>
                        <div class="desc">Connections</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Interactions Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Interactions</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="dailyInteractionsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interaction Summary Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-table"></i>
                            <span class="caption-subject font-dark sbold uppercase">Interaction Summary</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @php
                            $totalInteractions = $data['total_likes'] + $data['total_comments'] + $data['total_shares'] + 
                                                 $data['total_reposts'] + $data['total_messages'] + $data['total_connections'];
                            
                            // Calculate days based on period
                            $days = 30; // default
                            if ($period == 'custom' && isset($date_range)) {
                                $days = $date_range['start']->diffInDays($date_range['end']) + 1;
                            } elseif ($period == '7_days') {
                                $days = 7;
                            } elseif ($period == '30_days') {
                                $days = 30;
                            } elseif ($period == '90_days') {
                                $days = 90;
                            }
                        @endphp
                        
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Interaction Type</th>
                                    <th class="text-center">Total Count</th>
                                    <th class="text-center">Daily Average</th>
                                    <th class="text-center">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fa fa-heart text-danger"></i> Likes</td>
                                    <td class="text-center">{{ number_format($data['total_likes']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_likes'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_likes'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-comment text-info"></i> Comments</td>
                                    <td class="text-center">{{ number_format($data['total_comments']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_comments'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_comments'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-share-alt text-success"></i> Shares</td>
                                    <td class="text-center">{{ number_format($data['total_shares']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_shares'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_shares'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-retweet text-purple"></i> Reposts</td>
                                    <td class="text-center">{{ number_format($data['total_reposts']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_reposts'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_reposts'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-envelope text-warning"></i> Messages</td>
                                    <td class="text-center">{{ number_format($data['total_messages']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_messages'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_messages'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fa fa-link text-primary"></i> Connections</td>
                                    <td class="text-center">{{ number_format($data['total_connections']) }}</td>
                                    <td class="text-center">{{ $days > 0 ? round($data['total_connections'] / $days, 1) : 0 }}</td>
                                    <td class="text-center">
                                        @if($totalInteractions > 0)
                                            {{ round($data['total_connections'] / $totalInteractions * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                <tr class="active">
                                    <th>Total</th>
                                    <th class="text-center">{{ number_format($totalInteractions) }}</th>
                                    <th class="text-center">{{ $days > 0 ? round($totalInteractions / $days, 1) : 0 }}</th>
                                    <th class="text-center">100%</th>
                                </tr>
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
    .text-purple { color: #8775a7; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Daily Interactions Chart
    var dailyData = @json($data['daily_interactions']);
    var dailyCtx = document.getElementById('dailyInteractionsChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [{
                label: 'Daily Interactions',
                data: dailyData.map(item => item.count),
                backgroundColor: '#36c6d3',
                borderColor: '#2b9ca8',
                borderWidth: 1
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