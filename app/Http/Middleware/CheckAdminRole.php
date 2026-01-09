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
        // Check if a role is required for the route, and
        // if so, ensure that the user has that role.

        // In CheckAdminRole middleware, add this check:
        if ($requiredRoles === ['SUP_ADM'] || $requiredRoles === ['SUB_ADM']) {
            // Allow access if user has 'admin' role
            if ($userRole->role_abbreviation === 'admin') {
                return $next($request);
            }
        }
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
