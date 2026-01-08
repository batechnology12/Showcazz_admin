@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Inner Page Title start -->
@include('includes.inner_page_title', ['page_title'=>'Reset Password'])
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="useraccountwrap">
                    <div class="userccount whitebg">
                    
                    <div class="panel-body mt-5">
                        @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                        @endif
                        <form class="form-horizontal" method="POST" action="{{ route('password.email') }}">
                            {{ csrf_field() }}
                            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                <label for="email" class="mb-2 control-label">{{__('Email Address')}}</label>
                                
                                    <input id="email" type="email" class="form-control" placeholder="Enter Your Register Email" name="email" value="{{ old('email') }}" required>
                                    @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                
                            </div>
                            <div class="text-center mt-3">
                                
                                    <button type="submit" class="btn btn-primary">
                                        {{__('Send Password Reset Link')}}
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
@include('includes.footer')
@endsection