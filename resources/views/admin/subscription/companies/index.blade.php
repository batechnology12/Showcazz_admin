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
                    <span>Company Subscriptions</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Company Subscriptions Management</h3>

        @include('flash::message')

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-briefcase font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Companies with Packages</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div id="filterSection" class="collapse {{ request()->hasAny(['has_package','package_id','status','search']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.subscriptions.companies') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Has Package</label>
                                            <select name="has_package" class="form-control">
                                                <option value="">All</option>
                                                <option value="yes" {{ request('has_package') == 'yes' ? 'selected' : '' }}>Has Package</option>
                                                <option value="no" {{ request('has_package') == 'no' ? 'selected' : '' }}>No Package</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Package</label>
                                            <select name="package_id" class="form-control">
                                                <option value="">All Packages</option>
                                                @foreach($packages as $package)
                                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>
                                                    {{ $package->package_title }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Search</label>
                                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Company name, email...">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" style="margin-top: 10px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('admin.subscriptions.companies') }}" class="btn btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Company</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Days Left</th>
                                        <th>Listings Usage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($companies as $company)
                                    <tr>
                                        <td>{{ $company->id }}</td>
                                        <td>
                                            <strong>{{ $company->company_name ?? $company->name }}</strong><br>
                                            <small>{{ $company->email }}</small>
                                        </td>
                                        <td>
                                            @if($company->package)
                                                {{ $company->package->package_title }}
                                            @else
                                                <span class="label label-default">No Package</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($company->is_active)
                                                <span class="label label-success">Active</span>
                                            @elseif($company->package)
                                                <span class="label label-danger">Expired</span>
                                            @else
                                                <span class="label label-default">Inactive</span>
                                            @endif
                                        </td>
                                       <td>{{ $company->package_start_date ? \Carbon\Carbon::parse($company->package_start_date)->format('d M Y') : 'N/A' }}</td>

<td>{{ $company->package_end_date ? \Carbon\Carbon::parse($company->package_end_date)->format('d M Y') : 'N/A' }}</td>
                                        <td>
                                            @if($company->is_active)
                                                <span class="badge badge-success">{{ $company->days_remaining }} days</span>
                                            @else
                                                <span class="badge badge-danger">Expired</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($company->package_usage)
                                                <div class="progress" style="margin-bottom: 0;">
                                                    <div class="progress-bar progress-bar-success" 
                                                         role="progressbar" 
                                                         style="width: {{ $company->package_usage['percentage'] }}%">
                                                        {{ $company->package_usage['used'] }}/{{ $company->package_usage['total'] }}
                                                    </div>
                                                </div>
                                                <small>{{ $company->package_usage['remaining'] }} remaining</small>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.subscriptions.companies.view', $company->id) }}" 
                                               class="btn btn-xs btn-primary" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No companies found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $companies->firstItem() ?? 0 }} to {{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $companies->appends(request()->query())->links() }}
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
    .progress {
        height: 20px;
        margin-bottom: 5px;
    }
    .badge-success {
        background-color: #5cb85c;
        color: white;
        padding: 3px 6px;
        border-radius: 3px;
    }
    .badge-danger {
        background-color: #d9534f;
        color: white;
        padding: 3px 6px;
        border-radius: 3px;
    }
</style>
@endpush