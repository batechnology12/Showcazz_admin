@extends('admin.layouts.admin_layout')
@section('content')
<div class="page-content-wrapper"> 
    <div class="page-content"> 
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>
                <li> <span>Roles List</span> </li>
            </ul>
        </div>
        <h3 class="page-title"> Manage Roles <small>Admin Roles</small> </h3>
        <div class="row">
            <div class="col-md-12"> 
                <div class="portlet light portlet-fit portlet-datatable bordered">
                    <div class="portlet-title">
                        <div class="caption"> <i class="icon-settings font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Role(s)</span> </div>
                        <div class="actions">
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-plus"></i> Add New Role</a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-container">
                            <table class="table table-striped table-bordered table-hover" id="role_datatable">
                                <thead>
                                    <tr role="row" class="heading"> 
                                        <th>Name</th>
                                        <th>Abbreviation</th>
                                        <th>Permissions</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                    <tr id="role_row_{{ $role->id }}">
                                        <td><strong>{{ $role->role_name }}</strong></td>
                                        <td><span class="label label-sm label-info">{{ $role->role_abbreviation }}</span></td>
                                        <td>
                                            @foreach($role->permissions as $permission)
                                                <span class="label label-sm label-default" style="margin-bottom: 2px; display: inline-block;">
                                                    {{ $permission->label }}
                                                </span>
                                            @endforeach
                                            @if($role->permissions->isEmpty())
                                                <span class="text-muted small">No permissions assigned</span>
                                            @endif
                                        </td>
                                        <td>{{ $role->role_description }}</td>
                                        <td>
                                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-xs blue"><i class="fa fa-edit"></i> Edit</a>
                                            @if(strtolower($role->role_abbreviation) !== 'sup_adm')
                                            <a href="javascript:void(0)" onclick="delete_role({{ $role->id }})" class="btn btn-xs red"><i class="fa fa-trash"></i> Delete</a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts') 
<script>
    $(function () {
        $('#role_datatable').DataTable({
            "order": [[0, "asc"]],
            "stateSave": true
        });
    });
    function delete_role(id) {
        if (confirm('Are you sure you want to delete this role?')) {
            $.post("{{ url('admin/admin_roles') }}/" + id, {
                _method: 'DELETE', 
                _token: '{{ csrf_token() }}'
            }).done(function (response) {
                if (response == 'ok') {
                    location.reload();
                } else {
                    alert('Request Failed!');
                }
            }).fail(function(xhr) {
                alert(xhr.responseJSON.message || 'Request Failed!');
            });
        }
    }
</script> 
@endpush
