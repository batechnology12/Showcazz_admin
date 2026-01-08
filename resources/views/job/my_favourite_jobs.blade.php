@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Inner Page Title start -->
@include('includes.inner_page_title', ['page_title'=>__('Favourite Jobs')])
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row">
            @include('includes.user_dashboard_menu')

            <div class="col-md-9 col-sm-8"> 
                <div class="myads">
                <ul class="featuredlist row">
                        <!-- job start --> 
                        @if(isset($jobs) && count($jobs))
                        @foreach($jobs as $job)
                        @php $company = $job->getCompany(); @endphp
                        @if(null !== $company)
                      


                        <li class="col-lg-6 col-md-6">
                            <div class="jobint mt-0 mb-4">

                                <div class="d-flex">
                                    <div class="fticon"><i class="fas fa-briefcase"></i> {{$job->getJobType('job_type')}}</div>                        
                                </div>
                                <h4><a href="{{route('job.detail', [$job->slug])}}" title="{{$job->title}}">{!! \Illuminate\Support\Str::limit($job->title, $limit = 20, $end = '...') !!}</a>
                                </h4>
                                @if(!(bool)$job->hide_salary)                    
                                <div class="salary mb-2">Salary: <strong>{{$job->salary_currency.''.$job->salary_from}} - {{$job->salary_currency.''.$job->salary_to}}/{{$job->getSalaryPeriod('salary_period')}}</strong></div>
                                @endif 
                                <strong><i class="fas fa-map-marker-alt"></i> {{$job->getCity('city')}}</strong>                     
                                <div class="jobcompany">
                                <div class="ftjobcomp">
                                    <span>{{$job->created_at->format('M d, Y')}}</span>
                                    <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">{{$company->name}}</a>
                                </div>
                                <a href="{{route('company.detail', $company->slug)}}" class="company-logo" title="{{$company->name}}">{{$company->printCompanyImage()}} </a>
                                </div>

                                @if(Auth::check() && Auth::user()->isFavouriteJob($job->slug))
                                <a href="{{route('remove.from.favourite', $job->slug)}}" class="btn btn-danger mt-3"><i class="fas fa-times"></i> {{__('Remove')}}</a>                                
                                @endif


                            </div>
                        </li>



                        <!-- job end --> 
                        @endif
                        @endforeach
                        @else
                            
                            <div class="nodatabox">
                                <h4>{{__('No Favourite Jobs Found')}}</h4>
                                <div class="viewallbtn mt-2"><a href="{{url('/jobs')}}">{{__('Search Jobs')}}</a></div>
                            </div>


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
@endpush