{{-- resources/views/admin/job_new/show.blade.php --}}
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
                    <a href="{{ route('admin.jobs.index') }}">Jobs & Opportunities</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>{{ $job->title }}</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ route('admin.jobs.applications', $job->id) }}" class="btn btn-sm btn-info">
                    <i class="fa fa-users"></i> View Applications
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <div class="row">
            <div class="col-md-12">
                <!-- Job Header -->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-briefcase font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Opportunity Details</span>
                        </div>
                        <div class="actions">
                            @if(!$job->is_active)
                                <button class="btn btn-circle btn-success btn-sm" id="approveJobBtn" data-id="{{ $job->id }}">
                                    <i class="fa fa-check"></i> Approve
                                </button>
                                <button class="btn btn-circle btn-danger btn-sm" id="rejectJobBtn" data-id="{{ $job->id }}">
                                    <i class="fa fa-times"></i> Reject
                                </button>
                            @endif
                            <button class="btn btn-circle btn-{{ $job->is_featured ? 'warning' : 'default' }} btn-sm" id="toggleFeatureBtn" data-id="{{ $job->id }}">
                                <i class="fa fa-star"></i> {{ $job->is_featured ? 'Featured' : 'Not Featured' }}
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h2>{{ $job->title }}</h2>
                                <p class="text-muted">
                                    <i class="fa fa-building"></i> {{ $job->company_name ?? 'N/A' }} | 
                                    <i class="fa fa-map-marker"></i> {{ $job->job_location ?? 'N/A' }} | 
                                    <i class="fa fa-clock-o"></i> {{ ucfirst($job->work_mode ?? 'N/A') }}
                                </p>
                                
                                <div class="well well-sm">
                                    <strong>Type:</strong> 
                                    @if($job->category_id == 5)
                                        <span class="label label-primary">Mini Mission</span>
                                    @elseif($job->category_id == 6)
                                        <span class="label label-success">Internship</span>
                                    @endif
                                    
                                    @if($job->role_type)
                                        | <strong>Role:</strong> {{ ucfirst(str_replace('_', ' ', $job->role_type)) }}
                                    @endif
                                    
                                    @if($job->experience_required)
                                        | <strong>Experience:</strong> {{ $job->experience_required }}
                                    @endif
                                    
                                    <br>
                                    
                                    <strong>Status:</strong>
                                    @if($job->is_published && $job->is_active)
                                        <span class="label label-success">Active</span>
                                    @elseif(!$job->is_active)
                                        <span class="label label-danger">Inactive</span>
                                    @elseif(!$job->is_published)
                                        <span class="label label-warning">Draft</span>
                                    @endif
                                    
                                    @if($job->is_featured)
                                        <span class="label label-warning">Featured</span>
                                    @endif
                                    
                                    @if($job->application_deadline)
                                        <br>
                                        <strong>Deadline:</strong> 
                                        <span class="{{ \Carbon\Carbon::parse($job->application_deadline)->isPast() ? 'text-danger' : 'text-success' }}">
                                            {{ \Carbon\Carbon::parse($job->application_deadline)->format('d M Y') }}
                                            @if(\Carbon\Carbon::parse($job->application_deadline)->isPast())
                                                (Expired)
                                            @else
                                                ({{ \Carbon\Carbon::parse($job->application_deadline)->diffForHumans() }})
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                <h4>Description</h4>
                                <div class="well">
                                    {!! nl2br(e($job->content)) !!}
                                </div>

                                @if($job->short_description)
                                    <h4>Short Description</h4>
                                    <div class="well well-sm">
                                        {{ $job->short_description }}
                                    </div>
                                @endif

                                <h4>Key Deliverables</h4>
                                <div class="well">
                                    {{ $job->key_deliverables ?? 'N/A' }}
                                </div>

                                @if($job->category_id == 5) {{-- Mini Mission --}}
                                    <h4>Mini Mission Details</h4>
                                    <table class="table table-bordered">
                                        @if($job->deliverables)
                                            <tr>
                                                <th style="width: 200px;">Deliverables</th>
                                                <td>{{ $job->deliverables }}</td>
                                            </tr>
                                        @endif
                                        @if($job->timeline_start || $job->timeline_end)
                                            <tr>
                                                <th>Timeline</th>
                                                <td>
                                                    @if($job->timeline_start)
                                                        {{ \Carbon\Carbon::parse($job->timeline_start)->format('d M Y') }}
                                                    @endif
                                                    @if($job->timeline_end)
                                                        - {{ \Carbon\Carbon::parse($job->timeline_end)->format('d M Y') }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                @elseif($job->category_id == 6) {{-- Internship --}}
                                    <h4>Internship Details</h4>
                                    <table class="table table-bordered">
                                        @if($job->internship_duration)
                                            <tr>
                                                <th style="width: 200px;">Duration</th>
                                                <td>{{ $job->internship_duration }}</td>
                                            </tr>
                                        @endif
                                        @if($job->stipend_amount)
                                            <tr>
                                                <th>Stipend</th>
                                                <td>
                                                    {{ $job->stipend_currency ?? '₹' }} {{ number_format($job->stipend_amount) }}
                                                    @if($job->convertible_to_full_time)
                                                        <span class="label label-success">Convertible to Full Time</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                @endif

                                @if(!empty($skills))
                                    <h4>Skills Required</h4>
                                    <div class="well">
                                        @foreach($skills as $skillId => $skillName)
                                            <span class="label label-primary" style="margin: 2px; display: inline-block;">
                                                {{ $skillName }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($job->benefits)
                                    <h4>Benefits</h4>
                                    <div class="well">
                                        {{ $job->benefits }}
                                    </div>
                                @endif

                                @if($job->application_url)
                                    <h4>Application URL</h4>
                                    <div class="well">
                                        <a href="{{ $job->application_url }}" target="_blank">{{ $job->application_url }}</a>
                                    </div>
                                @endif

                                {{-- Images --}}
                                @if(!empty($images))
                                    <h4>Images</h4>
                                    <div class="row">
                                        @foreach($images as $image)
                                            <div class="col-md-3">
                                                <div class="thumbnail">
                                                    <img src="{{ env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image) }}" alt="Job Image" style="max-height: 150px; width: 100%; object-fit: cover;">
                                                    <div class="caption text-center">
                                                        <a href="{{ env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image) }}" target="_blank" class="btn btn-xs btn-primary">View Full</a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Sidebar --}}
                            <div class="col-md-4">
                                {{-- Author Info --}}
                                <div class="portlet light bordered">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-user"></i>
                                            <span class="caption-subject">Posted By</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body text-center">
                                        @if($author && $author['image'])
                                            <img src="{{ $author['image'] }}" class="img-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                        @else
                                            <div class="img-circle text-center" style="width: 80px; height: 80px; background: #ccc; line-height: 80px; margin: 0 auto;">
                                                <i class="fa fa-user fa-2x"></i>
                                            </div>
                                        @endif
                                        <h4>{{ $author['name'] ?? 'Unknown' }}</h4>
                                        <p><span class="label label-info">{{ $author['type'] ?? 'user' }}</span></p>
                                    </div>
                                </div>

                                {{-- Stats --}}
                                <div class="portlet light bordered">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-bar-chart"></i>
                                            <span class="caption-subject">Statistics</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <table class="table table-striped">
                                            <tr>
                                                <th>Views</th>
                                                <td>{{ $job->views_count ?? 0 }}</td>
                                            </tr>
                                            <tr>
                                                <th>Likes</th>
                                                <td>{{ $job->likes_count ?? 0 }}</td>
                                            </tr>
                                            <tr>
                                                <th>Comments</th>
                                                <td>{{ $job->comments_count ?? 0 }}</td>
                                            </tr>
                                            <tr>
                                                <th>Shares</th>
                                                <td>{{ $job->shares_count ?? 0 }}</td>
                                            </tr>
                                            <tr>
                                                <th>Applications</th>
                                                <td>
                                                    <a href="{{ route('admin.jobs.applications', $job->id) }}">
                                                        {{ $job->applications_count ?? 0 }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                {{-- Quick Info --}}
                                <div class="portlet light bordered">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-info-circle"></i>
                                            <span class="caption-subject">Quick Info</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <p><strong>Created:</strong> {{ $job->created_at->format('d M Y h:i A') }}</p>
                                        <p><strong>Updated:</strong> {{ $job->updated_at->format('d M Y h:i A') }}</p>
                                        <p><strong>Post ID:</strong> {{ $job->id }}</p>
                                    </div>
                                </div>
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
<style>
    .thumbnail {
        margin-bottom: 10px;
    }
    .thumbnail img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .well {
        background-color: #f9f9f9;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
    }
    .table-bordered th {
        background-color: #f5f5f5;
    }
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
    $('#approveJobBtn').click(function() {
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

    // Reject job - show modal
    $('#rejectJobBtn').click(function() {
        var id = $(this).data('id');
        $('#confirmRejectBtn').data('id', id);
        $('#rejectModal').modal('show');
    });

    // Confirm reject
    $('#confirmRejectBtn').click(function() {
        var id = $(this).data('id');
        var reason = $('#rejectReason').val();
        
        if (!reason) {
            toastr.warning('Please enter a rejection reason');
            return;
        }
        
        $.ajax({
            url: '{{ route("admin.jobs.reject", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#rejectModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            },
            error: function() {
                toastr.error('Failed to reject opportunity');
            }
        });
    });

    // Toggle featured
    $('#toggleFeatureBtn').click(function() {
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
                        button.removeClass('btn-default').addClass('btn-warning').html('<i class="fa fa-star"></i> Featured');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-default').html('<i class="fa fa-star"></i> Not Featured');
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