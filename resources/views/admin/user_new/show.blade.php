{{-- resources/views/admin/user_new/show.blade.php --}}
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
                    <a href="{{ route('users.index') }}">Users</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>{{ $user->getName() }}</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ route('users.export', $user->id) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export Data
                </a>
                <!--<button class="btn btn-sm btn-info send-notification" data-id="{{ $user->id }}">-->
                <!--    <i class="fa fa-envelope"></i> Send Notification-->
                <!--</button>-->
            </div>
        </div>
        <!-- END PAGE BAR -->

        @include('flash::message')

        {{-- User Header --}}
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <!--<div class="portlet-title">-->
                    <!--    <div class="caption">-->
                    <!--        <i class="icon-user font-dark"></i>-->
                    <!--        <span class="caption-subject font-dark sbold uppercase">User Overview</span>-->
                    <!--    </div>-->
                    <!--    <div class="actions">-->
                    <!--        <div class="btn-group">-->
                    <!--            <button class="btn btn-circle btn-{{ $user->is_active ? 'success' : 'danger' }} btn-sm" id="toggleUserStatus">-->
                    <!--                <i class="fa fa-power-off"></i> {{ $user->is_active ? 'Active' : 'Inactive' }}-->
                    <!--            </button>-->
                    <!--            <button class="btn btn-circle btn-{{ $user->is_featured ? 'warning' : 'default' }} btn-sm" id="toggleUserFeatured">-->
                    <!--                <i class="fa fa-star"></i> {{ $user->is_featured ? 'Featured' : 'Not Featured' }}-->
                    <!--            </button>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                @if($user->image)
                                    <img src="{{ filter_var($user->image, FILTER_VALIDATE_URL) ? $user->image : (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/'.$user->image)) }}" alt="{{ $user->getName() }}" class="img-responsive img-circle" style="max-width: 150px; max-height: 150px;">
                                @else
                                    <div class="well well-sm" style="min-height: 150px; line-height: 150px;">
                                        <i class="fa fa-user fa-3x"></i>
                                    </div>
                                @endif
                                
                                <h4>{{ $user->unique_id ?? 'No ID' }}</h4>
                                <!--<div class="margin-top-10">-->
                                <!--    <span class="label label-{{ $user->email_verified_at ? 'success' : 'warning' }}">-->
                                <!--        {{ $user->email_verified_at ? 'Email Verified' : 'Email Not Verified' }}-->
                                <!--    </span>-->
                                <!--</div>-->
                            </div>
                            <div class="col-md-5">
                                <h3>{{ $user->getName() }}</h3>
                                <p><i class="fa fa-envelope"></i> {{ $user->email }}</p>
                                <p><i class="fa fa-phone"></i> {{ $user->phone ?? 'N/A' }}</p>
                                <!--<p><i class="fa fa-tag"></i> {{ $user->headline ?? 'N/A' }}</p>-->
                                <!--<p><i class="fa fa-map-marker"></i> -->
                                <!--    @if($user->city)-->
                                <!--        {{ $user->city->city ?? '' }},-->
                                <!--    @endif-->
                                <!--    @if($user->state)-->
                                <!--        {{ $user->state->state ?? '' }},-->
                                <!--    @endif-->
                                <!--    @if($user->country)-->
                                <!--        {{ $user->country->country ?? '' }}-->
                                <!--    @endif-->
                                <!--</p>-->
                                <p><i class="fa fa-calendar"></i> Joined: {{ $user->created_at->format('d M Y h:i A') }}</p>
                                <!--<p><i class="fa fa-birthday-cake"></i> Age: {{ $user->getAge() ?? 'N/A' }}</p>-->
                            </div>
                            <div class="col-md-5">
                                <h4>Personal Details</h4>
                                <p><strong>User Type:</strong> <span class="label label-info">{{ ucfirst($user->usertype ?? 'user') }}</span></p>
                                <!--<p><strong>Gender:</strong> {{ $user->gender->gender ?? 'N/A' }}</p>-->
                                <p><strong>Unique ID:</strong> {{ $user->unique_id ?? 'N/A' }}</p>
                                <p><strong>college name Level:</strong> {{ $user->college_name  ?? 'N/A' }}</p>
                                <p><strong>School:</strong> {{ $user->school_name ?? 'N/A' }}</p>
                                <!--<p><strong>Industry:</strong> {{ $user->industry->industry ?? 'N/A' }}</p>-->
                                <!--<p><strong>Functional Area:</strong> {{ $user->functionalArea->functional_area ?? 'N/A' }}</p>-->
                            </div>
                        </div>
                        
                        @if($user->getProfileSummary('summary'))
                        <div class="row margin-top-20">
                            <div class="col-md-12">
                                <h4>Summary</h4>
                                <div class="well">
                                    {{ $user->getProfileSummary('summary') }}
                                </div>
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
                        <div class="number">{{ $analytics['connections']['followers'] }}</div>
                        <div class="desc">Followers</div>
                        <small>Following: {{ $analytics['connections']['following'] }}</small>
                    </div>
                   
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual">
                        <i class="fa fa-briefcase"></i>
                    </div>
                    <div class="details">
                        <div class="number">{{ $analytics['applications']['total'] }}</div>
                        <div class="desc">Job Applications</div>
                        <small>{{ $analytics['applications']['recent'] }} this month</small>
                    </div>
                   
                </div>
            </div>
        </div>

        {{-- Area of Interests & Skills --}}
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject">Area of Interests</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @forelse($areaOfInterests as $interest)
                            <span class="label label-info" style="margin: 2px; display: inline-block; font-size: 12px;">
                                {{ $interest->job_title }}
                            </span>
                        @empty
                            <p class="text-muted">No area of interests specified</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cog"></i>
                            <span class="caption-subject">Skills</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @forelse($skills as $skill)
                            <span class="label label-success" style="margin: 2px; display: inline-block; font-size: 12px;">
                                {{ $skill->job_skill }}
                            </span>
                        @empty
                            <p class="text-muted">No skills specified</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs for Posts, Engagement, Connections, Applications, Activity --}}
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
                                <a href="#connections" aria-controls="connections" role="tab" data-toggle="tab">
                                    <i class="fa fa-users"></i> Connections
                                </a>
                            </li>
                           
                            <li role="presentation">
                                <a href="#activity" aria-controls="activity" role="tab" data-toggle="tab">
                                    <i class="fa fa-clock-o"></i> Recent Activity
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="portlet-body">
                        <div class="tab-content">
                            {{-- Posts Tab --}}
                            <div role="tabpanel" class="tab-pane active" id="posts">
                                @include('admin.user_new.partials.posts_tab', [
                                    'posts' => $posts, 
                                    'jobPosts' => $jobPosts, 
                                    'regularPosts' => $regularPosts
                                ])
                            </div>

                            {{-- Engagement Tab --}}
                            <div role="tabpanel" class="tab-pane" id="engagement">
                                @include('admin.user_new.partials.engagement_tab', [
                                    'likes' => $likes ?? collect(),
                                    'comments' => $comments ?? collect(),
                                    'shares' => $shares ?? collect(),
                                    'analytics' => $analytics
                                ])
                            </div>

                            {{-- Connections Tab --}}
                            <div role="tabpanel" class="tab-pane" id="connections">
                                @include('admin.user_new.partials.connections_tab', [
                                    'followers' => $followers,
                                    'following' => $following,
                                    'pendingRequests' => $pendingRequests
                                ])
                            </div>

                           

                            {{-- Activity Tab --}}
                            <div role="tabpanel" class="tab-pane" id="activity">
                                @include('admin.user_new.partials.activity_tab', [
                                    'activities' => $recentActivity
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
            <form action="{{ route('users.send-notification', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Send Notification to {{ $user->getName() }}</h4>
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
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .dashboard-stat .details .number { font-size: 28px; }
    .dashboard-stat .details .desc { font-size: 14px; }
    .dashboard-stat .details small { font-size: 11px; color: #fff; opacity: 0.8; }
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
        var tables = ['#allPostsTable', '#jobPostsTable', '#regularPostsTable', '#likesTable', '#commentsTable', '#sharesTable', '#viewsTable', '#followersTable', '#followingTable', '#applicationsTable'];
        
        tables.forEach(function(tableId) {
            if ($.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            if ($(tableId).length) {
                $(tableId).DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "paging": false
                });
            }
        });
    }, 500);

    // Toggle user status
    $('#toggleUserStatus').click(function() {
        var button = $(this);
        var id = {{ $user->id }};
        var newStatus = {{ $user->is_active ? 0 : 1 }};
        
        $.ajax({
            url: '{{ route("users.update-status", ":id") }}'.replace(':id', id),
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
    $('#toggleUserFeatured').click(function() {
        var button = $(this);
        var id = {{ $user->id }};
        var newFeatured = {{ $user->is_featured ? 0 : 1 }};
        
        $.ajax({
            url: '{{ route("users.update-featured", ":id") }}'.replace(':id', id),
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
        window.location.href = '';
    });

    // Send notification button
    $('.send-notification').click(function() {
        $('#sendNotificationModal').modal('show');
    });
});
</script>
@endpush