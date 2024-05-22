<?php

namespace App\Http\Middleware;

use App\Traits\RoleRequirements;
use Closure;
use Illuminate\Http\Request;

class CheckRoles
{
    use RoleRequirements;

    public function handle(Request $request, Closure $next, $tierLevel)
    {
        try {

        } catch (\Exception $e) {
            return response()->view('error.error500', ['error' => 'Internal Server Error'], 500);
        }

        return $next($request);
    }
}
