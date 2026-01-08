@extends('layouts.app')

@section('content')

<!-- Header start -->

@include('includes.header')

<!-- Header end -->



<div class="listpgWraper mt-5">

    <div class="container">

        @include('flash::message')

        <!-- Job Header start -->
         <div class="row">
            <div class="col-lg-7">
            
         

        <div class="job-header">
            <div class="jobinfo">
                        <!-- Candidate Info -->

                        <div class="candidateinfo">

                            <div class="userPic"><a href="{{route('company.detail',$company->slug)}}">{{$company->printCompanyImage()}}</a>

                            </div>

                            <div class="title">{{$company->name}}</div>

                            <div class="desi">{{$company->getIndustry('industry')}}</div>

                            <div class="loctext"><i class="fa fa-history" aria-hidden="true"></i>

                                {{__('Member Since')}}, {{$company->created_at->format('M d, Y')}}</div>

                            <div class="loctext"><i class="fa fa-map-marker" aria-hidden="true"></i>

                                {{$company->location}}</div>

                            <div class="clearfix"></div>

                        </div>


            </div>

            <!-- Buttons -->
            <div class="jobButtons"> 
            @if(Auth::guard('web')->check() && Auth::guard('web')->user()->isFavouriteCompany($company->slug))
    <a href="{{ route('remove.from.favourite.company', $company->slug) }}" class="btn">
        <i class="fa fa-floppy-o" aria-hidden="true"></i> {{ __('Remove from Favourite') }}
    </a>
@else
    <a href="{{ route('add.to.favourite.company', $company->slug) }}" class="btn">
        <i class="fa fa-floppy-o" aria-hidden="true"></i> {{ __('Add to Favourite') }}
    </a>
