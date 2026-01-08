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

    private function normalizeRole($role)
{
    $mapping = [
        'SUP_ADM' => 'admin',
        'SUB_ADM' => 'moderator', // or whatever corresponds
        'admin' => 'admin',
        'moderator' => 'moderator',
    ];
    
    return $mapping[$role] ?? $role;
}

public function handle($request, Closure $next)
{
    $requiredRoles = $this->getRequiredRoleForRoute($request->route());
    
    if (!$requiredRoles) {
        return $next($request);
    }
    
    $user = $request->user();
    
    if (!$user || !$user->role_id) {
        return response()->view('admin.errors.401', [], 401);
    }
    
    $userRole = DB::table('roles')->where('id', $user->role_id)->first();
    
    if (!$userRole) {
        return response()->view('admin.errors.401', [], 401);
    }
    
    $requiredRoles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
    
    foreach ($requiredRoles as $requiredRole) {
        $normalizedRole = $this->normalizeRole($requiredRole);
        
        if ($userRole->role_abbreviation === $normalizedRole || 
            $userRole->role_name === $requiredRole || 
            $userRole->id == $requiredRole) {
            return $next($request);
        }
    }
    
    return response()->view('admin.errors.401', [], 401);
}

    private function getRequiredRoleForRoute($route)
    {
        $actions = $route->getAction();
        return isset($actions['allowed_roles']) ? $actions['allowed_roles'] : null;
    }

}
