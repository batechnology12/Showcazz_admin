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
                    <span>Notification Result</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.notifications.send.form') }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> Send Another
                </a>
                <a href="{{ route('admin.notifications.history') }}" class="btn btn-sm btn-info">
                    <i class="fa fa-history"></i> View History
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <!-- Success Header -->
                <div class="portlet light bordered" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                    <div class="portlet-body text-center" style="padding: 40px 20px;">
                        <div style="font-size: 80px; margin-bottom: 20px;">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h1 style="font-size: 36px; font-weight: 600; margin-bottom: 10px; color: white;">Notification Sent Successfully!</h1>
                        <p style="font-size: 18px; opacity: 0.9; margin-bottom: 5px;">Your message has been delivered to {{ $data['success_count'] }} recipients</p>
                        <p style="font-size: 16px; opacity: 0.8;">Notification ID: #{{ $data['notification_id'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="portlet light bordered" style="border-left: 4px solid #36c6d3;">
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-xs-8">
                                <h4 class="bold" style="margin-bottom: 5px;">Total Recipients</h4>
                                <span class="font-lg" style="font-size: 28px; font-weight: 600; color: #36c6d3;">{{ $data['total_recipients'] }}</span>
                            </div>
                            <div class="col-xs-4 text-right">
                                <i class="fa fa-users" style="font-size: 50px; color: #36c6d3; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="portlet light bordered" style="border-left: 4px solid #5cb85c;">
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-xs-8">
                                <h4 class="bold" style="margin-bottom: 5px;">Successfully Sent</h4>
                                <span class="font-lg" style="font-size: 28px; font-weight: 600; color: #5cb85c;">{{ $data['success_count'] }}</span>
                            </div>
                            <div class="col-xs-4 text-right">
                                <i class="fa fa-check-circle" style="font-size: 50px; color: #5cb85c; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="portlet light bordered" style="border-left: 4px solid #d9534f;">
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-xs-8">
                                <h4 class="bold" style="margin-bottom: 5px;">Failed</h4>
                                <span class="font-lg" style="font-size: 28px; font-weight: 600; color: #d9534f;">{{ $data['failed_count'] }}</span>
                            </div>
                            <div class="col-xs-4 text-right">
                                <i class="fa fa-times-circle" style="font-size: 50px; color: #d9534f; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="portlet light bordered" style="border-left: 4px solid #f0ad4e;">
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-xs-8">
                                <h4 class="bold" style="margin-bottom: 5px;">Success Rate</h4>
                                <span class="font-lg" style="font-size: 28px; font-weight: 600; color: #f0ad4e;">
                                    {{ $data['total_recipients'] > 0 ? round(($data['success_count'] / $data['total_recipients']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="col-xs-4 text-right">
                                <i class="fa fa-pie-chart" style="font-size: 50px; color: #f0ad4e; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-body">
                        <h4>Delivery Progress</h4>
                        <div class="progress" style="height: 30px; margin-top: 15px;">
                            <div class="progress-bar progress-bar-success" 
                                 style="width: {{ $data['total_recipients'] > 0 ? ($data['success_count'] / $data['total_recipients']) * 100 : 0 }}%; line-height: 30px;">
                                <strong>Success ({{ $data['success_count'] }})</strong>
                            </div>
                            <div class="progress-bar progress-bar-danger" 
                                 style="width: {{ $data['total_recipients'] > 0 ? ($data['failed_count'] / $data['total_recipients']) * 100 : 0 }}%; line-height: 30px;">
                                <strong>Failed ({{ $data['failed_count'] }})</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-envelope"></i>
                            <span class="caption-subject font-dark sbold uppercase">Message Details</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="well" style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin-top: 0; color: #333;">{{ session('notification_title') }}</h3>
                            <p style="font-size: 16px; line-height: 1.6; color: #666; margin-bottom: 20px;">{{ session('notification_message') }}</p>
                            
                            @if(session('notification_image'))
                            <div class="text-center">
                                <img src="{{ session('notification_image') }}" style="max-width: 100%; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Timeline</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <ul class="list-unstyled" style="margin: 0;">
                            <li style="padding: 10px 0; border-bottom: 1px solid #e5e5e5;">
                                <i class="fa fa-calendar text-primary" style="margin-right: 10px;"></i>
                                <strong>Sent Date:</strong> {{ now()->format('d M Y') }}
                            </li>
                            <li style="padding: 10px 0; border-bottom: 1px solid #e5e5e5;">
                                <i class="fa fa-clock-o text-success" style="margin-right: 10px;"></i>
                                <strong>Sent Time:</strong> {{ now()->format('h:i A') }}
                            </li>
                            <li style="padding: 10px 0;">
                                <i class="fa fa-tag text-info" style="margin-right: 10px;"></i>
                                <strong>Target:</strong> 
                                <span class="label label-info">{{ ucfirst(session('notification_target', 'all')) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-share-alt"></i>
                            <span class="caption-subject font-dark sbold uppercase">Quick Actions</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <a href="{{ route('admin.notifications.send.form') }}" class="btn btn-primary btn-block" style="margin-bottom: 10px;">
                            <i class="fa fa-plus"></i> Send Another Notification
                        </a>
                        <a href="{{ route('admin.notifications.history') }}" class="btn btn-info btn-block" style="margin-bottom: 10px;">
                            <i class="fa fa-history"></i> View All History
                        </a>
                        <a href="{{ route('admin.notifications.view', $data['notification_id']) }}" class="btn btn-success btn-block">
                            <i class="fa fa-eye"></i> View Detailed Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipient Preview (if less than 20 recipients) -->
        @if(isset($recipients) && count($recipients) > 0 && count($recipients) < 20)
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Recipient Preview</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recipients as $index => $recipient)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $recipient['name'] }}</td>
                                    <td>
                                        <span class="label label-info">{{ ucfirst($recipient['type'] ?? 'user') }}</span>
                                    </td>
                                    <td>
                                        @if($recipient['success'])
                                            <span class="label label-success">
                                                <i class="fa fa-check"></i> Delivered
                                            </span>
                                        @else
                                            <span class="label label-danger">
                                                <i class="fa fa-times"></i> Failed
                                            </span>
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

        <!-- Share Result Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered" style="background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);">
                    <div class="portlet-body text-center">
                        <h4 style="margin-bottom: 20px;">Share This Result</h4>
                        <div class="btn-group">
                            <button class="btn btn-primary" onclick="copyToClipboard()">
                                <i class="fa fa-copy"></i> Copy Result Summary
                            </button>
                           
                        </div>
                        <div id="copyAlert" style="display: none; margin-top: 15px;" class="alert alert-success">
                            <i class="fa fa-check"></i> Copied to clipboard!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const text = `Notification Result:
- Total Recipients: {{ $data['total_recipients'] }}
- Successfully Sent: {{ $data['success_count'] }}
- Failed: {{ $data['failed_count'] }}
- Success Rate: {{ $data['total_recipients'] > 0 ? round(($data['success_count'] / $data['total_recipients']) * 100, 1) : 0 }}%
- Sent at: {{ now()->format('d M Y h:i A') }}`;
    
    navigator.clipboard.writeText(text).then(() => {
        $('#copyAlert').fadeIn().delay(2000).fadeOut();
    });
}
</script>

<style>
.font-lg { font-size: 28px; font-weight: 600; }
.dashboard-stat .details .number { font-size: 28px; }
.progress-bar { transition: width 1s ease-in-out; }
.portlet.light.bordered { transition: transform 0.2s; }
.portlet.light.bordered:hover { transform: translateY(-2px); }
</style>
@endsection