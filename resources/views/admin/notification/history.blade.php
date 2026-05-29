@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('admin.notifications.dashboard') }}">Notification Dashboard</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Notification History</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Notification History</h3>

        @include('flash::message')

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-history"></i>
                            <span class="caption-subject font-dark sbold uppercase">Sent Notifications</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div id="filterSection" class="collapse {{ request()->hasAny(['type','date_from','date_to']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.notifications.history') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select name="type" class="form-control">
                                                <option value="">All Types</option>
                                                <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Users</option>
                                                <option value="companies" {{ request('type') == 'companies' ? 'selected' : '' }}>Companies</option>
                                                <option value="students" {{ request('type') == 'students' ? 'selected' : '' }}>Students</option>
                                                <option value="professionals" {{ request('type') == 'professionals' ? 'selected' : '' }}>Professionals</option>
                                                <option value="subscribed" {{ request('type') == 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                                            </select>
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
                                                <i class="fa fa-search"></i> Filter
                                            </button>
                                            <a href="{{ route('admin.notifications.history') }}" class="btn btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Recipients</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Sent By</th>
                                    <th>Sent At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>
                                        <strong>{{ \Illuminate\Support\Str::limit($notification->title, 50) }}</strong>
                                    </td>
                                    <td>
                                        <span class="label label-info">{{ ucfirst($notification->target_type) }}</span>
                                    </td>
                                    <td>{{ number_format($notification->total_recipients) }}</td>
                                    <td>
                                        <span class="label label-success">{{ $notification->success_count }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-danger">{{ $notification->failed_count }}</span>
                                    </td>
                                    <td>{{ $notification->sentBy->name ?? 'System' }}</td>
                                    <td>{{ $notification->created_at->format('d M Y h:i A') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.notifications.view', $notification->id) }}" 
                                               class="btn btn-xs btn-primary" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-xs btn-danger delete-notification" 
                                                    data-id="{{ $notification->id }}"
                                                    title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No notifications found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $notifications->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('.delete-notification').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Notification?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.notifications.delete", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success')
                                .then(() => location.reload());
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to delete notification', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush