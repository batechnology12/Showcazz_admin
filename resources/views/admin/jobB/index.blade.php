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
                <li> <span>Import Jobs</span> </li>
            </ul>
        </div>
        <!-- END PAGE BAR --> 
        <!-- BEGIN PAGE TITLE-->
        <h3 class="page-title">Import Jobs  </h3>
        <!-- END PAGE TITLE--> 
        <div class="alert alert-warning"><strong>Note:</strong> After importing the .CSV file, you must move the jobs to Active/Live status. Jobs in this listing will not appear on the frontend until they are activated.</div>
        <!-- END PAGE HEADER-->
        <div class="row">
            <div class="col-md-12"> 
                @include('flash::message')
                <!-- Begin: life time stats -->
                <div class="portlet light portlet-fit portlet-datatable bordered">
                    <div class="portlet-title">
                        <div class="caption"> <i class="icon-settings font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Jobs</span> </div>
                        <div class="actions"> 
                            <a href="javascript:;" onclick="javascript:$('#myModal3').modal('show')" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-open"></i> Import Jobs</a>&nbsp
                            <a href="{{asset('/sample.csv')}}" download="download" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-plus"></i> Sample CSV</a>  
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-container">
                            <form method="post" role="form" id="job-search-form">
                            <div class="pull-right" style="margin-bottom: -35px; position: relative; z-index: 2">
								</div>
                                <table class="table table-striped table-bordered table-hover"  id="jobDatatableAjax">
                                    <thead>
                                        <tr role="row" class="filter">
                                        <td style="text-align: center;">
                                        <button class="btn btn-primary move-to-active">Move to Active Jobs</button>
                                        <br>
                                        <input type="checkbox" id="selectall" name="selectall" onclick="toggle(this)" autocomplete="off">     
                                                </td>
                                            <td>{!! Form::select('company_id', ['' => 'Select Company']+$companies, null, array('id'=>'company_id', 'class'=>'form-control')) !!}</td>
                                            <td><input type="text" class="form-control" name="title" id="title" autocomplete="off" placeholder="Job title"></td>
                                            <td><input type="text" class="form-control" name="description" id="description" autocomplete="off" placeholder="Job description"></td>
                                            <td>
                                                <?php $default_country_id = Request::query('country_id', $siteSetting->default_country_id); ?>
                                                {!! Form::select('country_id', ['' => 'Select Country']+$countries, $default_country_id, array('id'=>'country_id', 'class'=>'form-control')) !!}
                                                <span id="default_state_dd">
                                                    {!! Form::select('state_id', ['' => 'Select State'], null, array('id'=>'state_id', 'class'=>'form-control')) !!}
                                                </span>
                                                <span id="default_city_dd">
                                                    {!! Form::select('city_id', ['' => 'Select City'], null, array('id'=>'city_id', 'class'=>'form-control')) !!}
                                                </span></td>
                                            <td><select name="is_active" id="is_active" class="form-control">
                                                    <option value="-1">Is Active?</option>
                                                    <option value="1" selected="selected">Active</option>
                                                    <option value="0">In Active</option>
                                                </select>
                                                <select name="is_featured" id="is_featured" class="form-control">
                                                    <option value="-1">Is Featured?</option>
                                                    <option value="1">Featured</option>
                                                    <option value="0">Not Featured</option>
                                                </select></td>
                                        </tr>
                                        <tr role="row" class="heading">
                                        <th style="text-align: center;">
                                                </th>
                                            <th>Company</th>
                                            <th>Job title</th>
                                            <th>Job description</th>
                                            <th>City</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END CONTENT BODY --> 
</div>
<div class="modal fade" id="myModal3" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel">Import Jobs</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    <div class="alert alert-warning">Do not import your own .CSV file. Instead, download the provided sample file, add your data to it, and then proceed with the import.</div>
                        <!-- Your form goes here -->
                        <form method="POST" action="{{route('import')}}" accept-charset="UTF-8" class="form" id="party-form" enctype="multipart/form-data">
                            @csrf
                            <div class="sbp-preview">
                                <div class="sbp-preview-content">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="title" class="">Select File</label>
                                                <input class="form-control" id="title" placeholder="Party" required="required" name="csv_file" type="file" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mt-2" style="    margin-top: 25px;">
                                             <label for="title" class="">&nbsp</label>
                                        <button type="submit" class="btn btn-primary">
                                            Import &nbsp;
                                            <!-- <i class="fa-solid fa-arrow-right"></i> Font Awesome fontawesome.com -->
                                        </button>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection
