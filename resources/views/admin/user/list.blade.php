@extends('admin.layouts.admin_layout')

@section('content')

<style type="text/css">

    .table td, .table th {

        font-size: 12px;

        line-height: 2.42857 !important;

    }	

</style>

<div class="page-content-wrapper"> 

    <!-- BEGIN CONTENT BODY -->

    <div class="page-content"> 

        <!-- BEGIN PAGE HEADER--> 

        <!-- BEGIN PAGE BAR -->

        <div class="page-bar">

            <ul class="page-breadcrumb">

                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>

                <li> <span>Search CVs</span> </li>

            </ul>

        </div>

        <!-- END PAGE BAR --> 

        <!-- BEGIN PAGE TITLE-->

        <h3 class="page-title">Search Jobs Seekers </h3>

        <!-- END PAGE TITLE--> 

        <!-- END PAGE HEADER-->

        <div class="row">

            <div class="col-md-12"> 

                <!-- Begin: life time stats -->

                <div class="portlet light portlet-fit portlet-datatable bordered">

                    <div class="portlet-title">

                        <div class="caption"> <i class="icon-settings font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Search Job Seekers</span> </div>

                       

                    </div>

                    <div class="portlet-body">

                        <div class="table-container">

                            <form action="{{route('admin.assign-cvs')}}" method="get">

                                    <div class="searchbar">

                                        <div class="srchbox">

                                        <div class="row srcsubfld additional_fields">

                                        <div class="col-md-12">

                                                <input type="text"  name="search" id="empsearch" value="{{Request::get('search')}}" class="form-control" maxlength="30" placeholder="{{__('Job Seeker Name')}}" autocomplete="off" /><br>

                                        </div>



                                        <div class="col-md-{{((bool)$siteSetting->country_specific_site)? 6:3}}">

                                            {!! Form::select('functional_area_id[]', ['' => __('Select Functional Area')]+$functionalAreas, Request::get('functional_area_id', null), array('class'=>'form-control', 'id'=>'functional_area_id')) !!}

                                        </div>



                                        @if((bool)$siteSetting->country_specific_site)

                                        {!! Form::hidden('country_id[]', Request::get('country_id[]', $siteSetting->default_country_id), array('id'=>'country_id')) !!}

                                        @else

                                        <div class="col-md-3">

                                            {!! Form::select('country_id[]', ['' => __('Select Country')]+$countries, Request::get('country_id', $siteSetting->default_country_id), array('class'=>'form-control', 'id'=>'country_id')) !!}

                                        </div>

                                        @endif



                                        <div class="col-md-3">

                                            <span id="state_dd">

                                                {!! Form::select('state_id[]', ['' => __('Select State')], Request::get('state_id', null), array('class'=>'form-control', 'id'=>'state_id')) !!}

                                            </span>

                                        </div>

                                        <div class="col-md-3">

                                            <span id="city_dd">

                                                {!! Form::select('city_id[]', ['' => __('Select City')], Request::get('city_id', null), array('class'=>'form-control', 'id'=>'city_id')) !!}

                                            </span>

                                            <br>

                                        </div>

                                        <div class="col-md-12">

                                            {!! Form::select('job_skill_id[]', $jobSkills, request()->job_skill_id, array('class'=>'form-control select2-multiple', 'id'=>'job_skill_id', 'multiple'=>'multiple')) !!}
											<div class="clearfix"></div>
                                            <br>

                                        </div> 

                                        <div class="col-md-3">

                                           {!! Form::select('degree_id[]', [''=>__('Select Education')]+$degrees, request()->degree_id, array('class'=>'form-control', 'id'=>'degree_id')) !!}
                                            

                                        </div> 



                                        <div class="col-md-3">

                                            {!! Form::select('job_experience_id[]', [''=>__('Select Experience')]+$jobExperiences, request()->job_experience_id, array('class'=>'form-control', 'id'=>'job_experience_id')) !!}

                                        </div>



                                        <div class="col-md-3">

                                            {!! Form::select('career_level_id[]', [''=>__('Select Career Level')]+$careerLevels, request()->career_level_id, array('class'=>'form-control', 'id'=>'career_level_id')) !!}
                                            <br>
                                        </div>



                                        <div class="col-md-4">

                                            {!! Form::select('industry_id[]', [''=>__('Select Industry')]+$industries, request()->industry_id, array('class'=>'form-control', 'id'=>'industry_id')) !!}

                                            <br>

                                        </div>



                                        <div class="col-md-4">

                                            {!! Form::text('expected_salary', null, array('class'=>'form-control', 'id'=>'expected_salary', 'placeholder'=>__('Expected Salary'))) !!}
                                            <br>
                                        </div>

                                        <div class="col-md-4">

                                            

                                            {!! Form::text('salary_currency', Null, array('class'=>'form-control', 'id'=>'salary_currency', 'placeholder'=>__('Salary Currency'), 'autocomplete'=>'off')) !!}

                                        </div>

                                        </div>

                                        

                                        <br>

                                         

                                          <span class="input-group-btn" style="text-align: center;">

                                            <input type="submit" class="btn btn-primary" value="{{__('Search Job Seeker')}}">

                                          </span>

                                        </div>

                                        

                                       

                                        

                                    </div>

                                </form>

                                 <br>

                                <br>

                                 <div class="pagiWrap">

                        <div class="row">

                            <div class="col-md-5">

                                <div class="showreslt">

                                    {{__('Showing Pages')}} : {{ $jobSeekers->firstItem() }} - {{ $jobSeekers->lastItem() }} {{__('Total')}} {{ $jobSeekers->total() }}

                                </div>

                            </div>

                        </div>

                    </div>  

                                <table class="table table-striped table-bordered table-hover"  id="user_datatable_ajax">

                                    <thead>

                                        <tr role="row" class="heading"> 

                                            <th>Id</th>                                        

                                            <th>Name</th>

                                            <th>Email</th>

                                            <th>Job Sector</th> 

                                            <th width="130">Skills</th>  

                                            <th>Work Experience</th>

                                            <th>Current Salary</th> 

                                            <th>Expected Salary</th>
                                            <th>CV Added</th>
                                            <th>Public Profile</th>                             
                                            <th>Registration Date</th>                             

                                            <!-- <th>Actions</th> -->

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @if(null!==($jobSeekers))

                                        @foreach($jobSeekers as $seeker)

                                        <tr id="user_dt_row_{{$seeker->id}}" role="row" class="odd">

                                            <td class="sorting_{{$seeker->id}}">{{$seeker->id}}</td>

                                            <td>{{$seeker->name}}</td>

                                            <td>{{$seeker->email}}</td>

                                            <td>{{$seeker->getFunctionalArea('functional_area')}}</td>

                                            <td>{!!$seeker->getProfileSkillsComma()!!}</td>

                                            <td>{{$seeker->getJobExperience('job_experience')}}</td>
                                            <td>{{$seeker->salary_currency}}{{$seeker->current_salary}}</td>

                                            <td>{{$seeker->salary_currency}}{{$seeker->expected_salary}}</td>

                                            <td>
                                                @if(count($seeker->profileCvs)<=0)
                                                <strong style="color: #E70509">No</strong>
                                                @else
                                               <strong style="color: #2DC507">Yes</strong>
                                                @endif
                                            </td>

                                            <td><a target="_blank" href="{{route('admin.view.public.profile',$seeker->id)}}">Profile</a></td>
                                            <td>{{date('d F Y',strtotime($seeker->created_at))}}</td>

                                           <!--  <td>

                                                <div class="btn-group">

                                                    <input type="checkbox" data-id="{{$seeker->id}}" value="{{$seeker->id}}" name="customcheck" class="form-control customCheck" id="customcheck_{{$seeker->id}}">

                                                    

                                                    

                                                </div>

                                            </td> -->

                                        </tr>

                                        @endforeach

                                        @endif

                                    </tbody>

                                </table>
                                <div class="pagiWrap">



                        <div class="row">



                            <div class="col-lg-8">



                                <div class="showreslt">



                                    {{__('Displaying Results')}} : {{ $jobSeekers->firstItem() }} - {{ $jobSeekers->lastItem() }} {{__('Total')}} {{ $jobSeekers->total() }}



                                </div>



                            </div>





                            <div class="col-lg-4">



                                @if(isset($jobSeekers) && count($jobSeekers))



                                {{ $jobSeekers->appends(request()->query())->links() }}

                             

                                @endif



                            </div>



                        </div>



                    </div>

                                @if($jobSeekers->total()==0)
                                <p>No records found</p> 
                                @endif

                                

                                

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- END CONTENT BODY --> 