@endif

                <a href="{{ route('report.abuse.company', $company->slug) }}" class="btn report">
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {{ __('Report Abuse') }}
                </a> 
    
            </div>
        </div>

        <!-- About Employee start -->

        <div class="job-header">

            <div class="contentbox">

                <h3>{{__('About Company')}}</h3>

                <p>{!! $company->description !!}</p>

            </div>

        </div>


        </div>

        <div class="col-lg-5">
         <!-- Company Detail start -->

         <div class="job-header">

            <div class="jobdetail">
                <h3>{{__('Company Detail')}}</h3>
                    <ul class="jbdetail row">
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">verified</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Verified')}}</strong>
                                        <span>{{((bool)$company->verified)? 'Yes':'No'}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">group</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Company Size')}}</strong>
                                        <span>{{$company->no_of_employees}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">cake</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Founded In')}}</strong>
                                        <span>{{$company->established_in}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">corporate_fare</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Organization Type')}}</strong>
                                        <span>{{$company->getOwnershipType('ownership_type')}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">corporate_fare</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Total Offices')}}</strong>
                                        <span>{{$company->no_of_offices}}</span>
                                    </div>
                                    </div>
                                </li>
                                <li class="col-lg-4 col-md-6 col-6">
                                    <div class="jbitlist">
                                    <span class="material-symbols-outlined">cases</span>
                                    <div class="jbitdata">
                                        <strong>{{__('Opend Jobs')}}</strong>
                                        <span>{{$company->countNumJobs('company_id',$company->id)}}</span>
                                    </div>
                                    </div>
                                </li>


                            
                    </ul>
            </div>

         </div>


         <div class="job-header">

                    <div class="jobdetail">
                        <iframe src="https://maps.google.it/maps?q={{urlencode(strip_tags($company->map))}}&output=embed" allowfullscreen></iframe>
                    </div>

                </div>


        </div>

        </div>









  <!-- Opening Jobs start -->

  <div class="relatedJobs">

<h3>{{__('Current Openings')}}</h3>

<ul class="featuredlist row">

    @if(isset($company->jobs) && count($company->jobs))

    @foreach($company->jobs as $companyJob)

    <!--Job start-->
    <li class="col-lg-3 col-md-6">
                <div class="jobint">

                    <div class="d-flex">
                        <div class="fticon"><i class="fas fa-briefcase"></i> {{$companyJob->getJobType('job_type')}}</div>                        
                    </div>

                    <h4><a href="{{route('job.detail', [$companyJob->slug])}}" title="{{$companyJob->title}}">{!! \Illuminate\Support\Str::limit($companyJob->title, $limit = 20, $end = '...') !!}</a>
                    
                    
                </h4>
                @if(!(bool)$companyJob->hide_salary)                    
                    <div class="salary mb-2">Salary: <strong>{{$companyJob->salary_currency.''.$companyJob->salary_from}} - {{$companyJob->salary_currency.''.$companyJob->salary_to}}/{{$companyJob->getSalaryPeriod('salary_period')}}</strong></div>
                    @endif 


                    <strong><i class="fas fa-map-marker-alt"></i> {{$companyJob->getCity('city')}}</strong> 
                    
                    <div class="jobcompany">
                     <div class="ftjobcomp">
                        <span>{{$companyJob->created_at->format('M d, Y')}}</span>
                        <a href="{{route('company.detail', $company->slug)}}" title="{{$company->name}}">{{$company->name}}</a>
                     </div>
                    <a href="{{route('company.detail', $company->slug)}}" class="company-logo" title="{{$company->name}}">{{$company->printCompanyImage()}} </a>
                    </div>
                </div>
            </li>

    <!--Job end-->

    @endforeach

    @else
    <div class="nodatabox">
    <h4>{{__('There are currently no open positions available.')}}</h4>
    <div class="viewallbtn mt-2"><a href="{{url('/search-jobs')}}">{{__('Search Jobs')}}</a></div>
</div>

    @endif



    <!-- Job end -->

</ul>

</div>









    </div>

</div>

<!-- Modal -->

<div class="modal fade" id="sendmessage" role="dialog">

    <div class="modal-dialog">



        <!-- Modal content-->

        <div class="modal-content">

            <form action="" id="send-form">

                @csrf

                <input type="hidden" name="company_id" id="company_id" value="{{$company->id}}">

                <div class="modal-header">                    

                    <h4 class="modal-title">Send Message</h4>

					<button type="button" class="close" data-bs-dismiss="modal">&times;</button>

                </div>

                <div class="modal-body">

                    <div class="form-group">

                        <textarea class="form-control" name="message" id="message" cols="10" rows="7"></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>

                    <button type="submit" class="btn btn-primary">Submit</button>

                </div>

            </form>

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

<script type="text/javascript">

$(document).ready(function() {

    $(document).on('click', '#send_company_message', function() {

        var postData = $('#send-company-message-form').serialize();

        $.ajax({

            type: 'POST',

            url: "{{ route('contact.company.message.send') }}",

            data: postData,

            //dataType: 'json',

            success: function(data) {

                response = JSON.parse(data);

                var res = response.success;

                if (res == 'success') {

                    var errorString = '<div role="alert" class="alert alert-success popmessage">' +

                        response.message + '</div>';

                    $('#alert_messages').html(errorString);

                    $('#send-company-message-form').hide('slow');

                    $(document).scrollTo('.alert', 2000);

                } else {

                    var errorString = '<div class="alert alert-danger" role="alert"><ul>';

                    response = JSON.parse(data);

                    $.each(response, function(index, value) {

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



function send_message() {

    const el = document.createElement('div')

    el.innerHTML =

        "Please <a class='btn' href='{{route('login')}}' onclick='set_session()'>log in</a> as a Canidate and try again."

    @if(Auth::check())

    $('#sendmessage').modal('show');

    @else

    swal({

        title: "You are not Loged in",

        content: el,

        icon: "error",

        button: "OK",

    });

    @endif

}

if ($("#send-form").length > 0) {

    $("#send-form").validate({

        validateHiddenInputs: true,

        ignore: "",



        rules: {

            message: {

                required: true,

                maxlength: 5000

            },

        },

        messages: {



            message: {

                required: "Message is required",

            }



        },

        submitHandler: function(form) {

            $.ajaxSetup({

                headers: {

                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')

                }

            });

            @if(null !== (Auth::user()))

            $.ajax({

                url: "{{route('submit-message')}}",

                type: "POST",

                data: $('#send-form').serialize(),

                success: function(response) {

                    $("#send-form").trigger("reset");

                    $('#sendmessage').modal('hide');

                    swal({

                        title: "Success",

                        text: response["msg"],

                        icon: "success",

                        button: "OK",

                    });

                }

            });

            @endif

        }

    })

}

</script>

@endpush