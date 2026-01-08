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

    //     dd('callig');
    //     // Get the required roles from the route
    //     $roles = $this->getRequiredRoleForRoute($request->route());
    //     // Check if a role is required for the route, and
    //     // if so, ensure that the user has that role.
    //     if ($request->user()->hasRole($roles) || !$roles) {
    //         return $next($request);
    //     }
    //     return response()->view('admin.errors.401', [], 401);
    // }


    public function handle(Request $request, Closure $next)
    {
        // Use dump() instead of dd() if you want to debug without stopping
        dump('Middleware called');
        dump('User:', $request->user());
        dump('Route:', $request->route());
        
        // Get the required roles from the route
        $roles = $this->getRequiredRoleForRoute($request->route());
        dump('Required roles:', $roles);
        
        // Check if a role is required for the route, and
        // if so, ensure that the user has that role.
        if ($request->user()->hasRole($roles) || !$roles) {
            dump('Access granted');
            return $next($request);
        }
        
        dump('Access denied');
        return response()->view('admin.errors.401', [], 401);
    }

    private function getRequiredRoleForRoute($route)
    {
        $actions = $route->getAction();
        return isset($actions['allowed_roles']) ? $actions['allowed_roles'] : null;
    }

}