@push('scripts') 
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10.14.1/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.14.1/dist/sweetalert2.min.js"></script>
<script>
    $(function () {
        var oTable = $('#jobDatatableAjax').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            searching: false,
            /*		
             "order": [[1, "asc"]],            
             paging: true,
             info: true,
             */
            ajax: {
                url: '{!! route('fetch.data.jobsB') !!}',
                data: function (d) {
                    d.company_id = $('#company_id').val();
                    d.title = $('#title').val();
                    d.description = $('#description').val();
                    d.country_id = $('#country_id').val();
                    d.state_id = $('#state_id').val();
                    d.city_id = $('#city_id').val();
                    d.is_active = $('#is_active').val();
                    d.is_featured = $('#is_featured').val();
                }
            }, columns: [
                {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                {data: 'company_id', name: 'company_id'},
                {data: 'title', name: 'title'},
                {data: 'description', name: 'description'},
                {data: 'city_id', name: 'city_id'},                
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
        $('#job-search-form').on('submit', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#company_id').on('change', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#title').on('keyup', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#description').on('keyup', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#country_id').on('change', function (e) {
            oTable.draw();
            e.preventDefault();
            filterDefaultStates(0);
        });
        $(document).on('change', '#state_id', function (e) {
            oTable.draw();
            e.preventDefault();
            filterDefaultCities(0);
        });
        $(document).on('change', '#city_id', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#is_active').on('change', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        $('#is_featured').on('change', function (e) {
            oTable.draw();
            e.preventDefault();
        });
        filterDefaultStates(0);
    });
    toggle = function(source) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source)
    checkboxes[i].checked = source.checked;
    }
    }
    $('.move-to-active').on('click', function () {
        var checkedVals = $('.checkboxes:checkbox:checked').map(function () {
            return this.value;
        }).get();
        var ids = checkedVals.join(",");
        $.ajax({
            method: "GET",
            url: "{{route('move-to-active')}}",
            data: { ids: ids }
        })
        .done(function (msg) {
            Swal.fire({
                icon: 'success',
                title: 'Moved Successful!',
                text: 'All Selected jobs moved to active jobs.',
                confirmButtonText: 'OK'
            })
            .then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
            oTable.draw();
            e.preventDefault();
        });
    });
    function deleteJob(id, is_default) {
        var msg = 'Are you sure?';
        if (confirm(msg)) {
            $.post("{{ route('delete.jobB') }}", {id: id, _method: 'DELETE', _token: '{{ csrf_token() }}'})
                    .done(function (response) {
                        if (response == 'done')
                        {
                            var table = $('#jobDatatableAjax').DataTable();
                            table.row('jobDtRow' + id).remove().draw(false);
                        } else
                        {
                            alert('Request Failed!');
                        }
                    });
        }
    }
    function makeActive(id) {
        $.post("{{ route('make.active.jobB') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})
                .done(function (response) {
                    if (response == 'ok')
                    {
                        var table = $('#jobDatatableAjax').DataTable();
                        table.row('jobDtRow' + id).remove().draw(false);
                    } else
                    {
                        alert('Request Failed!');
                    }
                });
    }
    function makeNotActive(id) {
        $.post("{{ route('make.not.active.jobB') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})
                .done(function (response) {
                    if (response == 'ok')
                    {
                        var table = $('#jobDatatableAjax').DataTable();
                        table.row('jobDtRow' + id).remove().draw(false);
                    } else
                    {
                        alert('Request Failed!');
                    }
                });
    }
    function makeFeatured(id) {
        $.post("{{ route('make.featured.jobB') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})
                .done(function (response) {
                    if (response == 'ok')
                    {
                        var table = $('#jobDatatableAjax').DataTable();
                        table.row('jobDtRow' + id).remove().draw(false);
                    } else
                    {
                        alert('Request Failed!');
                    }
                });
    }
    function makeNotFeatured(id) {
        $.post("{{ route('make.not.featured.jobB') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})
                .done(function (response) {
                    if (response == 'ok')
                    {
                        var table = $('#jobDatatableAjax').DataTable();
                        table.row('jobDtRow' + id).remove().draw(false);
                    } else
                    {
                        alert('Request Failed!');
                    }
                });
    }
    function filterDefaultStates(state_id)
    {
        var country_id = $('#country_id').val();
        if (country_id != '') {
            $.post("{{ route('filter.default.states.dropdown') }}", {country_id: country_id, state_id: state_id, _method: 'POST', _token: '{{ csrf_token() }}'})
                    .done(function (response) {
                        $('#default_state_dd').html(response);
                    });
        }
    }
    function filterDefaultCities(city_id)
    {
        var state_id = $('#state_id').val();
        if (state_id != '') {
            $.post("{{ route('filter.default.cities.dropdown') }}", {state_id: state_id, city_id: city_id, _method: 'POST', _token: '{{ csrf_token() }}'})
                    .done(function (response) {
                        $('#default_city_dd').html(response);
                    });
        }
    }
</script>
@endpush