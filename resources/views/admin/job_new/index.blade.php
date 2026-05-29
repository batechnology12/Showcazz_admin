{{-- resources/views/admin/job_new/index.blade.php --}}
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
                    <span>Jobs & Opportunities</span>
                </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Manage Jobs & Opportunities</h3>
        <!-- END PAGE TITLE -->

        @include('flash::message')

        {{-- Stats Cards --}}
        <div class="row">
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual">
                        <i class="fa fa-briefcase"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['total'] }}</div>
                        <div class="desc">Total Opportunities</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['active'] }}</div>
                        <div class="desc">Active</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual">
                        <i class="fa fa-graduation-cap"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['internships'] }}</div>
                        <div class="desc">Internships</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual">
                        <i class="fa fa-tasks"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['mini_missions'] }}</div>
                        <div class="desc">Mini Missions</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-mortar-board"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['fresher_roles'] }}</div>
                        <div class="desc">Fresher Roles</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $stats['total_applications'] }}</div>
                        <div class="desc">Applications</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-settings font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Opportunities List</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-success" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    {{-- Filter Section --}}
                    <div id="filterSection" class="collapse {{ request()->hasAny(['title','type','status','company']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.jobs.index') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <input type="text" name="title" class="form-control" value="{{ request('title') }}" placeholder="Search by title">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select name="type" class="form-control">
                                                <option value="">All Types</option>
                                                <option value="mini_mission" {{ request('type') == 'mini_mission' ? 'selected' : '' }}>Mini Mission</option>
                                                <option value="internship" {{ request('type') == 'internship' ? 'selected' : '' }}>Internship</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Work Mode</label>
                                            <select name="work_mode" class="form-control">
                                                <option value="">All</option>
                                                <option value="onsite" {{ request('work_mode') == 'onsite' ? 'selected' : '' }}>Onsite</option>
                                                <option value="remote" {{ request('work_mode') == 'remote' ? 'selected' : '' }}>Remote</option>
                                                <option value="hybrid" {{ request('work_mode') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Company</label>
                                            <input type="text" name="company" class="form-control" value="{{ request('company') }}" placeholder="Company name">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Location</label>
                                            <input type="text" name="location" class="form-control" value="{{ request('location') }}" placeholder="Location">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Date From</label>
                                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Date To</label>
                                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('admin.jobs.index') }}" class="btn btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
                                        <th>Applications</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Author</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                    <tr id="jobRow{{ $job->id }}">
                                        <td>{{ $job->id }}</td>
                                        <td>
                                            <strong>{{ \Illuminate\Support\Str::limit($job->title, 40) }}</strong>
                                            @if($job->is_featured)
                                                <span class="label label-warning">Featured</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->category_id == 5)
                                                <span class="label label-success">Mini Mission</span>
                                            @elseif($job->category_id == 6)
                                                <span class="label label-purple">Internship</span>
                                             @elseif($job->category_id == 7)
                                                <span class="label label-warning">Fresher Role</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->company_name)
                                                {{ $job->company_name }}
                                            @elseif($job->user && $job->user->usertype === 'company')
                                                {{ $job->user->company_name ?? $job->user->name }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                       
                                        <td>
                                            @if($job->work_mode)
                                                <span class="label label-info">{{ ucfirst($job->work_mode) }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.jobs.applications', $job->id) }}" class="badge badge-primary" title="View Applications">
                                                {{ $job->applications_count ?? 0 }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($job->application_deadline)
                                                @if(\Carbon\Carbon::parse($job->application_deadline)->isPast())
                                                    <span class="label label-danger">{{ \Carbon\Carbon::parse($job->application_deadline)->format('d M Y') }}</span>
                                                @else
                                                    {{ \Carbon\Carbon::parse($job->application_deadline)->format('d M Y') }}
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($job->is_published && $job->is_active)
                                                <span class="label label-success">Active</span>
                                            @elseif(!$job->is_active)
                                                <span class="label label-danger">Inactive</span>
                                            @elseif(!$job->is_published)
                                                <span class="label label-warning">Draft</span>
                                            @endif
                                            @if($job->application_deadline && \Carbon\Carbon::parse($job->application_deadline)->isPast())
                                                <span class="label label-danger">Expired</span>
                                            @endif
                                        </td>
                                        <td>{{ $job->author_name ?? 'Unknown' }}</td>
                                        <td>{{ $job->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.jobs.show', $job->id) }}" class="btn btn-xs btn-primary" title="View Details">
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
                                        <td colspan="12" class="text-center">No jobs or internships found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $jobs->firstItem() ?? 0 }} to {{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $jobs->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Reject Opportunity</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Reason for rejection</label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Enter rejection reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .dashboard-stat .details .number { font-size: 24px; }
    .dashboard-stat .details .desc { font-size: 14px; }
    .dashboard-stat { margin-bottom: 20px; }
    .dashboard-stat.blue { background-color: #3598dc; }
    .dashboard-stat.green { background-color: #32c5d2; }
    .dashboard-stat.purple { background-color: #8E44AD; }
    .dashboard-stat.yellow { background-color: #F4D03F; }
    .dashboard-stat.red { background-color: #E7505A; }
    .dashboard-stat.info { background-color: #5bc0de; }
    .badge-primary { background-color: #3598dc; color: white; padding: 3px 6px; border-radius: 3px; }
    .label-purple { background-color: #8E44AD; color: white; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };


    // Approve job
    $('.approve-job').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Approve Opportunity?',
            text: "This opportunity will be published and made active",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.jobs.approve", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to approve opportunity');
                    }
                });
            }
        });
    });

    // Toggle featured
    $('.toggle-feature').click(function() {
        var id = $(this).data('id');
        var button = $(this);
        
        $.ajax({
            url: '{{ route("admin.jobs.feature", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-default').addClass('btn-warning').html('<i class="fa fa-star"></i>');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-default').html('<i class="fa fa-star"></i>');
                    }
                    toastr.success(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to update featured status');
            }
        });
    });
});
</script>
@endpush