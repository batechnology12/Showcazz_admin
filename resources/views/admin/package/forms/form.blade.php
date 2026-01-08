{!! APFrmErrHelp::showOnlyErrorsNotice($errors) !!}
@include('flash::message')
<div class="form-body">
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'package_for') !!}">
        {!! Form::label('package_for', 'Package for?', ['class' => 'bold']) !!}
        <div class="radio-list">
            <?php
            $package_for_1 = 'checked="checked"';
            $package_for_2 = '';
            $package_for_3 = '';
            $package_for_4 = '';
            
            $selected_package = old('package_for', isset($package) ? $package->package_for : 'job_seeker');
            
            if ($selected_package == 'employer') {
                $package_for_1 = '';
                $package_for_2 = 'checked="checked"';
            } elseif ($selected_package == 'cv_search') {
                $package_for_1 = '';
                $package_for_3 = 'checked="checked"';
            } elseif ($selected_package == 'make_featured') {
                $package_for_1 = '';
                $package_for_4 = 'checked="checked"';
            }
            ?>

            @if(isset($package) && $package->id == 9)
                <label class="radio-inline">
                    <input id="make_featured" name="package_for" type="radio" value="make_featured" {{$package_for_4}}>
                    Candidate Featured Profile
                </label>
            @else
            <label class="radio-inline">
                <input id="job_seeker" name="package_for" type="radio" value="job_seeker" {{$package_for_1}}>
                Job Seeker </label>
            <label class="radio-inline">
                <input id="employer" name="package_for" type="radio" value="employer" {{$package_for_2}}>
                Employer </label>
            <label class="radio-inline">
                <input id="cv_search" name="package_for" type="radio" value="cv_search" {{$package_for_3}}>
                Cv Search </label>
                @endif
            
        </div>
        {!! APFrmErrHelp::showErrors($errors, 'package_for') !!}
    </div>
    
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'package_title') !!}"> {!! Form::label('package_title', 'Package Title', ['class' => 'bold']) !!}
        {!! Form::text('package_title', null, array('class'=>'form-control', 'id'=>'package_title', 'placeholder'=>'Package Title')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'package_title') !!} </div>
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'package_price') !!}"> {!! Form::label('package_price', 'Package Price(In USD)', ['class' => 'bold']) !!}
        {!! Form::text('package_price', null, array('class'=>'form-control', 'id'=>'package_price', 'placeholder'=>'Package Price')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'package_price') !!} </div>
    <div class="form-group {!! APFrmErrHelp::hasError($errors, 'package_num_days') !!}"> {!! Form::label('package_num_days', 'Package num days', ['class' => 'bold']) !!}
        {!! Form::text('package_num_days', null, array('class'=>'form-control', 'id'=>'package_num_days', 'placeholder'=>'Package num days')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'package_num_days') !!} </div>
    <div id="package_num_listings_group" class="form-group {!! APFrmErrHelp::hasError($errors, 'package_num_listings') !!}">
        {!! Form::label('package_num_listings', 'Package num listings*', ['class' => 'bold']) !!}
        {!! Form::text('package_num_listings', null, array('class'=>'form-control', 'id'=>'package_num_listings', 'placeholder'=>'Package num listings')) !!}
        {!! APFrmErrHelp::showErrors($errors, 'package_num_listings') !!}
        *On how many jobs a job seeker can apply<br />
        **How many jobs an employer can post
    </div>
    
    <div class="form-actions"> {!! Form::button('Update <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>', array('class'=>'btn btn-large btn-primary', 'type'=>'submit')) !!} </div>
</div>

<script>
    function togglePackageListings() {
        var selectedValue = document.querySelector('input[name="package_for"]:checked').value;
        var packageNumListingsGroup = document.getElementById('package_num_listings_group');

        if (selectedValue === 'make_featured') {
            packageNumListingsGroup.style.display = 'none';
        } else {
            packageNumListingsGroup.style.display = 'block';
        }
    }

    document.querySelectorAll('input[name="package_for"]').forEach(function (radio) {
        radio.addEventListener('change', togglePackageListings);
    });

    document.addEventListener('DOMContentLoaded', function() {
        togglePackageListings();
    });
</script>