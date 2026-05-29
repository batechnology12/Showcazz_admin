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
                    <span>Transactions</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Transaction History</h3>

        @include('flash::message')

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-exchange"></i></div>
                    <div class="details">
                        <div class="number">{{ $transactions->total() }}</div>
                        <div class="desc">Total Transactions</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-check-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ $successCount }}</div>
                        <div class="desc">Successful</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-times-circle"></i></div>
                    <div class="details">
                        <div class="number">{{ $failedCount }}</div>
                        <div class="desc">Failed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-money"></i></div>
                    <div class="details">
                        <div class="number">₹ {{ number_format($successAmount, 2) }}</div>
                        <div class="desc">Total Revenue</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-list"></i>
                            <span class="caption-subject font-dark sbold uppercase">Transactions List</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.subscriptions.transactions.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success">
                                <i class="fa fa-download"></i> Export CSV
                            </a>
                            <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#filterSection">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div id="filterSection" class="collapse {{ request()->hasAny(['status','payment_method','date_from','date_to','min_amount','max_amount','search']) ? 'in' : '' }}">
                        <div class="portlet-body">
                            <form method="GET" action="{{ route('admin.subscriptions.transactions') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Payment Method</label>
                                            <select name="payment_method" class="form-control">
                                                <option value="">All</option>
                                                <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                                <option value="netbanking" {{ request('payment_method') == 'netbanking' ? 'selected' : '' }}>Netbanking</option>
                                                <option value="upi" {{ request('payment_method') == 'upi' ? 'selected' : '' }}>UPI</option>
                                                <option value="wallet" {{ request('payment_method') == 'wallet' ? 'selected' : '' }}>Wallet</option>
                                                <option value="manual" {{ request('payment_method') == 'manual' ? 'selected' : '' }}>Manual</option>
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
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Min Amount (₹)</label>
                                            <input type="number" step="0.01" name="min_amount" class="form-control" value="{{ request('min_amount') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Max Amount (₹)</label>
                                            <input type="number" step="0.01" name="max_amount" class="form-control" value="{{ request('max_amount') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Search</label>
                                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Transaction ID, Order ID...">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('admin.subscriptions.transactions') }}" class="btn btn-default">
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
                                        <th>Transaction ID</th>
                                        <th>User</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td>
                                            <small>{{ substr($transaction->razorpay_payment_id, 0, 20) }}...</small>
                                        </td>
                                        <td>{{ $transaction->user_name }}</td>
                                        <td>{{ $transaction->package_title }}</td>
                                        <td>₹ {{ number_format($transaction->amount, 2) }}</td>
                                        <td>{{ ucfirst($transaction->payment_method) }}</td>
                                        <td>
                                            @if($transaction->status == 'success')
                                                <span class="label label-success">Success</span>
                                            @else
                                                <span class="label label-danger">Failed</span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.subscriptions.transactions.view', $transaction->id) }}" 
                                               class="btn btn-xs btn-primary" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No transactions found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="dataTables_info">
                                    Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} entries
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                {{ $transactions->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection