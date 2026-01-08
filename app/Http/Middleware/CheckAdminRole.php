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
    // public function handle($request, Closure $next)
    // {

        
    //     // Get the required roles from the route
    //     $roles = $this->getRequiredRoleForRoute($request->route());

        
    //     // Check if a role is required for the route, and
    //     // if so, ensure that the user has that role.
    //     if ($request->user()->hasRole($roles) || !$roles) {
    //         return $next($request);
    //     }
    //     return response()->view('admin.errors.401', [], 401);
    // }


    public function handle($request, Closure $next)
{
    $roles = $this->getRequiredRoleForRoute($request->route());

    if (!$request->user()) {
        return response()->view('admin.errors.401', [], 401);
    }

    // Extract role value from Role object
    $userRole = $request->user()->role->code ?? null;
    // OR ->name if that is your column

    if (!$roles || in_array($userRole, $roles)) {
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
