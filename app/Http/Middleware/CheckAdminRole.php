<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdminRole
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
{
    // Debug 1: Check if user exists
    dd('Step 1 - Check user', [
        'user_exists' => $request->user() ? 'YES' : 'NO',
        'user_object' => $request->user(),
        'user_id' => $request->user() ? $request->user()->id : 'No user',
        'user_email' => $request->user() ? $request->user()->email : 'No user',
    ]);
    
    // Get the required roles from the route
    $roles = $this->getRequiredRoleForRoute($request->route());
    
    // Debug 2: Check what roles are required from route
    dd('Step 2 - Required roles from route', [
        'required_roles' => $roles,
        'route_name' => $request->route() ? $request->route()->getName() : 'No route',
        'route_uri' => $request->route() ? $request->route()->uri() : 'No route',
        'route_actions' => $request->route() ? $request->route()->getAction() : 'No route',
    ]);
    
    // Debug 3: Check user's actual roles
    if ($request->user()) {
        dd('Step 3 - User roles check', [
            'user_hasRole_method_exists' => method_exists($request->user(), 'hasRole') ? 'YES' : 'NO',
            'user_roles_property' => $request->user()->roles ?? 'No roles property',
            'user_role_id' => $request->user()->role_id ?? 'No role_id',
            'user_role' => $request->user()->role ?? 'No role',
            
            // Try to get roles relation if exists
            'user_roles_relation_exists' => method_exists($request->user(), 'roles') ? 'YES' : 'NO',
            'user_roles_relation_data' => method_exists($request->user(), 'roles') ? $request->user()->roles()->get() : 'No roles method',
            
            // Try hasRole with the required roles
            'hasRole_result_with_required_roles' => method_exists($request->user(), 'hasRole') ? $request->user()->hasRole($roles) : 'Method not exists',
        ]);
    }
    
    // Check if a role is required for the route, and
    // if so, ensure that the user has that role.
    if ($request->user()->hasRole($roles) || !$roles) {
        return $next($request);
    }
    return response()->view('admin.errors.401', [], 401);
}

private function getRequiredRoleForRoute($route)
{
    $actions = $route->getAction();
    
    // Debug the actions to see what's available
    dd('getRequiredRoleForRoute - Debug', [
        'all_actions' => $actions,
        'allowed_roles_key_exists' => isset($actions['allowed_roles']) ? 'YES' : 'NO',
        'allowed_roles_value' => isset($actions['allowed_roles']) ? $actions['allowed_roles'] : 'NOT SET',
        'middleware_key' => isset($actions['middleware']) ? $actions['middleware'] : 'No middleware key',
    ]);
    
    return isset($actions['allowed_roles']) ? $actions['allowed_roles'] : null;
}

}
