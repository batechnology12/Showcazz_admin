@extends('layouts.app')

@section('content') 

<!-- Header start --> 

@include('includes.header') 

<!-- Header end --> 



<div class="authpages">

    <div class="container">

    <div class="row justify-content-center align-items-center">
        <div class="col-lg-5">
        @include('flash::message')

        

<div class="useraccountwrap">


     <div class="userccount whitebg">

         
         <div class="tab-content">
             <div id="employer" class="formpanel mt-0 tab-pane fade active">
                    <h3>{{__('Register as an Employer')}}</h3>
                 <form class="form-horizontal mt-3" method="POST" action="{{ route('company.register') }}">

                     {{ csrf_field() }}

                     <input type="hidden" name="candidate_or_employer" value="employer" />

                     <div class="formrow{{ $errors->has('name') ? ' has-error' : '' }}">

                         <input type="text" name="name" class="form-control" required="required" placeholder="{{__('Name')}}" value="{{old('name')}}">

                         @if ($errors->has('name')) <span class="help-block"> <strong>{{ $errors->first('name') }}</strong> </span> @endif </div>

                     <div class="formrow{{ $errors->has('email') ? ' has-error' : '' }}">

                         <input type="email" name="email" class="form-control" required="required" placeholder="{{__('Email')}}" value="{{old('email')}}">

                         @if ($errors->has('email')) <span class="help-block"> <strong>{{ $errors->first('email') }}</strong> </span> @endif </div>

                     <div class="formrow{{ $errors->has('password') ? ' has-error' : '' }}">

                         <input type="password" name="password" class="form-control" required="required" placeholder="{{__('Password')}}" value="">

                         @if ($errors->has('password')) <span class="help-block"> <strong>{{ $errors->first('password') }}</strong> </span> @endif </div>

                     <div class="formrow{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">

                         <input type="password" name="password_confirmation" class="form-control" required="required" placeholder="{{__('Password Confirmation')}}" value="">

                         @if ($errors->has('password_confirmation')) <span class="help-block"> <strong>{{ $errors->first('password_confirmation') }}</strong> </span> @endif </div>

                         <div class="formrow{{ $errors->has('is_subscribed') ? ' has-error' : '' }}">

<?php

$is_checked = '';

if (old('is_subscribed', 1)) {

$is_checked = 'checked="checked"';

}

?>

                         

                         <input type="checkbox" value="1" name="is_subscribed" {{$is_checked}} />
                         {{__('Subscribe to Newsletter')}}

                         @if ($errors->has('is_subscribed')) <span class="help-block"> <strong>{{ $errors->first('is_subscribed') }}</strong> </span> @endif </div>


                         

                     <div class="formrow{{ $errors->has('terms_of_use') ? ' has-error' : '' }}">

                         <input type="checkbox" value="1" name="terms_of_use" />

                         <a href="{{url('cms/terms-of-use')}}">{{__('I accept Terms of Use')}}</a>



                         @if ($errors->has('terms_of_use')) <span class="help-block"> <strong>{{ $errors->first('terms_of_use') }}</strong> </span> @endif </div>

                 <div
                         class="form-group col-12 col-sm-12 col-md-10 text-center mx-auto mobile-padding-no mb-3 {{ $errors->has('g-recaptcha-response') ? ' has-error' : '' }}">
                         {!! app('captcha')->display() !!}
                         @if ($errors->has('g-recaptcha-response')) <span class="help-block">
                             <strong>{{ $errors->first('g-recaptcha-response') }}</strong> </span> @endif
                     </div>

                     <input type="submit" class="btn" value="{{__('Register')}}">

                 </form>

             </div>

         </div>

         <!-- sign up form -->

         <div class="newuser"><i class="fas fa-user" aria-hidden="true"></i> {{__('Have Account')}}? <a href="{{url('company-login')}}">{{__('Sign in')}}</a></div>

         <!-- sign up form end--> 



     </div>

 </div>
        </div>


       </div>

        

    </div>

</div>

@include('includes.footer')

@endsection 