</div>

@endsection

@push('scripts')

 @include('includes.country_state_city_js')

<script type="text/javascript">

    



    $("#company_id").select2();





    $(document).on('change', '.job_id', function (e) {

    var company_id =$('#company_id').val();

    var job_id =$(this).val();

    var availed_cvs_ids =$(this).find(':selected').data('ids');

       var sThisVal =$(".customCheck").map(function(){

      return $(this).data('id');

    }).get();

    //console.log(availed_cvs_ids);

    $(".customCheck").prop('checked', false);

    $('.checker span').removeClass( 'checked');

       //console.log(sThisVal);

    sThisVal.forEach(function(item) {

        if(item!=''){

               $.ajax({



          type: "GET", 



          url: '{{route("check-lock-unlock")}}?job_id='+job_id+'&company_id='+company_id,   



          success: function(data){

                var arr = data.toString().split(",");

                for (i = 0; i < arr.length; i++) { 

                   if($.trim(arr[i]) == item){

                    $('#customcheck_'+item).attr( 'checked', true );

                    $('#uniform-customcheck_'+item+' span').addClass( 'checked');

                   }

                }

          },



      }); 

               

        }

    });

    //alert('adfhkj');

    //$('#customcheck_21').prop('checked', true);

});







 $('#company_id').on('change', function (e) {





            e.preventDefault();

            var sThisVal =$(".customCheck").map(function(){

              return $(this).data('id');

            }).get();



            sThisVal.forEach(function(item) {

                if(item!=''){ 



                            $('#customcheck_'+item).attr( 'checked', false );

                            $('#uniform-customcheck_'+item+' span').removeClass( 'checked');

                       

                }

            });





            filterJobs(0);







        });





  function filterJobs(job_id)







    {







        var company_id = $('#company_id').val();







        if (company_id != '') {







            $.post("{{ route('filter.default.jobs.dropdown') }}", {company_id: company_id, job_id: job_id, _method: 'POST', _token: '{{ csrf_token() }}'})







                    .done(function (response) {







                        $('#default_jobs_dd').html(response);



                        



                        







                    });







        }







    }



$(".customCheck").change(function() {

        var user_id=$(this).val();

        var company_id=$('#company_id').val();

        var job_id = $('#job_id').val();

        //alert(user_id);

       var id='';

        if(company_id!='' && company_id!='Select Employer' && job_id!='' && job_id!='Select Job'){

                if($(this).is(':checked')){

                    $.ajax({

                          type: "GET", 

                          url: '{{route("admin.company.unlock.future")}}?user_id='+user_id+'&company_id='+company_id+'&job_id='+job_id,   

                          success: function(data){

                            if(data=='You have successully Unlocked this profile'){

                                $('#customcheck_'+user_id).prop('checked', true);

                                $('#uniform-customcheck_'+user_id+' span').addClass( 'checked');

                           }else{

                               

                                $('#customcheck_'+user_id).prop('checked', false);

                                $('#uniform-customcheck_'+user_id+' span').removeClass( 'checked');

                            }



                          },

                      });

                } else {

                        $.ajax({

                          type: "GET", 

                          url: '{{route("admin.company.lock")}}?user_id='+user_id+'&company_id='+company_id+'&job_id='+job_id,   

                          success: function(data){    

                          },

                      });

                }

                

        }else{

            alert('Please Select Employer and Job')

            $('.customCheck:checked').prop('checked', false);

            $('.checker span').removeClass( 'checked');

        }

        if($(this).is(':checked')){

          abc='yes'

        }else{

          abc='no';

        }

        

}); 

$('.select2-multiple').select2({
    placeholder: "Select Required Skills",
    allowClear: true
});

$("form").submit(function () {



            $(this).find(":input").filter(function () {



                return !this.value;



            }).attr("disabled", "disabled");



            return true;



        });

</script>

@endpush