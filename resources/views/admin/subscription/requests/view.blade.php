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
                    <a href="{{ route('admin.subscriptions.requests') }}">Payment Requests</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Request #{{ $request->order_number }}</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.subscriptions.requests') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <h3 class="page-title">Payment Request Details</h3>

        @include('flash::message')

        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-info-circle"></i>
                            <span class="caption-subject font-dark sbold uppercase">Request Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 150px;">Order Number</th>
                                <td><strong>{{ $request->order_number }}</strong></td>
                            </tr>
                            <tr>
                                <th>Status</th>
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
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>₹ {{ number_format($request->amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Currency</th>
                                <td>{{ $request->currency }}</td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $request->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>IP Address</th>
                                <td>{{ $request->ip_address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>User Agent</th>
                                <td><small>{{ $request->user_agent ?? 'N/A' }}</small></td>
                            </tr>
                            @if($request->admin_notes)
                            <tr>
                                <th>Admin Notes</th>
                                <td>{{ $request->admin_notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-user"></i>
                            <span class="caption-subject font-dark sbold uppercase">Customer Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 120px;">Name</th>
                                <td>{{ $request->name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $request->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $request->phone }}</td>
                            </tr>
                            @if($request->user)
                            <tr>
                                <th>User ID</th>
                                <td>{{ $request->user->id }}</td>
                            </tr>
                            <tr>
                                <th>User Type</th>
                                <td>{{ ucfirst($request->user->usertype) }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cube"></i>
                            <span class="caption-subject font-dark sbold uppercase">Package Information</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @if($request->package)
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 120px;">Package</th>
                                <td>{{ $request->package->package_title }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $request->package->package_subtitle ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Price</th>
                                <td>{{ $request->package->formatted_price }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ $request->package->package_num_days }} days</td>
                            </tr>
                            <tr>
                                <th>Listings</th>
                                <td>{{ $request->package->package_num_listings }}</td>
                            </tr>
                            <tr>
                                <th>Features</th>
                                <td>
                                @if(!empty($request->package->package_features))
                                    <pre style="white-space: pre-wrap;">
                                {{ is_array($request->package->package_features) 
                                    ? implode("\n", $request->package->package_features) 
                                    : $request->package->package_features }}
                                    </pre>
                                @else
                                    N/A
                                @endif
                                </td>
                            </tr>
                        </table>
                        @else
                        <p class="text-center">Package information not available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($request->transaction)
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-exchange"></i>
                            <span class="caption-subject font-dark sbold uppercase">Transaction Details</span>
                        </div>
                        <div class="actions">
                            <a href="{{ route('admin.subscriptions.transactions.view', $request->transaction->id) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-eye"></i> View Full Transaction
                            </a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th style="width: 150px;">Transaction ID</th>
                                <td>{{ $request->transaction->razorpay_payment_id }}</td>
                            </tr>
                            <tr>
                                <th>Order ID</th>
                                <td>{{ $request->transaction->razorpay_order_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Method</th>
                                <td>{{ $request->transaction->payment_method ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Status</th>
                                <td>
                                    @if($request->transaction->status == 'success')
                                        <span class="label label-success">Success</span>
                                    @else
                                        <span class="label label-danger">{{ ucfirst($request->transaction->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>{{ $request->transaction->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-edit"></i>
                            <span class="caption-subject font-dark sbold uppercase">Update Status</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <form id="updateStatusForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="pending" {{ $request->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="paid" {{ $request->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="failed" {{ $request->status == 'failed' ? 'selected' : '' }}>Failed</option>
                                            <option value="cancelled" {{ $request->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Admin Notes</label>
                                        <input type="text" name="notes" class="form-control" value="{{ $request->admin_notes }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group" style="margin-top: 25px;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#updateStatusForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("admin.subscriptions.requests.update-status", $request->id) }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update status'
                });
            }
        });
    });
});
</script>
@endpush