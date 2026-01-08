<ul class="row profilestat">
    <li class="col-md-4 col-6">
        <a href="{{route('posted.jobs')}}" class="inbox"> <i class="fas fa-clock" aria-hidden="true"></i>
            <h6>{{Auth::guard('company')->user()->countOpenJobs()}}
            <strong>{{__('Open Jobs')}}</strong>
        </h6>
</a>
    </li>
    <li class="col-md-4 col-6">
        <a href="{{route('company.followers')}}" class="inbox"> <i class="fas fa-user" aria-hidden="true"></i>
            <h6>{{Auth::guard('company')->user()->countFollowers()}}
            <strong>{{__('Followers')}}</strong> 
        </h6>
</a>
    </li>
     <li class="col-md-4 col-6">
        <a href="{{route('company.messages')}}" class="inbox"> <i class="fas fa-envelope" aria-hidden="true"></i>
            <h6>{{Auth::guard('company')->user()->countCompanyMessages()}}
            <strong>{{__('Messages')}}</strong>
        </h6>
</a>
    </li>
</ul>