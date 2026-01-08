@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 


@include('includes.inner_top_search')
@include('flash::message')

@php
$company = $job->getCompany();
@endphp






<div class="listpgWraper">
    <div class="container"> 
        @include('flash::message')
       

    <div class="row jobPagetitle">
            <div class="col-lg-8">
                <div class="jobinfo">
                    <h2>{{$job->title}}</h2>
                    <div class="ptext">{{__('Date Posted')}}: {{$job->created_at->format('M d, Y')}}</div>
                    
                    @if(!(bool)$job->hide_salary)
                    <div class="salary">{{$job->getSalaryPeriod('salary_period')}}: <strong>{{$job->salary_currency.' '.$job->salary_from}} - {{$job->salary_currency.' '.$job->salary_to}}</strong></div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">

            <div class="jobButtons applybox">
            @if($job->isJobExpired())
                <span class="jbexpire"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Job is expired')}}</span>
                @elseif(Auth::check() && Auth::user()->isAppliedOnJob($job->id))
                    <a href="javascript:;" class="btn apply applied"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Already Applied')}}</a>
                @else
                    @if(!Auth::check())
                        @if($job->application_url != '')
                            <a href="{{route('job.apply', $job->slug)}}" class="btn apply"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Apply Now')}}</a>
                        @else
                            <a href="{{route('apply.job', $job->slug)}}" class="btn apply"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Apply Now')}}</a>
                        @endif
                    @else
                        @php
                            $user = Auth::user();
                            $profileIncomplete = count($user->getProfileProjectsArray()) == 0 ||
                                                count($user->getProfileCvsArray()) == 0 ||
                                                count($user->profileExperience()->get()) == 0 ||
                                                count($user->profileEducation()->get()) == 0 ||
                                                count($user->profileSkills()->get()) == 0;
                        @endphp

                        @if($profileIncomplete)
                            <a href="{{ route('my.profile') }}" class="btn apply"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{__('Complete your profile to apply')}}</a>
                        @else
                            @if($job->application_url != '')
                                <a href="{{route('job.apply', $job->slug)}}" class="btn apply"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Apply Now')}}</a>
                            @else
                                <a href="{{route('apply.job', $job->slug)}}" class="btn apply"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{__('Apply Now')}}</a>
                            @endif
                        @endif
                    @endif
                @endif

				</div>

            </div>
       </div>




        <!-- Job Detail start -->
        <div class="row">
            <div class="col-lg-7"> 
				
				 <!-- Job Header start -->
        <div class="job-header">
           
			
			<!-- Job Detail start -->
                <div class="jobmainreq">
                    <div class="jobdetail">
                       <h3><i class="fa fa-align-left" aria-hidden="true"></i> {{__('Job Detail')}}</h3>
						
							
                       <ul class="jbdetail row">
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">location_on</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Location')}}</strong>
                                        @if((bool)$job->is_freelance)
                                        <span>Freelance</span>
                                        @else
                                        <span>{{$job->getLocation()}}</span>
                                        @endif
                                    </div>
                                    </div>
                                </li>
                               
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist"> 
                                    <span class="material-symbols-outlined">desktop_windows</span>                                    
                                    <div class="jbitdata">
                                        <strong>{{__('Job Type')}}:</strong>
                                        <span>{{$job->getJobType('job_type')}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist"> 
                                    <span class="material-symbols-outlined">schedule</span>                                                                          
                                    <div class="jbitdata">
                                        <strong>{{__('Shift')}}:</strong>
                                        <span>{{$job->getJobShift('job_shift')}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">analytics</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Career Level')}}:</strong>                                        
                                        <span>{{$job->getCareerLevel('career_level')}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">group</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Positions')}}:</strong>
                                        <span>{{$job->num_of_positions}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">calendar_view_day</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Experience')}}:</strong>    
                                        <span>{{$job->getJobExperience('job_experience')}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">male</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Gender')}}:</strong>
                                        <span>{{$job->getGender('gender')}}</span></div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">school</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Degree')}}:</strong>
                                        <span>{{$job->getDegreeLevel('degree_level')}}</span></div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">calendar_month</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Apply Before')}}:</strong>
                                        <span>{{ \Carbon\Carbon::parse($job->expiry_date)->format('M d, Y') }}</span></div>
                                    </div>
                                </li> 
                                
                            </ul>
							
							
                       
                    </div>
                </div>
			
			
            <div class="jobButtons">
                <a href="{{route('email.to.friend', $job->slug)}}" class="btn"><i class="fas fa-envelope" aria-hidden="true"></i> {{__('Email to Friend')}}</a>
                @if(Auth::check() && Auth::user()->isFavouriteJob($job->slug)) <a href="{{route('remove.from.favourite', $job->slug)}}" class="btn"><i class="fas fa-floppy" aria-hidden="true"></i> {{__('Remove From Favourite Job')}} <i class="fas fa-times"></i></a> @else <a href="{{route('add.to.favourite', $job->slug)}}" class="btn"><i class="fas fa-floppy"></i> {{__('Add to Favourite')}}</a> @endif
                <a href="{{route('report.abuse', $job->slug)}}" class="btn report"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{__('Report Abuse')}}</a>
                </div>
            </div>
				
				
				
                <!-- Job Description start -->
                <div class="job-header">
                    <div class="contentbox">
                        <h3><i class="fas fa-file-text" aria-hidden="true"></i> {{__('Job Description')}}</h3>
                        <p>{!! $job->description !!}</p>                       
                    </div>
                </div>
				
				@if (!empty($job->benefits))
				<div class="job-header benefits">
                    <div class="contentbox">
                        <h3><i class="fa fa-file-text" aria-hidden="true"></i> {{__('Benefits')}}</h3>
                        <p>{!! $job->benefits !!}</p>                       
                    </div>
                </div>
                @endif
				
				<div class="job-header">
                    <div class="contentbox">                        
                        <h3><i class="fas fa-puzzle-piece" aria-hidden="true"></i> {{__('Skills Required')}}</h3>
                        <ul class="skillslist">
                            {!!$job->getJobSkillsList()!!}
                        </ul>
                    </div>
                </div>
				
				
                <!-- Job Description end --> 

                
            </div>
            <!-- related jobs end -->

            <div class="col-lg-5"> 
				
				
				
				<div class="companyinfo">
					<h3><i class="fas fa-building" aria-hidden="true"></i> {{__('Company Overview')}}</h3>
                            <div class="companylogo"><a href="{{route('company.detail',$company->slug)}}">{{$company->printCompanyImage()}}</a></div>
                            <div class="title"><a href="{{route('company.detail',$company->slug)}}">{{$company->name}}</a></div>
                            <div class="ptext">{{$company->getLocation()}}</div>
                            <div class="opening">
                                <a href="{{route('company.detail',$company->slug)}}">
                                    {{App\Company::countNumJobs('company_id', $company->id)}} {{__('Current Jobs Openings')}}
                                </a>
                            </div>
                            <div class="clearfix"></div>
					<hr>
				<div class="companyoverview">
				
					<p>{{\Illuminate\Support\Str::limit(strip_tags($company->description), 250, '...')}} <a href="{{route('company.detail',$company->slug)}}">Read More</a></p>
					</div>
                        </div>
				
			<!-- Google Map start -->
            <div class="job-header">
                    <div class="jobdetail">
                        <h3><i class="fas fa-map-marker" aria-hidden="true"></i> {{__('Google Map')}}</h3>
                        <div class="gmap">
                            <iframe src="https://maps.google.it/maps?q={{urlencode(strip_tags($company->map))}}&output=embed" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
				
			
            
               
            </div>
        </div>




	<!-- related jobs start -->
    <div class="relatedJobs">
        <h3 class="mb-0">{{__('Related Jobs')}}</h3>
        <ul class="featuredlist row">
            @if(isset($relatedJobs) && count($relatedJobs))
            @foreach($relatedJobs as $relatedJob)
            <?php $relatedJobCompany = $relatedJob->getCompany(); ?>
            @if(null !== $relatedJobCompany)
            <!--Job start-->
            <li class="col-lg-3 col-md-6">
                <div class="jobint">

                    <div class="d-flex">
                        <div class="fticon"><i class="fas fa-briefcase"></i> {{$relatedJob->getJobType('job_type')}}</div>                        
                    </div>
                    <h4><a href="{{route('job.detail', [$relatedJob->slug])}}" title="{{$relatedJob->title}}">{!! \Illuminate\Support\Str::limit($relatedJob->title, $limit = 20, $end = '...') !!}</a>
                    </h4>
                    @if(!(bool)$relatedJob->hide_salary)                    
                    <div class="salary mb-2">Salary: <strong>{{$relatedJob->salary_currency.''.$relatedJob->salary_from}} - {{$relatedJob->salary_currency.''.$relatedJob->salary_to}}/{{$relatedJob->getSalaryPeriod('salary_period')}}</strong></div>
                    @endif 
                    <strong><i class="fas fa-map-marker-alt"></i> {{$relatedJob->getCity('city')}}</strong>                     
                    <div class="jobcompany">
                     <div class="ftjobcomp">
                        <span>{{$relatedJob->created_at->format('M d, Y')}}</span>
                        <a href="{{route('company.detail', $relatedJobCompany->slug)}}" title="{{$company->name}}">{{$company->name}}</a>
                     </div>
                    <a href="{{route('company.detail', $relatedJobCompany->slug)}}" class="company-logo" title="{{$company->name}}">{{$company->printCompanyImage()}} </a>
                    </div>
                </div>
            </li>
        
            <!--Job end--> 
            @endif
            @endforeach
            @else
    <div class="nodatabox">
    <h4>{{__('There are currently no open positions available.')}}</h4>
    <div class="viewallbtn mt-2"><a href="{{url('/search-jobs')}}">{{__('Search Jobs')}}</a></div>
</div>
            @endif

            <!-- Job end -->
        </ul>
    </div>
                





    </div>



</div>
@include('includes.footer')
@endsection
@push('styles')
<style type="text/css">
    .view_more{display:none !important;}
</style>
@endpush
@push('scripts') 
<script>
    $(document).ready(function ($) {
        $("form").submit(function () {
            $(this).find(":input").filter(function () {
                return !this.value;
            }).attr("disabled", "disabled");
            return true;
        });
        $("form").find(":input").prop("disabled", false);

        $(".view_more_ul").each(function () {
            if ($(this).height() > 100)
            {
                $(this).css('height', 100);
                $(this).css('overflow', 'hidden');
                //alert($( this ).next());
                $(this).next().removeClass('view_more');
            }
        });



    });
</script> 
@endpush