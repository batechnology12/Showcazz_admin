@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Header start -->
@include('includes.inner_page_title', ['page_title'=>__('Verify')])
<!-- Header end -->



<div class="authpages">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="useraccountwrap">
                    <div class="userccount whitebg">
                   

                    <div class="card-body text-center">
                         <h3>{{ __('Verify Your Email Address') }}</h3>
                         
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('We resend verification email, please check your email for a verification link.') }}
                            </div>
                        @endif

                        <p>{{ __('Before proceeding, please check your email for a verification link.') }} <br>
                        {{ __('If you did not receive the email') }},
                        </p>
                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary mt-3">{{ __('click here to request another') }}</button>.
                        </form>
                    </div>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div> 
    
    
@include('includes.footer')
@endsection