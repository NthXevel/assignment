<?php
// Author: Clive Lee Ee Xuan
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->hasPermission($permission)) {
            abort(403, 'Insufficient permissions');
        }
        
        return $next($request);
    }
}
