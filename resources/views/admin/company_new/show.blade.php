{{-- resources/views/admin/company_new/show.blade.php --}}
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
                    <a href="{{ route('companies.index') }}">Companies</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>{{ $company->company_name ?? $company->name }}</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('companies.index') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ route('companies.export', $company->id) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export Data
                </a>
                <!--<button class="btn btn-sm btn-info" data-toggle="modal" data-target="#sendNotificationModal">-->
                <!--    <i class="fa fa-envelope"></i> Send Notification-->
                <!--</button>-->
            </div>
        </div>
        <!-- END PAGE BAR -->

        @include('flash::message')

        {{-- Company Header --}}
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <!--<div class="portlet-title">-->
                    <!--    <div class="caption">-->
                    <!--        <i class="icon-building font-dark"></i>-->
                    <!--        <span class="caption-subject font-dark sbold uppercase">Company Overview</span>-->
                    <!--    </div>-->
                    <!--    <div class="actions">-->
                    <!--        <div class="btn-group">-->
                    <!--            <button class="btn btn-circle btn-{{ $company->is_active ? 'success' : 'danger' }} btn-sm" id="toggleCompanyStatus">-->
                    <!--                <i class="fa fa-power-off"></i> {{ $company->is_active ? 'Active' : 'Inactive' }}-->
                    <!--            </button>-->
                    <!--            <button class="btn btn-circle btn-{{ $company->is_featured ? 'warning' : 'default' }} btn-sm" id="toggleCompanyFeatured">-->
                    <!--                <i class="fa fa-star"></i> {{ $company->is_featured ? 'Featured' : 'Not Featured' }}-->
                    <!--            </button>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                @if($company->company_logo)
                                    <img src="{{ asset('company_logos/'.$company->company_logo) }}" alt="{{ $company->company_name ?? $company->name }}" class="img-responsive" style="max-width: 150px;">
                                @elseif($company->image)
                                    <img src="{{ asset('user_images/'.$company->image) }}" alt="{{ $company->company_name ?? $company->name }}" class="img-responsive" style="max-width: 150px;">
                                @else
                                    <div class="well well-sm" style="min-height: 150px; line-height: 150px;">No Logo</div>
                                @endif
                                
                                <h4>{{ $company->unique_id ?? 'No ID' }}</h4>
                                @if($company->verified)
                                    <span class="label label-success"><i class="fa fa-check-circle"></i> Verified</span>
                                @endif
                            </div>
                            <div class="col-md-5">
                                <h3>{{ $company->company_name ?? $company->name }}</h3>
                                <p><i class="fa fa-envelope"></i> {{ $company->email }}</p>
                                <p><i class="fa fa-phone"></i> {{ $company->phone ?? 'N/A' }}</p>
                                <p><i class="fa fa-globe"></i> {{ $company->company_website ?? 'N/A' }}</p>
                                <p><i class="fa fa-map-marker"></i> {{ $company->company_location ?? 'N/A' }}</p>
                                <p><i class="fa fa-calendar"></i> Joined: {{ $company->created_at->format('d M Y h:i A') }}</p>
                            </div>
                            <div class="col-md-5">
                                <h4>Details</h4>
                               
                                <p><strong>GST No:</strong> {{ $company->gst_number ?? 'N/A' }}</p>
                                <p><strong>UPI ID:</strong> {{ $company->upi_id ?? 'N/A' }}</p>
                                <p><strong>LinkedIn:</strong> {{ $company->linkedin_url ?? 'N/A' }}</p>
                            </div>
                        </div>
                        
                        <div class="row margin-top-20">
                            <div class="col-md-12">
                                <h4>About Company</h4>
                                <p>{{ $company->company_description ?? 'No description provided.' }}</p>
                            </div>
                        </div>

                        @if(!empty($documents))
                        <div class="row margin-top-20">
                            <div class="col-md-12">
                                <h4>Documents</h4>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Comment</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($documents as $key => $document)
                                            @if($document['file'])
                                            <tr>
                                                <td>{{ $document['label'] }}</td>
                                                <td>
                                                    @if($document['status'] == 0)
                                                        <span class="label label-warning">Pending</span>
                                                    @elseif($document['status'] == 1)
                                                        <span class="label label-success">Approved</span>
                                                    @elseif($document['status'] == 2)
                                                        <span class="label label-danger">Rejected</span>
                                                    @endif
                                                </td>
                                                <td>{{ $document['comment'] ?? 'No comments' }}</td>
                                                <td>
                                                    <a href="{{ $document['url'] }}" target="_blank" class="btn btn-xs btn-primary">
                                                        <i class="fa fa-eye"></i> View
                                                    </a>
                                                    <button class="btn btn-xs btn-success verify-document" 
                                                            data-document="{{ $key }}">
                                                        <i class="fa fa-check"></i> Verify
                                                    </button>
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Analytics Cards --}}
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual">
                        <i class="fa fa-file-text"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $analytics['posts']['total'] }}</div>
                        <div class="desc">Total Posts</div>
                        <small>{{ $analytics['posts']['job_posts'] }} Jobs | {{ $analytics['posts']['regular_posts'] }} Regular</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual">
                        <i class="fa fa-heart"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ number_format($analytics['engagement']['likes'] + $analytics['engagement']['comments'] + $analytics['engagement']['shares']) }}</div>
                        <div class="desc">Total Engagement</div>
                        <small>{{ $analytics['engagement']['likes'] }} Likes | {{ $analytics['engagement']['comments'] }} Comments | {{ $analytics['engagement']['shares'] }} Shares</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $analytics['followers']['total'] }}</div>
                        <div class="desc">Total Followers</div>
                        <small>{{ $analytics['followers']['new_this_month'] }} new this month</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-eye"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ number_format($analytics['engagement']['views']) }}</div>
                        <div class="desc">Total Views</div>
                        <small>{{ $analytics['engagement']['unique_viewers'] }} Unique Viewers</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs for Posts, Engagement, Followers, Activity --}}
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#posts" aria-controls="posts" role="tab" data-toggle="tab">
                                    <i class="fa fa-file-text"></i> Posts ({{ $posts->total() }})
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#engagement" aria-controls="engagement" role="tab" data-toggle="tab">
                                    <i class="fa fa-heart"></i> Engagement
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#followers" aria-controls="followers" role="tab" data-toggle="tab">
                                    <i class="fa fa-users"></i> Followers ({{ $followers->total() }})
                                </a>
                            </li>
                            <!--<li role="presentation">-->
                            <!--    <a href="#jobs" aria-controls="jobs" role="tab" data-toggle="tab">-->
                            <!--        <i class="fa fa-briefcase"></i> Jobs ({{ $analytics['jobs']['total'] }})-->
                            <!--    </a>-->
                            <!--</li>-->
                            <li role="presentation">
                                <a href="#activity" aria-controls="activity" role="tab" data-toggle="tab">
                                    <i class="fa fa-clock-o"></i> Recent Activity
                                </a>
                            </li>
                           
                            <li role="presentation">
                                <a href="#postTypes" aria-controls="postTypes" role="tab" data-toggle="tab">
                                    <i class="fa fa-tags"></i> Post Types
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="portlet-body">
                        <div class="tab-content">
                            {{-- Posts Tab --}}
                            <div role="tabpanel" class="tab-pane active" id="posts">
                                @include('admin.company_new.partials.posts_tab', ['posts' => $posts, 'jobPosts' => $jobPosts, 'regularPosts' => $regularPosts])
                            </div>

                            {{-- Engagement Tab --}}
                            <div role="tabpanel" class="tab-pane" id="engagement">
                                @include('admin.company_new.partials.engagement_tab', [
                                    'likes' => $likes ?? collect(),
                                    'comments' => $comments ?? collect(),
                                    'shares' => $shares ?? collect(),
                                    'views' => $views ?? collect(),
                                    'analytics' => $analytics
                                ])
                            </div>

                            {{-- Followers Tab --}}
                            <div role="tabpanel" class="tab-pane" id="followers">
                                @include('admin.company_new.partials.followers_tab', ['followers' => $followers])
                            </div>

                            {{-- Jobs Tab --}}
                            <div role="tabpanel" class="tab-pane" id="jobs">
                                @include('admin.company_new.partials.jobs_tab', ['jobs' => $company->jobs()->paginate(10)])
                            </div>

                            {{-- Activity Tab --}}
                            <div role="tabpanel" class="tab-pane" id="activity">
                                @include('admin.company_new.partials.activity_tab', ['activities' => $recentActivity])
                            </div>

                            {{-- Analytics Tab --}}
                            <div role="tabpanel" class="tab-pane" id="analytics">
                                @include('admin.company_new.partials.analytics_tab', ['analytics' => $analytics])
                            </div>

                            {{-- Post Types Tab --}}
                            <div role="tabpanel" class="tab-pane" id="postTypes">
                                @include('admin.company_new.partials.post_types_tab', [
                                    'postTypeSummary' => $postTypeSummary,
                                    'categorySummary' => $categorySummary
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Send Notification Modal --}}
<div class="modal fade" id="sendNotificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('companies.send-notification', $company->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Send Notification to {{ $company->company_name ?? $company->name }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Document Verification Modal --}}
<div class="modal fade" id="verifyDocumentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="verifyDocumentForm" method="POST">
                @csrf
                <input type="hidden" name="document_type" id="document_type">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Verify Document</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="1">Approve</option>
                            <option value="2">Reject</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Comment (Optional)</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .dashboard-stat .details .number { font-size: 28px; }
    .dashboard-stat .details .desc { font-size: 14px; }
    .dashboard-stat .details small { font-size: 11px; color: #fff; opacity: 0.8; }
    .timeline { position: relative; padding: 20px 0; list-style: none; }
    .timeline-item { position: relative; margin-bottom: 20px; }
    .timeline-badge { position: absolute; top: 0; left: 0; width: 40px; height: 40px; border-radius: 50%; text-align: center; line-height: 40px; color: #fff; z-index: 100; }
    .timeline-body { margin-left: 60px; padding: 15px; background: #f4f4f4; border-radius: 4px; }
    .timeline-body-content { color: #333; }
    .bg-blue { background-color: #36c6d3; }
    .bg-green { background-color: #26c281; }
    .bg-red { background-color: #e7505a; }
    .bg-purple { background-color: #8775a7; }
    .bg-orange { background-color: #F4D03F; }
    .badge-primary { background-color: #36c6d3; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-info { background-color: #5bc0de; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-warning { background-color: #f0ad4e; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-success { background-color: #5cb85c; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-danger { background-color: #d9534f; color: white; padding: 3px 6px; border-radius: 3px; }
    .well { min-height: 20px; padding: 19px; margin-bottom: 20px; background-color: #f5f5f5; border: 1px solid #e3e3e3; border-radius: 4px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap.min.js"></script>
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

    // Initialize DataTables
    setTimeout(function() {
        // Destroy existing DataTables instances first
        var tables = ['#allPostsTable', '#jobPostsTable', '#regularPostsTable', '#likesTable', '#commentsTable', '#sharesTable', '#viewsTable', '#followersTable', '#jobsTable'];
        
        tables.forEach(function(tableId) {
            if ($(tableId).length && $.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
        });

        // Initialize DataTables only if elements exist
        if ($('#allPostsTable').length) {
            $('#allPostsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#jobPostsTable').length) {
            $('#jobPostsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#regularPostsTable').length) {
            $('#regularPostsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#likesTable').length) {
            $('#likesTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#commentsTable').length) {
            $('#commentsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#sharesTable').length) {
            $('#sharesTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#viewsTable').length) {
            $('#viewsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#followersTable').length) {
            $('#followersTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
        if ($('#jobsTable').length) {
            $('#jobsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true,
                "paging": false
            });
        }
    }, 500);

    // Toggle company status
    $('#toggleCompanyStatus').click(function() {
        var button = $(this);
        var id = {{ $company->id }};
        var newStatus = {{ $company->is_active ? 0 : 1 }};
        
        $.ajax({
            url: '{{ route("companies.update-status", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                is_active: newStatus,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-danger').addClass('btn-success').html('<i class="fa fa-power-off"></i> Active');
                    } else {
                        button.removeClass('btn-success').addClass('btn-danger').html('<i class="fa fa-power-off"></i> Inactive');
                    }
                    toastr.success(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to update status');
            }
        });
    });

    // Toggle featured status
    $('#toggleCompanyFeatured').click(function() {
        var button = $(this);
        var id = {{ $company->id }};
        var newFeatured = {{ $company->is_featured ? 0 : 1 }};
        
        $.ajax({
            url: '{{ route("companies.update-featured", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                is_featured: newFeatured,
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

    // View post details
    $(document).on('click', '.view-post', function() {
        var postId = $(this).data('id');
        var urlTemplate = "{{ route('companies.post.show', ':id') }}";
        var url = urlTemplate.replace(':id', postId);
        window.location.href = url;
    });
   
    // Delete post
    $(document).on('click', '.delete-post', function() {
        var postId = $(this).data('id');
        var row = $(this).closest('tr');
        
        Swal.fire({
            title: 'Delete Post?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.post.delete", ":id") }}'.replace(':id', postId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        row.fadeOut();
                        toastr.success('Post deleted successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete post');
                    }
                });
            }
        });
    });

    // Toggle post status
    $(document).on('click', '.toggle-post-status', function() {
        var button = $(this);
        var postId = button.data('id');
        
        $.ajax({
            url: '{{ route("companies.post.toggle-status", ":id") }}'.replace(':id', postId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-warning').addClass('btn-success').html('<i class="fa fa-check"></i> Published');
                    } else {
                        button.removeClass('btn-success').addClass('btn-warning').html('<i class="fa fa-eye-slash"></i> Draft');
                    }
                    toastr.success('Post status updated');
                }
            },
            error: function() {
                toastr.error('Failed to update post status');
            }
        });
    });

    // Delete comment
    $(document).on('click', '.delete-comment', function() {
        var commentId = $(this).data('id');
        var row = $(this).closest('tr');
        
        Swal.fire({
            title: 'Delete Comment?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.comment.delete", ":id") }}'.replace(':id', commentId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        row.fadeOut();
                        toastr.success('Comment deleted successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete comment');
                    }
                });
            }
        });
    });

    // Toggle comment status
    $(document).on('click', '.toggle-comment-status', function() {
        var button = $(this);
        var commentId = button.data('id');
        
        $.ajax({
            url: '{{ route("companies.comment.toggle", ":id") }}'.replace(':id', commentId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-danger').addClass('btn-success').text('Active');
                    } else {
                        button.removeClass('btn-success').addClass('btn-warning').text('Inactive');
                    }
                    toastr.success('Comment status updated');
                }
            },
            error: function() {
                toastr.error('Failed to update comment status');
            }
        });
    });

    // Verify document
    $(document).on('click', '.verify-document', function() {
        var documentType = $(this).data('document');
        var urlTemplate = "{{ route('companies.verify-document', $company->id) }}";
        
        $('#document_type').val(documentType);
        $('#verifyDocumentForm').attr('action', urlTemplate);
        $('#verifyDocumentModal').modal('show');
    });

    // Bulk delete posts
    $('#bulkDeletePosts').click(function() {
        var selectedIds = [];
        $('.post-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            toastr.warning('Please select at least one post');
            return;
        }
        
        Swal.fire({
            title: 'Delete ' + selectedIds.length + ' Posts?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.posts.bulk-delete") }}',
                    type: 'POST',
                    data: {
                        post_ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            selectedIds.forEach(function(id) {
                                $('#postRow' + id).fadeOut();
                            });
                            toastr.success(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete posts');
                    }
                });
            }
        });
    });

    // Select all posts
    $('#selectAllPosts').change(function() {
        $('.post-checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush