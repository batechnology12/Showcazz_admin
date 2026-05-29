{{-- resources/views/admin/report/jobs-applied.blade.php --}}
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
                    <span>Jobs Applied</span>
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
        <h3 class="page-title">Job Applications Report</h3>
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
                                <a href="{{ route('admin.reports.jobs.applied', ['period' => '7_days']) }}" class="btn {{ $period == '7_days' ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.jobs.applied', ['period' => '30_days']) }}" class="btn {{ $period == '30_days' ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.jobs.applied', ['period' => '90_days']) }}" class="btn {{ $period == '90_days' ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.jobs.applied', ['period' => 'this_month']) }}" class="btn {{ $period == 'this_month' ? 'btn-success' : 'btn-default' }} btn-sm">This Month</a>
                                <button type="button" class="btn {{ $period == 'custom' ? 'btn-success' : 'btn-default' }} btn-sm" data-toggle="collapse" data-target="#customDateSection">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div id="customDateSection" class="collapse {{ $period == 'custom' ? 'in' : '' }}">
                            <div class="well">
                                <form action="{{ route('admin.reports.jobs.applied') }}" method="GET" class="form-inline">
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

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Job Applications List</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="applicationsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Job Title</th>
                                        <th>Job Type</th>
                                        <th>Company</th>
                                        <th>Message</th>
                                        <th>Portfolio Links</th>
                                        <th>Resume</th>
                                        <th>Applied Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($applications as $application)
                                    <tr>
                                        <td>{{ $application->id }}</td>
                                        <td>
                                            @if($application->applicant && $application->applicant['image'])
                                                <img src="{{ $application->applicant['image'] }}" class="img-circle" style="width: 30px; height: 30px; margin-right: 5px;">
                                            @endif
                                            {{ $application->applicant['name'] ?? 'Unknown' }}
                                        </td>
                                        <td>{{ $application->applicant['email'] ?? 'N/A' }}</td>
                                        <td>
                                            @if($application->job)
                                                <a href="{{ route('admin.jobs.show', $application->job->id) }}">
                                                    {{ \Illuminate\Support\Str::limit($application->job->title, 40) }}
                                                </a>
                                            @else
                                                <span class="label label-danger">Job Deleted</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($application->job)
                                                @if($application->job->category_id == 5)
                                                    <span class="label label-primary">Mini Mission</span>
                                                @elseif($application->job->category_id == 6)
                                                    <span class="label label-success">Internship</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $application->job->company_name ?? 'N/A' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#messageModal{{ $application->id }}">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                            <!-- Message Modal -->
                                            <div class="modal fade" id="messageModal{{ $application->id }}" tabindex="-1" role="dialog">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            <h4 class="modal-title">Application Message</h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>{{ $application->message_txt }}</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(!empty($application->application_data['portfolio_links']))
                                                @foreach($application->application_data['portfolio_links'] as $link)
                                                    <a href="{{ $link }}" target="_blank" class="btn btn-xs btn-primary">
                                                        <i class="fa fa-link"></i>
                                                    </a>
                                                @endforeach
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($application->application_data['resume_link']))
                                                <a href="{{ $application->application_data['resume_link'] }}" target="_blank" class="btn btn-xs btn-success">
                                                    <i class="fa fa-file-pdf-o"></i>
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $application->created_at->format('d M Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.jobs.show', $application->job->id) }}" class="btn btn-xs btn-primary">
                                                <i class="fa fa-eye"></i> View Job
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No applications found in this period</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 text-center">
                                {{ $applications->appends(request()->query())->links() }}
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
    .img-circle {
        border-radius: 50%;
        object-fit: cover;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#applicationsTable').DataTable({
        "pageLength": 25,
        "ordering": true,
        "info": true,
        "searching": true,
        "paging": false
    });
});
</script>
@endpush