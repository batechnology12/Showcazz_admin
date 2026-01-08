@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Inner Page Title start -->
@include('includes.inner_top_search')
<!-- Inner Page Title end -->
<div class="about-wraper">
    <div class="container">

        
    <h1>{{$cmsContent->page_title}}</h1>
    <p>{!! $cmsContent->page_content !!}</p>
            
       
    </div>  
</div>
@include('includes.footer')
@endsection