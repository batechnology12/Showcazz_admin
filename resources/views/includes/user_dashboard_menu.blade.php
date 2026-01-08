<div class="col-lg-3">

 <!-- Featured Profile Package -->
 <?php 
    $featured_package = App\Package::where('package_for', 'make_featured')->first();
    $packageEndDate = auth()->user()->featured_package_end_at ?? auth()->user()->package_end_date;
    $isExpired = $packageEndDate ? \Carbon\Carbon::parse($packageEndDate)->isPast() : true;
?>

@if(!auth()->user()->is_featured || $isExpired)
    <div class="featuredprofile">
        <div class="packginfor">
            <h5><i class="fas fa-bolt"></i> {{$featured_package->package_title}}</h5>
            <div class="featprice">{{ $siteSetting->default_currency_code ?? '' }}{{$featured_package->package_price}}<span>For {{$featured_package->package_num_days}} {{__('Days')}}</span></div>
            <p>Gain a competitive edge in the job market with exclusive features</p>
            <ul class="pckfeatlist">
                <li><i class="fas fa-crown"></i> Premium Badge</li>
                <li><i class="fas fa-chart-line"></i> Rank Booster</li>
                <li><i class="fas fa-ribbon"></i> Your CV above all others</li>
                <li><i class="fas fa-briefcase"></i> Increased Job Opportunities</li>
                <li><i class="fas fa-eye"></i> Higher Profile Views</li>
                <li><i class="fas fa-bell"></i> Exclusive Alerts</li>
            </ul>
        </div>
        <div class="order">
            @if(count(auth()->user()->getProfileProjectsArray()) == 0 || count(auth()->user()->getProfileCvsArray()) == 0 || count(auth()->user()->profileExperience()->get()) == 0 || count(auth()->user()->profileEducation()->get()) == 0 || count(auth()->user()->profileSkills()->get()) == 0)				
                <a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#buyfeatured">Buy Now</a>
            @else
                <a href="{{ route('order.package', $featured_package->id) }}"> 
                    <i class="fab fa-cc-paypal" aria-hidden="true"></i> {{__('PayPal')}}
                </a>
                <a href="{{ route('stripe.order.form', [$featured_package->id, 'new']) }}" class="mt-3">
                    <i class="fab fa-cc-stripe" aria-hidden="true"></i> {{__('Stripe')}}
                </a>
            @endif
        </div>
    </div>
@else
    <div class="featuredprofile purchased">
        <div class="packginfor">
            <h5>Featured Profile</h5>
            <p>Congratulations! Your profile is now prominently displayed to attract more attention from recruiters and employers.</p>
        </div>
        <ul class="pckfeatlist">
            <li><i class="fas fa-crown"></i> Premium Badge</li>
            <li><i class="fas fa-chart-line"></i> Rank Booster</li>
            <li><i class="fas fa-ribbon"></i> Your CV above all others</li>
            <li><i class="fas fa-briefcase"></i> Increased Job Opportunities</li>
            <li><i class="fas fa-eye"></i> Higher Profile Views</li>
            <li><i class="fas fa-bell"></i> Exclusive Alerts</li>
        </ul>
        <div class="">
            <div class="order">
                <span>Package Start On</span> 
                <strong>{{ \Carbon\Carbon::parse(auth()->user()->package_start_date)->format('d M Y') }}</strong>
            </div>
            <div class="order">
                <span>Package Ends On</span> 
                <strong>{{ \Carbon\Carbon::parse($packageEndDate)->format('d M Y') }}</strong>
            </div>
        </div>
    </div>
@endif

            <!-- Modal -->
            <div class="modal fade mypremodal" id="buyfeatured" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="preuserinfo">
                        <h3>To Buy Featured Package you need to first complete your profile.</h3>
                        <a href="{{ route('my.profile') }}" class="btn btn-yellow mt-3">Edit Profile</a>
                        </div>
                    </div>
                    </div>
                </div>
            </div>




	<div class="usernavwrap">
    <div class="switchbox">
        <div class="txtlbl">{{__('Open to Work')}} <i class="fas fa-question-circle" title="{{__('Are you immediate available')}}?"></i>
        </div> 
        <div class="">
            <label class="switch switch-green"> @php
                $checked = ((bool)Auth::user()->is_immediate_available)? 'checked="checked"':'';
                @endphp
                <input type="checkbox" name="is_immediate_available" id="is_immediate_available" class="switch-input" {{$checked}} onchange="changeImmediateAvailableStatus({{Auth::user()->id}}, {{Auth::user()->is_immediate_available}});">
                <span class="switch-label" data-on="Yes" data-off="No"></span> <span class="switch-handle"></span> </label>
        </div>
        <div class="clearfix"></div>
    </div>
    <ul class="usernavdash">
        <li class="{{ Request::url() == route('home') ? 'active' : '' }}"><a href="{{route('home')}}"><i class="fas fa-tachometer" aria-hidden="true"></i> {{__('Dashboard')}}</a>
        </li>
        <li class="{{ Request::url() == route('my.profile') ? 'active' : '' }}"><a href="{{ route('my.profile') }}"><i class="fas fa-pencil" aria-hidden="true"></i> {{__('Edit Profile')}}</a>
        </li>
        <li class="{{ Request::url() == route('build.resume') ? 'active' : '' }}"><a href="{{ route('build.resume') }}"><i class="fas fa-file" aria-hidden="true"></i> {{ __('Build Resume') }}</a></li>
        <li><a href="{{ route('resume', Auth::user()->id) }}"><i class="fa fa-print" aria-hidden="true"></i> {{__('Download CV')}}</a></li>
        <li><a href="{{ route('view.public.profile', Auth::user()->id) }}"><i class="fas fa-eye" aria-hidden="true"></i> {{__('View Public Profile')}}</a>
        </li>
        <li class="{{ Request::url() == route('my.job.applications') ? 'active' : '' }}"><a href="{{ route('my.job.applications') }}"><i class="fas fa-desktop" aria-hidden="true"></i> {{__('My Job Applications')}}</a>
        </li>
        <li class="{{ Request::url() == route('my.favourite.jobs') ? 'active' : '' }}"><a href="{{ route('my.favourite.jobs') }}"><i class="fas fa-heart" aria-hidden="true"></i> {{__('My Favourite Jobs')}}</a>
        </li>
        <li class="{{ Request::url() == route('my-alerts') ? 'active' : '' }}"><a href="{{ route('my-alerts') }}"><i class="fas fa-bullhorn" aria-hidden="true"></i> {{__('My Job Alerts')}}</a>
        </li>

        <li class="{{ Request::url() == route('candidate.list-payment-history') ? 'active' : '' }}">
        <a href="{{ route('candidate.list-payment-history') }}"><i class="fas fa-file-invoice-dollar"></i> {{__('Payment History')}}</a>
        </li>


        <li><a href="{{url('my-profile#cvs')}}"><i class="fas fa-file" aria-hidden="true"></i> {{__('Manage Resume')}}</a>
        </li>
        <li class="{{ Request::url() == route('my.messages') ? 'active' : '' }}"><a href="{{route('my.messages')}}"><i class="fas fa-envelope" aria-hidden="true"></i> {{__('My Messages')}}</a>
        </li>
        <li class="{{ Request::url() == route('my.followings') ? 'active' : '' }}"><a href="{{route('my.followings')}}"><i class="fas fa-user" aria-hidden="true"></i> {{__('My Followings')}}</a>
        </li>
        <li><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out" aria-hidden="true"></i> {{__('Logout')}}</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </li>
    </ul>
		</div>
   
		
</div>