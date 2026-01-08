<div class="header" id="siteheader">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-2 col-md-12 col-12"> <a href="{{url('/')}}" class="logo"><img src="{{ asset('/') }}sitesetting_images/thumb/{{ $siteSetting->site_logo }}" alt="{{ $siteSetting->site_name }}" /></a>
                <div class="navbar-header navbar-light">
                    <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nav-main" aria-controls="nav-main" aria-expanded="false" aria-label="Toggle navigation"> <i class="fas fa-bars"></i></button>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="col-lg-10 col-md-12 col-12"> 

                <!-- Nav start -->
                <nav class="navbar navbar-expand-lg navbar-light">
					
                    <div class="navbar-collapse collapse" id="nav-main">
                    <button class="close-toggler" type="button" data-toggle="offcanvas"> <span><i class="fas fa-times-circle" aria-hidden="true"></i></span> </button>

                        <ul class="navbar-nav">
              <li class="nav-item {{ Request::url() == route('index') ? 'active' : '' }}"><a href="{{url('/')}}" class="nav-link">{{__('Home')}}</a> </li>
							
                            
							@if(Auth::guard('company')->check())
                  <li class="nav-item {{ Request::url() == url('/job-seekers') ? 'active' : '' }}">
                      <a href="{{url('/job-seekers')}}" class="nav-link">{{__('Search Talent')}}</a>
                  </li>
              @else
                  <li class="nav-item {{ Request::url() == url('/search-jobs') ? 'active' : '' }}">
                      <a href="{{url('/search-jobs')}}" class="nav-link">{{__('Jobs')}}</a>
                  </li>
              @endif




              <li class="nav-item {{ Request::url() == url('/companies') ? 'active' : '' }}">
                  <a href="{{url('/companies')}}" class="nav-link">{{__('Companies')}}</a>
              </li>

                           
                            
							<li class="nav-item {{ Request::url() == route('blogs') ? 'active' : '' }}"><a href="{{ route('blogs') }}" class="nav-link">{{__('Blog')}}</a> </li>
                            <li class="nav-item {{ Request::url() == route('contact.us') ? 'active' : '' }}"><a href="{{ route('contact.us') }}" class="nav-link">{{__('Contact Us')}}</a> </li>
                            @if(Auth::check() && !Auth::guard('company')->check())
                            <li class="nav-item dropdown userbtn"><a href="">{{Auth::user()->printUserImage()}}</a>
                                <ul class="dropdown-menu">
                                    <li class="nav-item"><a href="{{route('home')}}" class="nav-link"><i class="fa fa-tachometer" aria-hidden="true"></i> {{__('Dashboard')}}</a> </li>
                                    <li class="nav-item"><a href="{{ route('my.profile') }}" class="nav-link"><i class="fa fa-user" aria-hidden="true"></i> {{__('My Profile')}}</a> </li>
                                    <li class="nav-item"><a href="{{ route('view.public.profile', Auth::user()->id) }}" class="nav-link"><i class="fa fa-eye" aria-hidden="true"></i> {{__('View Public Profile')}}</a> </li>
                                    <li><a href="{{ route('my.job.applications') }}" class="nav-link"><i class="fa fa-desktop" aria-hidden="true"></i> {{__('My Job Applications')}}</a> </li>
                                    <li class="nav-item"><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();" class="nav-link"><i class="fa fa-sign-out" aria-hidden="true"></i> {{__('Logout')}}</a> </li>
                                    <form id="logout-form-header" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </ul>
                            </li>
                            @elseif(Auth::guard('company')->check())   
                            <li class="nav-item register"><a href="{{route('post.job')}}" class="nav-link register">{{__('Post a job')}}</a> </li>
                            <li class="nav-item dropdown userbtn"><a href="">{{Auth::guard('company')->user()->printCompanyImage()}}</a>
                                <ul class="dropdown-menu">
                                    <li class="nav-item"><a href="{{route('company.home')}}" class="nav-link"><i class="fa fa-tachometer" aria-hidden="true"></i> {{__('Dashboard')}}</a> </li>
                                    <li class="nav-item"><a href="{{ route('company.profile') }}" class="nav-link"><i class="fa fa-user" aria-hidden="true"></i> {{__('Company Profile')}}</a></li>
                                    <li class="nav-item"><a href="{{ route('post.job') }}" class="nav-link"><i class="fa fa-desktop" aria-hidden="true"></i> {{__('Post Job')}}</a></li>
                                    <li class="nav-item"><a href="{{route('company.messages')}}" class="nav-link"><i class="fa fa-envelope" aria-hidden="true"></i> {{__('Company Messages')}}</a></li>
                                    <li class="nav-item"><a href="{{ route('company.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header1').submit();" class="nav-link"><i class="fa fa-sign-out" aria-hidden="true"></i> {{__('Logout')}}</a> </li>
                                    <form id="logout-form-header1" action="{{ route('company.logout') }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </ul>
                            </li>
                            @endif 
                            
                            @if(!Auth::user() && !Auth::guard('company')->user())
                            <li class="nav-item"><a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#headlogin" class="nav-link">{{__('Sign in')}}</a> </li>
							              <li class="nav-item register"><a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#headregister" class="nav-link register">{{__('Register')}}</a> </li>                            
                            @endif
                            <li class="dropdown userbtn"><a href="{{url('/')}}"><img src="{{asset('/')}}images/lang.png" alt="Change Language" class="userimg" /></a>
                                <ul class="dropdown-menu">
                                    @foreach($siteLanguages as $siteLang)
                                    <li><a href="javascript:;" onclick="event.preventDefault(); document.getElementById('locale-form-{{$siteLang->iso_code}}').submit();" class="nav-link">{{$siteLang->native}}</a>
                                        <form id="locale-form-{{$siteLang->iso_code}}" action="{{ route('set.locale') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="locale" value="{{$siteLang->iso_code}}"/>
                                            <input type="hidden" name="return_url" value="{{url()->full()}}"/>
                                            <input type="hidden" name="is_rtl" value="{{$siteLang->is_rtl}}"/>
                                        </form>
                                    </li>
                                    @endforeach
                                </ul>
                            </li>
                        </ul>

                        <!-- Nav collapes end --> 

                    </div>
                    <div class="clearfix"></div>
                </nav>

                <!-- Nav end --> 

            </div>
        </div>

        <!-- row end --> 

    </div>

    <!-- Header container end --> 

