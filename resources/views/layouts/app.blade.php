<?php

if (!isset($seo)) {

    $seo = (object)array('seo_title' => $siteSetting->site_name, 'seo_description' => $siteSetting->site_name, 'seo_keywords' => $siteSetting->site_name, 'seo_other' => '');

}

?>

<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}" class="{{ (session('localeDir', 'ltr'))}}" dir="{{ (session('localeDir', 'ltr'))}}">



<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{__($seo->seo_title) }}</title>

    <meta name="Description" content="{!! $seo->seo_description !!}">

    <meta name="Keywords" content="{!! $seo->seo_keywords !!}">

    {!! $seo->seo_other !!}

    <!-- Fav Icon -->

    <link rel="shortcut icon" href="{{asset('/')}}favicon.ico">

       <!-- Owl carousel -->

    <link href="{{asset('/')}}css/owl.carousel.css" rel="stylesheet">
    <link href="{{asset('/')}}css/jquery-ui.min.css" rel="stylesheet">

    <!-- Slider -->
    <link href="{{asset('/')}}js/revolution-slider/css/settings.css" rel="stylesheet">

    <!-- Bootstrap -->

    <link href="{{asset('/')}}css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->

    <link href="{{asset('/')}}css/all.min.css" rel="stylesheet">

    <!-- Custom Style -->

    <link href="{{asset('/')}}css/main.css" rel="stylesheet">

    @if((session('localeDir', 'ltr') == 'rtl'))

    <!-- Rtl Style -->

    <link href="{{asset('/')}}css/rtl-style.css" rel="stylesheet">

    @endif

    <link href="{{ asset('/') }}admin_assets/global/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css" rel="stylesheet" type="text/css" />

    <link href="{{ asset('/') }}admin_assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <link href="{{ asset('/') }}admin_assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->

    <!--[if lt IE 9]>

          <script src="{{asset('/')}}js/html5shiv.min.js"></script>

          <script src="{{asset('/')}}js/respond.min.js"></script>

        <![endif]-->

    @stack('styles')



    {!! $siteSetting->ganalytics !!}

    {!! $siteSetting->google_tag_manager_for_head !!}



</head>



<body>

    @yield('content')
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/turbolinks/5.0.0/turbolinks.min.js" data-turbolinks-eval="false" data-turbo-eval="false"></script> -->
    <!-- Bootstrap's JavaScript -->


    <script src="{{asset('/')}}js/jquery.min.js"></script>
    <script src="{{asset('/')}}js/popper.js"></script>
    <script src="{{asset('/')}}js/bootstrap.bundle.min.js"></script>



    <!-- Owl carousel -->

    <script src="{{asset('/')}}js/jquery-ui.min.js"></script>

    <script src="{{asset('/')}}js/owl.carousel.js"></script>

    <script src="{{ asset('/') }}admin_assets/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js" type="text/javascript"></script>

    <script src="{{ asset('/') }}admin_assets/global/plugins/Bootstrap-3-Typeahead/bootstrap3-typeahead.min.js" type="text/javascript"></script>

    <!-- END PAGE LEVEL PLUGINS -->

    <script src="{{ asset('/') }}admin_assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>

    <script src="{{ asset('/') }}admin_assets/global/plugins/jquery.scrollTo.min.js" type="text/javascript"></script>

   
<!-- Revolution Slider -->

<script type="text/javascript" src="{{ asset('/') }}js/revolution-slider/js/jquery.themepunch.tools.min.js"></script>

<script type="text/javascript" src="{{ asset('/') }}js/revolution-slider/js/jquery.themepunch.revolution.min.js"></script>
    

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/additional-methods.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.2/dragula.min.js"></script>

   
    {!! NoCaptcha::renderJs() !!}

    @stack('scripts')

    <!-- Custom js -->

    <script src="{{asset('/')}}js/script.js"></script>

    <script type="text/JavaScript">

        $(document).ready(function(){

            $(document).scrollTo('.has-error', 2000);

            });

            function showProcessingForm(btn_id){		

            $("#"+btn_id).val( 'Processing .....' );

            $("#"+btn_id).attr('disabled','disabled');		

            }

		

		setInterval("hide_savedAlert()",7000);

        function hide_savedAlert(){

          $(document).find('.svjobalert').hide();

        }



        $(document).ready(function(){

            $.ajax({

                type: 'get',

                url: "{{route('check-time')}}",

                success: function(res) {

                        $('.notification').html(res);

                   

                }

            });

        });

		

        </script>
        <script type="text/javascript">
    $("#jbsearch").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "{{ route('jobs.autocomplete') }}", // API endpoint to fetch suggestions
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            // Action after selecting a suggestion
        }
    });
  
$(document).ready(function () {
$('.select2-multiple-jobtype').select2({
placeholder: "{{__('Select Multiple Jobtypes')}}",
allowClear: true
});
})

function showAdvanceSearch(){
    $("#showAdvanceSearchRow").show();
    $('.select2-multiple-jobtype').select2({
placeholder: "{{__('Select Multiple Jobtypes')}}",
allowClear: true
});

    $("#advSearch").hide();
}
</script>

</body>



</html>