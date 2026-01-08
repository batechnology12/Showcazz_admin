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

             <div id="candidate" class="formpanel mt-0 tab-pane active">
                <h3 class="text-center">{{__('Register as a Candidate')}}</h3>
                
           
                
                 <form class="form-horizontal mt-3" method="POST" action="{{ route('register') }}">
                    @csrf

                     <input type="hidden" name="candidate_or_employer" value="candidate" />

                     <div class="formrow{{ $errors->has('first_name') ? ' has-error' : '' }}">

                         <input type="text" name="first_name" class="form-control" required="required" placeholder="{{__('First Name')}}" value="{{old('first_name')}}">

                         @if ($errors->has('first_name')) <span class="help-block"> <strong>{{ $errors->first('first_name') }}</strong> </span> @endif </div>

                     

                     <div class="formrow{{ $errors->has('last_name') ? ' has-error' : '' }}">

                         <input type="text" name="last_name" class="form-control" required="required" placeholder="{{__('Last Name')}}" value="{{old('last_name')}}">

                         @if ($errors->has('last_name')) <span class="help-block"> <strong>{{ $errors->first('last_name') }}</strong> </span> @endif </div>

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

                         <div class="form-group mb-3 {{ $errors->has('g-recaptcha-response') ? ' has-error' : '' }}">
                         {!! app('captcha')->display() !!}
                         @if ($errors->has('g-recaptcha-response')) <span class="help-block">
                             <strong>{{ $errors->first('g-recaptcha-response') }}</strong> </span> @endif
                     </div>





                 
                     <input type="submit" class="btn" value="{{__('Register')}}">

                 </form>

             </div>

      

         </div>

         <!-- sign up form -->

         <div class="newuser"><i class="fas fa-user" aria-hidden="true"></i> {{__('Have Account')}}? <a href="{{route('login')}}">{{__('Sign in')}}</a></div>

         <!-- sign up form end--> 



     </div>

 </div>
        </div>

    

      

       </div>

        

    </div>

</div>

@include('includes.footer')

@endsection 