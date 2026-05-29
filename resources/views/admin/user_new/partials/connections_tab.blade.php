{{-- resources/views/admin/user_new/partials/applications_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped table-bordered table-hover" id="applicationsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Expected Salary</th>
                    <th>Status</th>
                    <th>Applied Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobApplications as $application)
                <tr>
                    <td>{{ $application->id }}</td>
                    <td>
                        <strong>{{ $application->job->title ?? 'N/A' }}</strong><br>
                        <small>Ref: {{ $application->job->reference ?? 'N/A' }}</small>
                    </td>
                    <td>
                        @if($application->job && $application->job->company)
                            <a href="{{ route('companies.show', $application->job->company->id) }}">
                                {{ $application->job->company->name }}
                            </a>
                        @else
                                            N/A
                        @endif
                    </td>
                    <td>
                        @if($application->job)
                            {{ $application->job->location ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if($application->expected_salary)
                            {{ $application->salary_currency ?? '₹' }} {{ number_format($application->expected_salary) }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @php
                            $statusClass = [
                                'pending' => 'label-warning',
                                'reviewed' => 'label-info',
                                'shortlisted' => 'label-primary',
                                'interviewed' => 'label-success',
                                'hired' => 'label-success',
                                'rejected' => 'label-danger'
                            ];
                            $status = $application->status ?? 'pending';
                            $class = $statusClass[$status] ?? 'label-default';
                        @endphp
                        <span class="label {{ $class }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>{{ $application->created_at->format('d M Y') }}</td>
                    <td>
                        @if($application->job)
                            <a href="{{ route('jobs.show', $application->job->id) }}" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> View Job
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No job applications found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $jobApplications->links() }}
            </div>
        </div>
    </div>
</div>