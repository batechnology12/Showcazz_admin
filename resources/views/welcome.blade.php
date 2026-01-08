@extends('layouts.app')
@section('content')
<!-- Header start -->
@include('includes.header')
<!-- Header end --> 
<!-- Search start -->
@include('includes.search')
<!-- Search End --> 

<div class="infodatawrap">
<div class="container">
<div class="row">
    <div class="col-md-6">@include('includes.login_text')</div>
    <div class="col-md-6">@include('includes.employer_login_text')</div>
</div>

</div>
</div>

<!-- Top Employers start -->
@include('includes.top_employers')
<!-- Top Employers ends --> 


<!-- industries start -->
@include('includes.industries')
<!-- industries ends --> 

<!-- Featured Jobs start -->
@include('includes.featured_jobs')
<!-- Featured Jobs ends -->

<!-- Popular Searches start -->
@include('includes.popular_searches')
<!-- Popular Searches ends --> 

<!-- Latest Jobs start -->
@include('includes.latest_jobs')
<!-- Latest Jobs ends -->

<!-- Top Cities start -->
@include('includes.top_cities')
<!-- Top Cities Ends -->

<!-- Premium Seekers start -->
@include('includes.latest_resume')
<!-- Premium Ends -->

<!-- How it Works start -->
@include('includes.how_it_works')
<!-- How it Works Ends -->


<!-- Testimonials start -->
@include('includes.testimonials')
<!-- Testimonials End -->

<!-- Top countrie start -->
@include('includes.top_countries')
<!-- Top countrie End -->

<!-- Testimonials start -->
@include('includes.home_blogs')
<!-- Testimonials End -->

@include('includes.footer')
@endsection
@push('scripts') 
<script>
    $(document).ready(function ($) {
        $("form").submit(function () {
            $(this).find(":input").filter(function () {
                return !this.value;
            }).attr("disabled", "disabled");
            return true;
        });
        $("form").find(":input").prop("disabled", false);
    });
</script>
@include('includes.country_state_city_js')
@endpush
