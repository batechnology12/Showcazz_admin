@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end -->
<!-- Inner Page Title start -->
@include('includes.inner_page_title', ['page_title'=>__('My Job Alerts')])
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row"> @include('includes.user_dashboard_menu')
            <div class="col-lg-9 col-sm-8">
                <div class="userdashbox">
                    <div class="d-flex mb-3">
                    <h3>{{__('Manage Jobs Alerts')}}</h3>
                    <button type="button" class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#customAlertModal">
                        Create Alert
                    </button>
                    </div>
                   
						<table class="table alrtstable">
						  <tbody>
							<tr>
							  <th scope="col">Alert Title</th>	
								@if(isset($id) && $id!='')
							  <th scope="col">Location</th>
								@endif
								<th scope="col">Created On</th>
							  <th scope="col">Action</th>
							</tr>							
							 <!-- job start -->
                        @if(isset($alerts) && count($alerts))
                        @foreach($alerts as $alert)
                        <tr id="delete_{{$alert->id}}">
                            @php
                            if(null!==($alert->search_title)){
                            $title = $alert->search_title;
                            }

                            @endphp
                            @php
                            if(null!==($alert->country_id)){
                            $id = $alert->country_id;
                            }

                            if(isset($title) && $title!='' && isset($id) && $id!=''){
                            $cols = 'col-lg-4';
                            }else{
                            $cols = 'col-lg-8';
                            }
                            @endphp
							
							@if(isset($title) && $title!='')
							<td>{{$title}}</td>
							@endif
                                @if(isset($id) && $id!='')
							  <td> {{$id}}</td>
							@endif
							  <td> {{$alert->created_at->format('M d, Y - H:i:s')}}</td>
							  <td> <a href="javascript:;" onclick="delete_alert({{$alert->id}})" class="delete_alert"><i class="fas fa-trash"></i> Delete</a></td>							
                        </tr>
                        <!-- job end -->
                        @endforeach
                        @else
                        <tr>
                            <td colspan="3">
                            <div class="nodatabox">
                                <h4>{{__('No Job Alerts Found')}}</h4>
                                <div class="viewallbtn mt-2"><a href="{{url('/search-jobs')}}">{{__('Search Jobs')}}</a></div>
                            </div>
                            </td>
                        </tr>
                        @endif 
							  
							  
						  </tbody>
						</table>
                </div>
            </div>
        </div>
    </div>


    <!-- Custom Job Alert Modal -->
<div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customAlertModalLabel">Add Custom Job Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="custom_alert_form">
                    @csrf
                    <div class="mb-3">
                        <label for="custom_alert" class="form-label">Alert Title</label>
                        <input type="text" class="form-control" name="custom_alert" id="custom_alert" placeholder="Enter Custom Alert Title" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" 
                            value="{{ Auth::check() ? Auth::user()->email : '' }}" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Alert</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




    @include('includes.footer')
    @endsection
    @push('scripts')
    <script>
        function delete_alert(id) {

            $.ajax({
                type: 'GET',
                url: "{{url('/')}}/delete-alert/" + id,
                success: function(response) {
                    if (response["status"] == true) {
                        $('#delete_' + id).hide();
                        swal({
                            title: "Success",
                            text: response["msg"],
                            icon: "success",
                            button: "OK",
                        });

                    } else {
                        swal({
                            title: "Already exist",
                            text: response["msg"],
                            icon: "error",
                            button: "OK",
                        });
                    }

                }
            });
        }
    </script>

<script>
    $(document).on('submit', '#custom_alert_form', function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('custom.alert') }}", // Your new route for custom alerts
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.status) {
                    swal({
                        title: "Success!",
                        text: response.msg,
                        icon: "success",
                        button: "OK",
                    }).then(() => {
                        $('#customAlertModal').modal('hide'); // Close modal
                        location.reload(); // Reload page to show new alert
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: response.msg,
                        icon: "error",
                        button: "OK",
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = "Something went wrong. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message; // Extract Laravel validation message
                }
                swal({
                    title: "Error!",
                    text: errorMsg,
                    icon: "error",
                    button: "OK",
                });
            }
        });
    });
</script>




    @include('includes.immediate_available_btn')
    @endpush