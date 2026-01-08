@extends('layouts.app')

@section('content') 
<!-- Header start --> 
@include('includes.header') 
<!-- Header end --> 

@push('styles')
<style>
    .table-dark { background: #000; }
    .table {
        border-collapse: collapse;
        border-spacing: 0;
        width: 100%;
        border: 1px solid #eee;
        font-size: 14px;
    }
    .table th { color: #fff; padding: 5px; border-right: 1px solid #eee; }
    .table td { padding: 5px; text-align: center; border-top: 1px solid #eee; }
    .table .btn-primary{padding: 7px 15px;    font-size: 14px;}
</style>
@endpush


<!-- Inner Page Title start --> 
@include('includes.inner_page_title', ['page_title' => __('Payment History')]) 
<!-- Inner Page Title end -->
<div class="listpgWraper">
    <div class="container">
        <div class="row">
            @include('includes.company_dashboard_menu')
            <div class="col-md-9 col-sm-8"> 
                @include('flash::message') 
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Package Title</th>
                            <th>Price</th>
                            <th>Jobs Quota</th>
                            <th>Payment Method</th>
                            <th>Package Start Date</th>
                            <th>Package End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td>{{ $company->package->package_title ?? 'N/A' }}</td>
                                <td>{{ $siteSetting->default_currency_code ?? '' }}{{ $company->package->package_price ?? 'N/A' }}</td>
                                <td>{{ $company->jobs_quota ?? 'N/A' }}</td>
                                <td>
                                    @if (!empty($company->payment_method) && $company->payment_method !== 'offline')
                                        {{ $company->payment_method }}
                                    @else
                                        Offline (Added by Admin)
                                    @endif
                                </td>
                                <td>{{ $company->package_start_date ? \Carbon\Carbon::parse($company->package_start_date)->format('d-m-Y') : 'N/A' }}</td>
                                <td>{{ $company->package_end_date ? \Carbon\Carbon::parse($company->package_end_date)->format('d-m-Y') : 'N/A' }}</td>
                                
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



@include('includes.footer')
@endsection

@push('scripts')
<!-- jsPDF Library -->



@endpush
