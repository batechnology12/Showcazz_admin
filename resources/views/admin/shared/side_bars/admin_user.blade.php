@if(APAuthHelp::checkPermission('manage_admin_users') || APAuthHelp::checkPermission('manage_roles'))
<li class="heading">
    <h3 class="uppercase">Admin Management</h3>
</li>
<li class="nav-item {{ request()->routeIs('list.admin.users') || request()->routeIs('create.admin.user') || request()->routeIs('admin.roles.*') ? 'active open' : '' }}"> 
    <a href="javascript:;" class="nav-link nav-toggle"> <i class="icon-user"></i> <span class="title">Admins & Roles</span> <span class="arrow"></span> </a>
    <ul class="sub-menu">
        @if(APAuthHelp::checkPermission('manage_admin_users'))
        <li class="nav-item {{ request()->routeIs('list.admin.users') ? 'active' : '' }}"> <a href="{{ route('list.admin.users') }}" class="nav-link "> <i class="icon-user"></i> <span class="title">List All Admin Users</span> </a> </li>
        <li class="nav-item {{ request()->routeIs('create.admin.user') ? 'active' : '' }}"> <a href="{{ route('create.admin.user') }}" class="nav-link "> <i class="icon-users"></i> <span class="title">Add Admin User</span> </a> </li>
        @endif

        @if(APAuthHelp::checkPermission('manage_roles'))
        <li class="nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"> <a href="{{ route('admin.roles.index') }}" class="nav-link "> <i class="icon-shield"></i> <span class="title">Roles & Permissions</span> </a> </li>
        @endif
    </ul>
</li>
@endif