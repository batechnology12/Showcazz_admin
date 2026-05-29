{{-- resources/views/admin/job_new/applications.blade.php --}}
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
                    <a href="{{ route('admin.jobs.index') }}">Jobs & Opportunities</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('admin.jobs.show', $job->id) }}">{{ $job->title }}</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Applications</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.jobs.show', $job->id) }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Job
                </a>
                <a href="{{ route('admin.jobs.applications.export', $job->id) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Applications for "{{ $job->title }}"</span>
                        </div>
                        <div class="actions">
                            <span class="badge badge-primary">Total: {{ $applications->total() }}</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @forelse($applications as $application)
                        <div class="panel panel-default" style="margin-bottom: 15px;">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Applicant:</strong> 
                                        @if($application->applicant)
                                            {{ $application->applicant['name'] ?? 'Unknown' }}
                                            <span class="label label-info">{{ $application->applicant['type'] ?? 'user' }}</span>
                                        @else
                                            Unknown
                                        @endif
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Applied:</strong> {{ $application->created_at->format('d M Y h:i A') }}
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <span class="badge badge-info">Messages: {{ $application->conversation_count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-2 text-center">
                                        @if($application->applicant && $application->applicant['image'])
                                            <img src="{{ $application->applicant['image'] }}" class="img-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                        @else
                                            <div class="img-circle text-center" style="width: 80px; height: 80px; background: #ccc; line-height: 80px; margin: 0 auto;">
                                                <i class="fa fa-user fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-5">
                                        <p><strong>Email:</strong> {{ $application->applicant['email'] ?? 'N/A' }}</p>
                                        <p><strong>Phone:</strong> {{ $application->applicant['phone'] ?? 'N/A' }}</p>
                                        <p><strong>Headline:</strong> {{ $application->applicant['headline'] ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-5">
                                        @if(!empty($application->application_data['portfolio_links']))
                                            <p><strong>Portfolio Links:</strong></p>
                                            <ul>
                                                @foreach($application->application_data['portfolio_links'] as $link)
                                                    <li><a href="{{ $link }}" target="_blank">{{ Str::limit($link, 30) }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(!empty($application->application_data['resume_link']))
                                            <p><strong>Resume:</strong> <a href="{{ $application->application_data['resume_link'] }}" target="_blank">View Resume</a></p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-md-12">
                                        <div class="well well-sm">
                                            <strong>Application Message:</strong>
                                            <p>{{ $application->message_txt }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($application->application_data['answers']))
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-12">
                                        <strong>Additional Answers:</strong>
                                        <ul>
                                            @foreach($application->application_data['answers'] as $answer)
                                                <li><strong>{{ $answer['question'] }}:</strong> {{ $answer['answer'] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif

                                @if(!empty($application->application_data['additional_notes']))
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-12">
                                        <strong>Additional Notes:</strong>
                                        <p>{{ $application->application_data['additional_notes'] }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="alert alert-info">No applications found for this opportunity.</div>
                        @endforelse

                        <div class="text-center">
                            {{ $applications->links() }}
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
    .badge-primary { background-color: #3598dc; color: white; padding: 3px 6px; border-radius: 3px; }
    .panel-default { border-color: #ddd; }
    .panel-heading { background-color: #f5f5f5; padding: 10px 15px; }
    .well-sm { margin-bottom: 0; }
</style>
@endpush