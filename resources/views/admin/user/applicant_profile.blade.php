@extends('admin.layouts.admin_layout')
@push('css')
@endpush
@section('content')



<?php $true = FALSE; 

$profileCv = $user->getDefaultCv();
 ?>



<?php 

if(Auth::guard('company')->user()){

$package = Auth::guard('company')->user();

if(null!==($package)){

    $array_ids = explode(',',$package->availed_cvs_ids);

    if(in_array($user->id, $array_ids)){

        $true = TRUE;

    }

}

}

$profile_skills = $user->profileSkills;
$skills_arr = array();
if(null!==($profile_skills)){
    foreach ($profile_skills as $key => $value) {
        $skills_arr[] = $value->job_skill_id;
    }
}

?>
<!-- Inner Page Title end -->

<div class="page-content-wrapper"> 

    <!-- BEGIN CONTENT BODY -->

    <div class="page-content"> 

        <!-- BEGIN PAGE HEADER--> 

        <!-- BEGIN PAGE BAR -->

        <div class="page-bar">

            <ul class="page-breadcrumb">

                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>

                <li> <span>Profile</span> </li>

            </ul>

        </div>

        <!-- Job Detail start -->

        <div class="row mt-3">

            <div class="col-md-8"> 

                

                <!-- Job Header start -->

        <div class="job-header">

            <div class="jobinfo">
                        <!-- Candidate Info -->

                        <div class="candidateinfo">
							
							<div class="row">
								<div class="col-md-3"><div class="userPic">{{$user->printUserImage()}}</div></div>
								<div class="col-md-9">
								
								<div class="title">{{$user->getName()}}</div>
                                <div class="desi">{{$user->address}}</div>
                                <div class="loctext"><i class="fa fa-history" aria-hidden="true"></i> {{__('Member Since')}}, {{$user->created_at->format('M d, Y')}}</div>

                                <div class="jobButtons">
                                    @if(isset($job) && isset($company))
                                    @if(Auth::guard('company')->check() && Auth::guard('company')->user()->isFavouriteApplicant($user->id, $job->id, $company->id))
                                    @else
                                    @endif
                                    @endif
                                    @if(Auth::guard('company')->user())
                                    @if(null !== $profileCv)@endif @endif 
                                    
                                    @if(Auth::guard('company')->user() || Auth::guard('admin')->user())
                                    @if(count($user->profileCvs)<=0)
                                    <a class="btn btn-default disabled">{{__('No CV Available')}}</a>
                                    @else
                                    <a href="{{asset('cvs/'.$profileCv->cv_file)}}" target="_blank" class="btn btn-default"> {{__('View Candidates CV')}}</a>
                                    @endif
                                
                                    @endif 

                                    @if(null !== $profileCv && !empty($profileCv->cv_file) && file_exists( public_path() . 'cvs/' .$profileCv->cv_file))<a target="_blank" href="{{asset('cvs/'.$profileCv->cv_file)}}" class="btn btn-default"><i class="fa fa-download" aria-hidden="true"></i> {{__('Download CV')}}</a>@endif                                
                                </div>




								</div>
							</div>
                        </div>
                

            </div>

            <!-- Buttons -->

            

        </div>

                

                

                

                

                <!-- About Employee start -->

                <div class="job-header">

                    <div class="contentbox">

                        <h3>About Me (Summary)</h3>

                        <p>{{$user->getProfileSummary('summary')}}</p>

                    </div>

                </div>


                <!-- Education start -->
                <div class="job-header">
                    <div class="contentbox">
                        <h3>{{__('Education')}}</h3>
                        <div class="" id="education_div"></div>            
                    </div>
                </div>


                <!-- Portfolio start -->
                <div class="job-header">
                    <div class="contentbox">
                        <h3>{{__('Portfolio')}}</h3>
                        <div class="" id="projects_div"></div>            
                    </div>
                </div>



               




                <?php 
                	$job_ids = App\JobApply::where('user_id',$user->id)->orderBy('id', 'DESC')->get();


                 ?>
                

                <h3 style="    font-size: 24px;
    font-weight: 700;
    color: #009eff;
    margin-bottom: 10px;
    margin-top: 0px;">{{__('Applied On Jobs')}}</h3>

                 <table class="table table-striped table-bordered table-hover" id="user_datatable_ajax">
                <thead>
                <tr role="row" class="heading">
                <th>Name</th>
                <th>Job Title</th>
                <th>Company Name</th>
                <th>Applied Date</th>
                </tr>
                </thead>
                <tbody>
                    @if(null!==($job_ids))
                    @foreach($job_ids as $jo)
                    <?php 
                        $jobb = App\Job::where('id',$jo->job_id)->first();
                        $userr = App\User::findorFail($jo->user_id);
                        if(null!==($jobb)){
                        	$companyy = $jobb->getCompany();
                        }
                        
                     ?>
                     @if(isset($companyy) && null!==$companyy && null!==($jobb))
                    <tr id="user_dt_row_{{$jo->id}}" role="row" class="odd">
                    <td>{{$userr->name}}</td>

                    <td><a target="_blank" href="{{route('public.job', ['id' => $jo->job_id])}}">{{$jobb->title}}</a></td>
                    <td>{{$companyy->name}}</td>
                    <td>{{date('d/m/Y', strtotime($jo->created_at))}}</td>
                    
