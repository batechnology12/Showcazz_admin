<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Log for debugging
        Log::info('CheckAdminRole: Checking access for ' . $request->path());
        
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                Log::warning('CheckAdminRole: No authenticated user');
                return response()->view('admin.errors.401', [], 401);
            }
            
            $user = $request->user();
            Log::info('CheckAdminRole: User authenticated', [
                'id' => $user->id,
                'email' => $user->email,
                'role_id' => $user->role_id
            ]);
            
            // Get required roles from route
            $requiredRoles = $this->getRequiredRoleForRoute($request->route());
            Log::info('CheckAdminRole: Required roles', ['required_roles' => $requiredRoles]);
            
            // If no roles required, allow access
            if (!$requiredRoles) {
                Log::info('CheckAdminRole: No roles required - access granted');
                return $next($request);
            }
            
            // Ensure $requiredRoles is an array
            if (!is_array($requiredRoles)) {
                $requiredRoles = [$requiredRoles];
            }
            
            // Get user's role
            $userRole = DB::table('roles')->where('id', $user->role_id)->first();
            
            if (!$userRole) {
                Log::error('CheckAdminRole: Role not found for user', [
                    'user_id' => $user->id,
                    'role_id' => $user->role_id
                ]);
                return response()->view('admin.errors.401', [], 401);
            }
            
            Log::info('CheckAdminRole: User role', [
                'role_id' => $userRole->id,
                'abbreviation' => $userRole->role_abbreviation,
                'name' => $userRole->role_name
            ]);
            
            // Check if user has any required role
            foreach ($requiredRoles as $requiredRole) {
                if ($userRole->role_abbreviation === $requiredRole || 
                    $userRole->role_name === $requiredRole || 
                    $userRole->id == $requiredRole) {
                    Log::info('CheckAdminRole: Access granted - role match', [
                        'user_role' => $userRole->role_abbreviation,
                        'required_role' => $requiredRole
                    ]);
                    return $next($request);
                }
            }
            
            // No match found
            Log::warning('CheckAdminRole: Access denied', [
                'user_role_abbr' => $userRole->role_abbreviation,
                'user_role_name' => $userRole->role_name,
                'required_roles' => $requiredRoles
            ]);
            
            return response()->view('admin.errors.401', [], 401);
            
        } catch (\Exception $e) {
            Log::error('CheckAdminRole: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // In production, show 500 error
            return response()->view('admin.errors.500', [], 500);
        }
    }

    private function getRequiredRoleForRoute($route)
    {
        if (!$route) {
            return null;
        }
        
        $actions = $route->getAction();
        return isset($actions['allowed_roles']) ? $actions['allowed_roles'] : null;
    }
}