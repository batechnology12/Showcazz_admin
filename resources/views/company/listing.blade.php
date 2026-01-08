@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 



<div class="pageSearch text-center">
            <h3>{{__('Get hired in most high rated companies')}}.</h3>
            </div>




<div class="listpgWraper">
<div class="container">

    <div class="row">
        <div class="col-lg-3">



      
                
                <form id="top-search" method="GET" action="{{route('company.listing')}}">    
                                  
                <div class="compnysidebarserch">           
                    <h4>Search Filter</h4>
                    <div class="mb-3">                        
                    <input type="text" name="search" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('keywords e.g. "Google"')}}" />  
                    </div>
                    <div class="mb-3">
                        <label for="">Country</label>
                        {!! Form::select('country_id[]', ['' => __('Select Country')]+$countries, Request::get('country_id', $siteSetting->default_country_id), array('class'=>'form-control', 'id'=>'country_id')) !!}
                    </div>
                    <div class="mb-3">
                        <label for="">State</label>
                        <span id="state_dd">
                {!! Form::select('state_id[]', ['' => __('Select State')], Request::get('state_id', null), array('class'=>'form-control', 'id'=>'state_id')) !!}
                </span>
                    </div>
                    <div class="mb-3">
                        <label for="">City</label>
                        <span id="city_dd">
                    {!! Form::select('city_id[]', ['' => __('Select City')], Request::get('city_id', null), array('class'=>'form-control', 'id'=>'city_id')) !!}
                </span>
                    </div>

                    <div class="mb-3">
                    <label for="">Industry</label>
                    <ul class="optionlist">
                        @foreach($industries as $key => $industry)
                            <li>
                                <input type="checkbox" name="industry_id[]" id="industry_id_{{$key}}" value="{{$key}}" {{ in_array($key, (array)old('industry_id', $industry_id)) ? 'checked' : '' }}>
                                <label for="industry_id_{{$key}}"></label>
                                {{ $industry['name'] }} <span>{{ $industry['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    </div>

                    <div class="comfilter">
                    <button type="submit" id="submit-form-top" class="btn"><i class="fa fa-search" aria-hidden="true"></i> {{__('Search Company')}}</button>
                    </div>

                </div>
                
                
                </form>
         



        </div>
        <div class="col-lg-9">



            <h3>{{ $companies->total() }} Employers Found</h3>
            <div class="showreslt mb-4">
                        {{__('Showing Companies')}} : {{ $companies->firstItem() }} - {{ $companies->lastItem() }} {{__('Total')}} {{ $companies->total() }}
                    </div>

        <ul class="row compnaieslist">
            @if($companies->isEmpty())
                <p>No active and verified companies found.</p>
            @else
            @foreach($companies as $company)   
            <li class="col-lg-4 col-md-6">                
                        <div class="empint">
                        <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">
                            <div class="emptbox">
                            <div class="comimg">{{$company->printCompanyImage()}}</div>
                                <div class="text-info-right">
                                <h4>{{$company->name}}</h4>	
                                @if($company->getIndustry('industry'))
                                    <div class="indst">                           
                                        {{ $company->getIndustry('industry') }}                         
                                    </div>
                                @endif
                                <div class="emloc"><i class="fas fa-map-marker-alt"></i> {{$company->getCity('city')}}</div>
                                </div>	                                        
                            

                            <div class="cm-info-bottom"><span><i class="fas fa-briefcase"></i> {{$company->countNumJobs('company_id',$company->id)}} {{__('Open Jobs')}}</span></div>	
                            </div>
                        </a>					
                        </div>
                </li>
            @endforeach
            @endif

        </ul>
	    <div class="pagiWrap">
            <div class="row">
                <div class="col-md-5">
                    <div class="showreslt">
                        {{__('Showing Companies')}} : {{ $companies->firstItem() }} - {{ $companies->lastItem() }} {{__('Total')}} {{ $companies->total() }}
                    </div>
                </div>
                <div class="col-md-7 text-right">
                    @if(isset($companies) && count($companies))
                    {{ $companies->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>


        </div>
    </div>



  
</div>
</div>

@include('includes.footer')
@endsection
@push('styles')
<style type="text/css">
    .formrow iframe {
        height: 78px;
    }
</style>
@endpush
@push('scripts') 

@include('includes.country_state_city_js')
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', '#send_company_message', function () {
            var postData = $('#send-company-message-form').serialize();
            $.ajax({
                type: 'POST',
                url: "{{ route('contact.company.message.send') }}",
                data: postData,
                //dataType: 'json',
                success: function (data)
                {
                    response = JSON.parse(data);
                    var res = response.success;
                    if (res == 'success')
                    {
                        var errorString = '<div role="alert" class="alert alert-success">' + response.message + '</div>';
                        $('#alert_messages').html(errorString);
                        $('#send-company-message-form').hide('slow');
                        $(document).scrollTo('.alert', 2000);
                    } else
                    {
                        var errorString = '<div class="alert alert-danger" role="alert"><ul>';
                        response = JSON.parse(data);
                        $.each(response, function (index, value)
                        {
                            errorString += '<li>' + value + '</li>';
                        });
                        errorString += '</ul></div>';
                        $('#alert_messages').html(errorString);
                        $(document).scrollTo('.alert', 2000);
                    }
                },
            });
        });
    });
</script> 
@endpush