{{-- resources/views/admin/report/jobs-posted.blade.php --}}
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
                    <span>Jobs Posted</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.jobs') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Job Reports
                </a>
                <a href="{{ route('admin.reports.jobs.export', ['period' => request('period', '30_days')]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Jobs Posted Report</h3>
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
                                <a href="{{ route('admin.reports.jobs.posted', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.jobs.posted', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.jobs.posted', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.jobs.posted', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <a href="{{ route('admin.reports.jobs.posted', ['period' => 'this_year']) }}" class="btn {{ $period == 'this_year' ? 'btn-success' : 'btn-default' }} btn-sm">This Year</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-briefcase"></i>
                            <span class="caption-subject font-dark sbold uppercase">Jobs Posted List</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="jobsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Company</th>
                                       
                                        <th>Work Mode</th>
                                        <th>Posted By</th>
                                        <th>Views</th>
                                        <th>Likes</th>
                                        <th>Comments</th>
                                        <th>Applications</th>
                                        <th>Deadline</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                    @php
                                        $applicationsCount = \App\UserMessage::where('listing_id', $job->id)
                                            ->where('chat_type_id', function($q) {
                                                $q->select('id')->from('chat_types')->where('slug', 'job_application');
                                            })
                                            ->count();
                                    @endphp
                                    <tr>
                                        <td>{{ $job->id }}</td>
                                        <td>
                                            <a href="{{ route('admin.jobs.show', $job->id) }}">
                                                {{ \Illuminate\Support\Str::limit($job->title, 50) }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($job->category_id == 5)
                                                <span class="label label-primary">Mini Mission</span>
                                            @elseif($job->category_id == 6)
                                                <span class="label label-success">Internship</span>
                                            @else
                                                <span class="label label-default">Other</span>
                                            @endif
                                        </td>
                                        <td>{{ $job->company_name ?? 'N/A' }}</td>
                                      
                                        <td>
                                            @if($job->work_mode)
                                                <span class="label label-info">{{ ucfirst($job->work_mode) }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $job->user->name ?? 'Unknown' }}</td>
                                        <td>{{ number_format($job->views_count) }}</td>
                                        <td>{{ number_format($job->likes_count) }}</td>
                                        <td>{{ number_format($job->comments_count) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-warning">{{ $applicationsCount }}</span>
                                        </td>
                                        <td>
                                            @if($job->application_deadline)
                                                @if(\Carbon\Carbon::parse($job->application_deadline)->isPast())
                                                    <span class="label label-danger">{{ \Carbon\Carbon::parse($job->application_deadline)->format('d M Y') }}</span>
                                                @else
                                                    {{ \Carbon\Carbon::parse($job->application_deadline)->format('d M Y') }}
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $job->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.jobs.show', $job->id) }}" class="btn btn-xs btn-primary" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.jobs.applications', $job->id) }}" class="btn btn-xs btn-info" title="View Applications">
                                                    <i class="fa fa-users"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="14" class="text-center">No jobs found in this period</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 text-center">
                                {{ $jobs->appends(request()->query())->links() }}
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap.min.css">
<style>
    .badge-warning { background-color: #f0ad4e; color: white; padding: 3px 6px; border-radius: 3px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#jobsTable').DataTable({
        "pageLength": 25,
        "ordering": true,
        "info": true,
        "searching": true,
        "paging": false
    });
});
</script>
@endpush