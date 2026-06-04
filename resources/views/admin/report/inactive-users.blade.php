{{-- resources/views/admin/report/inactive-users.blade.php --}}
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
                    <a href="{{ route('admin.reports.users') }}">User Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Dormant Users</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.users') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to User Reports
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Dormant Users (Inactive for more than {{ $days }} Days)</h3>
        <!-- END PAGE TITLE -->

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o text-warning"></i>
                            <span class="caption-subject font-dark sbold uppercase">Dormant Users List</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <a href="{{ route('admin.reports.users.inactive', ['days' => 30]) }}" class="btn {{ $days == 30 ? 'btn-danger' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.users.inactive', ['days' => 60]) }}" class="btn {{ $days == 60 ? 'btn-danger' : 'btn-default' }} btn-sm">60 Days</a>
                                <a href="{{ route('admin.reports.users.inactive', ['days' => 90]) }}" class="btn {{ $days == 90 ? 'btn-danger' : 'btn-default' }} btn-sm">90 Days</a>
                                <a href="{{ route('admin.reports.users.inactive', ['days' => 180]) }}" class="btn {{ $days == 180 ? 'btn-danger' : 'btn-default' }} btn-sm">180 Days</a>
                                <a href="{{ route('admin.reports.users.inactive', ['days' => 365]) }}" class="btn {{ $days == 365 ? 'btn-danger' : 'btn-default' }} btn-sm">1 Year</a>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Days Dormant</th>
                                        <th>Last Login</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            @if($user->image)
                                                <img src="{{ (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $user->image) : asset('user_images/' . $user->image)) }}" class="img-circle" style="width: 30px; height: 30px; margin-right: 5px;">
                                            @else
                                                <div class="img-circle text-center" style="width: 30px; height: 30px; background: #ccc; line-height: 30px; display: inline-block; margin-right: 5px;">
                                                    <i class="fa fa-user"></i>
                                                </div>
                                            @endif
                                            {{ $user->getName() }}
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="label label-info">{{ ucfirst($user->usertype ?? 'user') }}</span>
                                        </td>
                                        <td>
                                            @if($user->last_login_at)
                                                <span class="label label-danger">{{ $user->last_login_at->diffInDays(now()) }} days</span>
                                            @else
                                                <span class="label label-warning">Never logged in</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->last_login_at)
                                                {{ $user->last_login_at->format('d M Y H:i') }}
                                                <br>
                                                <small class="text-muted">({{ $user->last_login_at->diffForHumans() }})</small>
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            @if(!$user->last_login_at)
                                                <span class="label label-warning">Never Active</span>
                                            @else
                                                <span class="label label-danger">Dormant</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-xs btn-primary" target="_blank">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                           
                                        </td>
                                    </tr>
                                    
                                    <!-- Email Modal for each user -->
                                    <div class="modal fade" id="emailModal-{{ $user->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                    <h4 class="modal-title">Send Email to {{ $user->getName() }}</h4>
                                                </div>
                                                <form action="" method="POST">
                                                    @csrf
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
                                                        <button type="submit" class="btn btn-primary">Send Email</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No dormant users found for the selected period.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 text-center">
                                {{ $users->appends(request()->query())->links() }}
                            </div>
                        </div>
                        
                        @if($users->isNotEmpty())
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>Total Dormant Users:</strong> {{ $users->total() }} users have been inactive for more than {{ $days }} days.
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .img-circle {
        border-radius: 50%;
        object-fit: cover;
    }
</style>
@endpush