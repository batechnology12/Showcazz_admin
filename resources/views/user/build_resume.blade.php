@extends('layouts.app')
@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 
<!-- Inner Page Title start --> 
@include('includes.inner_page_title', ['page_title'=>__('Build Resume')]) 
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row">
            @include('includes.user_dashboard_menu')

            <div class="col-md-9 col-sm-8"> 
						 <div class="editprofilebox">
                            <div class="formpanel">
                                <h3>{{__('Build Your Resume')}}</h3>
                                <!-- Personal Information -->
                                @include('user.forms.cv.cvs')
                                @include('user.forms.project.projects')
                                @include('user.forms.experience.experience')
                                @include('user.forms.education.education')
                                @include('user.forms.skill.skills')
                                @include('user.forms.language.languages')
                            </div>
                        </div>
            </div>
        </div>
    </div>  
</div>
@include('includes.footer')
@endsection
@push('styles')
<style type="text/css">
    .userccount p{ text-align:left !important;}
</style>
@endpush
@push('scripts')
@include('includes.immediate_available_btn')

<script>
    $(document).on('click', '.btn-close', function() {
        $('.modal').css('display','none');
        $('.modal-backdrop').remove();
        $('.modal').removeAttr('style');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('body').removeAttr('style');    
    });
</script>

@endpush