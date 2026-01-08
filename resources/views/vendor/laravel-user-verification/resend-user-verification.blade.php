@extends('layouts.app')
@section('content')
    <!-- Header start -->
    @include('includes.header')
    <!-- Header end -->
    <main>
    <!-- Inner Page Title start -->
    @include('includes.inner_page_title', ['page_title'=>__('User Verification')])
    <!-- Inner Page Title end -->
    <!-- Page Title start -->
    <!-- Page Title End -->
    <div class="about-wraper">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="userccount">
                        <h5>User Verification</h5>
                        <div class="formpanel">
                            <div class="formrow"><span
                                        class="help-block">
                                    <strong>For security of your account, Verification link has been sent to your register email ID {{auth()->user()->email}}. <br>
                                        If you did not receive the email then click below.</strong>
                                 </span>
                            </div>
                            <div class="formrow">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <div class="mt-3">
                                        <button type="submit"
                                                class="btn btn-primary"> Resend Verification Email
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection