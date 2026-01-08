@extends('admin.layouts.admin_layout')
@push('css')
@endpush
@section('content')


@php

$company = $job->getCompany();

@endphp


  <?php 

if($job->salary_type == 'single_salary'){
if(null!==($job->salary_from)){
$salary = '<strong><span class="symbol">'.$job->salary_currency.'</span>'.number_format($job->salary_from).'</strong>';
}else{
$salary = '';
}

}else if($job->salary_type == 'salary_in_range'){
//echo $job->salary_from;
$salary_from = (null!==($job->salary_from))?'<strong><span class="symbol">'.$job->salary_currency.'</span>'.number_format($job->salary_from):null;
$salary_to = (null!==($job->salary_from))?' - <span class="symbol">'.$job->salary_currency.'</span>'.number_format($job->salary_to).'</strong>':null;
$salary = $salary_from.$salary_to;

}else if($job->salary_type=='negotiable'){
    if(null!==($job->salary_from)){
$salary = '<strong>'.$job->salary_from.'</strong>';
}else{
$salary = '';
}
}else{
if(null!==($job->salary_from)){
$salary = '<strong><span class="symbol">'.$job->salary_currency.'</span>'.$job->salary_from.'</strong>';
}else{
$salary = '';
}
} 


?>  






<div class="page-content-wrapper"> 

    <!-- BEGIN CONTENT BODY -->

    <div class="page-content"> 

        <!-- BEGIN PAGE HEADER--> 

        <!-- BEGIN PAGE BAR -->

        <div class="page-bar">

            <ul class="page-breadcrumb">

                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>

                <li> <span>Detail</span> </li>

            </ul>

        </div>




