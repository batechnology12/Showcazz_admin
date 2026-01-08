@extends('layouts.app')

@section('content') 

<!-- Header start --> 

@include('includes.header') 

<!-- Header end --> 

<!-- Inner Page Title start --> 

@include('includes.inner_page_title', ['page_title'=>__('Unlocked Seekers')]) 

<!-- Inner Page Title end -->

<div class="listpgWraper">

    <div class="container">

        <div class="row">

            @include('includes.company_dashboard_menu')



            <div class="col-md-9 col-sm-8"> 

                <div class="myads">

                    <h3>{{__('Unlocked Seekers')}}</h3>

                    <ul class="userlisting row">

                        <!-- job start --> 

                        @if(isset($users) && count($users))

                        @foreach($users as $user)

                        <li class="col-lg-4">
            <div class="seekerbox">                

                <div class="ltisusrinf">
                    <div class="userltimg">{{$user->printUserImage(100, 100)}}</div>
                </div>                                

                <div class="hmseekerinfo">
                    <h3>{{$user->getName()}}</h3>                
                    <div class="hmcate justify-content-center" title="Functional Area">{{$user->getFunctionalArea('functional_area')}}</div>
                    <div class="hmcate justify-content-center" title="Career Level"><i class="fas fa-chart-line"></i> {{$user->getCareerLevel('career_level')}}</div>
                    <div class="hmcate justify-content-center"><i class="fas fa-map-marker-alt"></i> {{$user->getCity('city')}}</div>  
                    
                    <div class="listbtn">
                    <a href="{{route('user.profile', $user->id)}}">{{__('View Profile')}}</a>

                   </div>

                </div>    
            </div>
        </li>



                        <!-- job end --> 

                        @endforeach
                        @else
                            
                            <div class="nodatabox">
                                <h4>{{__('No Unlocked Seekers Found')}}</h4>
                                <div class="viewallbtn mt-2"><a href="{{url('/job-seekers')}}">{{__('Search Candidates')}}</a></div>
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