</tr>
@endif
@endforeach
@endif
                </tbody>
</table>

    

                


            </div>

            <div class="col-md-4"> 

                

                 <!-- Candidate Contact -->

                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('Candidate Contact Details')}}</h3>

                        <div class="candidateinfo">            

                            @if(!empty($user->phone))

                            <div class="loctext"><i class="fa fa-phone" aria-hidden="true"></i> <a href="tel:{{$user->phone}}">{{$user->phone}}</a></div>

                            @endif

                            @if(!empty($user->mobile_num))

                            <div class="loctext"><i class="fa fa-phone" aria-hidden="true"></i> <a href="tel:{{$user->mobile_num}}">{{$user->mobile_num}}</a></div>

                            @endif

                            @if(!empty($user->email))

                            <div class="loctext"><i class="fa fa-envelope" aria-hidden="true"></i> <a href="mailto:{{$user->email}}">{{$user->email}}</a></div>

                            @endif

                            <div class="loctext"><i class="fa fa-map-marker" aria-hidden="true"></i> {{$user->getlocation()}}</div>

                        </div>  

                    </div>

                </div>

                

                

                <!-- Candidate Detail start -->

                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('Candidate Details')}}</h3>

                        <ul class="jbdetail">
                         

                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Available Immediately')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{((bool)$user->is_immediate_available)? 'Yes':'No'}}</span></div>
                            </li>
							@If(!(bool)$user->is_immediate_available)
							<li class="row">
                                <div class="col-md-6 col-xs-6">Available from</div>
                                <div class="col-md-6 col-xs-6"><span>({{null!==($user->immediate_date_from)?date('d/m/Y',strtotime($user->immediate_date_from)):date('d/m/Y')}})</span></div>
                            </li>
                            @endif

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Eligible to work in the UK:')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{((bool)$user->eligible_uk)? 'Yes':'No'}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Eligible to work in the EU:')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{((bool)$user->eligible_eu)? 'Yes':'No'}}</span></div>

                            </li>
							
							


                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Age')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->getAge()}} Years</span></div>

                            </li>
                            @if(null!==($user->getGender('gender')))
                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Gender')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->getGender('gender')}}</span></div>

                            </li>
                            @endif
							
							@if(null!==($user->getMaritalStatus('marital_status')))

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Marital Status')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->getMaritalStatus('marital_status')}}</span></div>

                            </li>
                            @endif
						
							
                             @if(null!==($user->education_id))
                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Education')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->education->degree_level}}</span></div>

                            </li>

                            @endif

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Experience')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->getJobExperience('job_experience')}}</span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Career Level')}}</div>

                                <div class="col-md-6 col-xs-6"><span>{{$user->getCareerLevel('career_level')}}</span></div>

                            </li>             

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Current Salary')}}</div>

                                <div class="col-md-6 col-xs-6"><span class="permanent">{{$user->salary_currency}}{{$user->current_salary}} </span></div>

                            </li>

                            <li class="row">

                                <div class="col-md-6 col-xs-6">{{__('Expected Salary')}}</div>

                                <div class="col-md-6 col-xs-6"><span class="freelance">{{$user->salary_currency}}{{$user->expected_salary}} </span></div>

                            </li>              

                        </ul>

                    </div>

                </div>



                <!-- Google Map start -->
                @if(count($user->profileCvs)<=0)
                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('CV Added')}} - <strong style="color: #E70509">No</strong></h3>          

                    </div>

                </div>
                @else
                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('CV Added')}} - <strong style="color: #2DC507">Yes</strong></h3>          

                    </div>

                </div>
                @endif
			
                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('Skills')}}</h3>

                        <div id="skill_div"></div>            
						<div class="clearfix"></div>
                    </div>

                </div>



                <div class="job-header">

                    <div class="jobdetail">

                        <h3>{{__('Languages')}}</h3>

                        <div id="language_div"></div>            

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

     function send_messagee(id) {
        $('.seeker_id').val(id);
        $('#sendmessagee').modal('show');
    }
    
    $(".datepicker").datetimepicker();
  

 function myfunction(id){
    var ab = $('#all_ids').val();
    if($("#input_chcked_"+id).is(":checked") == true){
        var toString = ab+','+id;
        toString = toString.replace(/,+$/,'');
        $('#all_ids').val(toString);
    }else{
        var ary = ab.split(',');
        for(var i = 0 ; i < ary.length ; i++) {
          if(ary[i] == id) {
             ary.splice(i, id);
             ary.join(",");
          }
       }
        $('#all_ids').val(ary);
    }
    
 } 



  
