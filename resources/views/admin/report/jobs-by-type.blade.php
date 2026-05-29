{{-- resources/views/admin/report/jobs-by-type.blade.php --}}
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
                    <a href="{{ route('admin.reports.jobs') }}">Job Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Jobs by Type</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.jobs') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Job Reports
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Jobs by Type Analysis</h3>
        <!-- END PAGE TITLE -->

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-tasks"></i>
                            <span class="caption-subject font-dark sbold uppercase">Mini Missions</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h1>{{ number_format($mini_missions) }}</h1>
                                <p>Total Posted</p>
                            </div>
                            <div class="col-md-6">
                                <h1>{{ number_format($mini_missions_with_apps) }}</h1>
                                <p>Total Applications</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-graduation-cap"></i>
                            <span class="caption-subject font-dark sbold uppercase">Internships</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h1>{{ number_format($internships) }}</h1>
                                <p>Total Posted</p>
                            </div>
                            <div class="col-md-6">
                                <h1>{{ number_format($internships_with_apps) }}</h1>
                                <p>Total Applications</p>
                            </div>
                        </div>
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
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Jobs Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="jobsDistributionChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Applications Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="applicationsDistributionChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-table"></i>
                            <span class="caption-subject font-dark sbold uppercase">Type Comparison</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th class="text-center">Mini Missions</th>
                                    <th class="text-center">Internships</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Total Jobs Posted</strong></td>
                                    <td class="text-center">{{ number_format($mini_missions) }}</td>
                                    <td class="text-center">{{ number_format($internships) }}</td>
                                    <td class="text-center"><strong>{{ number_format($mini_missions + $internships) }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Applications</strong></td>
                                    <td class="text-center">{{ number_format($mini_missions_with_apps) }}</td>
                                    <td class="text-center">{{ number_format($internships_with_apps) }}</td>
                                    <td class="text-center"><strong>{{ number_format($mini_missions_with_apps + $internships_with_apps) }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Applications per Job</strong></td>
                                    <td class="text-center">
                                        @if($mini_missions > 0)
                                            {{ round($mini_missions_with_apps / $mini_missions, 1) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($internships > 0)
                                            {{ round($internships_with_apps / $internships, 1) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($mini_missions + $internships) > 0)
                                            {{ round(($mini_missions_with_apps + $internships_with_apps) / ($mini_missions + $internships), 1) }}
                                        @else
                                            0
                                        @endif
                                    </td>
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
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Jobs Distribution Chart
    var jobsCtx = document.getElementById('jobsDistributionChart').getContext('2d');
    new Chart(jobsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Mini Missions', 'Internships'],
            datasets: [{
                data: [{{ $mini_missions }}, {{ $internships }}],
                backgroundColor: ['#36c6d3', '#26c281']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Jobs by Type'
            }
        }
    });

    // Applications Distribution Chart
    var appsCtx = document.getElementById('applicationsDistributionChart').getContext('2d');
    new Chart(appsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Mini Missions', 'Internships'],
            datasets: [{
                data: [{{ $mini_missions_with_apps }}, {{ $internships_with_apps }}],
                backgroundColor: ['#36c6d3', '#26c281']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Applications by Job Type'
            }
        }
    });
});
</script>
@endpush