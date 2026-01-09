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
        
        // ERROR: $requiredRoles is undefined! You're using $roles above
        if ($requiredRoles === ['SUP_ADM'] || $requiredRoles === ['SUB_ADM']) { // <-- ERROR
            // ERROR: $userRole is undefined!
            if ($userRole->role_abbreviation === 'admin') { // <-- ERROR
                return $next($request);
            }
        }
        
        // ERROR: $request->user() might be null
        if ($request->user()->hasRole($roles) || !$roles) { // <-- POTENTIAL ERROR
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
