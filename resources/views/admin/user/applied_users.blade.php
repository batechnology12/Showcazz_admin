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

                <li> <span>Jobseekers</span> </li>

            </ul>

        </div>

        <!-- END PAGE BAR --> 

        <!-- BEGIN PAGE TITLE-->

        <h3 class="page-title">Todays Job Applicants</h3>

        <!-- END PAGE TITLE--> 

        <!-- END PAGE HEADER-->

        <div class="row">

            <div class="col-md-12"> 




                <!-- Begin: life time stats -->

                <div class="portlet light portlet-fit portlet-datatable bordered">

                    <div class="portlet-title">

                        <div class="caption"> <i class="icon-settings font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Today Applicants</span> </div>

                    </div>

                    <div class="portlet-body">

                        <div class="table-container">

                            <form method="post" role="form" id="user-search-form">

                                <table class="table table-striped table-bordered table-hover"  id="user_datatable_ajax">

                                    <thead>

                                        <tr role="row" class="filter">                  

                                            <td>
                                                <input type="text" class="form-control" name="name" id="name" placeholder="Search By Name" autocomplete="off">
                                            </td>                    

                                            <td>
                                                <input type="text" class="form-control" name="job_title" placeholder="Search By Job Title" id="job_title" autocomplete="off">
                                            </td>

                                            <td>
                                                <input type="text" class="form-control" name="company" placeholder="Search By Company" id="company" autocomplete="off">
                                            </td>

                                            <td>
                                                <input type="date" class="form-control" name="date" id="date" autocomplete="off" placeholder="Applied Date">

                                                
                                            </td>
                                            <td>
                                                
                                            </td>

                                        </tr>

                                        <tr role="row" class="heading"> 

                                            <th>Applicant Name</th>                                        

                                            <th>Job Title</th>

                                            <th>Company</th>  

                                            <th>Applied Date</th>

                                                                                  

                                            <th>Actions</th>
                                            

                                        </tr>

                                    </thead>

                                    <tbody>

                                    </tbody>

                                </table></form>

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

<script>

    $(function () {

        var oTable = $('#user_datatable_ajax').DataTable({

            processing: true,

            serverSide: true,

            stateSave: true,

            searching: false,

            "language": {                
                "infoFiltered": ""
            },

            "order": [[0, "desc"]],
            "ordering": false,
            /*		

             paging: true,

             info: true,

             */

            ajax: {

                url: '{!! route('fetch.data.applicants') !!}',

                data: function (d) {

                    d.name = $('input[name=name]').val();

                    d.job_title = $('input[name=job_title]').val();

                    d.company = $('input[name=company]').val();

                     d.date = $('#date').val();

                     

                }

            }, columns: [

                /*{data: 'id_checkbox', name: 'id_checkbox', orderable: false, searchable: false},*/

                {data: 'name', name: 'name'},

                {data: 'job_title', name: 'job_title'},

                {data: 'company', name: 'company'},



                 {data: 'created_at', name: 'created_at'},

                {data: 'action', name: 'action', orderable: false, searchable: false},

            ]

        });

        $('#user-search-form').on('submit', function (e) {

            oTable.draw();

            e.preventDefault();

        });

        $('#name').on('keyup', function (e) {

            oTable.draw();

            e.preventDefault();

        });

        $('#job_title').on('keyup', function (e) {

            oTable.draw();

            e.preventDefault();

        });

        $('#company').on('keyup', function (e) {

            oTable.draw();

            e.preventDefault();

        });

        

         $('#date').on('change', function (e) {

            oTable.draw();

            e.preventDefault();

        });

        

    });

    function delete_user(id,title) {

        if (confirm('Are you sure! you want to delete ('+title+')')) {

            $.post("{{ route('delete.user') }}", {id: id, _method: 'DELETE', _token: '{{ csrf_token() }}'})

                    .done(function (response) {

                        

                            var table = $('#user_datatable_ajax').DataTable();

                            table.row('user_dt_row_' + id).remove().draw(false);

                        

                    });

        }

    }
    @if(isset(request()->is_active))
    $('#is_active').val({{request()->is_active}});
    @endif


    @if(isset(request()->is_verified))
    $('#is_verified').val({{request()->is_verified}});
    @endif
    function make_active(id) {

        $.post("{{ route('make.active.user') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        $('#onclick_active_' + id).attr("onclick", "make_not_active(" + id + ")");

                        $('#onclick_active_' + id).html("<i class=\"fa fa-check-square-o\" aria-hidden=\"true\"></i>Make InActive");

                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }

    function make_not_active(id) {

        $.post("{{ route('make.not.active.user') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        $('#onclick_active_' + id).attr("onclick", "make_active(" + id + ")");

                        $('#onclick_active_' + id).html("<i class=\"fa fa-square-o\" aria-hidden=\"true\"></i>Make Active");

                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }

    function make_verified(id) {

        $.post("{{ route('make.verified.user') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        $('#onclick_verified_' + id).attr("onclick", "make_not_verified(" + id + ")");

                        $('#onclick_verified_' + id).html("<i class=\"fa fa-check-square-o\" aria-hidden=\"true\"></i>Verified");

                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }

    function make_not_verified(id) {

        $.post("{{ route('make.not.verified.user') }}", {id: id, _method: 'PUT', _token: '{{ csrf_token() }}'})

                .done(function (response) {

                    if (response == 'ok')

                    {

                        $('#onclick_verified_' + id).attr("onclick", "make_verified(" + id + ")");

                        $('#onclick_verified_' + id).html("<i class=\"fa fa-square-o\" aria-hidden=\"true\"></i>Not Verified");

                    } else

                    {

                        alert('Request Failed!');

                    }

                });

    }
 toggle = function(source) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source)
    checkboxes[i].checked = source.checked;
    }
    }
    $('.delete').on('click',function(){
        var checkedVals = $('.checkboxes:checkbox:checked').map(function() {
            return this.value;
        }).get();
        var ids = checkedVals.join(",");
        $.ajax({
          method: "GET",
          url: "{{route('delete.users')}}",
          data: { ids: ids}
        })
        .done(function( msg ) {
            
        });
    })

</script> 
<style type="text/css">
    table td:last-child {text-align: center;}
    .checkboxes{
        
    }
</style>
@endpush