$('#skills').select2({
            placeholder: "{{__('Select Skills')}}",
            allowClear: true
});

$('#company_id').select2({
            placeholder: "{{__('Select Company')}}",
            allowClear: true
});




    $(document).ready(function () {

    $(document).on('click', '#send_applicant_message', function () {

    var postData = $('#send-applicant-message-form').serialize();

    $.ajax({

    type: 'POST',

            url: "{{ route('contact.applicant.message.send') }}",

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

            $('#send-applicant-message-form').hide('slow');

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

    showEducation();

    showProjects();

    showExperience();

    showSkills();

    showLanguages();

    });

    function showProjects()

    {

    $.post("{{ route('show.applicant.profile.projects', $user->id) }}", {user_id: {{$user->id}}, _method: 'POST', _token: '{{ csrf_token() }}'})

            .done(function (response) {

            $('#projects_div').html(response);

            });

    }

    function showExperience()

    {

    $.post("{{ route('show.applicant.profile.experience', $user->id) }}", {user_id: {{$user->id}}, _method: 'POST', _token: '{{ csrf_token() }}'})

            .done(function (response) {

            $('#experience_div').html(response);

            });

    }

    function showEducation()

    {

    $.post("{{ route('show.applicant.profile.education', $user->id) }}", {user_id: {{$user->id}}, _method: 'POST', _token: '{{ csrf_token() }}'})

            .done(function (response) {

            $('#education_div').html(response);

            });

    }

    function showLanguages()

    {

    $.post("{{ route('show.applicant.profile.languages', $user->id) }}", {user_id: {{$user->id}}, _method: 'POST', _token: '{{ csrf_token() }}'})

            .done(function (response) {

            $('#language_div').html(response);

            });

    }

    function showSkills()

    {

    $.post("{{ route('show.applicant.profile.skills', $user->id) }}", {user_id: {{$user->id}}, _method: 'POST', _token: '{{ csrf_token() }}'})

            .done(function (response) {

            $('#skill_div').html(response);

            });

    }

  

    $(document).ready(function(){

        $('#skills').on('change', function (e) {
            e.preventDefault();
            filterCompanies(0);
        });
    });
    
    $('table').DataTable({"orderable": false});
    
</script> 
<style type="text/css">

    
.select2-selection--multiple:before {
    content: "";
    position: absolute;
    right: 7px;
    top: 42%;
    border-top: 5px solid #888;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
}
</style>
@endpush