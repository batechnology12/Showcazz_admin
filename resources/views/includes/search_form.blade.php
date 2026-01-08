@if(Auth::guard('company')->check())
<form action="{{route('job.seeker.list')}}" method="get">
    <div class="searchbar">
        
        <h3>{{__('Resume Search for Employers')}}</h3>
        
		<div class="srchbox">		
	
		 
		

		<div class="input-group mt-3">
        <input type="text"  name="search" id="empsearch" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('Search Candidate')}}" autocomplete="off" />
			    @if((bool)$siteSetting->country_specific_site)
        {!! Form::hidden('country_id[]', Request::get('country_id[]', $siteSetting->default_country_id), array('id'=>'country_id')) !!}
        @else
        
            {!! Form::select('country_id[]', ['' => __('Select Country')]+$countries, Request::get('country_id', $siteSetting->default_country_id), array('class'=>'form-control', 'id'=>'country_id')) !!}
       
        @endif

        
            <span id="state_dd">
                {!! Form::select('state_id[]', ['' => __('Select State')], Request::get('state_id', null), array('class'=>'form-control', 'id'=>'state_id')) !!}
            </span>
       
            <span id="city_dd">
                {!! Form::select('city_id[]', ['' => __('Select City')], Request::get('city_id', null), array('class'=>'form-control', 'id'=>'city_id')) !!}
            </span>        
       
        <button type="submit" class="btn"><i class="fas fa-search"></i></button>
       
	
			    </div>





		</div>
		
       
        
    </div>
</form>
@else
		
	<form action="{{route('job.list')}}" method="get">		
		<div class="searchbar">
		    <h3>{{__('Search Jobs')}}</h3>
			<div class="input-group mb-3">
				<input type="text"  name="search" id="jbsearch" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('Enter Skills or job title')}}" autocomplete="off" />
				{!! Form::select('functional_area_id[]', ['' => __('Select Functional Area')]+$functionalAreas, Request::get('functional_area_id', null), array('class'=>'form-control', 'id'=>'functional_area_id')) !!}

                <button type="submit" class="btn"><i class="fas fa-search"></i></button>
				
			</div>
			

			
			
		</div>
	</form>

    	
@endif

