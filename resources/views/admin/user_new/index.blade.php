{{-- resources/views/admin/user_new/index.blade.php --}}
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
                    <span>Users</span>
                </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Manage Users</h3>
        <!-- END PAGE TITLE -->

        @include('flash::message')

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-users font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Users List</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-success" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    {{-- Filter Section --}}
                    <div id="filterSection" class="collapse {{ request()->hasAny(['name','email','usertype','is_active','is_featured']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('users.index') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ request('name') }}" placeholder="Search by name">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="text" name="email" class="form-control" value="{{ request('email') }}" placeholder="Search by email">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>User Type</label>
                                            <select name="usertype" class="form-control">
                                                <option value="-1">All</option>
                                                <option value="student" {{ request('usertype') == 'student' ? 'selected' : '' }}>Student</option>
                                                <option value="professional" {{ request('usertype') == 'professional' ? 'selected' : '' }}>Professional</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="is_active" class="form-control">
                                                <option value="-1">All</option>
                                                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Featured</label>
                                            <select name="is_featured" class="form-control">
                                                <option value="-1">All</option>
                                                <option value="1" {{ request('is_featured') == '1' ? 'selected' : '' }}>Featured</option>
                                                <option value="0" {{ request('is_featured') == '0' ? 'selected' : '' }}>Not Featured</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
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
                                    <div class="col-md-6">
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('users.index') }}" class="btn btn-default">
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
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <!--<th>Featured</th>-->
                                        <th>Posts</th>
                                        <th>Followers</th>
                                        <th>Following</th>
                                        <th>Engagement</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr id="userRow{{ $user->id }}">
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            @if($user->image)
                                                <img src="{{ asset('user_images/'.$user->image) }}" alt="{{ $user->getName() }}" style="max-width: 50px; max-height: 50px; border-radius: 50%;">
                                            @else
                                                <span class="label label-default">No Photo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $user->getName() }}</strong><br>
                                            <small>{{ $user->unique_id ?? 'No ID' }}</small>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="label label-info">{{ ucfirst($user->usertype ?? 'user') }}</span>
                                        </td>
                                        <td>{{ $user->phone ?? 'N/A' }}</td>
                                        <td>
                                            <button class="btn btn-sm {{ $user->is_active ? 'btn-success' : 'btn-danger' }} toggle-status" 
                                                    data-id="{{ $user->id }}"
                                                    data-status="{{ $user->is_active ? 0 : 1 }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <!--<td>-->
                                        <!--    <button class="btn btn-sm {{ $user->is_featured ? 'btn-warning' : 'btn-default' }} toggle-featured" -->
                                        <!--            data-id="{{ $user->id }}"-->
                                        <!--            data-featured="{{ $user->is_featured ? 0 : 1 }}">-->
                                        <!--        {{ $user->is_featured ? 'Featured' : 'Not Featured' }}-->
                                        <!--    </button>-->
                                        <!--</td>-->
                                        <td>
                                            <a href="{{ route('users.show', $user->id) }}#posts" class="badge badge-primary" title="View Posts">
                                                {{ $user->posts_count ?? 0 }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $user->followers_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">{{ $user->following_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ number_format($user->total_engagement ?? 0) }}</span>
                                        </td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('users.show', $user->id) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-user" 
                                                        data-id="{{ $user->id }}"
                                                        title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="14" class="text-center">No users found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $users->appends(request()->query())->links() }}
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
            <form action="{{ route('users.send-notification', ':id') }}" method="POST" id="notificationForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Send Notification</h4>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .badge-primary { background-color: #36c6d3; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-info { background-color: #5bc0de; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-warning { background-color: #f0ad4e; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-success { background-color: #5cb85c; color: white; padding: 3px 6px; border-radius: 3px; }
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

    // Toggle status
    $('.toggle-status').click(function() {
        var button = $(this);
        var id = button.data('id');
        var newStatus = button.data('status');
        
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
                        button.removeClass('btn-danger').addClass('btn-success').text('Active');
                        button.data('status', 0);
                    } else {
                        button.removeClass('btn-success').addClass('btn-danger').text('Inactive');
                        button.data('status', 1);
                    }
                    toastr.success(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to update status');
            }
        });
    });

    // Toggle featured
    $('.toggle-featured').click(function() {
        var button = $(this);
        var id = button.data('id');
        var newFeatured = button.data('featured');
        
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
                        button.removeClass('btn-default').addClass('btn-warning').text('Featured');
                        button.data('featured', 0);
                    } else {
                        button.removeClass('btn-warning').addClass('btn-default').text('Not Featured');
                        button.data('featured', 1);
                    }
                    toastr.success(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to update featured status');
            }
        });
    });

    // Delete user
    $('.delete-user').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the user and all their posts. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("users.destroy", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#userRow' + id).fadeOut();
                        toastr.success('User deleted successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete user');
                    }
                });
            }
        });
    });

    // Send notification
    $('.send-notification').click(function() {
        var id = $(this).data('id');
        var form = $('#notificationForm');
        form.attr('action', form.attr('action').replace(':id', id));
        $('#sendNotificationModal').modal('show');
    });
});
</script>
@endpush