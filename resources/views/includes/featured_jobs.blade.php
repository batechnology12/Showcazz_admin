<div class="section featuredjobwrap">
    <div class="container"> 
    @if(isset($featuredJobs) && count($featuredJobs))
        <!-- title start -->
        <div class="titleTop text-center">
            <h3>{{__('Featured Jobs')}}</h3>
        </div>
        <!-- title end --> 

        <!--Featured Job start-->
        <ul class="featuredlist row">
            
            @foreach($featuredJobs as $featuredJob)
            <?php $company = $featuredJob->getCompany(); ?>
            @if(null !== $company)
            <!--Job start-->
            <li class="col-lg-3 col-md-6">
                <div class="jobint">
                    <div class="d-flex">
                        <div class="fticon"><i class="fas fa-briefcase"></i> {{$featuredJob->getJobType('job_type')}}</div>                        
                    </div>

                    <h4><a href="{{route('job.detail', [$featuredJob->slug])}}" title="{{$featuredJob->title}}">{!! \Illuminate\Support\Str::limit($featuredJob->title, $limit = 20, $end = '...') !!}</a></h4>
                    <strong><i class="fas fa-map-marker-alt"></i> {{$featuredJob->getCity('city')}}</strong> 
                    
                    <div class="jobcompany">
                     <div class="ftjobcomp">
                        <span>{{$featuredJob->created_at->format('M d, Y')}}</span>
                        <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">{{$company->name}}</a>
                     </div>
                    <a href="{{route('company.detail', $company->slug)}}" class="company-logo" title="{{$company->name}}">{{$company->printCompanyImage()}} </a>
                    </div>
                </div>
            </li>
            <!--Job end--> 
            @endif
            @endforeach
           

        </ul>
        <!--Featured Job end--> 

        <!--button start-->
        <div class="viewallbtn"><a href="{{route('job.list', ['is_featured'=>1])}}">{{__('View All Featured Jobs')}}</a></div>
        <!--button end--> 
    
     @endif
    </div>

    
    
</div>