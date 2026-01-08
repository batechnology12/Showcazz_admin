<div class="section">
	<div class="container position-relative">
        <div class="titleTop mb-4 text-center">
            <h3><i class="fas fa-ribbon"></i> {{__('Featured Candidates')}}</h3>
        </div>
        
        
        <ul class="userlisting homeseekerlist owl-carousel">
            <!-- job start --> 
            @if(isset($jobSeekers) && count($jobSeekers))
            @foreach($jobSeekers as $jobSeeker)
            

            <li class="item">
            <div class="seekerbox">  
              <div class="ribbon ribbon-top-left"><span><i class="fas fa-star"></i> Featured</span></div> 

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
        
        <div class="custom-nav">
    <button class="custom-prev"><i class="fas fa-chevron-left"></i></button>
    <button class="custom-next"><i class="fas fa-chevron-right"></i></button>
</div>
        
        <div class="viewallbtn mt-0"><a href="{{url('/job-seekers')}}">View All Candidates</a></div>
        
	</div>
</div>  





<!-- Hire Candidate -->
<div class="modal fade mypremodal" id="hireme" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
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




