<ul class="row profilestat">
    <li class="col-lg-3 col-md-3 col-6">
        <a href="{{ route('resume', Auth::user()->id) }}" class="inbox"> 
            <i class="fas fa-eye" aria-hidden="true"></i>
            <h6>{{Auth::user()->num_profile_views}}
            <strong>{{__('Profile Views')}}</strong>
            </h6>
        </a>
    </li>
    <li class="col-lg-3 col-md-3 col-6">
        <a href="{{route('my.followings')}}" class="inbox"> 
            <i class="fas fa-user" aria-hidden="true"></i>
            <h6>{{Auth::user()->countFollowings()}}
            <strong>{{__('Followings')}}</strong>
        </h6>
        </a>
    </li>
    <li class="col-lg-3 col-md-3 col-6">
        <a href="{{url('my-profile#cvs')}}" class="inbox"> 
            <i class="fas fa-briefcase" aria-hidden="true"></i>
            <h6>{{Auth::user()->countProfileCvs()}}
            <strong>{{__('My CV List')}}</strong>
        </h6>
        </a>
    </li>
    <li class="col-lg-3 col-md-3 col-6">
        <a href="{{route('my.messages')}}" class="inbox"> 
            <i class="fas fa-envelope" aria-hidden="true"></i>
            <h6>{{Auth::user()->countUserMessages()}}
            <strong>{{__('Messages')}}</strong>
            </h6>
        </a>
    </li>
</ul>