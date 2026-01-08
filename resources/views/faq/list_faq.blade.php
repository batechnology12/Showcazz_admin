@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Inner Page Title start -->
@include('includes.inner_page_title', ['page_title'=>__('Frequently asked questions')])
<!-- Inner Page Title end -->
<!-- Page Title End -->
<div class="listpgWraper">
    <div class="container"> 
        <!--Question-->
        <div class="faqs">
            <div class="accordion" id="accordion">
                <h3>&nbsp;</h3>
                @if(isset($faqs) && count($faqs))
                @foreach($faqs as $faq)
                <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $faq->id }}" aria-expanded="true" aria-controls="collapseOne">
                    {!! $faq->faq_question !!}
                    </button>
                </h2>

                <div id="collapse{{ $faq->id }}" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                {!! $faq->faq_answer !!}
                </div>
                </div>

                </div>
                @endforeach
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-6">{!! $siteSetting->cms_page_ad !!}</div>
            <div class="col-md-3"></div>
        </div>
    </div>
</div>

@include('includes.footer')
@endsection