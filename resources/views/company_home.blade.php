@extends('layouts.app')

@section('content') 

<!-- Header start --> 

@include('includes.header') 

<!-- Header end --> 

<!-- Inner Page Title start --> 

@include('includes.inner_page_title', ['page_title'=>__('Welcome to Employer Dashboard')]) 

<!-- Inner Page Title end -->

<div class="listpgWraper">

    <div class="container">@include('flash::message')

        <div class="row"> @include('includes.company_dashboard_menu')
        <?php $company = auth()->guard('company')->user(); ?>

        <div class="col-md-9 col-sm-8"> 
            <?php if ($company->is_active == 1 && (($company->package_end_date === null) || 
                (\Carbon\Carbon::parse($company->package_end_date)->lt(\Carbon\Carbon::now())) || 
                ($company->jobs_quota <= $company->availed_jobs_quota))) { ?>    

                <div class="userprofilealert">
                    <h5>
                        <i class="fas fa-check"></i> 
                        {{_('Your account is active now, Start Posting Jobs.')}}
                    </h5>
                </div>

            <?php } elseif ($company->is_active != 1) { ?> 
                <div class="userprofilealert">
                    <h5>
                        <i class="fas fa-times"></i> 
                        {{__('Your account is currently inactive due to pending verification.')}}
                    </h5>
                </div>
            <?php } ?> 
      

            
            
            @include('includes.company_dashboard_stats')

           @if($company->getPackage('id') == 13 && $company->package_end_date !== null && Carbon\Carbon::parse($company->package_end_date)->gt(Carbon\Carbon::now()) && $company->jobs_quota > $company->availed_jobs_quota)
                <div class="freepackagebox">                   
                    <div class="frpkgct">                    
                        <h5>{{__('Congratulations Your Account is Active now')}}</h5>
                        <p>{{__('You have got')}} {{$company->jobs_quota - $company->availed_jobs_quota}} {{__('free jobs postings, valid for 48 hours. Hurry Up before it ends')}}</p>
                    </div>
                    <a href="{{url('/post-job')}}">{{_('Post a Job')}}</a>
                </div>
            @endif



        <?php
        if((bool)config('company.is_company_package_active')){        
        $packages = App\Package::where('package_for', 'like', 'employer')->get();
        $package = Auth::guard('company')->user()->getPackage();
        ?>

        

        <?php if(null !== $package){ ?>
        @include('includes.company_package_msg')
        @include('includes.company_packages_upgrade')
        <?php }elseif(null !== $packages){ ?>
        @include('includes.company_packages_new')
        <?php }} ?>



        <div class="paypackages mt-5">
    <!---four-plan-->
    <?php 
        $company = Auth::guard('company')->user(); 
        $currentPackage = $company->cvs_getPackage(); 
    ?>
    @if(null !== $currentPackage)
    @if(null!==($currentPackage) && !empty($currentPackage))
<div class="instoretxt">

<h3>{{__('Purchased Cvs  Package Details')}}</h3>
<div class="table-responsive">
    <table class="table table-bordered mb-0">
    <thead class="table-dark">
        <tr>
        <th scope="col">{{ __('Package Name') }}</th>
<th scope="col">{{ __('Price') }}</th>
<th scope="col">{{ __('Available CV quota') }}</th>
<th scope="col">{{ __('Purchased On') }}</th>
<th scope="col">{{ __('Package Expired') }}</th>

        </tr>
    </thead>
    <tbody>
        <tr>
        <th>{{$currentPackage->package_title}}</th>
        <td>{{ $siteSetting->default_currency_code }}{{$currentPackage->package_price}}</td>
        <td><strong>{{ $company->availed_cvs_quota ?? 0 }}</strong> / <strong>{{$company->cvs_quota}}</strong></td>
        <td><strong>{{Carbon\Carbon::parse($company->cvs_package_start_date)->format('d M, Y')}}</strong></td>
        <td><strong>{{Carbon\Carbon::parse($company->cvs_package_end_date)->format('d M, Y')}}</strong></td>
        </tr>
    </tbody>
    </table>
</div>
</div>





            @endif
        <div class="four-plan">
            <h3>{{__('Upgrade CV Search Package')}}</h3>
            <div class="row">
                <?php $packages = App\Package::get(); ?>
                @foreach($packages as $package)
                    @if($package->package_for == 'cv_search')
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
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Applicant CV Views')}} {{$package->package_num_listings}}</li>
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('CV View Access')}} {{$package->package_num_days}} {{__('Days')}}</li>
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Premium Support 24/7')}}</li> 
                                
                                <li class="order paypal"><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#buypack{{$package->id}}" class="reqbtn">{{__('Buy Now')}} <i class="fas fa-arrow-right"></i></a></li>
                                
                            </ul>
                        </div>


                        <div class="modal fade" id="buypack{{$package->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{__('Buy Now')}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                       
                        <div class="invitereval">
                        <h3>{{__('Choose Your Payment Method')}}</h3>	
                            
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
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div class="four-plan">
            <h3>{{__('CV Search Packages')}}</h3>
            <div class="row">
                <?php $packages = App\Package::get(); ?>
                @foreach($packages as $package)
                    @if($package->package_for == 'cv_search')
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
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Applicant CV Views')}} {{$package->package_num_listings}}</li>
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('CV View Access')}} {{$package->package_num_days}} {{__('Days')}}</li>
                                <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Premium Support 24/7')}}</li> 
                                
                                <li class="order paypal"><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#buypack{{$package->id}}" class="reqbtn">{{__('Buy Now')}} <i class="fas fa-arrow-right"></i></a></li>

                            </ul>
                        </div>



                        <div class="modal fade" id="buypack{{$package->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        <div class="modal-header">
                                <h5 class="modal-title">{{__('Buy Now')}}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                        <div class="modal-body">
            
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



                    @endif
                @endforeach
            </div>
        </div>
    @endif
    <!---end four-plan-->
</div>




        </div>
        </div>
    </div>
</div>




@include('includes.footer')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://sandbox.paypal.com/sdk/js?client-id={{ env('PAYPAL_CLIENT_ID')}}"></script>
@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '{{ __("Success") }}',
            text: '{{ session("success") }}',
            confirmButtonText: '{{ __("OK") }}'
        });
    </script>
@endif
<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return fetch('/paypal/order', {
                method: 'post',
                headers: {
                    'content-type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    package_id:'3'  // Pass the relevant package_id
                })
            }).then(function(res) {
                return res.json();
            }).then(function(orderData) {
                return orderData.id;
            });
        },
        onApprove: function(data, actions) {
            return fetch('/paypal/order/3/capture', {
                method: 'post',
                headers: {
                    'content-type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(function(res) {
                return res.json();
            }).then(function(orderData) {
                // Handle the captured order details
                console.log('Capture result', orderData);
            });
        }
    }).render('#paypal-button-container');
</script>

@include('includes.immediate_available_btn')

@endpush

