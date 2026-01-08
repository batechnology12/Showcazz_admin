@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 


@include('flash::message')
        <form action="{{route('job.seeker.list')}}" method="get">
            <!-- Page Title start -->
            <div class="pageSearch">
				<div class="container">
                   
                    <div class="row justify-content-center">   
                    <div class="col-lg-4"><h3>{{__('Find Candidates')}}</h3></div>                  
                        <div class="col-lg-8">
                            <div class="searchform">
                               <div class="input-group">
                                <input type="text" name="search" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('Enter Skills or job seeker details')}}" />
                                {!! Form::select('functional_area_id[]', ['' => __('Select Functional Area')]+$functionalAreas, Request::get('functional_area_id', null), array('class'=>'form-control', 'id'=>'functional_area_id')) !!}

                                <button type="submit" class="btn"><i class="fa fa-search" aria-hidden="true"></i></button>
                                
                                </div>
                            </div>




                        </div>
                    </div>
				</div>
            </div>
            <!-- Page Title end -->
        </form>


        

<div class="listpgWraper">
    <div class="container">
        
        <form action="{{route('job.seeker.list')}}" method="get">


            <!-- Search Result and sidebar start -->
            <div class="row">              
                    <div class="col-lg-3"> 

                        @include('includes.job_seeker_list_side_bar')   
                           
                    </div>    

                <div class="col-lg-9"> 
                    <!-- Search List -->
                    <ul class="userlisting row">
                        <!-- job start --> 
                        @if(isset($jobSeekers) && count($jobSeekers))
    @foreach($jobSeekers as $jobSeeker)
        <li class="col-lg-4">
            <div class="seekerbox">  
                @if($jobSeeker->is_featured)
                    <!-- <div class="featureduser"><i class="fas fa-star"></i> Featured</div>  -->
                    <div class="ribbon ribbon-top-left"><span><i class="fas fa-star"></i> Featured</span></div>  
                @endif

                <div class="ltisusrinf">
                    <div class="userltimg">{{$jobSeeker->printUserImage(100, 100)}}</div>
                </div>                                

                <div class="hmseekerinfo">
                    <h3>{{$jobSeeker->getName()}}</h3>                
                    <div class="hmcate justify-content-center" title="Functional Area">{{$jobSeeker->getFunctionalArea('functional_area')}}</div>
                    <div class="hmcate justify-content-center" title="Career Level"><i class="fas fa-chart-line"></i> {{$jobSeeker->getCareerLevel('career_level')}}</div>
                    <div class="hmcate justify-content-center"><i class="fas fa-map-marker-alt"></i> {{$jobSeeker->getCity('city')}}</div>  
                    
                    <div class="listbtn">
                    @if(Auth::user() || (!Auth::user() && !Auth::guard('company')->user()))                   
                    <a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#hireme">{{__('View Profile')}}</a>
                    @else
                    <a href="{{route('user.profile', $jobSeeker->id)}}">{{__('View Profile')}}</a>
                    @endif

                   </div>

                </div>    
            </div>
        </li>
    @endforeach
@endif

                    </ul>

                    <!-- Pagination Start -->
                    <div class="pagiWrap">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="showreslt">
                                    {{__('Showing Pages')}} : {{ $jobSeekers->firstItem() }} - {{ $jobSeekers->lastItem() }} {{__('Total')}} {{ $jobSeekers->total() }}
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                @if(isset($jobSeekers) && count($jobSeekers))
                                {{ $jobSeekers->appends(request()->query())->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Pagination end --> 
                    <div class=""><br />{!! $siteSetting->listing_page_horizontal_ad !!}</div>
                </div> 
            </div>
				
            </div>
        </form>
    </div>
</div>



<!-- Hire Candidate -->
<div class="modal fade mypremodal" id="hireme" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      <div class="modal-body">
        <div class="preuserinfo">
        <h3>{{ __('Our users rely on us to keep their information secure.') }}</h3>
<p>{{ __('Log in or register as an employer to access candidate details.') }}</p>
<a href="{{ url('company-login') }}" class="btn btn-yellow mt-3">{{ __('Login') }}</a>
<a href="{{ url('company-register') }}" class="btn btn-dark mt-3">{{ __('Register') }}</a>

        </div>
      </div>
      
    </div>
  </div>
</div>



@include('includes.footer')
@endsection
@push('styles')
<style type="text/css">
    .searchList li .jobimg {
        min-height: 80px;
    }
    .hide_vm_ul{
        height:100px;
        overflow:hidden;
    }
    .hide_vm{
        display:none !important;
    }
    .view_more{
        cursor:pointer;
    }
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
                $(this).addClass('hide_vm_ul');
                $(this).next().removeClass('hide_vm');
            }
        });
        $('.view_more').on('click', function (e) {
            e.preventDefault();
            $(this).prev().removeClass('hide_vm_ul');
            $(this).addClass('hide_vm');
        });

    });
</script>
@include('includes.country_state_city_js')
@endpush