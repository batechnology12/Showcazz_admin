{{-- resources/views/admin/company/partials/jobs_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped table-bordered table-hover" id="jobsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Title</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Applications</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jobs as $job)
                <tr>
                    <td>{{ $job->id }}</td>
                    <td>
                        <strong>{{ $job->title }}</strong><br>
                        <small>Reference: {{ $job->reference ?? 'N/A' }}</small>
                    </td>
                    <td>{{ $job->jobType->job_type ?? 'N/A' }}</td>
                    <td>
                        @if($job->city)
                            {{ $job->city->city ?? '' }},
                        @endif
                        @if($job->country)
                            {{ $job->country->country ?? '' }}
                        @endif
                    </td>
                    <td>
                        @php
                            $applications = \DB::table('job_applies')
                                ->where('job_id', $job->id)
                                ->count();
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
                @endforeach
            </tbody>
        </table>
        
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</div>