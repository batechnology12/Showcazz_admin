@extends('admin.layouts.admin_layout')
@section('content')
<div class="page-content-wrapper"> 
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="background-color:#eef1f5;"> 
        <!-- BEGIN PAGE HEADER-->     
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li> <a href="index.html">Home</a> <i class="fa fa-circle"></i> </li>
                <li> <span>{{ $siteSetting->site_name }} Admin Panel</span> </li>
            </ul>
        </div>
        <!-- END PAGE BAR --> 
        <!-- BEGIN PAGE TITLE-->
        <h3 class="page-title"> {{ $siteSetting->site_name }} Admin Panel <small>{{ $siteSetting->site_name }} Admin Panel</small> </h3>
        <!-- END PAGE TITLE--> 
        <!-- END PAGE HEADER-->
        <div class="row">
            <div class="col-md-6 col-sm-6">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 green" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalTodaysUsers }}</span> </div>
                            <div class="desc"> Todays Users </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 red" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalActiveUsers }}</span> </div>
                            <div class="desc"> Active Users </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 blue" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalVerifiedUsers }}</span> </div>
                            <div class="desc"> Verified Users </div>
                        </div>
                    </a> </div>
            </div>
            <div class="col-md-6 col-sm-6">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 green" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalTodaysCompanies }}</span> </div>
                            <div class="desc"> Todays Companies </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 red" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalActiveCompanies }}</span> </div>
                            <div class="desc"> Active Companies </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 blue" href="#">
                        <div class="visual"> <i class="fa fa-user"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalInactiveCompanies }}</span> </div>
                            <div class="desc"> Inactive Companies</div>
                        </div>
                    </a> </div>
            </div>
            <div class="col-md-6 col-sm-6">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 red" href="#">
                        <div class="visual"> <i class="fa fa-list"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalTodaysJobs }}</span> </div>
                            <div class="desc"> Todays Jobs </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 blue" href="#">
                        <div class="visual"> <i class="fa fa-list"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalActiveJobs }}</span> </div>
                            <div class="desc"> Active Jobs </div>
                        </div>
                    </a> </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12"> <a class="dashboard-stat dashboard-stat-v2 purple" href="#">
                        <div class="visual"> <i class="fa fa-list"></i> </div>
                        <div class="details">
                            <div class="number"> <span data-counter="counterup" data-value="1349">{{ $totalFeaturedJobs }}</span> </div>
                            <div class="desc"> Featured Jobs </div>
                        </div>
                    </a> </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"> <i class="icon-share font-dark hide"></i> <span class="caption-subject font-dark bold uppercase">Active Companies</span> </div>
                    </div>
                    <div class="portlet-body">
                        <div class="slimScrol">
                            <ul class="feeds">
                                @foreach($activeCompanies as $recentUser)
                                <li>
                                    <div class="col1">
                                        <div class="cont">
                                            <div class="cont-col1">
                                                <div class="label label-sm label-info"> <i class="fa fa-check"></i> </div>
                                            </div>
                                            <div class="cont-col2">
                                                <div class="desc"><a href="{{ url('admin/public-company/'.$recentUser->id) }}"> {{ $recentUser->name }} ({{ $recentUser->email }}) </a>  - <i class="fa fa-home" aria-hidden="true"></i> {{ $recentUser->getLocation()}}</div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="scroller-footer">
                            <div class="btn-arrow-link pull-right"> <a href="{{ route('list.companies') }}">See All Companies</a> <i class="icon-arrow-right"></i> </div>
                        </div>
                    </div>
                </div>
            </div>
           
        </div>
        <div class="row">
            <div class="col-md-6 col-sm-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"> <i class="icon-share font-dark hide"></i> <span class="caption-subject font-dark bold uppercase">Recent Registered Users</span> </div>
                    </div>
                    <div class="portlet-body">
                        <div class="slimScrol">
                            <ul class="feeds">
                                @foreach($recentUsers as $recentUser)
                                <li>
                                    <div class="col1">
                                        <div class="cont">
                                            <div class="cont-col1">
                                                <div class="label label-sm label-info"> <i class="fa fa-check"></i> </div>
                                            </div>
                                            <div class="cont-col2">
                                                <div class="desc"><a href="{{ route('edit.user', $recentUser->id) }}"> {{ $recentUser->name }} ({{ $recentUser->email }}) </a>  - <i class="fa fa-home" aria-hidden="true"></i> {{ $recentUser->getLocation()}}</div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="scroller-footer">
                            <div class="btn-arrow-link pull-right"> <a href="{{ route('list.users') }}">See All Users</a> <i class="icon-arrow-right"></i> </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"> <i class="icon-share font-dark hide"></i> <span class="caption-subject font-dark bold uppercase">Recent Jobs</span> </div>
                    </div>
                    <div class="portlet-body">
                        <div class="slimScrol">
                            <ul class="feeds">
                                @foreach($recentJobs as $recentJob)
                                <li>
                                    <div class="col1">
                                        <div class="cont">
                                            <div class="cont-col1">
                                                <div class="label label-sm label-info"> <i class="fa fa-check"></i> </div>
                                            </div>
                                            <div class="cont-col2">
                                                <div class="desc"><a href="{{ route('edit.job', $recentJob->id) }}"> {{ \Illuminate\Support\Str::limit($recentJob->title, 50) }} </a>  - <i class="fa fa-list" aria-hidden="true"></i> {{ $recentJob->getCompany('name') }} - <i class="fa fa-home" aria-hidden="true"></i> {{ $recentJob->getLocation() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach                  
                            </ul>
                        </div>
                        <div class="scroller-footer">
                            <div class="btn-arrow-link pull-right"> <a href="{{ route('list.jobs') }}">See All Jobs</a> <i class="icon-arrow-right"></i> </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <ul class="row dashnav">
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.jobs') }}" class="waves-effect waves-light"><i class="icon-briefcase"></i><span> Jobs </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.companies') }}" class="waves-effect waves-light"><i class="fa fa-building"></i><span> Companies </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.users') }}" class="waves-effect waves-light"><i class="icon-user"></i><span> Users </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.cms') }}" class="waves-effect waves-light"><i class="fa fa-file-text-o" aria-hidden="true"></i><span> CMS </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('blog') }}" class="waves-effect waves-light"><i class="icon-briefcase"></i><span> Manage Blog </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.seo') }}" class="waves-effect waves-light"><i class="fa fa-line-chart" aria-hidden="true"></i><span> SEO </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.faqs') }}" class="waves-effect waves-light"><i class="fa fa-question-circle" aria-hidden="true"></i><span> FAQs </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.testimonials') }}" class="waves-effect waves-light"><i class="fa fa-file-text-o" aria-hidden="true"></i><span> Testimonials </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.packages') }}" class="waves-effect waves-light"><i class="fa fa-money" aria-hidden="true"></i><span> Packages </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.countries') }}" class="waves-effect waves-light"><i class="fa fa-globe" aria-hidden="true"></i><span> Countries </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.states') }}" class="waves-effect waves-light"><i class="fa fa-map" aria-hidden="true"></i><span> State </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('list.cities') }}" class="waves-effect waves-light"><i class="fa fa-map-marker" aria-hidden="true"></i><span> Cities </span></a></li>
            <li class="col-lg-2 col-md-3 col-6"><a href="{{ route('edit.site.setting') }}" class="waves-effect waves-light"><i class="icon-wrench"></i><span> Site Settings </span></a></li>
        </ul>


    </div>
    <!-- END CONTENT BODY --> 
</div>
@endsection
@push('scripts')
<script type="text/javascript">
    $(function () {
        $('.slimScrol').slimScroll({
            height: '250px',
            railVisible: true,
            alwaysVisible: true
        });
    });
</script>
@endpush