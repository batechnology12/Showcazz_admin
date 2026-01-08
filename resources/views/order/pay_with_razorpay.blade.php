@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 
<!-- Inner Page Title start --> 
@include('includes.inner_page_title', ['page_title'=>__('Pay with Razorpay')]) 
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row"> 
            @if(Auth::guard('company')->check())
                @include('includes.company_dashboard_menu')
            @else
                @include('includes.user_dashboard_menu')
            @endif
            <div class="col-md-9 col-sm-8">
                <div class="userccount">
                    <div class="row">
                        <div class="col-md-5">
                            <img src="{{ asset('/') }}images/razorpay-logo.png" alt="" />
                            <div class="razorpckinfo">
                                <h5>{{ __('Invoice Details') }}</h5>
                                <div class="pkginfo">{{ __('Package') }}: <strong>{{ $package->package_title }}</strong></div>
                                <div class="pkginfo">{{ __('Price') }}: <strong>{{ $siteSetting->default_currency_code }}{{ $package->package_price }}</strong></div>
                                <div class="pkginfo">
                                    {{ __('Package Duration') }}: <strong>{{ $package->package_num_days }} {{ __('Days') }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="formpanel">
                                @include('flash::message')
                                <h5>{{ __('Razorpay Payment Details') }}</h5>
                                {!! Form::open(['method' => 'post', 'route' => 'razorpay.order.package', 'id' => 'razorpay-form']) !!}
                                {{ Form::hidden('package_id', $package_id) }}
                                <div class="formrow">
                                    <button id="rzp-button" class="btn btn-primary">
                                        {{ __('Pay with Razorpay') }} 
                                        <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                                {!! Form::close() !!}
                                <hr>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.footer')
@endsection
@push('styles')
<style type="text/css">
    .userccount p { text-align: left !important; }
</style>
@endpush
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "{{ config('services.razorpay.key') }}",
        "amount": "{{ $package->package_price * 100 }}", // Amount in paisa
        "currency": "{{ $siteSetting->default_currency_code }}",
        "name": "{{ $siteSetting->site_name }}",
        "description": "{{ __('Payment for') }} {{ $package->package_title }}",
        "image": "{{ asset('images/logo.png') }}",
        "handler": function (response) {
            // Add Razorpay response data to the form and submit
            var form = document.getElementById('razorpay-form');
            var paymentId = document.createElement('input');
            paymentId.setAttribute('type', 'hidden');
            paymentId.setAttribute('name', 'razorpay_payment_id');
            paymentId.setAttribute('value', response.razorpay_payment_id);
            form.appendChild(paymentId);
            form.submit();
        },
        "prefill": {
            "name": "{{ Auth::user()->name ?? '' }}",
            "email": "{{ Auth::user()->email ?? '' }}",
        },
        "theme": {
            "color": "#F37254"
        }
    };

    var rzp1 = new Razorpay(options);
    document.getElementById('rzp-button').onclick = function (e) {
        e.preventDefault();
        rzp1.open();
    };
</script>
@endpush
