@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 
<!-- Inner Page Title start --> 
<div class="pageSearch pt-md-5 pb-md-5">
<form action="{{route('job.list')}}" method="get">
	<!-- Page Title start -->
	<div class="container">
				<div class="row justify-content-center">
					<div class="col-lg-7">
					<h3 class="mt-0 text-center">{{__('Welcome to Your Candidate Dashboard')}}</h3>
						<div class="searchform">
						<div class="input-group">
							<input type="text"  name="search" id="jbsearch" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('Enter Skills or job title')}}" autocomplete="off" />
							<button type="submit" class="btn"><i class="fas fa-search"></i></button>
						</div>
						</div>
					</div>
				</div>
	</div>
	<!-- Page Title end -->
</form>
</div>
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">@include('flash::message')
        <div class="row"> @include('includes.user_dashboard_menu')
            <div class="col-lg-9">
            @if(count(auth()->user()->getProfileProjectsArray())==0 || count(auth()->user()->getProfileCvsArray())==0 || count(auth()->user()->profileExperience()->get()) == 0 || count(auth()->user()->profileEducation()->get()) == 0 || count(auth()->user()->profileSkills()->get()) == 0)
				<div class="userprofilealert"><h5><i class="fas fa-exclamation-triangle"></i> Your Resume is incomplete please update.</h5>
				<div class="editbtbn"><a href="{{ route('build.resume') }}"><i class="fas fa-user-edit"></i> Complete CV </a></div>	</div>
				@endif
            @include('includes.user_dashboard_stats')
            <div class="usercoverphoto">{{auth()->user()->printUserCoverImage()}}                    
                <a href="{{ route('my.profile') }}"><i class="fas fa-edit"></i></a>
            </div>
             <!-- Profile Information -->
			<div class="profileban">
				<div class="abtuser">
					<div class="row">
						<div class="col-lg-2 col-md-3">
							<div class="uavatar">{{auth()->user()->printUserImage()}}</div>						
						</div>
						<div class="col-lg-10 col-md-9">
							<h4>{{auth()->user()->name}}</h4>
							<ul class="userdata">
								<li><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{Auth::user()->getLocation()}}</li>
								<li><i class="fas fa-phone" aria-hidden="true"></i> {{auth()->user()->phone}}</li>
								<li><i class="fas fa-envelope" aria-hidden="true"></i> {{auth()->user()->email}}</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
            <!-- Applied jobs -->
			<div class="appliedjobswrap">
                <h3>{{__('My Applied Jobs')}}</h3>	   	
                @if(isset($appliedJobs) && count($appliedJobs))
                    <table class="table table-striped">
                    <thead class="table-dark">
                            <tr>
                            <th>{{ __('Job Title') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th>{{ __('Applied Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appliedJobs as $appliedjob)
                                @php
                                    $job = App\Job::where('id', $appliedjob->job_id)->first();
                                    $company = $job->getCompany();
                                    // Assuming you are using the JobApplication model to store applied jobs
                                    $appliedDate = $appliedjob->created_at->format('Y-m-d'); 
                                @endphp
                                @if(null !== $company)
                                    <tr>
                                        <td>
                                            <h5><a href="{{ route('job.detail', [$job->slug]) }}" title="{{ $job->title }}">{{ $job->title }}</a></h5>
                                        </td>
                                        <td>
                                            <a href="{{ route('company.detail', $company->slug) }}" title="{{ $company->name }}">{{ $company->name }}</a>
                                        </td>
                                        <td>{{ $job->getCity('city') }}</td>
                                        <td>{{ $appliedDate }}</td> 
                                        <td>{{ $appliedjob->status }}</td>
                                        <td>
                                            <a href="{{ route('job.detail', [$job->slug]) }}" class="btn btn-primary">{{ __('View Details') }}</a>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No applied jobs found.</p>
                @endif
			</div>

          


                @if((bool)config('jobseeker.is_jobseeker_package_active'))
                @php
                $packages = App\Package::where('package_for', 'like', 'job_seeker')->get();
                $package = Auth::user()->getPackage();
                if(null !== $package){
                $packages = App\Package::where('package_for', 'like', 'job_seeker')->where('id', '<>', $package->id)->where('package_price', '>=', $package->package_price)->get();
                }
                @endphp
                @if(null !== $package)
                @include('includes.user_package_msg')
                @include('includes.user_packages_upgrade')
                @else
                @if(null !== $packages)
                @include('includes.user_packages_new')
                @endif
                @endif
                @endif 



                            <div class="profbox">
                                <h3>{{__('Recommended Jobs')}}</h3>
                                <ul class="featuredlist">
                                @if(!empty($matchingJobs) && count($matchingJobs) > 0)
    @foreach($matchingJobs as $match)
        <li class="col-lg-4 col-md-6 @if($match->is_featured == 1) featured @endif">
            <div class="jobint">
                @if($match->is_featured == 1) 
                    <span class="promotepof-badge"><i class="fa fa-bolt" title="{{__('This Match is Featured')}}"></i></span> 
                @endif
                <div class="d-flex">
                    <div class="fticon"><i class="fas fa-briefcase"></i> {{$match->getJobType('job_type')}}</div>                        
                </div>
                <h4>
                    <a href="{{route('job.detail', [$match->slug])}}" title="{{$match->title}}">
                        {!! \Illuminate\Support\Str::limit($match->title, 20, '...') !!}
                    </a>
                </h4>                                        
                @if(!(bool)$match->hide_salary)                    
                    <div class="salary mb-2">Salary: 
                        <strong>{{$match->salary_currency.''.$match->salary_from}} - {{$match->salary_currency.''.$match->salary_to}}/{{$match->getSalaryPeriod('salary_period')}}</strong>
                    </div>
                @endif 
                <strong><i class="fas fa-map-marker-alt"></i> {{$match->getCity('city')}}</strong>                                         
                <div class="jobcompany">
                    <div class="ftjobcomp">
                        <span>{{$match->created_at->format('M d, Y')}}</span>
                        @if(isset($match->company))
                            <a href="{{route('company.detail', $match->company->slug)}}" title="{{$match->company->name}}">{{$match->company->name}}</a>
                        @endif
                    </div>
                    @if(isset($match->company))
                        <a href="{{route('company.detail', $match->company->slug)}}" class="company-logo" title="{{$match->company->name}}">{{$match->company->printCompanyImage()}}</a>
                    @endif
                </div>
            </div>
        </li>
    @endforeach 
@else
    <div class="alert alert-danger">{{__('No matching jobs found')}}</div>
@endif

                                </ul>
                            </div>

 <!-- My Followings -->

                            <div class="profbox followbox">
								<h3>{{__('My Followings')}} <a href="{{route('my.followings')}}">{{__('View All')}} <i class="fas fa-arrow-right"></i></a></h3>
								<ul class="row compnaieslist">
								@if(isset($followers) && $followers->isNotEmpty())
                                @foreach($followers as $follow)
                                @php
                                    $company = \App\Company::where('slug', $follow->company_slug)
                                        ->where('is_active', 1)
                                        ->first();
                                @endphp
                                @if(isset($company))
                                    <li class="col-lg-4 col-md-6">
                                        <div class="empint">
                                            <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">
                                                <div class="emptbox">
                                                    <div class="comimg">{{$company->printCompanyImage()}}</div>
                                                    <div class="text-info-right">
                                                        <h4>{{$company->name}}</h4>    
                                                        @if($company->getIndustry('industry'))
                                                            <div class="indst">                            
                                                                {{ $company->getIndustry('industry') }}                          
                                                            </div>
                                                        @endif
                                                        <div class="emloc"><i class="fas fa-map-marker-alt"></i> {{$company->location}}</div>
                                                    </div>                                         
                                                    <div class="cm-info-bottom">
                                                        <span><i class="fas fa-briefcase"></i> {{$company->countNumJobs('company_id',$company->id)}} {{__('Open Jobs')}}</span>
                                                    </div>    
                                                </div>
                                            </a>                    
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                                @else
                                <li class="col-lg-12">{{ __('No Followings Found') }}</li>
                                @endif
								</ul>
								
							</div>


			</div>
        </div>
    </div>
</div>
@include('includes.footer')
@endsection
@push('scripts')
@include('includes.immediate_available_btn')
<script>
$(document).ready(function() {
    function fadeTextEffect(texts) {
        var index = 0;
        function fadeText() {
            $(texts[index])
                .fadeIn(500) // Fade in over 1 second
                .delay(8000) // Display for 8 seconds
                .fadeOut(500, function() { // Fade out over 1 second
                    index = (index + 1) % texts.length; // Move to the next text
                    fadeText(); // Recursively call to continue the loop
                });
        }
        fadeText(); // Start the animation loop
    }
    // Apply the fade effect to both fade-text and fadetext2
    fadeTextEffect($('.fade-text'));
    fadeTextEffect($('.fadetext2'));
});
</script>
@endpush