@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="row">
            <div class="col-md-12 page-404">
                <div class="number font-red"> 403 </div>
                <div class="details">
                    <h3>Access Denied!</h3>
                    <p> You do not have permission to access this page.
                        <br>
                        @if(isset($permission))
                            Required permission: <strong>{{ $permission }}</strong>
                            <br>
                        @endif
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
