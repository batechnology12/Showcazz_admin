<div class="section countrieswrap greybg">
    <div class="container">
        <!-- title start -->
        <div class="titleTop text-center">            
            <h3>{{__('Find Jobs by Country')}}</h3>
        </div>
        <!-- title end -->
        <div class="srchint">
            <ul class="row countrieslist">
               
@if(isset($topCountryIds) && count($topCountryIds)) 
    @foreach($topCountryIds as $country_id_num_jobs)
        <?php
        $country = App\Country::getCountryById($country_id_num_jobs->country_id);
        ?> 
        @if(null !== $country)
            <li class="col-lg-3 col-md-6">
            <a href="{{route('job.list', ['country_id[]'=>$country->id])}}" title="{{$country->country}}" class="countryinfobox">                
                    <h4>{{__('Jobs in')}} {{$country->country}}</h4>
                    <span>({{$country_id_num_jobs->num_jobs}}) {{__('Open Jobs')}}</span>                
                </a>
            </li>
        @endif 
    @endforeach 
@endif

            </ul>
            <!--Countries end-->
        </div>
    </div>
</div>
