@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 

<div class="pageSearch">
<div class="container">



      <div class="row">
        <div class="col-lg-7">
        <h3 class="mb-1">{{__('Browse Companies')}}.</h3>	
        <h5 class="mb-3">{{__('Get hired in most high rated companies')}}.</h5>

        <section id="joblisting-header" role="search" class="searchform">
        	
            <form id="top-search" method="GET" action="{{route('company.listing')}}">    
            
            <div class="input-group">
                 {!! Form::select('country_id[]', ['' => __('Select Country')]+$countries, Request::get('country_id', $siteSetting->default_country_id), array('class'=>'form-control', 'id'=>'country_id')) !!}
                 <span id="state_dd">
                {!! Form::select('state_id[]', ['' => __('Select State')], Request::get('state_id', null), array('class'=>'form-control', 'id'=>'state_id')) !!}
                </span>
           
                <span id="city_dd">
                    {!! Form::select('city_id[]', ['' => __('Select City')], Request::get('city_id', null), array('class'=>'form-control', 'id'=>'city_id')) !!}
                </span>
            </div> 
            
            <div class="input-group mt-3"> 
            {!! Form::select('industry_id', ['' => __('Select Industry')]+$industries, Request::get('industry_id', null), array('class'=>'form-control', 'id'=>'industry_id')) !!}
            <input type="text" name="search" value="{{Request::get('search', '')}}" class="form-control" placeholder="{{__('keywords e.g. "Google"')}}" />        
            <button type="submit" id="submit-form-top" class="btn"><i class="fa fa-search" aria-hidden="true"></i> {{__('Search Company')}}</button>
            </div> 
            </form>
        </section>  
        </div>
      </div>



</div></div>






<div class="listpgWraper">
<div class="container">
    <ul class="row compnaieslist">
        @if($companies->isEmpty())
            <p>No active and verified companies found.</p>
        @else
        @foreach($companies as $company)   
        <li class="col-lg-3 col-md-6">                
					<div class="empint">
                    <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">
                        <div class="emptbox">
                        <div class="comimg">{{$company->printCompanyImage()}}</div>
                            <div class="text-info-right">
                            <h4>{{$company->name}}</h4>	
                            <div class="emloc"><i class="fas fa-map-marker-alt"></i> {{$company->getCity('city')}}</div>
                            </div>	
                            		
                        </div>
                        <div class="cm-info-bottom mt-3"><i class="fas fa-briefcase"></i> {{$company->countNumJobs('company_id',$company->id)}} {{__('Open Jobs')}}</div>	
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