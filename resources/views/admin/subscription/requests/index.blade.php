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
                    <span>Payment Requests</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Payment Requests Management</h3>

        @include('flash::message')

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-basket font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Payment Requests List</span>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div id="filterSection" class="collapse {{ request()->hasAny(['status','date_from','date_to','search']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.subscriptions.requests') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Date From</label>
                                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Date To</label>
                                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Search</label>
                                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Order #, Name, Email...">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" style="margin-top: 10px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('admin.subscriptions.requests') }}" class="btn btn-default">
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
                                        <th>Order #</th>
                                        <th>User</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment Link</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $request)
                                    <tr>
                                        <td>{{ $request->id }}</td>
                                        <td>
                                            <strong>{{ $request->order_number }}</strong>
                                        </td>
                                        <td>
                                            {{ $request->user_display_name }}<br>
                                            <small>{{ $request->email }}</small>
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
                                            @elseif($request->status == 'cancelled')
                                                <span class="label label-default">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($request->payment_link_url)
                                                <a href="{{ $request->payment_link_url }}" target="_blank" class="btn btn-xs btn-info">
                                                    <i class="fa fa-external-link"></i> View
                                                </a>
                                            @else
                                                <span class="label label-default">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.subscriptions.requests.view', $request->id) }}" 
                                               class="btn btn-xs btn-primary" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No payment requests found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $requests->appends(request()->query())->links() }}
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush