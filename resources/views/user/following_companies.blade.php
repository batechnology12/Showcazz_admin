@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 
<!-- Inner Page Title start --> 
@include('includes.inner_page_title', ['page_title'=>__('My Followings')]) 
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row">
            @include('includes.user_dashboard_menu')

            <div class="col-md-9 col-sm-8"> 
                <div class="myads">                    
                    <ul class="compnaieslist row">
                        <!-- job start --> 
                        @if(isset($companies) && count($companies))
                        @foreach($companies as $company)
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
                                <div class="emloc"><i class="fas fa-map-marker-alt"></i> {{$company->getCity('city')}}</div>
                                </div>	                                        
                            

                            <div class="cm-info-bottom"><span><i class="fas fa-briefcase"></i> {{$company->countNumJobs('company_id',$company->id)}} {{__('Open Jobs')}}</span></div>	
                            </div>
                        </a>					
                        </div>
                </li>


                        <!-- job end --> 
                        @endforeach
                        @else
                            
                            <div class="nodatabox">
                                <h4>{{__('No Followings Found')}}</h4>
                                <div class="viewallbtn mt-2"><a href="{{url('/companies')}}">{{__('Search Companies')}}</a></div>
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