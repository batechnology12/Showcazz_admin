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
        @if(APAuthHelp::checkPermission('manage_companies'))
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
            </ul>
        </li>
        @endif
        
        
        {{-- User Management Menu Item --}}
        @if(APAuthHelp::checkPermission('manage_users'))
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
        @endif
        
        
        {{-- Post Management Menu --}}
        @if(APAuthHelp::checkPermission('manage_posts'))
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
                <li class="nav-item">
                    <a href="{{ route('admin.posts.index', ['has_media' => 'images']) }}" class="nav-link">
                        <span class="title">With Images</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
        
        {{-- Job Management Menu --}}
        @if(APAuthHelp::checkPermission('manage_jobs'))
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
            </ul>
        </li>
        @endif
        
        
        
            
        {{-- Add this to your admin sidebar menu --}}
        @if(APAuthHelp::checkPermission('manage_subscriptions'))
        <li class="nav-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
            <a href="{{ route('admin.subscriptions.dashboard') }}" class="nav-link nav-toggle">
                <i class="fa fa-credit-card"></i>
                <span class="title">Subscription Management</span>
                <span class="arrow {{ request()->routeIs('admin.subscriptions.*') ? 'open' : '' }}"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('admin.subscriptions.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.dashboard') }}" class="nav-link">
                        <i class="fa fa-dashboard"></i>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.subscriptions.packages') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.packages') }}" class="nav-link">
                        <i class="fa fa-cubes"></i>
                        <span class="title">Packages</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.subscriptions.requests') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.requests') }}" class="nav-link">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="title">Payment Requests</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.subscriptions.transactions') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.transactions') }}" class="nav-link">
                        <i class="fa fa-exchange"></i>
                        <span class="title">Transactions</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.subscriptions.companies') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.companies') }}" class="nav-link">
                        <i class="fa fa-building"></i>
                        <span class="title">Company Subscriptions</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
        
        
        {{-- Reports & Analytics Menu --}}
        @if(APAuthHelp::checkPermission('manage_reports') || APAuthHelp::checkPermission('manage_firebase_analytics'))
        <li class="nav-item {{ request()->routeIs('admin.reports.*') || request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
            <a href="javascript:;" class="nav-link nav-toggle">
                <i class="fa fa-bar-chart"></i>
                <span class="title">Reports & Analytics</span>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                @if(APAuthHelp::checkPermission('manage_reports'))
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
                    </a>
                </li>
                @endif
                
                @if(APAuthHelp::checkPermission('manage_firebase_analytics'))
                {{-- inside the Reports & Analytics sub-menu --}}
                 <li class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'active open' : '' }}">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="fa fa-fire"></i>
                        <span class="title">Firebase Analytics</span>
                        <span class="arrow"></span>
                    </a>
                    <ul class="sub-menu">
                        <li class="nav-item {{ request()->routeIs('admin.analytics.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.analytics.dashboard') }}" class="nav-link">
                                <i class="fa fa-dashboard"></i>
                                <span class="title">Analytics Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.analytics.retention') ? 'active' : '' }}">
                            <a href="{{ route('admin.analytics.retention') }}" class="nav-link">
                                <i class="fa fa-users"></i>
                                <span class="title">User Retention</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.analytics.realtime') ? 'active' : '' }}">
                            <a href="{{ route('admin.analytics.realtime') }}" class="nav-link">
                                <i class="fa fa-clock-o"></i>
                                <span class="title">Real-time Users</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
            </ul>
        </li>
        @endif
        
        
        @if(APAuthHelp::checkPermission('manage_notifications'))
        <li class="nav-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <a href="{{ route('admin.notifications.dashboard') }}" class="nav-link nav-toggle">
                <i class="fa fa-bell"></i>
                <span class="title">Push Notifications</span>
                <span class="arrow {{ request()->routeIs('admin.notifications.*') ? 'open' : '' }}"></span>
            </a>
            <ul class="sub-menu">
                <li class="nav-item {{ request()->routeIs('admin.notifications.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.dashboard') }}" class="nav-link">
                        <i class="fa fa-dashboard"></i>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.notifications.send.form') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.send.form') }}" class="nav-link">
                        <i class="fa fa-send"></i>
                        <span class="title">Send Notification</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.notifications.advanced.form') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.advanced.form') }}" class="nav-link">
                        <i class="fa fa-rocket"></i>
                        <span class="title">Advanced Notification</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.notifications.history') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.history') }}" class="nav-link">
                        <i class="fa fa-history"></i>
                        <span class="title">History</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.notifications.stats') ? 'active' : '' }}">
                    <a href="{{ route('admin.notifications.stats') }}" class="nav-link">
                        <i class="fa fa-bar-chart"></i>
                        <span class="title">Statistics</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
       
		
		@if(APAuthHelp::check(['SUP_ADM']))
       


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