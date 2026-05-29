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
                    <a href="{{ route('admin.subscriptions.companies') }}">Company Subscriptions</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>{{ $company->company_name ?? $company->name }}</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.subscriptions.companies') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <h3 class="page-title">Company Subscription: {{ $company->company_name ?? $company->name }}</h3>

        @include('flash::message')

        <!-- Company Info Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-building"></i>
                            <span class="caption-subject font-dark sbold uppercase">Company Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 150px;">Company Name</th>
                                        <td>{{ $company->company_name ?? $company->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>{{ $company->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td>{{ $company->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td>{{ $company->company_location ?? $company->location ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 150px;">Website</th>
                                        <td>{{ $company->company_website ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Industry</th>
                                        <td>{{ $company->company_industry_id ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Established</th>
                                        <td>{{ $company->company_established_in ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Employees</th>
                                        <td>{{ $company->company_no_of_employees ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Subscription -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-credit-card"></i>
                            <span class="caption-subject font-dark sbold uppercase">Current Subscription</span>
                        </div>
                        <div class="actions">
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#updatePackageModal">
                                <i class="fa fa-edit"></i> Update Package
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @if($company->package)
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 150px;">Package</th>
                                        <td><strong>{{ $company->package->package_title }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>{{ $company->package->package_subtitle ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Price</th>
                                        <td>{{ $company->package->formatted_price }}</td>
                                    </tr>
                                    <tr>
                                        <th>Duration</th>
                                        <td>{{ $company->package->package_num_days }} days</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 150px;">Start Date</th>
                                        
                                        <td>{{ \Carbon\Carbon::parse($company->package_start_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>End Date</th>
                                        <td>{{ \Carbon\Carbon::parse($company->package_end_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($company->is_active)
                                                <span class="label label-success">Active</span>
                                                <small>({{ $company->days_remaining }} days remaining)</small>
                                            @else
                                                <span class="label label-danger">Expired</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Listings Usage</th>
                                        <td>
                                            <div class="progress" style="margin-bottom: 5px;">
                                                <div class="progress-bar progress-bar-success" 
                                                     role="progressbar" 
                                                     style="width: {{ $company->package_usage['percentage'] }}%">
                                                    {{ $company->package_usage['used'] }}/{{ $company->package_usage['total'] }}
                                                </div>
                                            </div>
                                            <small>{{ $company->package_usage['remaining'] }} remaining</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                       
                        @else
                        <p class="text-center">This company does not have any active package</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-history"></i>
                            <span class="caption-subject font-dark sbold uppercase">Payment History</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentRequests as $request)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.subscriptions.requests.view', $request->id) }}">
                                            {{ $request->order_number }}
                                        </a>
                                    </td>
                                    <td>{{ $request->package->package_title ?? 'N/A' }}</td>
                                    <td>₹ {{ number_format($request->amount, 2) }}</td>
                                    <td>
                                        @if($request->status == 'paid')
                                            <span class="label label-success">Paid</span>
                                        @elseif($request->status == 'pending')
                                            <span class="label label-warning">Pending</span>
                                        @elseif($request->status == 'failed')
                                            <span class="label label-danger">Failed</span>
                                        @else
                                            <span class="label label-default">{{ ucfirst($request->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $request->created_at->format('d M Y') }}</td>
                                    <td>
                                        @if($request->transaction)
                                            <a href="{{ route('admin.subscriptions.transactions.view', $request->transaction->id) }}" 
                                               class="btn btn-xs btn-primary">
                                                View Transaction
                                            </a>
                                        @else
                                            <span class="label label-default">No Transaction</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No payment history found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $paymentRequests->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Posted -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-briefcase"></i>
                            <span class="caption-subject font-dark sbold uppercase">Jobs Posted</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Job Title</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Posted Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>{{ $job->title }}</td>
                                    <td>{{ $job->job_type ?? 'N/A' }}</td>
                                    <td>{{ $job->location ?? 'N/A' }}</td>
                                    <td>
                                        @if($job->is_active)
                                            <span class="label label-success">Active</span>
                                        @else
                                            <span class="label label-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $job->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No jobs posted yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $jobs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Package Modal -->
<div class="modal fade" id="updatePackageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.subscriptions.companies.update-package', $company->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Update Company Package</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Package</label>
                        <select name="package_id" class="form-control" required>
                            <option value="">Select Package</option>
                            @foreach($packages as $package)
                            <option value="{{ $package->id }}" {{ $company->package_id == $package->id ? 'selected' : '' }}>
                                {{ $package->package_title }} ({{ $package->formatted_price }} - {{ $package->package_num_days }} days)
                            </option>
                            @endforeach
                        </select>
                    </div>
                   
                    <div class="form-group">
                        <label>Total Listings Quota</label>
                        <input type="number" name="jobs_quota" class="form-control" 
                               value="{{ $company->jobs_quota ?? '' }}" min="1">
                    </div>
                    <div class="form-group">
                        <label>Used Listings</label>
                        <input type="number" name="availed_jobs_quota" class="form-control" 
                               value="{{ $company->availed_jobs_quota ?? 0 }}" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Package</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .progress {
        height: 20px;
        margin-bottom: 5px;
    }
</style>
@endpush