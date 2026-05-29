<?php

namespace App\Http\Middleware;

use Closure;
use APAuthHelp;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        if (!APAuthHelp::checkPermission($permission)) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return response()->view('admin.errors.403', ['permission' => $permission], 403);
        }

        return $next($request);
    }
}
