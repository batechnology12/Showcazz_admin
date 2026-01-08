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
    <div class="col-md-4">
                <div class="panel panel-default">
                  
                    <div class="panel-body">
                        @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                        @endif
                        <form class="form-horizontal" method="POST" action="{{ route('company.password.email') }}">
                            {{ csrf_field() }}
                            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                <label for="email" class="control-label mb-2">{{__('Email Address')}}</label>
                               
                                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                                    @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                               
                            </div>
                            <div class="form-group mt-3 text-center">
                               
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
@include('includes.footer')
@endsection