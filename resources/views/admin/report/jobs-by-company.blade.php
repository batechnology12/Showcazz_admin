{{-- resources/views/admin/report/jobs-by-company.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('admin.reports.jobs') }}">Job Reports</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Jobs by Company</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.jobs') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Job Reports
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h3 class="page-title">Jobs by Company</h3>
        <!-- END PAGE TITLE -->

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-building"></i>
                            <span class="caption-subject font-dark sbold uppercase">Companies Job Statistics</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Total Jobs</th>
                                        <th>Mini Missions</th>
                                        <th>Internships</th>
                                        <th>Total Views</th>
                                        <th>Total Likes</th>
                                        <th>Total Applications</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($companies as $company)
                                    @php
                                        $miniMissions = \App\Post::where('user_id', $company->user_id)
                                            ->where('category_id', 5)
                                            ->count();
                                        $internships = \App\Post::where('user_id', $company->user_id)
                                            ->where('category_id', 6)
                                            ->count();
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $company->name ?? 'Unknown' }}</strong>
                                            <br><small>ID: {{ $company->user_id }}</small>
                                        </td>
                                        <td class="text-center"><span class="badge badge-primary">{{ $company->job_count }}</span></td>
                                        <td class="text-center"><span class="badge badge-info">{{ $miniMissions }}</span></td>
                                        <td class="text-center"><span class="badge badge-success">{{ $internships }}</span></td>
                                        <td class="text-center">{{ number_format($company->total_views) }}</td>
                                        <td class="text-center">{{ number_format($company->total_likes) }}</td>
                                        <td class="text-center"><span class="badge badge-warning">{{ number_format($company->applications) }}</span></td>
                                        
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No companies found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 text-center">
                                {{ $companies->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .badge-primary { background-color: #3598dc; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-info { background-color: #5bc0de; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-success { background-color: #5cb85c; color: white; padding: 3px 6px; border-radius: 3px; }
    .badge-warning { background-color: #f0ad4e; color: white; padding: 3px 6px; border-radius: 3px; }
</style>
@endpush