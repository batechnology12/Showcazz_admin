@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end -->
<!-- Inner Page Title start -->
@include('includes.inner_page_title', ['page_title'=>__('Cvs Search Packages')])
<!-- Inner Page Title end -->
<?php $company = Auth::guard('company')->user(); ?>

<div class="listpgWraper">
    <div class="container">@include('flash::message')
        <div class="row"> @include('includes.company_dashboard_menu')
            <div class="col-md-9 col-sm-8">
                @if(null!==($success_package) && !empty($success_package))
                <div class="instoretxt">

<h3>Purchased Cvs  Package Details</h3>
<div class="table-responsive">
    <table class="table table-bordered mb-0">
    <thead class="table-dark">
        <tr>
        <th scope="col">Package Name</th>
        <th scope="col">Price</th>
        <th scope="col">Available CV quota</th>
        <th scope="col">Purchased On</th>
        <th scope="col">Package Expired</th>
        </tr>
    </thead>
    <tbody>
    <tr>
    <th>{{$success_package->package_title}}</th>
    <td>{{ $siteSetting->default_currency_code }}{{$success_package->package_price}}</td>
    <td><strong>{{ $company->availed_cvs_quota ?? 0 }}</strong> / <strong>{{$company->cvs_quota}}</strong></td>
    <td><strong>{{Carbon\Carbon::parse($company->cvs_package_start_date)->format('d M, Y')}}</strong></td>
    <td><strong>{{Carbon\Carbon::parse($company->cvs_package_end_date)->format('d M, Y')}}</strong></td>
</tr>

    </tbody>
    </table>
</div>
</div>
            @endif
                
                        <div class="paypackages">
    <!---four-paln-->
    <?php 
        $package = Auth::guard('company')->user()->cvs_getPackage();
     ?>
     @if(null!==($package))
       <div class="four-plan">
        <h3>{{__('Upgrade CV Search Packages')}}</h3>
        <div class="row"> @foreach($packages as $package)
        <div class="col-md-4 col-sm-6 col-xs-12">
                            <ul class="boxes">
                                <li class="plan-name">{{$package->package_title}}</li>
                                <li>
                                    <div class="main-plan">
                                        <div class="plan-price1-1">{{ $siteSetting->default_currency_code }}</div>
                                        <div class="plan-price1-2">{{$package->package_price}}</div>
                                        <div class="clearfix"></div>
                                    </div>
                                </li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('Applicant CV Views')}} {{$package->package_num_listings}}</li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('CV View Access')}} {{$package->package_num_days}} {{__('Days')}}</li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('Premium Support 24/7')}}</li> 
                                
                                <li class="order paypal"><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#buypack{{$package->id}}" class="reqbtn">{{__('Buy Now')}}</a></li>
                                
                            </ul>
                        </div>


                        <div class="modal fade" id="buypack{{$package->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        <div class="modal-body">
                        <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                        </button>
                        <div class="invitereval">
                        <h3>Choose Your Payment Method</h3>	
                            
                        <div class="totalpay">{{__('Total Amount to pay')}}: <strong>{{ $siteSetting->default_currency_code }} {{$package->package_price}}</strong></div>
                            
                        <ul class="btn2s">
                        
                                @if((bool)$siteSetting->is_paypal_active)
                                <li class="order paypal p-2">
                                        <a href="{{route('order.upgrade.package', $package->id)}}" class="paypal">
                                            <i class="fab fa-cc-paypal" aria-hidden="true"></i> {{__('PayPal')}}
                                        </a>
                                        </li>
                                @endif
                                @if((bool)$siteSetting->is_stripe_active)
                                <li class="order p-2">
                                        <a href="{{route('stripe.order.form', [$package->id, 'upgrade'])}}">
                                            <i class="fab fa-cc-stripe" aria-hidden="true"></i> {{__('Stripe')}}
                                        </a>
                                        </li>
                                @endif

                        </ul>		
                        </div>
                        </div>
                        </div>
                        </div>
                        </div>
            @endforeach </div>
    </div>
     @else
    <div class="four-plan">
        <h3>{{__('CV Search Packages')}}</h3>
        <div class="row"> @foreach($packages as $package)
        <div class="col-md-4 col-sm-6 col-xs-12">
                            <ul class="boxes">
                                <li class="plan-name">{{$package->package_title}}</li>
                                <li>
                                    <div class="main-plan">
                                        <div class="plan-price1-1">{{ $siteSetting->default_currency_code }}</div>
                                        <div class="plan-price1-2">{{$package->package_price}}</div>
                                        <div class="clearfix"></div>
                                    </div>
                                </li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('Applicant CV Views')}} {{$package->package_num_listings}}</li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('CV View Access')}} {{$package->package_num_days}} {{__('Days')}}</li>
                                <li class="plan-pages"><i class="far fa-check-square"></i> {{__('Premium Support 24/7')}}</li> 
                                
                                <li class="order paypal"><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#buypack{{$package->id}}" class="reqbtn">{{__('Buy Now')}}</a></li>

                            </ul>
                        </div>



                        <div class="modal fade" id="buypack{{$package->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        <div class="modal-body">
                        <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                        </button>
                        <div class="invitereval">
                        <h3> Choose Your Payment Method</h3>	
                            
                        <div class="totalpay">{{__('Total Amount to pay')}}: <strong>{{ $siteSetting->default_currency_code }} {{$package->package_price}}</strong></div>
                            
                        <ul class="btn2s">
                        
                                @if((bool)$siteSetting->is_paypal_active)
                                <li class="order paypal p-2">
                                        <a href="{{route('order.upgrade.package', $package->id)}}" class="paypal">
                                            <i class="fab fa-cc-paypal" aria-hidden="true"></i> {{__('PayPal')}}
                                        </a>
                                        </li>
                                @endif
                                @if((bool)$siteSetting->is_stripe_active)
                                <li class="order p-2">
                                        <a href="{{route('stripe.order.form', [$package->id, 'upgrade'])}}">
                                            <i class="fab fa-cc-stripe" aria-hidden="true"></i> {{__('Stripe')}}
                                        </a>
                                        </li>
                                @endif

                        </ul>		
                        </div>
                        </div>
                        </div>
                        </div>
                        </div>
            @endforeach </div>
    </div>
    @endif
    <!---end four-paln-->
</div>
            </div>
        </div>
    </div>
</div>
@include('includes.footer')
@endsection
@push('scripts')
@include('includes.immediate_available_btn')
@endpush