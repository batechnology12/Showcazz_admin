@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i></li>
                <li><span>Posts Management</span></li>
            </ul>
        </div>

        <h3 class="page-title">Manage Posts</h3>

        @include('flash::message')

        {{-- Stats Cards --}}
        <div class="row">
            <div class="col-lg-2 col-md-3 col-sm-6">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number">{{ $stats['total'] }}</div>
                        <div class="desc">Total Posts</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ $stats['published'] }}</div>
                        <div class="desc">Published</div>
                    </div>
                </div>
            </div>
          
            <div class="col-lg-2 col-md-3 col-sm-6">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-image"></i></div>
                    <div class="details">
                        <div class="number">{{ $stats['with_images'] }}</div>
                        <div class="desc">With Images</div>
                    </div>
                </div>
            </div>
            <!--<div class="col-lg-2 col-md-3 col-sm-6">-->
            <!--    <div class="dashboard-stat red">-->
            <!--        <div class="visual"><i class="fa fa-file-archive-o"></i></div>-->
            <!--        <div class="details">-->
            <!--            <div class="number">{{ $stats['with_files'] }}</div>-->
            <!--            <div class="desc">With Files</div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</div>-->
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-settings font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Posts List</span>
                        </div>
                        <div class="actions">
                            <button class="btn btn-success" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                                <i class="fa fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>

                    {{-- Filter Section --}}
                    <div id="filterSection" class="collapse {{ request()->hasAny(['title','category_id','status','author']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.posts.index') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <input type="text" name="title" class="form-control" value="{{ request('title') }}" placeholder="Search by title">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Author</label>
                                            <input type="text" name="author" class="form-control" value="{{ request('author') }}" placeholder="Author name">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Media</label>
                                            <select name="has_media" class="form-control">
                                                <option value="">All</option>
                                                <option value="images" {{ request('has_media') == 'images' ? 'selected' : '' }}>Has Images</option>
                                                <option value="files" {{ request('has_media') == 'files' ? 'selected' : '' }}>Has Files</option>
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
                                            <a href="{{ route('admin.posts.index') }}" class="btn btn-default">
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
                            <table class="table table-striped table-bordered table-hover" id="postsTable">
                                <thead>
                                    <tr>
                                        <th width="20"><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Media</th>
                                        <th>Stats</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($posts as $post)
                                    <tr id="postRow{{ $post->id }}">
                                        <td><input type="checkbox" class="post-checkbox" value="{{ $post->id }}"></td>
                                        <td>{{ $post->id }}</td>
                                        <td>
                                            <strong>{{ \Illuminate\Support\Str::limit($post->title, 50) }}</strong>
                                            @if($post->is_featured)
                                                <span class="label label-warning">Featured</span>
                                            @endif
                                        </td>
                                        <td>{{ $post->author_name ?? 'Unknown' }}</td>
                                        <td>{{ $post->category->name ?? 'N/A' }}</td>
                                        <td>{{ $post->postType->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($post->images && json_decode($post->images))
                                                <span class="label label-info" title="Has Images">
                                                    <i class="fa fa-image"></i> {{ count(json_decode($post->images)) }}
                                                </span>
                                            @endif
                                            @if($post->files && json_decode($post->files))
                                                <span class="label label-warning" title="Has Files">
                                                    <i class="fa fa-file"></i> {{ count(json_decode($post->files)) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="fa fa-eye" title="Views"></i> {{ $post->views_count ?? 0 }}<br>
                                            <i class="fa fa-heart" title="Likes"></i> {{ $post->likes_count ?? 0 }}<br>
                                            <i class="fa fa-comment" title="Comments"></i> {{ $post->comments_count ?? 0 }}
                                        </td>
                                        <td id="statusBadge{{ $post->id }}">
                                            @if($post->is_published)
                                                <span class="label label-success">Published</span>
                                            @else
                                                <span class="label label-warning">Draft</span>
                                            @endif
                                            @if($post->is_active)
                                                <span class="label label-info">Active</span>
                                            @else
                                                <span class="label label-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $post->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.posts.show', $post->id) }}" class="btn btn-xs btn-primary" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <button class="btn btn-xs {{ $post->is_active ? 'btn-warning' : 'btn-info' }} toggle-status" data-id="{{ $post->id }}" title="{{ $post->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="fa {{ $post->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                                @if(!$post->is_active && !$post->is_published)
                                                    <button class="btn btn-xs btn-success approve-post" data-id="{{ $post->id }}" title="Approve">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-xs btn-danger delete-post" data-id="{{ $post->id }}" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No posts found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $posts->firstItem() ?? 0 }} to {{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $posts->appends(request()->query())->links() }}
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
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Reject Post</h4>
            </div>
            <div class="modal-body">
                <textarea id="rejectReason" class="form-control" rows="3" placeholder="Enter rejection reason..."></textarea>
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right"
    };

    // Select all checkbox
    $('#selectAll').change(function() {
        $('.post-checkbox').prop('checked', $(this).prop('checked'));
        $('#bulkDeleteBtn').toggle($('.post-checkbox:checked').length > 0);
    });

    $('.post-checkbox').change(function() {
        $('#bulkDeleteBtn').toggle($('.post-checkbox:checked').length > 0);
    });

    // Toggle status
    $('.toggle-status').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Change Post Status?',
            text: "This will toggle the active/inactive status of the post.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.toggle-status", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Update button
                            if (response.is_active) {
                                btn.removeClass('btn-info').addClass('btn-warning').attr('title', 'Deactivate').html('<i class="fa fa-pause"></i>');
                            } else {
                                btn.removeClass('btn-warning').addClass('btn-info').attr('title', 'Activate').html('<i class="fa fa-play"></i>');
                            }
                            // Update badge - reload status cell or refresh page
                            location.reload(); 
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update status');
                    }
                });
            }
        });
    });

    // Approve post
    $('.approve-post').click(function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve Post?',
            text: "This post will be published and user will be notified.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.approve", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() { location.reload(); }, 1000);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to approve post');
                    }
                });
            }
        });
    });

    // Delete post with reason
    $('.delete-post').click(function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete Post?',
            text: "Please provide a reason for deletion. This will notify the user.",
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Enter reason here...',
            inputAttributes: {
                'aria-label': 'Enter reason here'
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Deletion reason is required')
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.destroy", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: { 
                        _token: '{{ csrf_token() }}',
                        reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#postRow' + id).fadeOut();
                            toastr.success('Post deleted and user notified');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to delete post');
                    }
                });
            }
        });
    });

    // Bulk actions
    $('#bulkDeleteBtn').click(function() {
        var selectedIds = $('.post-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Bulk Delete ' + selectedIds.length + ' Posts?',
            text: "Please provide a reason for deletion. This will notify all affected users.",
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Enter reason here...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Deletion reason is required')
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.bulk-action") }}',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        post_ids: selectedIds,
                        reason: result.value,
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
                    error: function() {
                        toastr.error('Failed to delete posts');
                    }
                });
            }
        });
    });
});
</script>
@endpush