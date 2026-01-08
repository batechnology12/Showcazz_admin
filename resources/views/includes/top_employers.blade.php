<div class="section topemployers">



    <div class="container"> 
        <!-- title start -->
        <div class="titleTop">            
            <h2>{{__('Top Companies are Hiring')}}</h2>
        </div>
        <!-- title end -->

        <ul class="employerList owl-carousel owl-theme" data-group-item="2">
            <!--employer-->
            @if(isset($topCompanyIds) && count($topCompanyIds))
            @foreach($topCompanyIds as $company_id_num_jobs)
            <?php
            $company = App\Company::where('id', '=', $company_id_num_jobs->company_id)->active()->first();
            if (null !== $company) {
                ?>
                <li class="item-child" data-number="{{$company->id}}">                
					<div class="empint">
                    <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">
                        <div class="emptbox">
                        <div class="comimg">{{$company->printCompanyImage()}}</div>
                            <div class="text-info-right">
                            <h4>{{$company->name}}</h4>	
                            <div class="emloc"><i class="fas fa-map-marker-alt"></i> {{$company->getCountry('country')}}</div>
                            </div>	
                            		
                        </div>
                        <div class="cm-info-bottom mt-3"><i class="fas fa-briefcase"></i> {{$company->countNumJobs('company_id',$company->id)}} {{__('Open Jobs')}}</div>	
                    </a>					
					</div>
			</li>
                <?php
            }
            ?>
            @endforeach
            @endif
        </ul>
        <div class="viewallbtn"><a href="{{url('/featured-companies')}}">View All Featured Companies</a></div>

    </div> 
	
	

	
	
</div>


