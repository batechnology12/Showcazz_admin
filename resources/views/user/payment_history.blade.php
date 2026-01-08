@extends('layouts.app')

@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 



<!-- Inner Page Title start --> 
@include('includes.inner_page_title', ['page_title' => __('Payment History')]) 
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row">
        @include('includes.user_dashboard_menu')
            <div class="col-md-9 col-sm-8"> 
    @include('flash::message') 
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Package Title</th>
                <th>Price</th>
                <th>Featured Profile Days</th>
                <th>Payment Method</th>
                <th>Package Start Date</th>
                <th>Package End Date</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($candidatePayments as $payment)
    @if ($payment->package) <!-- Only display if package exists -->
        <tr>
            <td>{{ $payment->package->package_title ?? 'N/A' }}</td>
            <td>{{ $siteSetting->default_currency_code ?? '' }}{{ $payment->package->package_price ?? 'N/A' }}</td>
            <td>{{ $payment->package->package_num_days ?? 'N/A' }}</td>
            <td>
                @if (!empty($payment->payment_method) && $payment->payment_method !== 'offline')
                    {{ $payment->payment_method }}
                @else
                    Offline (Added by Admin)
                @endif
            </td>
            <td>{{ $payment->featured_package_start_at ? \Carbon\Carbon::parse($payment->featured_package_start_at)->format('d-m-Y') : 'N/A' }}</td>
            <td>{{ $payment->featured_package_end_at ? \Carbon\Carbon::parse($payment->featured_package_end_at)->format('d-m-Y') : 'N/A' }}</td>
        </tr>
    @endif
@empty
    <tr>
        <td colspan="7" class="text-center">No records found</td>
    </tr>
@endforelse



        </tbody>
    </table>
            </div>
        </div>
    </div>
</div>



@include('includes.footer')
@endsection

@push('scripts')
<!-- jsPDF Library -->



@endpush
