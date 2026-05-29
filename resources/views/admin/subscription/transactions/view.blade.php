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
                    <a href="{{ route('admin.subscriptions.transactions') }}">Transactions</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Transaction #{{ substr($transaction->razorpay_payment_id, 0, 10) }}...</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.subscriptions.transactions') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <h3 class="page-title">Transaction Details</h3>

        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-info-circle"></i>
                            <span class="caption-subject font-dark sbold uppercase">Transaction Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 150px;">Transaction ID</th>
                                <td>{{ $transaction->razorpay_payment_id }}</td>
                            </tr>
                            <tr>
                                <th>Order ID</th>
                                <td>{{ $transaction->razorpay_order_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Signature</th>
                                <td><small>{{ $transaction->razorpay_signature ?? 'N/A' }}</small></td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td><strong>₹ {{ number_format($transaction->amount, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <th>Currency</th>
                                <td>{{ $transaction->currency }}</td>
                            </tr>
                            <tr>
                                <th>Payment Method</th>
                                <td>{{ ucfirst($transaction->payment_method) }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($transaction->status == 'success')
                                        <span class="label label-success">Success</span>
                                    @else
                                        <span class="label label-danger">{{ ucfirst($transaction->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>{{ $transaction->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-user"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @if($transaction->user)
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 120px;">User ID</th>
                                <td>{{ $transaction->user->id }}</td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td>{{ $transaction->user_name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $transaction->user->email }}</td>
                            </tr>
                            <tr>
                                <th>User Type</th>
                                <td>{{ ucfirst($transaction->user->usertype) }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $transaction->user->phone ?? 'N/A' }}</td>
                            </tr>
                        </table>
                        @else
                        <p class="text-center">User information not available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cube"></i>
                            <span class="caption-subject font-dark sbold uppercase">Package Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @if($transaction->package)
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 120px;">Package</th>
                                <td>{{ $transaction->package->package_title }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $transaction->package->package_subtitle ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Price</th>
                                <td>{{ $transaction->package->formatted_price }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ $transaction->package->package_num_days }} days</td>
                            </tr>
                            <tr>
                                <th>Listings</th>
                                <td>{{ $transaction->package->package_num_listings }}</td>
                            </tr>
                        </table>
                        @else
                        <p class="text-center">Package information not available</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-shopping-cart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Payment Request</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @if($transaction->paymentRequest)
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 120px;">Order Number</th>
                                <td>
                                    <a href="{{ route('admin.subscriptions.requests.view', $transaction->paymentRequest->id) }}">
                                        {{ $transaction->paymentRequest->order_number }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Request Status</th>
                                <td>
                                    @if($transaction->paymentRequest->status == 'paid')
                                        <span class="label label-success">Paid</span>
                                    @elseif($transaction->paymentRequest->status == 'pending')
                                        <span class="label label-warning">Pending</span>
                                    @elseif($transaction->paymentRequest->status == 'failed')
                                        <span class="label label-danger">Failed</span>
                                    @else
                                        <span class="label label-default">{{ ucfirst($transaction->paymentRequest->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Customer Name</th>
                                <td>{{ $transaction->paymentRequest->name }}</td>
                            </tr>
                            <tr>
                                <th>Customer Email</th>
                                <td>{{ $transaction->paymentRequest->email }}</td>
                            </tr>
                            <tr>
                                <th>Customer Phone</th>
                                <td>{{ $transaction->paymentRequest->phone }}</td>
                            </tr>
                        </table>
                        @else
                        <p class="text-center">Payment request information not available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

       @if($transaction->payment_response)
    <div class="row">
        <div class="col-md-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-code"></i>
                        <span class="caption-subject font-dark sbold uppercase">Raw Payment Response</span>
                    </div>
                </div>
                <div class="portlet-body">
                    <pre style="max-height: 300px; overflow-y: auto;">{{ json_encode($transaction->payment_response, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    </div>
@endif
    </div>
</div>
@endsection