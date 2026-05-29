@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="row">
            <div class="col-md-12 page-404">
                <div class="number font-red"> 500 </div>
                <div class="details">
                    <h3>Oops! Something went wrong.</h3>
                    <p> We are experiencing an internal server error. Please try again later.
                        <br>
                        <a href="{{ route('admin.home') }}"> Return to dashboard </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.page-404 { text-align: center; margin-top: 100px; }
.page-404 .number { font-size: 120px; font-weight: 700; display: block; line-height: 120px; margin-bottom: 20px; }
.page-404 .details { display: inline-block; text-align: left; vertical-align: top; margin-left: 40px; }
</style>
@endpush
