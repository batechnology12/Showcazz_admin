<div class="form-body">
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'role_name') !!}">
        {!! Form::label('role_name', 'Role Name', ['class' => 'bold']) !!}
        {!! Form::text('role_name', null, array('class'=>'form-control', 'id'=>'role_name', 'placeholder'=>'Role Name', 'required'=>'required')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'role_name') !!}
    </div>
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'role_abbreviation') !!}">
        {!! Form::label('role_abbreviation', 'Role Abbreviation', ['class' => 'bold']) !!}
        {!! Form::text('role_abbreviation', null, array('class'=>'form-control', 'id'=>'role_abbreviation', 'placeholder'=>'Role Abbreviation', 'required'=>'required')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'role_abbreviation') !!}
    </div>
    <div class="form-group">
        {!! Form::label('role_description', 'Description', ['class' => 'bold']) !!}
        {!! Form::textarea('role_description', null, array('class'=>'form-control', 'id'=>'role_description', 'placeholder'=>'Description', 'rows'=>3)) !!}
    </div>

    <hr>
    <h4 class="bold">Permissions</h4>
    <div class="row">
        @foreach($permissions as $group => $groupPermissions)
            <div class="col-md-12">
                <h5 class="bold" style="background: #f5f5f5; padding: 10px; margin-top: 20px;">{{ $group }}</h5>
                <div class="row">
                    @foreach($groupPermissions as $permission)
                        <div class="col-md-4">
                            <div class="md-checkbox" style="margin-bottom: 10px;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}" class="md-check" 
                                    {{ isset($rolePermissions) && in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                <label for="perm_{{ $permission->id }}">
                                    <span></span>
                                    <span class="check"></span>
                                    <span class="box"></span>
                                    {{ $permission->label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