<div class="listpgWraper">

    <div class="container"> 

        @include('flash::message')
        <div class="row">
            <div class="col-md-12">
                <div class="portlet" style="margin-top: 30px;">
                  
                        <div class="row">
                            <div class="col-md-4">
                                <div class="caption font-red-sunglo"> <i class="icon-settings font-red-sunglo"></i> <span class="caption-subject bold uppercase">Job Details</span> </div>
                            </div>
                            
                            <div class="col-md-8 text-right">
								                        

                        @if($job->status=='request_to_delete')
                        <a class="btn btn-danger" href="javascript:void(0);" onClick="deleteJob({{$job->id}})" data-job="{{$job->title}}"  id="job-{{$job->id}}"><i class="fa fa-square-o" aria-hidden="true"></i> Delete</a>
                        @endif
                       
                        
								
						<a class="btn btn-primary" href="{{route('edit.job', ['id' => $job->id])}}"><i class="fa fa-edit" aria-hidden="true"></i> Edit Job</a>
								
                            </div>
                        </div>
                        
                    
                    <div class="portlet-body form">          
                       
                        {!! Form::model($job, array('method' => 'put', 'route' => array('update.job', $job->id), 'class' => 'form', 'files'=>true)) !!}
                        {!! Form::hidden('id', $job->id) !!}            
                        
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

       



        <!-- Job Detail start -->

        <div class="row">

            <div class="col-lg-7"> 

                

                 <!-- Job Header start -->

        <div class="job-header">

            <div class="jobinfo">

               

                        <h2>{{$job->title}}</h2>

                        <div class="ptext">{{__('Date Posted')}}: {{$job->created_at->format('M d, Y')}}</div>

                        

                        

                        @if(!(bool)$job->hide_salary)

                        <div class="salary">{{$job->getSalaryPeriod('salary_period')}}: <strong>{{$job->salary_from.' '.$job->salary_currency}} - {{$job->salary_to.' '.$job->salary_currency}}</strong></div>


                        @endif

                    

            </div>

            

            <!-- Job Detail start -->

                <div class="jobmainreq">

                    <div class="jobdetail">

                       <h3><i class="fa fa-align-left" aria-hidden="true"></i> {{__('Job Details')}}</h3>

                        

                            

                             <ul class="jbdetail">

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Location(s)')}}:</div>
                                <?php 
                                      $locations = json_decode($job->multi_locations);
                                      $titles = array();
                                      if(null!==($locations)){
                                        foreach($locations as $lo){
                                            if($lo->multi_locations){
                                              $titles[] = $lo->multi_locations;
                                            }
                                        
                                        }
                                      }
                                      
                                  ?>
                                <div class="col-md-8 col-xs-7">

                                    @if((bool)$job->is_freelance)

                                    <span>Freelance</span>

                                    @else

                                    <span>{{$job->getLocation()}}</span>

                                    @endif

                                </div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Company')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{isset($company)?$company->name:null}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Type')}}:</div>

                                <div class="col-md-8 col-xs-7"><span class="permanent">{{$job->getJobType('job_type')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Shift')}}:</div>

                                <div class="col-md-8 col-xs-7"><span class="freelance">{{$job->getJobShift('job_shift')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Career Level')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{$job->getCareerLevel('career_level')}}</span></div>

                            </li>

                                <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Positions')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{$job->num_of_positions}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Experience')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{$job->getJobExperience('job_experience')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Gender')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{$job->getGender('gender')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Degree')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{$job->getDegreeLevel('degree_level')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-4 col-xs-5">{{__('Apply Before')}}:</div>

                                <div class="col-md-8 col-xs-7"><span>{{ \Carbon\Carbon::parse($job->expiry_date)->format('M d, Y') }}</span></div>

                            </li> 

                            

                        </ul>

                            

                            

                       

                    </div>

                </div>

            

            <hr>

          

        </div>

                

                

                

                <!-- Job Description start -->

                <div class="job-header">

                    <div class="contentbox">

                        <h3><i class="fa fa-file-text-o" aria-hidden="true"></i> {{__('Job Description')}}</h3>

                        <p>{!! $job->description !!}</p>                       

                    </div>

                </div>

                

                

                <div class="job-header bonus">

                    <div class="contentbox">

                    <h3><i class="fa fa-file-text" aria-hidden="true"></i> {{__('Benefits')}}</h3>
                        <p>{!! $job->benefits !!}</p>      

                        

                    </div>

                </div>

                

                <div class="job-header">

                    <div class="contentbox">                        

                        <h3><i class="fa fa-puzzle-piece" aria-hidden="true"></i> {{__('Skills Required')}}</h3>

                        <ul class="skillslist">

                            {!!$job->getJobSkillsList()!!}

                        </ul>

                    </div>

                </div>

                

                

                <!-- Job Description end --> 



                

            </div>

            <!-- related jobs end -->



            <div class="col-lg-5"> 

                
                @if($job->isJobExpired())
				<div class="jobButtons applybox">
                <span class="jbexpire"><i class="fa fa-paper-plane" aria-hidden="true"></i> {{__('Job Expired')}}</span>
					</div>
                @endif
				

              

                        <div class="companyinfo">

                    <h3><i class="fa fa-building-o" aria-hidden="true"></i> {{__('Company Overview')}}</h3>

                            <div class="companylogo">{{isset($company)?$company->printCompanyImage():null}}</div>

                            <div class="title">{{isset($company)?$company->name:null}}</div>

                            <div class="ptext">{{isset($company)?$company->getLocation():null}}</div>
                            

                            <div class="clearfix"></div>

           

                        </div>

                

            

                

                

                <!-- Google Map start -->

                <div class="job-header">

                    <div class="jobdetail">

                        <h3><i class="fa fa-map-marker" aria-hidden="true"></i> {{__('Job Location on Map')}}</h3>

                        <div class="gmap">

                            <iframe src="https://maps.google.it/maps?q={{urlencode(strip_tags($job->getLocation()))}}&output=embed" width="100%" height="270" frameborder="0" style="border:0" allowfullscreen=""></iframe>

                        </div>

                    </div>

                </div>

              

            </div>

        </div>

    </div>

</div>
</div>
</div>



@endsection

@push('styles')

<style type="text/css">

    .formrow iframe {

        height: 78px;

    }

</style>

@endpush

@push('scripts') 

<script type="text/javascript">
     function makeActive(id) {

        $.post("{{ route('make.active.job') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        $('#onclickActive{{$job->id}} i').removeClass('fa-square-o');
                        $('#onclickActive{{$job->id}} i').addClass('fa-check-square-o');
                        location.reload();

                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }



    function makeNotReviewed(id) {

        $('#rejected_job_id').val('');
        $('#rejected_job_id').val(id);
        $('#rejectedModal').modal('show');

    }
    function makeNotActive(id) {

        $.post("{{ route('make.not.active.job') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        
                        $('#onclickActive{{$job->id}} i').removeClass('fa-check-square-o');
                        $('#onclickActive{{$job->id}} i').addClass('fa-square-o');

                        location.reload();
                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }



function deleteJob(id) {

        var msg = 'Please confirm you want to delete Job: '+$('#job-'+id).data('job');

        if (confirm(msg)) {

            $.post("{{ route('delete.job') }}", {id: id, _method: 'DELETE', _token: '{{ csrf_token() }}'})

                    .done(function (response) {

                        if (response == 'ok')

                        {

                            location.reload();

                        } else

                        {

                            alert('Request Failed!');

                        }

                    });

        }

    }

</script> 

@endpush