</div>






<?php /*?>@if(!Auth::user() && !Auth::guard('company')->user())
	<div class="">my dive 2</div>
@endif<?php */?>




<!-- Login -->
<div class="modal fade mypremodal" id="headlogin" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      <div class="modal-body">
        <div class="preuserinfo">
        <h3>Login as</h3>
        <a href="{{route('login')}}" class="btn btn-yellow mt-3">Job Seeker</a>
        <a href="{{url('company-login')}}" class="btn btn-dark mt-3">Company</a>
        </div>
      </div>
      
    </div>
  </div>
</div>


<!-- Register -->
<div class="modal fade mypremodal" id="headregister" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      <div class="modal-body">
        <div class="preuserinfo p-2 pb-4">
        <h3>Register as a</h3>
        <a href="{{route('register')}}" class="btn btn-yellow mt-3">Job Seeker</a>
        <a href="{{url('company-register')}}" class="btn btn-dark mt-3">Company</a>
        </div>
      </div>
      
    </div>
  </div>
</div>



<!-- Modal -->
<div class="modal fade mypremodal" id="preresume" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      <div class="modal-body">
        <div class="preuserinfo">
        <h3>Login or register to create your Resume/CV</h3>
        <a href="{{route('login')}}" class="btn btn-yellow mt-3">Login</a>
        <a href="{{route('register')}}" class="btn btn-dark mt-3">Register</a>
        </div>
      </div>
      
    </div>
  </div>
</div>

<div class="modal fade mypremodal" id="prejobpost" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      <div class="modal-body">
        <div class="preuserinfo ps-0 pe-0">
        <h3>{{__('Welcome to Employer Portal')}}</h3>
        <p>Earn our user's trust. Get your account approved to start posting jobs</p>

        @if(!Auth::user() && !Auth::guard('company')->user())
        <a href="{{url('company-login')}}" class="btn btn-yellow mt-3">Login</a>
        <a href="{{url('company-register')}}" class="btn btn-dark mt-3">Register</a>

       
        @endif




        </div>
      </div>
      
    </div>
  </div>
</div>


