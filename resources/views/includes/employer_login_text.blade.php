

@if(Auth::check() && Auth::guard('company')->check())
<a href="{{route('register')}}" class="userloginbox">
@else
<a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#prejobpost" class="userloginbox">
@endif		

<h3>{{__('Post a Job Today')}}</h3>
<p>{{__('Discover the ideal candidate for your team')}}</p>
<img src="{{asset('/')}}images/postjob.png" alt="Post a Job Today" />
	
</a>
