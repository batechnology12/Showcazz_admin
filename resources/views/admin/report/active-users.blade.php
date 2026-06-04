{{-- resources/views/admin/report/active-users.blade.php --}}
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
                    <span>Active Users</span>
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
        <h3 class="page-title">Active Users (Last {{ $days }} Days)</h3>
        <!-- END PAGE TITLE -->

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-check-circle text-success"></i>
                            <span class="caption-subject font-dark sbold uppercase">Active Users List</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <a href="{{ route('admin.reports.users.active', ['days' => 7]) }}" class="btn {{ $days == 7 ? 'btn-success' : 'btn-default' }} btn-sm">7 Days</a>
                                <a href="{{ route('admin.reports.users.active', ['days' => 30]) }}" class="btn {{ $days == 30 ? 'btn-success' : 'btn-default' }} btn-sm">30 Days</a>
                                <a href="{{ route('admin.reports.users.active', ['days' => 60]) }}" class="btn {{ $days == 60 ? 'btn-success' : 'btn-default' }} btn-sm">60 Days</a>
                                <a href="{{ route('admin.reports.users.active', ['days' => 90]) }}" class="btn {{ $days == 90 ? 'btn-success' : 'btn-default' }} btn-sm">90 Days</a>
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
                                        <th>Posts</th>
                                        <th>Comments</th>
                                        <th>Likes</th>
                                        <th>Last Login</th>
                                        <th>Joined</th>
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
                                        <td>{{ $user->posts_count ?? 0 }}</td>
                                        <td>{{ $user->comments_count ?? 0 }}</td>
                                        <td>{{ $user->likes_count ?? 0 }}</td>
                                        <td>{{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Never' }}</td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-xs btn-primary" target="_blank">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No active users found</td>
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