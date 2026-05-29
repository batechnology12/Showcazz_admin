{{-- resources/views/admin/company_new/partials/jobs_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped table-bordered table-hover" id="jobsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Title</th>
                    <th>Type</th>
                    
                    <th>Applications</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                <tr>
                    <td>{{ $job->id }}</td>
                    <td>
                        <strong>{{ $job->title }}</strong><br>
                        <small>Reference: {{ $job->reference ?? 'N/A' }}</small>
                    </td>
                    <td>
                        @if($job->job_type)
                            {{ $job->job_type }}
                        @elseif($job->jobType && $job->jobType->job_type)
                            {{ $job->jobType->job_type }}
                        @else
                            N/A
                        @endif
                    </td>
                   
                    <td>
                        @php
                            $applications = 0;
                            // Try different possible table names for job applications
                            if (\Illuminate\Support\Facades\Schema::hasTable('job_applications')) {
                                $applications = \DB::table('job_applications')
                                    ->where('job_id', $job->id)
                                    ->count();
                            } elseif (\Illuminate\Support\Facades\Schema::hasTable('job_applies')) {
                                $applications = \DB::table('job_applies')
                                    ->where('job_id', $job->id)
                                    ->count();
                            } elseif (\Illuminate\Support\Facades\Schema::hasTable('job_apply')) {
                                $applications = \DB::table('job_apply')
                                    ->where('job_id', $job->id)
                                    ->count();
                            }
                        @endphp
                        <span class="badge">{{ $applications }}</span>
                    </td>
                    <td>
                        @if($job->is_active)
                            <span class="label label-success">Active</span>
                        @else
                            <span class="label label-danger">Inactive</span>
                        @endif
                        @if($job->is_featured)
                            <span class="label label-warning">Featured</span>
                        @endif
                    </td>
                    <td>
                        @if($job->expiry_date)
                            @if(\Carbon\Carbon::parse($job->expiry_date)->isPast())
                                <span class="label label-danger">{{ date('d M Y', strtotime($job->expiry_date)) }}</span>
                            @else
                                {{ date('d M Y', strtotime($job->expiry_date)) }}
                            @endif
                        @elseif($job->application_deadline)
                            @if(\Carbon\Carbon::parse($job->application_deadline)->isPast())
                                <span class="label label-danger">{{ date('d M Y', strtotime($job->application_deadline)) }}</span>
                            @else
                                {{ date('d M Y', strtotime($job->application_deadline)) }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $job->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.jobs.show', $job->id) }}" class="btn btn-xs btn-primary">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No jobs found for this company</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if(method_exists($jobs, 'links'))
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $jobs->links() }}
            </div>
        </div>
        @endif
    </div>
</div>