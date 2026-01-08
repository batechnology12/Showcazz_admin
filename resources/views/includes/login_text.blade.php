@if(!Auth::user() && !Auth::guard('company')->user())
<a href="javascript:void();" data-bs-toggle="modal" data-bs-target="#preresume" class="userloginbox">
@else

<a href="{{url('my-profile')}}" class="userloginbox">
@endif
		
		<h3>{{__('Search your desired Job')}}</h3>
		<p>{{__('Discover a career you are passionate about')}}</p>
		<img src="{{asset('/')}}images/search-job-icon.png" alt="Search your desired Job" />
		
</a>


