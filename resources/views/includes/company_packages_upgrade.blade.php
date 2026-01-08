@if($packages->count())

<div class="paypackages"> 

    <!---four-paln-->

    <div class="four-plan">

        <h3>{{__('Upgrade Job Packages')}}</h3>

        <div class="row"> @foreach($packages as $package)
		@if($package->package_price > 0)
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

                    <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Job Posting')}} {{$package->package_num_listings}}</li>
                    <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Job Displayed for')}} {{$package->package_num_days}} {{__('Days')}}</li>   

					@if($package->package_price == 10)
					<li class="plan-pages noadded"><i class="far fa-times-circle"></i> {{__('Highlights Jobs')}}</li>  
					<li class="plan-pages @if($package->package_price == 0) disabled @endif"><i class="far fa-times-circle"></i> {{__('Premium Support 24/7')}}</li> 
					@else
                    <li class="plan-pages"><i class="far fa-check-circle"></i> {{__('Highlights jobs on Demand')}}</li>  
					<li class="plan-pages @if($package->package_price == 0) disabled @endif"><i class="far fa-check-circle"></i> {{__('Premium Support 24/7')}}</li>  
					
					@endif
					
                   

                    <li class="order paypal"><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#buypack{{$package->id}}" class="reqbtn">{{__('Buy Now')}} <i class="fas fa-arrow-right"></i></a></li>

                </ul>
				
				
				<div class="modal fade" id="buypack{{$package->id}}" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
		<div class="modal-header">
        <h5 class="modal-title">{{__('Buy Now')}}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

		<div class="modal-body">
		
		<div class="invitereval">
		<h3>Please Choose Your Payment Method to Pay</h3>	
			
		<div class="totalpay">{{__('Total Amount to pay')}}: <strong>{{ $siteSetting->default_currency_code }} {{$package->package_price}}</strong></div>
			
		<ul class="btn2s">
		@if((bool)$siteSetting->is_paypal_active)
		<li class="order paypal p-2"><a href="{{route('order.upgrade.package', $package->id)}}"><i class="fab fa-cc-paypal" aria-hidden="true"></i> {{__('Paypal')}}</a></li>
		@endif
		@if((bool)$siteSetting->is_stripe_active)
		<li class="order p-2"><a href="{{route('stripe.order.form', [$package->id, 'upgrade'])}}"><i class="fab fa-cc-stripe" aria-hidden="true"></i> {{__('Stripe')}}</a></li>
		@endif
		@if((bool)$siteSetting->is_payu_active)
		   <li class="order payu p-2"><a href="{{route('payu.order.package', ['package_id='.$package->id, 'type=upgrade'])}}">{{__('PayU')}}</a></li>
		@endif
		</ul>		
		</div>
		</div>
		</div>
		</div>
		</div>
				

            </div>
			@endif
            @endforeach </div>

    </div>

    <!---end four-paln--> 

</div>

@endif