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
                    <span>Subscription Packages</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Manage Subscription Packages</h3>

        @include('flash::message')

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-cube font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Packages List</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div id="filterSection" class="collapse {{ request()->hasAny(['package_for','is_active','search']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.subscriptions.packages') }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="is_active" class="form-control">
                                                <option value="-1">All</option>
                                                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Search</label>
                                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by title...">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('admin.subscriptions.packages') }}" class="btn btn-default">
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
                                        <th>Title</th>
                                        <th>For</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Listings</th>
                                        <th>Status</th>
                                        <th>Purchases</th>
                                        <th>Revenue</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($packages as $package)
                                    <tr id="packageRow{{ $package->id }}">
                                        <td>{{ $package->id }}</td>
                                        <td>
                                            <strong>{{ $package->package_title }}</strong><br>
                                            <small>{{ $package->package_subtitle }}</small>
                                        </td>
                                        <td>
                                            @if($package->package_for == 'employer')
                                                <span class="label label-primary">Employer</span>
                                            @elseif($package->package_for == 'job_seeker')
                                                <span class="label label-success">Job Seeker</span>
                                            @elseif($package->package_for == 'cv_search')
                                                <span class="label label-info">CV Search</span>
                                            @elseif($package->package_for == 'featured')
                                                <span class="label label-warning">Featured</span>
                                            @endif
                                        </td>
                                        <td>{{ $package->formatted_price }}</td>
                                        <td>{{ $package->package_num_days }} days</td>
                                        <td>{{ $package->package_num_listings }}</td>
                                        <td>
                                            <button class="btn btn-xs {{ $package->status ? 'btn-success' : 'btn-danger' }} toggle-status" 
                                                    data-id="{{ $package->id }}">
                                                {{ $package->status ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td>{{ $package->purchases_count }}</td>
                                        <td>{{ $package->formatted_revenue }}</td>
                                        <td>
                                            <a href="{{ route('admin.subscriptions.packages.edit', $package->id) }}" class="btn btn-xs btn-primary">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No packages found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $packages->firstItem() ?? 0 }} to {{ $packages->lastItem() ?? 0 }} of {{ $packages->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $packages->appends(request()->query())->links() }}
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
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

    // Toggle status
    $('.toggle-status').click(function() {
        var button = $(this);
        var id = button.data('id');
        
        $.ajax({
            url: '{{ route("admin.subscriptions.packages.toggle", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-danger').addClass('btn-success').text('Active');
                    } else {
                        button.removeClass('btn-success').addClass('btn-danger').text('Inactive');
                    }
                    toastr.success(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to update status');
            }
        });
    });

    // Delete package
    $('.delete-package').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Package?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.subscriptions.packages.delete", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#packageRow' + id).fadeOut();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete package');
                    }
                });
            }
        });
    });
});
</script>
@endpush