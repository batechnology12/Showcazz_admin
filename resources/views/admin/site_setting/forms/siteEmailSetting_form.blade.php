{!! APFrmErrHelp::showErrorsNotice($errors) !!}
@include('flash::message')
<div class="form-body">
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_driver') !!}">
        {!! Form::label('mail_driver', 'Mail Driver', ['class' => 'bold']) !!}                    
        {!! Form::select('mail_driver',$mail_drivers, null, array('class'=>'form-control', 'id'=>'mail_driver')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'mail_driver') !!}                                       
    </div>
    <br>
    <fieldset>
        <legend>SMTP Settings:</legend>    
        <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_host') !!}">
            {!! Form::label('mail_host', 'Mail Host', ['class' => 'bold']) !!}                    
            {!! Form::text('mail_host', null, array('class'=>'form-control', 'id'=>'mail_host', 'placeholder'=>'Mail Host')) !!}
            {!! APFrmErrHelp::showErrors($errors, 'mail_host') !!}                                       
        </div>    
        <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_port') !!}">
            {!! Form::label('mail_port', 'Mail Port', ['class' => 'bold']) !!}                    
            {!! Form::text('mail_port', null, array('class'=>'form-control', 'id'=>'mail_port', 'placeholder'=>'Mail Port')) !!}
            {!! APFrmErrHelp::showErrors($errors, 'mail_port') !!}                                       
        </div>    
        <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_encryption') !!}">
            {!! Form::label('mail_encryption', 'Mail Encryption', ['class' => 'bold']) !!}                    
            {!! Form::text('mail_encryption', null, array('class'=>'form-control', 'id'=>'mail_encryption', 'placeholder'=>'Mail Encryption')) !!}
            {!! APFrmErrHelp::showErrors($errors, 'mail_encryption') !!}                                       
        </div>
        <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_username') !!}">
            {!! Form::label('mail_username', 'Mail Username', ['class' => 'bold']) !!}                    
            {!! Form::text('mail_username', null, array('class'=>'form-control', 'id'=>'mail_username', 'placeholder'=>'Mail Username')) !!}
            {!! APFrmErrHelp::showErrors($errors, 'mail_username') !!}                                       
        </div>
        <div class="form-group {!! APFrmErrHelp::hasError($errors, 'mail_password') !!}">
            {!! Form::label('mail_password', 'Mail Password', ['class' => 'bold']) !!}                    
            {!! Form::text('mail_password', null, array('class'=>'form-control', 'id'=>'mail_password', 'placeholder'=>'Mail Password')) !!}
            {!! APFrmErrHelp::showErrors($errors, 'mail_password') !!}                                       
        </div>
    </fieldset>
    

   
   
    
</div>
