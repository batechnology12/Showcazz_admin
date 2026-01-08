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

                <li> <span>Users</span> </li>

            </ul>

        </div>
                 <?php
            if(Request::segment(3)){
                $job = App\Job::findorFail(Request::segment(3));
            }
            //$company_link = '<a target="_blank" href="'.route('public.company',$job->getCompany('id')).'">'.$job->getCompany('name').'</a>';
?>
        <div class="row">

            <div class="col-md-12 col-sm-12"> 
                <div class="myads">
                    <h3>{{__('Candidates listed against')}} ({{$job->title}})</h3>
                    <ul class="searchList">
                        <!-- job start --> 
                        @if(isset($job_applications) && count($job_applications))
                        @foreach($job_applications as $job_application)
                        @php
                        $user = $job_application->getUser();
                        $job = $job_application->getJob();
                        $company = $job->getCompany();             
                        $profileCv = $job_application->getProfileCv();
                        @endphp
                        @if(null !== $job_application && null !== $user && null !== $job && null !== $company && null !== $profileCv)
                        <li>
                            <div class="row">
                                <div class="col-md-5 col-sm-5">
                                    <div class="jobimg">{{$user->printUserImage(100, 100)}}</div>
                                    <div class="jobinfo">
                                        <h3><a href="{{route('admin.view.public.profile', $user->id)}}">{{$user->getName()}}</a></h3>
                                        <div class="location"> {{$user->getLocation()}}</div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="col-md-4 col-sm-4">
                                    <div class="minsalary">{{$job_application->expected_salary}} {{$job_application->salary_currency}} <span>/ {{$job->getSalaryPeriod('salary_period')}}</span></div>
                                </div>
                                <div class="col-md-3 col-sm-3">
                                    <div class="listbtn"><a href="{{route('admin.view.public.profile', [$user->id,'company_id='.$company_id,'job_id='.$job_id])}}">{{__('View Profile')}}</a></div>
                                </div>
                            </div>
                            <p>{{\Illuminate\Support\Str::limit($user->getProfileSummary('summary'),150,'...')}}</p>
                        </li>
                        <!-- job end --> 
                        @endif
                        @endforeach
                        @else
                        <div class="alert alert-warning">No Candidates applied yet</div>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script type="text/javascript">
    
    @if(session()->has('message.added'))
     swal({

              title: "Success",

              text: "{!! session('message.content') !!}",

              icon: "success",

              button: "OK",

          });
    @endif
</script>
@endpush