@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-users"></i>
                        <span class="caption-subject font-dark sbold uppercase">Inactive Users Report</span>
                    </div>
                    <div class="actions">
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <label>Inactive for more than:</label>
                                <select name="days" class="form-control" onchange="this.form.submit()">
                                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 days</option>
                                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 days</option>
                                    <option value="180" {{ $days == 180 ? 'selected' : '' }}>180 days</option>
                                    <option value="365" {{ $days == 365 ? 'selected' : '' }}>1 year</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="portlet-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->last_login_at)
                                            {{ $user->last_login_at->format('Y-m-d H:i:s') }}
                                            <br>
                                            <small class="text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                                        @else
                                            <span class="label label-danger">Never logged in</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$user->last_login_at)
                                            <span class="label label-warning">Never Active</span>
                                        @else
                                            <span class="label label-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-xs btn-primary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No inactive users found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        {{ $users->appends(['days' => $days])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection