@extends('layouts.app')

@section('content') 

<!-- Header start --> 

@include('includes.header') 

<!-- Header end --> 

<!-- Inner Page Title start --> 

@include('includes.inner_page_title', ['page_title'=>__('Submit Your Job Application')]) 

<!-- Inner Page Title end -->

<div class="listpgWraper">

    <div class="container"> @include('flash::message')

        <div class="row justify-content-center">

            <div class="col-md-7">

                <div class="userccount">

                    <div class="formpanel"> {!! Form::open(['method' => 'post', 'route' => ['post.job.apply', $job_slug], 'files' => true]) !!} 

                        <!-- Job Information -->

                        <h5>{{__('You are about to apply for the job')}}: {{$job->title}}</h5>

                        <div class="row">

                            

                            <div class="col-md-12">

                                <div class="formrow{{ $errors->has('name') ? ' has-error' : '' }}"> {!! Form::text('name', auth()->user()?auth()->user()->name:null, array('class'=>'form-control', 'id'=>'name', 'placeholder'=>__('Full Name'),'required'=>'required' )) !!}

                                    @if ($errors->has('name')) <span class="help-block"> <strong>{{ $errors->first('name') }}</strong> </span> @endif </div>

                            </div>

                            <div class="col-md-6">

                                <div class="formrow{{ $errors->has('email') ? ' has-error' : '' }}"> {!! Form::text('email', auth()->user()?auth()->user()->email:null, array('class'=>'form-control', 'id'=>'email', 'placeholder'=>__('Email'),'required'=>'required' )) !!}

                                    @if ($errors->has('email')) <span class="help-block"> <strong>{{ $errors->first('email') }}</strong> </span> @endif </div>

                            </div>

                            <div class="col-md-6">

                                <div class="formrow{{ $errors->has('phone') ? ' has-error' : '' }}"> {!! Form::text('phone', auth()->user()?auth()->user()->phone:null, array('class'=>'form-control', 'id'=>'phone', 'placeholder'=>__('Phone') ,'required'=>'required')) !!}

                                    @if ($errors->has('phone')) <span class="help-block"> <strong>{{ $errors->first('phone') }}</strong> </span> @endif </div>

                            </div>

                            

                            <div class="col-md-12">

                                <div class="formrow{{ $errors->has('cv') ? ' has-error' : '' }}">

                                    <input type="file" name="cv" id="cv" class="form-control" required>

                                    @if ($errors->has('cv')) <span class="help-block"> <strong>{{ $errors->first('cv') }}</strong> </span> @endif </div>

                            </div>

                        </div>

                        <br>

                        <input type="submit" class="btn" value="{{__('Apply on Job')}}">

                        {!! Form::close() !!} </div>

                </div>

            </div>

        </div>

    </div>

</div>

@include('includes.footer')

@endsection

@push('scripts') 
<?php
if (session('message.url')) {
    $url = session('message.url');
    header('Location: ' . $url);
    exit(); 
}
?>






<script>

    $(document).ready(function () {

        $('#salary_currency').typeahead({

            source: function (query, process) {

                return $.get("{{ route('typeahead.currency_codes') }}", {query: query}, function (data) {

                    console.log(data);

                    data = $.parseJSON(data);

                    return process(data);

                });

            }

        });



    });

</script> 

@endpush