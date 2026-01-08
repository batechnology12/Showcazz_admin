
<div class="howitsection greybg">
<div class="container">   
<div class="howitwrap">

    
            <?php $widget =widget(6); ?>
            <!-- title start -->
            <div class="titleTop">
                <h3>{{__('How It Works')}}</h3>
            </div>
            <!-- title end -->
            <ul class="howlist row">
                <!--step 1-->
                <li class="col-lg-4">
                    <div class="iconcircle"><span class="material-symbols-outlined">person_add</span></div>
                    <div class="">
                    <h4>{{ __($widget->extra_field_1) }}</h4>
                    <p>{{ __($widget->extra_field_2) }}.</p>
                    </div>
                </li>
                <!--step 1 end-->
                <!--step 2-->
                <li class="col-lg-4">
                    <div class="iconcircle"><span class="material-symbols-outlined">fact_check</span></div>
                    <div class="">
                    <h4>{{ __($widget->extra_field_3) }}</h4>
                    <p>{{ __($widget->extra_field_4) }}.</p>
                    </div>
                </li>
                <!--step 2 end-->
                <!--step 3-->
                <li class="col-lg-4">
                    <div class="iconcircle"><span class="material-symbols-outlined">touchpad_mouse</span></div>
                    <div class="">
                    <h4>{{ __($widget->extra_field_5) }}</h4>
                    <p>{{ __($widget->extra_field_6) }}.</p>
                    </div>
                </li>
                <!--step 3 end-->
            </ul>

</div>
        
   
</div>
</div>