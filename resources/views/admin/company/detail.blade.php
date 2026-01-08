@extends('admin.layouts.admin_layout')
@push('css')
@endpush
@section('content')
<style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}
#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}
#customers tr:nth-child(even){background-color: #f2f2f2;}
#customers tr:hover {background-color: #ddd;}
#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #04AA6D;
  color: white;
}
        .userccount {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .formpanel {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead th {
            background-color: #007bff;
            color: #fff;
            padding: 12px;
            text-align: left;
        }
        table tbody td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-primary {
            background-color: #28a745;
        }
        .btn-primary:hover {
            background-color: #218838;
        }
        .text-success {
            color: #28a745;
        }
        .text-danger {
            color: #dc3545;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 8px;
            font-size: 14px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .formrow {
            margin-bottom: 15px;
        }
        .formrow label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .btn i {
            margin-left: 5px;
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
                <li> <span>Company Details</span> </li>
            </ul>
        </div>
<div class="listpgWraper">
    <div class="container">
<br><br>
        @include('flash::message')
        <!-- Job Header start -->
        <div class="job-header">
            <div class="jobinfo">
                <div class="row">
                    <div class="col-md-8 col-sm-8">
                        <!-- Candidate Info -->
                        <div class="candidateinfo">
							<div class="row">
								<div class="col-md-2"><div class="userPic">{{$company->printCompanyImage()}}</div></div>
								<div class="col-md-10">
								<div class="title">{{$company->name}}</div>
                            <div class="desi">{{$company->getIndustry('industry')}}</div>
                            <div class="loctext"><i class="fa fa-history" aria-hidden="true"></i>
                                {{__('Member Since')}}, {{$company->created_at->format('M d, Y')}}</div>
                            <div class="loctext"><i class="fa fa-map-marker" aria-hidden="true"></i>
                                {{$company->location}}</div>
								</div>
							</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <!-- Candidate Contact -->
                        <div class="candidateinfo">
                            @if(!empty($company->phone))
                            <div class="loctext"><i class="fa fa-phone" aria-hidden="true"></i> <a href="tel:{{$company->phone}}">{{$company->phone}}</a></div>
                            @endif
                            @if(!empty($company->email))
                            <div class="loctext"><i class="fa fa-envelope" aria-hidden="true"></i> <a href="mailto:{{$company->email}}">{{$company->email}}</a></div>
                            @endif
                            @if(!empty($company->website) && filter_var($company->website, FILTER_VALIDATE_URL) !== FALSE)
                            <div class="loctext"><i class="fa fa-globe" aria-hidden="true"></i> <a href="{{$company->website}}" target="_blank">{{$company->website}}</a></div>
                            @else
                            URL not present in profile
                            @endif
                            <div class="cadsocial"> {!!$company->getSocialNetworkHtml()!!} </div>
                        </div>                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Job Detail start -->
        <div class="row">
            <div class="col-md-8">
                <!-- About Employee start -->
                <div class="job-header">
                    <div class="contentbox">
                        <h3>{{__('About Company')}}</h3>
                        <p>{!! $company->description !!}</p>
                    </div>
                </div>
                <!-- Opening Jobs start -->
                <div class="relatedJobs">
                    <h3>{{__('Job Openings')}}</h3>
                    <ul class="searchList">
                        <?php $jobs = $company->jobs()->notExpire()->where('jobs.is_active', 1)->get(); ?>
                        @if(isset($jobs) && count($jobs))
                        @foreach($jobs as $companyJob)
                        <!--Job start-->
                        <li>
                            <div class="row">
                                <div class="col-md-8 col-sm-8">                                   
                                    <div class="jobinfo">
                                        <h3><a href="{{ route('public.job', ['id' => $companyJob->id]) }}"
                                                title="{{$companyJob->title}}">{{$companyJob->title}}</a></h3>
                                        <div class="location">
                                            <label class="fulltime"
                                                title="{{$companyJob->getJobType('job_type')}}">{{$companyJob->getJobType('job_type')}}</label>
                                            <label class="partTime"
                                                title="{{$companyJob->getJobShift('job_shift')}}">{{$companyJob->getJobShift('job_shift')}}</label>
                                            - <span>{{$companyJob->getCity('city')}}</span></div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="col-md-4 col-sm-4">
                                    <div class="listbtn"><a
                                            href="{{ route('public.job', ['id' => $companyJob->id]) }}">{{__('View Job Details')}}</a>
                                    </div>
                                </div>
                            </div>
                            <p>{{\Illuminate\Support\Str::limit(strip_tags($companyJob->description), 150, '...')}}</p>
                        </li>
                        <!--Job end-->
                        @endforeach
                        @endif
                        <!-- Job end -->
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <!-- Company Detail start -->
                <div class="job-header">
                    <div class="jobdetail">
                        <h3>{{__('Company Details')}}</h3>
                        <ul class="jbdetail">
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Email Verified')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{((bool)$company->verified)? 'Yes':'No'}}</span>
                                </div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Total Employees')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{$company->no_of_employees}}</span></div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Established In')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{$company->established_in}}</span></div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Current jobs')}}</div>
                                <div class="col-md-6 col-xs-6">
                                    <span>{{$company->countNumJobs('company_id',$company->id)}}</span></div>
                            </li>
                        </ul>

<hr>
                        <h3 class="mt-3">{{__('Company Person')}}</h3>
                        <ul class="jbdetail">
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Name')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{$company->contact_name}}</span>
                                </div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Email')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{$company->contact_email}}</span></div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Designation')}}</div>
                                <div class="col-md-6 col-xs-6"><span>{{$company->ceo}}</span></div>
                            </li>
                            <li class="row">
                                <div class="col-md-6 col-xs-6">{{__('Company Reg. Number')}}</div>
                                <div class="col-md-6 col-xs-6">
                                    <span>{{$company->registration_number}}</span></div>
                            </li>
                        </ul>




                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<!-- Status Update Modal -->
