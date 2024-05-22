<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use App\Models\User; // Import the User model

class RedirectIfAuthenticated extends BaseMiddleware
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
        $tokenString = $request->cookie('jwt_token');

        if ($tokenString) {
            try {
                $tokenContent = JWTAuth::decode(new Token($tokenString));

                $userId = $tokenContent->get('user_id');
                $companyId = $tokenContent->get('company_id');

                $user = User::find($userId);

                if ($user && $user->company_id == $companyId) {
                    $companyName = $user->company->slug;
                    return Redirect::route('dashboard', ['company' => $companyName]);
                }

            } catch (\Exception $e) {
                return response()->view('error.error500', ['error' => 'Contact the admin'], 500);
            }
        }
        return $next($request);
    }
}