<div class="mobilenav">
  <ul>
  <li><a href="{{url('/')}}">
    <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
    <span>Home</span>
    </a></li>


        @if(Auth::guard('company')->check())
              <li>
                <a href="{{url('/job-seekers')}}">
                <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M160-120q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v440q0 33-23.5 56.5T800-120H160Zm0-80h640v-440H160v440Zm240-520h160v-80H400v80ZM160-200v-440 440Z"/></svg>
                <span>Talent</span>  
              </a>
              </li>
              @else
              <li>
      <a href="{{url('/search-jobs')}}">
      <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M160-120q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v440q0 33-23.5 56.5T800-120H160Zm0-80h640v-440H160v440Zm240-520h160v-80H400v80ZM160-200v-440 440Z"/></svg>
      <span>Jobs</span>  
    </a>
    </li>
  @endif




    @if(!Auth::user() && !Auth::guard('company')->user())
    <li>
      <a href="{{url('/companies')}}">
    <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
    <span>Companies</span>
    </a>
    </li>


    <li>
      <a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#headlogin">
    <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
    <span>Login</span>
    </a>
    </li>
    @endif


    
  @if(Auth::check() && !Auth::guard('company')->check())
  <li>
      <a href="{{route('my.messages')}}">
      <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M880-80 720-240H320q-33 0-56.5-23.5T240-320v-40h440q33 0 56.5-23.5T760-440v-280h40q33 0 56.5 23.5T880-640v560ZM160-473l47-47h393v-280H160v327ZM80-280v-520q0-33 23.5-56.5T160-880h440q33 0 56.5 23.5T680-800v280q0 33-23.5 56.5T600-440H240L80-280Zm80-240v-280 280Z"/></svg>
      <span>Messages</span>
      </a>
    </li>
  <li>
      <a href="javascript:void();" class="openmbnav">
    <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
    <span>User</span>
    </a>
    </li>
    
                           
  @elseif(Auth::guard('company')->check())    
  <li>
      <a href="{{route('company.messages')}}">
      <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M880-80 720-240H320q-33 0-56.5-23.5T240-320v-40h440q33 0 56.5-23.5T760-440v-280h40q33 0 56.5 23.5T880-640v560ZM160-473l47-47h393v-280H160v327ZM80-280v-520q0-33 23.5-56.5T160-880h440q33 0 56.5 23.5T680-800v280q0 33-23.5 56.5T600-440H240L80-280Zm80-240v-280 280Z"/></svg>
      <span>Messages</span>
      </a>
    </li>
  <li>
    <a href="javascript:void();" class="openmbnav">
    <svg xmlns="http://www.w3.org/2000/svg" height="36px" viewBox="0 -960 960 960" width="36px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
    <span>Dashboard</span>
    </a>
    </li>
    
    @endif




  </ul>
</div>






        

@if(Auth::check() && !Auth::guard('company')->check())
<ul class="usernavdash" id="usermbnav">
<li class="nav-item"><a href="{{route('home')}}" class="nav-link"><i class="fa fa-tachometer" aria-hidden="true"></i> {{__('Dashboard')}}</a> </li>
        <li class="nav-item"><a href="{{ route('my.profile') }}" class="nav-link"><i class="fa fa-user" aria-hidden="true"></i> {{__('My Profile')}}</a> </li>
        <li class="nav-item"><a href="{{ route('view.public.profile', Auth::user()->id) }}" class="nav-link"><i class="fa fa-eye" aria-hidden="true"></i> {{__('View Public Profile')}}</a> </li>
        <li><a href="{{ route('my.job.applications') }}" class="nav-link"><i class="fa fa-desktop" aria-hidden="true"></i> {{__('My Job Applications')}}</a> </li>
        <li class="nav-item"><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();" class="nav-link"><i class="fa fa-sign-out" aria-hidden="true"></i> {{__('Logout')}}</a> </li>
        <form id="logout-form-header" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
        </ul>
@elseif(Auth::guard('company')->check()) 
<ul class="usernavdash" id="usermbnav">  
<li class="nav-item"><a href="{{route('company.home')}}" class="nav-link"><i class="fa fa-tachometer" aria-hidden="true"></i> {{__('Dashboard')}}</a> </li>
        <li class="nav-item"><a href="{{ route('company.profile') }}" class="nav-link"><i class="fa fa-user" aria-hidden="true"></i> {{__('Company Profile')}}</a></li>
        <li class="nav-item"><a href="{{ route('post.job') }}" class="nav-link"><i class="fa fa-desktop" aria-hidden="true"></i> {{__('Post Job')}}</a></li>

        <li class="nav-item"><a href="{{ route('posted.jobs') }}" class="nav-link"><i class="fab fa-black-tie"></i> {{__('Manage Jobs')}}</a></li>

        <li class="nav-item"><a href="{{ route('company.packages') }}" class="nav-link"><i class="fas fa-search" aria-hidden="true"></i> {{__('CV Search Packages')}}</a></li>

        <li class="nav-item"><a href="{{ url('/list-payment-history') }}" class="nav-link"><i class="fas fa-file-invoice"></i> {{__('Payment History')}}</a></li>
        
        <li class="nav-item"><a href="{{ route('company.unloced-users') }}" class="nav-link"><i class="fas fa-user" aria-hidden="true"></i> {{__('Unlocked Users')}}</a></li>
        <li class="nav-item"><a href="{{route('company.followers')}}" class="nav-link"><i class="fas fa-users" aria-hidden="true"></i> {{__('Company Followers')}}</a></li>

      
        <li class="nav-item"><a href="{{ route('company.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header1').submit();" class="nav-link"><i class="fa fa-sign-out" aria-hidden="true"></i> {{__('Logout')}}</a> </li>
        <form id="logout-form-header1" action="{{ route('company.logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
        </ul>
@endif 


