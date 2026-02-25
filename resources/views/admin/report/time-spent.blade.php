{{-- resources/views/admin/report/time-spent.blade.php --}}
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
                    <span>Time Spent Analysis</span>
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
        <h3 class="page-title">Time Spent Analysis</h3>
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
                                <a href="{{ route('admin.reports.engagement.time-spent', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.engagement.time-spent', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.engagement.time-spent', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Spent Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-home"></i>
                            <span class="caption-subject font-dark sbold uppercase">Home Page</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <h1>{{ $data['home_page_avg'] }} <small>sec</small></h1>
                        <p>Average time spent on home page</p>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-success" role="progressbar" 
                                 style="width: {{ min(100, ($data['home_page_avg'] / 60) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-briefcase"></i>
                            <span class="caption-subject font-dark sbold uppercase">Opportunity Page</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <h1>{{ $data['opportunity_page_avg'] }} <small>sec</small></h1>
                        <p>Average time spent on opportunity pages</p>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-info" role="progressbar" 
                                 style="width: {{ min(100, ($data['opportunity_page_avg'] / 60) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-comments"></i>
                            <span class="caption-subject font-dark sbold uppercase">Messaging</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <h1>{{ $data['messaging_avg'] }} <small>sec</small></h1>
                        <p>Average time spent on messaging</p>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-warning" role="progressbar" 
                                 style="width: {{ min(100, ($data['messaging_avg'] / 60) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-user"></i>
                            <span class="caption-subject font-dark sbold uppercase">Profile Page</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <h1>{{ $data['profile_page_avg'] }} <small>sec</small></h1>
                        <p>Average time spent on profile pages</p>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-danger" role="progressbar" 
                                 style="width: {{ min(100, ($data['profile_page_avg'] / 60) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-search"></i>
                            <span class="caption-subject font-dark sbold uppercase">Search Page</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <h1>{{ $data['search_page_avg'] }} <small>sec</small></h1>
                        <p>Average time spent on search</p>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-purple" role="progressbar" 
                                 style="width: {{ min(100, ($data['search_page_avg'] / 60) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Total Time</span>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        @php
                            $totalTime = $data['home_page_avg'] + $data['opportunity_page_avg'] + 
                                         $data['messaging_avg'] + $data['profile_page_avg'] + 
                                         $data['search_page_avg'];
                        @endphp
                        <h1>{{ floor($totalTime / 60) }} <small>min</small> {{ $totalTime % 60 }} <small>sec</small></h1>
                        <p>Total average session time</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Distribution Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Time Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="timeDistributionChart" height="300"></canvas>
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
    .progress-lg { height: 30px; margin-bottom: 15px; }
    .progress-bar-purple { background-color: #8775a7; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Time Distribution Chart
    var timeCtx = document.getElementById('timeDistributionChart').getContext('2d');
    new Chart(timeCtx, {
        type: 'pie',
        data: {
            labels: ['Home Page', 'Opportunity Page', 'Messaging', 'Profile Page', 'Search Page'],
            datasets: [{
                data: [
                    {{ $data['home_page_avg'] }},
                    {{ $data['opportunity_page_avg'] }},
                    {{ $data['messaging_avg'] }},
                    {{ $data['profile_page_avg'] }},
                    {{ $data['search_page_avg'] }}
                ],
                backgroundColor: ['#26c281', '#36c6d3', '#F4D03F', '#e7505a', '#8775a7']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var value = data.datasets[0].data[tooltipItem.index];
                        return data.labels[tooltipItem.index] + ': ' + value + ' sec';
                    }
                }
            }
        }
    });
});
</script>
@endpush