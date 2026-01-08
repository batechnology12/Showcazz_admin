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

        
        // Get the required roles from the route
        $roles = $this->getRequiredRoleForRoute($request->route());

        dd([
        'user_role_column' => $request->user()->role ?? 'NO role column',
        'has_roles_relation' => method_exists($request->user(), 'roles'),
        'roles_relation' => method_exists($request->user(), 'roles')
            ? $request->user()->roles->pluck('name')
            : 'NO RELATION',
        'required_roles' => $roles
    ]);
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
        return isset($actions['allowed_roles']) ? $actions['allowed_roles'] : null;
    }

}