<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Update Document Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    <div class="form-group">
                        <label for="documentTitle">Document Title</label>
                        <input type="text" class="form-control" id="documentTitle" readonly>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label><br>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="approve" value="1">
                            <label class="form-check-label" for="approve">Approve</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="reject" value="0">
                            <label class="form-check-label" for="reject">Reject</label>
                        </div>
                    </div>
                    <div class="form-group" id="commentsGroup" style="display:none;">
                        <label for="comments">Comments</label>
                        <textarea class="form-control" id="comments" rows="3"></textarea>
                    </div>
                    <input type="hidden" id="documentField" name="field">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">Save changes</button>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
      $(document).ready(function() {
    $('.status-icon').click(function() {
        var $icon = $(this);
        var field = $icon.data('field');
        var documentTitle = $icon.closest('tr').find('label').text();
        var isVerified = $icon.find('i').hasClass('text-success');
        // Set modal values
        $('#documentTitle').val(documentTitle);
        $('#documentField').val(field);
        $('input[name="status"]').prop('checked', false);
        $('#commentsGroup').css('display','none'); // Hide comments textarea by default
        // Show modal
        $('#statusModal').modal('show');
    });
    $('input[name="status"]').change(function() {
        if ($(this).val() == '0') { // If "Reject" is selected
            $('#commentsGroup').show();
        } else {
            $('#commentsGroup').hide();
        }
    });
    $('#saveStatusBtn').click(function() {
        var field = $('#documentField').val();
        var status = $('input[name="status"]:checked').val();
        var comments = $('#comments').val();
        $.ajax({
            url: "{{route('edit.changeStatus',$company->id)}}", // Replace with your route
            type: 'POST',
            data: {
                field: field,
                status: status,
                comments: comments,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                   var $icon = $('.status-icon[data-field="' + field + '"]');
                    var $i = $icon.find('i');
                    if (status == 1) { // Approved
                        $i.removeClass('fa-times-circle text-danger').addClass('fa-check-circle text-success');
                        $icon.attr('title', 'Verified');
                        $icon.contents().filter(function() { return this.nodeType === 3; }).remove(); // Remove existing text node
                        $icon.append(' Approved');
                    } else { // Rejected
                        $i.removeClass('fa-check-circle text-success').addClass('fa-times-circle text-danger');
                        $icon.attr('title', 'Not Verified');
                        $icon.contents().filter(function() { return this.nodeType === 3; }).remove(); // Remove existing text node
                        $icon.append(' Rejected');
                    }
                    Swal.fire(
                        'Changed!',
                        'The status has been updated.',
                        'success'
                    );
                } else {
                    Swal.fire(
                        'Error!',
                        'There was an error updating the status.',
                        'error'
                    );
                }
                $('#statusModal').modal('hide');
            },
            error: function() {
                Swal.fire(
                    'Error!',
                    'There was an error updating the status.',
                    'error'
                );
                $('#statusModal').modal('hide');
            }
        });
    });
});
    </script>
<script type="text/javascript"></script> 
@endpush