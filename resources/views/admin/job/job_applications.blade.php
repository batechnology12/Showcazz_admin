@extends('admin.layouts.admin_layout')

@section('content')

<style type="text/css">
    .table td, .table th {
        font-size: 13px;
        line-height: 2.42857 !important;
    }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>
                <li> <span>Applied Users</span> </li>
            </ul>
        </div>
        
        <?php
            if(Request::segment(3)){
                $job = App\Job::findOrFail(Request::segment(3));
            }
        ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="myads">
                    <h3>{{__('Candidates listed against')}} ({{$job->title}})</h3>
                    <button id="downloadCsv" class="btn btn-success">Download CSV</button>
                    <br><br>
                    <table id="appliedUsersTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Candidate Name</th>
                                <th>Location</th>
                                <th>Expected Salary</th>
                                <th>Experience</th>
                                <th>Career Level</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($job_applications) && count($job_applications))
                                @foreach($job_applications as $job_application)
                                    @php
                                        $user = $job_application->getUser();
                                        $job = $job_application->getJob();
                                        $company = $job->getCompany();             
                                        $profileCv = $job_application->getProfileCv();
                                    @endphp
                                    @if($user && $job && $company && $profileCv)
                                        <tr>
                                            <td><a href="{{ route('admin.view.public.profile', $user->id) }}">{{$user->getName()}}</a></td>
                                            <td>{{$user->getLocation()}}</td>
                                            <td>{{$job_application->expected_salary}} {{$job_application->salary_currency}}</td>
                                            <td>{{$user->getJobExperience('job_experience')}}</td>
                                            <td>{{$user->getCareerLevel('career_level')}}</td>
                                            <td>{{$user->phone}}</td>
                                            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.view.public.profile', [$user->id, 'company_id='.$company_id, 'job_id='.$job_id]) }}">View Profile</a></td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center">No Candidates applied yet</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#appliedUsersTable').DataTable();

        $('#downloadCsv').click(function () {
            let csvContent = "data:text/csv;charset=utf-8," + "Name,Location,Expected Salary,Experience,Career Level,Phone\n";
            
            @if(isset($job_applications) && count($job_applications))
                @foreach($job_applications as $job_application)
                    @php
                        $user = $job_application->getUser();
                        $job = $job_application->getJob();
                    @endphp
                    @if($user && $job)
                        csvContent += `"{{$user->getName()}}","{{$user->getLocation()}}","{{$job_application->expected_salary}} {{$job_application->salary_currency}}","{{$user->getJobExperience('job_experience')}}","{{$user->getCareerLevel('career_level')}}","{{$user->phone}}"\n`;
                    @endif
                @endforeach
            @endif

            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "applied_users_{{$job->title}}.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
@endsection
