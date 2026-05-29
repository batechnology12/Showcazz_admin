@extends('admin.layouts.admin_layout')
@section('content')
<div class="page-content-wrapper"> 
    <div class="page-content"> 
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li> <a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i> </li>
                <li> <a href="{{ route('admin.roles.index') }}">Roles List</a> <i class="fa fa-circle"></i> </li>
                <li> <span>Add New Role</span> </li>
            </ul>
        </div>
        <h3 class="page-title"> Add New Role <small>Admin Roles</small> </h3>
        <div class="row">
            <div class="col-md-10">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption font-red-sunglo"> <i class="icon-settings font-red-sunglo"></i> <span class="caption-subject bold uppercase">Add New Role Form</span> </div>
                    </div>
                    <div class="portlet-body form">
                        {!! Form::open(array('route' => 'admin.roles.store', 'class' => 'form')) !!}            
                        @include('admin.role.add_edit_form')
                        <div class="form-actions">
                            {!! Form::submit('Create Role', array('class'=>'btn blue')) !!}
                            <a href="{{ route('admin.roles.index') }}" class="btn default">Cancel</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
