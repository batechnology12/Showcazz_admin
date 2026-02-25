<!-- BEGIN SIDEBAR -->
<!-- DOC: Set data-auto-scroll="false" to disable the sidebar from auto scrolling/focusing -->
<!-- DOC: Change data-auto-speed="200" to adjust the sub menu slide up/down speed -->
<div class="page-sidebar navbar-collapse collapse">
    <!-- BEGIN SIDEBAR MENU -->
    <!-- DOC: Apply "page-sidebar-menu-light" class right after "page-sidebar-menu" to enable light sidebar menu style(without borders) -->
    <!-- DOC: Apply "page-sidebar-menu-hover-submenu" class right after "page-sidebar-menu" to enable hoverable(hover vs accordion) sub menu mode -->
    <!-- DOC: Apply "page-sidebar-menu-closed" class right after "page-sidebar-menu" to collapse("page-sidebar-closed" class must be applied to the body element) the sidebar sub menu mode -->
    <!-- DOC: Set data-auto-scroll="false" to disable the sidebar from auto scrolling/focusing -->
    <!-- DOC: Set data-keep-expand="true" to keep the submenues expanded -->
    <!-- DOC: Set data-auto-speed="200" to adjust the sub menu slide up/down speed -->
    <ul class="page-sidebar-menu page-header-fixed" data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200" style="padding-top: 20px">
        <!-- DOC: To remove the sidebar toggler from the sidebar you just need to completely remove the below "sidebar-toggler-wrapper" LI element -->
        <li class="sidebar-toggler-wrapper hide">
            <!-- BEGIN SIDEBAR TOGGLER BUTTON -->
            <div class="sidebar-toggler"> </div>
            <!-- END SIDEBAR TOGGLER BUTTON -->
        </li>
        <!-- DOC: To remove the search box from the sidebar you just need to completely remove the below "sidebar-search-wrapper" LI element -->
        <li class="sidebar-search-wrapper">
            <!-- BEGIN RESPONSIVE QUICK SEARCH FORM -->
            <!-- DOC: Apply "sidebar-search-bordered" class the below search form to have bordered search box -->
            <!-- DOC: Apply "sidebar-search-bordered sidebar-search-solid" class the below search form to have bordered & solid search box -->
            <!-- END RESPONSIVE QUICK SEARCH FORM -->
        </li>
        <li class="nav-item start active"> <a href="{{ route('admin.home') }}" class="nav-link"> <i class="icon-home"></i> <span class="title">Dashboard</span> </a> </li>
        @include('admin/shared/side_bars/admin_user')

        <li class="heading">
            <h3 class="uppercase">Modules</h3>
        </li>
        
        
        {{-- In your admin sidebar layout file --}}
        <li class="nav-item {{ request()->routeIs('companies.*') ? 'active' : '' }}">
            <a href="{{ route('companies.index') }}" class="nav-link nav-toggle">
                <i class="fa fa-building"></i>
                <span class="title">Companies</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('companies.index') ? 'active' : '' }}">
                    <a href="{{ route('companies.index') }}" class="nav-link">
                        <span class="title">All Companies</span>
                    </a>
                </li>
        
              
                <!--<li class="nav-item">-->
                <!--    <a href="{{ route('companies.index', ['is_featured' => 1]) }}" class="nav-link">-->
                <!--        <span class="title">Featured Companies</span>-->
                <!--    </a>-->
                <!--</li>-->
            </ul>
        </li>
        
        
        {{-- User Management Menu Item --}}
        <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}" class="nav-link nav-toggle">
                <i class="fa fa-users"></i>
                <span class="title">Users</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}" class="nav-link">
                        <span class="title">All Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index', ['usertype' => 'student']) }}" class="nav-link">
                        <span class="title">Students</span>
                        @php
                            $studentCount = \App\User::where('usertype', 'student')->where('is_active', 1)->count();
                        @endphp
                        @if($studentCount > 0)
                            <span class="badge badge-info">{{ $studentCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index', ['usertype' => 'professional']) }}" class="nav-link">
                        <span class="title">Professionals</span>
                        @php
                            $professionalCount = \App\User::where('usertype', 'professional')->where('is_active', 1)->count();
                        @endphp
                        @if($professionalCount > 0)
                            <span class="badge badge-success">{{ $professionalCount }}</span>
                        @endif
                    </a>
                </li>
                
            </ul>
        </li>
        
        
        {{-- Post Management Menu --}}
        <li class="nav-item {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
            <a href="javascript:;" class="nav-link nav-toggle">
                <i class="fa fa-file-text"></i>
                <span class="title">Posts</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('admin.posts.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.posts.index') }}" class="nav-link">
                        <span class="title">All Posts</span>
                        @php
                            $pendingPostsCount = \App\Post::whereNotIn('category_id', [5,6])
                                ->where('is_active', false)
                                ->where('is_published', true)
                                ->count();
                        @endphp
                        @if($pendingPostsCount > 0)
                            <span class="badge badge-warning">{{ $pendingPostsCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.posts.index', ['status' => 'published']) }}" class="nav-link">
                        <span class="title">Published</span>
                    </a>
                </li>
                <!--<li class="nav-item">-->
                <!--    <a href="{{ route('admin.posts.index', ['status' => 'draft']) }}" class="nav-link">-->
                <!--        <span class="title">Drafts</span>-->
                <!--    </a>-->
                <!--</li>-->
                <li class="nav-item">
                    <a href="{{ route('admin.posts.index', ['has_media' => 'images']) }}" class="nav-link">
                        <span class="title">With Images</span>
                    </a>
                </li>
            </ul>
        </li>
        
        {{-- Job Management Menu --}}
        <li class="nav-item {{ request()->routeIs('admin.jobs.*') ? 'active' : '' }}">
            <a href="javascript:;" class="nav-link nav-toggle">
                <i class="fa fa-briefcase"></i>
                <span class="title">Jobs & Opportunities</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('admin.jobs.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.jobs.index') }}" class="nav-link">
                        <span class="title">All Opportunities</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.jobs.mini-mission') }}" class="nav-link">
                        <span class="title">Mini Missions</span>
                        @php
                            $miniMissionsCount = \App\Post::where('category_id', 5)->count();
                        @endphp
                        <span class="badge badge-info">{{ $miniMissionsCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.jobs.internship') }}" class="nav-link">
                        <span class="title">Internships</span>
                        @php
                            $internshipsCount = \App\Post::where('category_id', 6)->count();
                        @endphp
                        <span class="badge badge-success">{{ $internshipsCount }}</span>
                    </a>
                </li>
                <!--<li class="nav-item">-->
                <!--    <a href="{{ route('admin.jobs.index', ['status' => 'active']) }}" class="nav-link">-->
                <!--        <span class="title">Active</span>-->
                <!--    </a>-->
                <!--</li>-->
                <!--<li class="nav-item">-->
                <!--    <a href="{{ route('admin.jobs.index', ['status' => 'expired']) }}" class="nav-link">-->
                <!--        <span class="title">Expired</span>-->
                <!--    </a>-->
                <!--</li>-->
            </ul>
        </li>
        
        
        {{-- Reports & Analytics Menu --}}
        <li class="nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <a href="javascript:;" class="nav-link nav-toggle">
                <i class="fa fa-bar-chart"></i>
                <span class="title">Reports & Analytics</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                {{-- Dashboard --}}
                
                {{-- User Reports --}}
                <li class="nav-item {{ request()->routeIs('admin.reports.users*') ? 'active' : '' }}">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="fa fa-users"></i>
                        <span class="title">User Reports</span>
                        <span class="arrow"></span>
                    </a>
                    <ul class="sub-menu">
                        <li class="nav-item {{ request()->routeIs('admin.reports.users') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.users') }}" class="nav-link">
                                <i class="fa fa-pie-chart"></i>
                                <span class="title">Overview</span>
                            </a>
                        </li>
                       
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.users.export') }}" class="nav-link">
                                <i class="fa fa-download"></i>
                                <span class="title">Export Report</span>
                            </a>
                        </li>
                    </ul>
                </li>
        
                {{-- Post Reports --}}
                <li class="nav-item {{ request()->routeIs('admin.reports.posts*') ? 'active' : '' }}">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="fa fa-file-text"></i>
                        <span class="title">Post Reports</span>
                        <span class="arrow"></span>
                    </a>
                    <ul class="sub-menu">
                        <li class="nav-item {{ request()->routeIs('admin.reports.posts') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.posts') }}" class="nav-link">
                                <i class="fa fa-pie-chart"></i>
                                <span class="title">Overview</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.posts.popular') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.posts.popular') }}" class="nav-link">
                                <i class="fa fa-star"></i>
                                <span class="title">Popular Posts</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.posts.engagement') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.posts.engagement') }}" class="nav-link">
                                <i class="fa fa-heart"></i>
                                <span class="title">Post Engagement</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.posts.categories') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.posts.categories') }}" class="nav-link">
                                <i class="fa fa-tags"></i>
                                <span class="title">By Category</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.posts.export') }}" class="nav-link">
                                <i class="fa fa-download"></i>
                                <span class="title">Export Report</span>
                            </a>
                        </li>
                    </ul>
                </li>
        
                {{-- Job Reports --}}
                <li class="nav-item {{ request()->routeIs('admin.reports.jobs*') ? 'active' : '' }}">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="fa fa-briefcase"></i>
                        <span class="title">Job Reports</span>
                        <span class="arrow"></span>
                    </a>
                    <ul class="sub-menu">
                        <li class="nav-item {{ request()->routeIs('admin.reports.jobs') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.jobs') }}" class="nav-link">
                                <i class="fa fa-pie-chart"></i>
                                <span class="title">Overview</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.jobs.posted') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.jobs.posted') }}" class="nav-link">
                                <i class="fa fa-upload"></i>
                                <span class="title">Jobs Posted</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.jobs.applied') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.jobs.applied') }}" class="nav-link">
                                <i class="fa fa-send"></i>
                                <span class="title">Jobs Applied</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.jobs.by-type') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.jobs.by-type') }}" class="nav-link">
                                <i class="fa fa-tasks"></i>
                                <span class="title">By Type</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.jobs.by-company') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.jobs.by-company') }}" class="nav-link">
                                <i class="fa fa-building"></i>
                                <span class="title">By Company</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.jobs.export') }}" class="nav-link">
                                <i class="fa fa-download"></i>
                                <span class="title">Export Report</span>
                            </a>
                        </li>
                    </ul>
                </li>
        
                {{-- Engagement Reports --}}
                <li class="nav-item {{ request()->routeIs('admin.reports.engagement*') ? 'active' : '' }}">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="fa fa-heart"></i>
                        <span class="title">Engagement Reports</span>
                        <span class="arrow"></span>
                    </a>
                    <ul class="sub-menu">
                        <li class="nav-item {{ request()->routeIs('admin.reports.engagement') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.engagement') }}" class="nav-link">
                                <i class="fa fa-pie-chart"></i>
                                <span class="title">Overview</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.engagement.time-spent') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.engagement.time-spent') }}" class="nav-link">
                                <i class="fa fa-clock-o"></i>
                                <span class="title">Time Spent</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.engagement.page-views') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.engagement.page-views') }}" class="nav-link">
                                <i class="fa fa-eye"></i>
                                <span class="title">Page Views</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.reports.engagement.interactions') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.engagement.interactions') }}" class="nav-link">
                                <i class="fa fa-comments"></i>
                                <span class="title">Interactions</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.engagement.export') }}" class="nav-link">
                                <i class="fa fa-download"></i>
                                <span class="title">Export Report</span>
                            </a>
                        </li>
                    </ul>
                </li>
        
                {{-- Platform Performance --}}
                <li class="nav-item {{ request()->routeIs('admin.reports.performance*') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.performance') }}" class="nav-link">
                        <i class="fa fa-dashboard"></i>
                        <span class="title">Platform Performance</span>
                        @php
                            $performanceAlert = false; // You can add logic for alerts
                        @endphp
                        @if($performanceAlert)
                            <span class="badge badge-danger">!</span>
                        @endif
                    </a>
                </li>
        
                {{-- Export All --}}
                <li class="nav-item">
                    <a href="{{ route('admin.reports.export.all') }}" class="nav-link" onclick="return confirm('This will generate a ZIP file with all reports. Continue?')">
                        <i class="fa fa-file-archive-o"></i>
                        <span class="title">Export All Reports</span>
                    </a>
                </li>
            </ul>
        </li>
        <!--@include('admin/shared/side_bars/job')-->
        <!--@include('admin/shared/side_bars/company')-->
        <!--@include('admin/shared/side_bars/site_user')-->
        <!--@include('admin/shared/side_bars/cms')-->
        <!--@include('admin/shared/side_bars/blogs')-->
        <!--@include('admin/shared/side_bars/seo')-->
        <!--@include('admin/shared/side_bars/faq')-->
        <!--@include('admin/shared/side_bars/video')-->
        <!--@include('admin/shared/side_bars/testimonial')-->
        <!--@include('admin/shared/side_bars/slider')-->
		
		
		@if(APAuthHelp::check(['SUP_ADM']))
        <!--<li class="heading">-->
        <!--    <h3 class="uppercase">Translation</h3>-->
        <!--</li>		-->
        <!--@include('admin/shared/side_bars/language')-->




        <!--<li class="heading">-->
        <!--    <h3 class="uppercase">Manage Location</h3>-->
        <!--</li>-->
        <!--@include('admin/shared/side_bars/country')-->
        <!--@include('admin/shared/side_bars/country_detail')-->
        <!--@include('admin/shared/side_bars/state')-->
        <!--@include('admin/shared/side_bars/city')-->

		
        <!--<li class="heading">-->
        <!--    <h3 class="uppercase">User Packages</h3>-->
        <!--</li>-->
        <!--@include('admin/shared/side_bars/package')-->

		
		
        <!--<li class="heading">-->
        <!--    <h3 class="uppercase">Job Attributes</h3>-->
        <!--</li>-->
        <!--@include('admin/shared/side_bars/language_level')-->
        <!--@include('admin/shared/side_bars/career_level')-->
        <!--@include('admin/shared/side_bars/functional_area')-->
        <!--@include('admin/shared/side_bars/gender') -->
        <!--@include('admin/shared/side_bars/industry') -->
        <!--@include('admin/shared/side_bars/job_experience') -->
        <!--@include('admin/shared/side_bars/job_skill') -->
        <!--@include('admin/shared/side_bars/job_type') -->
        <!--@include('admin/shared/side_bars/job_shift') -->
        <!--@include('admin/shared/side_bars/degree_level') -->
        <!--@include('admin/shared/side_bars/degree_type') -->
        <!--@include('admin/shared/side_bars/major_subject')  -->
        <!--@include('admin/shared/side_bars/result_type')-->
        <!--@include('admin/shared/side_bars/marital_status')-->
        <!--@include('admin/shared/side_bars/ownership_type') -->
        <!--@include('admin/shared/side_bars/salary_period') -->
		
        <!--<li class="heading">-->
        <!--    <h3 class="uppercase">Manage</h3>-->
        <!--</li>		 -->
        <!--@include('admin/shared/side_bars/site_setting')   -->
		@endif
		
		
		
    </ul>
    <!-- END SIDEBAR MENU -->
    <!-- END SIDEBAR MENU -->
</div>
<!-- END SIDEBAR -->