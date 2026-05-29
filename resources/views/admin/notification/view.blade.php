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
                    <a href="{{ route('admin.notifications.history') }}">History</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Notification Details</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.notifications.history') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to History
                </a>
            </div>
        </div>

        <h3 class="page-title">Notification Details</h3>

        <div class="row">
            <div class="col-md-8">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bell"></i>
                            <span class="caption-subject font-dark sbold uppercase">Notification Content</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <h3>{{ $notification->title }}</h3>
                        <p style="font-size: 16px; line-height: 1.6;">{{ $notification->message }}</p>
                        
                        @if($notification->response_data && isset($notification->response_data[0]['image']))
                        <div class="text-center">
                            <img src="{{ $notification->response_data[0]['image'] }}" style="max-width: 100%; max-height: 300px;">
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-info-circle"></i>
                            <span class="caption-subject font-dark sbold uppercase">Details</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            <tr>
                                <th>Type</th>
                                <td><span class="label label-info">{{ ucfirst($notification->target_type) }}</span></td>
                            </tr>
                            <tr>
                                <th>Sent By</th>
                                <td>{{ $notification->sentBy->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <th>Sent At</th>
                                <td>{{ $notification->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Total Recipients</th>
                                <td>{{ number_format($notification->total_recipients) }}</td>
                            </tr>
                            <tr>
                                <th>Success</th>
                                <td><span class="label label-success">{{ $notification->success_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Failed</th>
                                <td><span class="label label-danger">{{ $notification->failed_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Success Rate</th>
                                <td>
                                    <div class="progress" style="margin-bottom: 0;">
                                        <div class="progress-bar progress-bar-success" 
                                             style="width: {{ $notification->total_recipients > 0 ? ($notification->success_count / $notification->total_recipients) * 100 : 0 }}%">
                                            {{ $notification->total_recipients > 0 ? round(($notification->success_count / $notification->total_recipients) * 100, 2) : 0 }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($notification->target_type === 'custom' && $notification->target_users)
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Targeted Users</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notification->target_users as $userId)
                                    @php $user = App\User::find($userId); @endphp
                                    @if($user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->usertype === 'company' ? ($user->company_name ?? $user->name) : $user->getName() }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="label label-info">{{ ucfirst($user->usertype) }}</span></td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($recipients->isNotEmpty())
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-list"></i>
                            <span class="caption-subject font-dark sbold uppercase">Delivery Details</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recipients as $recipient)
                                <tr>
                                    <td>
                                        <strong>{{ $recipient->name }}</strong>
                                        <br>
                                        <small>ID: {{ $recipient->user_id }}</small>
                                    </td>
                                    <td>
                                        @if($recipient->success)
                                            <span class="label label-success">Success</span>
                                        @else
                                            <span class="label label-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($recipient->error))
                                            <span class="text-danger">{{ $recipient->error }}</span>
                                        @else
                                            <span class="text-success">Delivered</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection