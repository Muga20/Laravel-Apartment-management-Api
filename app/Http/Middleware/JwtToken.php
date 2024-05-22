<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtToken
{
    /**
     * Decode the JWT token and retrieve the payload.
     *
     * @param  string  $tokenString
     * @return mixed|null
     */
    private function decodeToken($tokenString)
    {
        try {
            // Attempt to decode the token
            return JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            // Token has expired
            return response()->json(['error' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            // Token is invalid
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            // An unexpected error occurred while attempting to decode the token
            return response()->json(['error' => 'Failed to decode token'], 500);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Rate Limiting
        $ip = $request->ip();
        $rateLimitKey = 'ratelimit:' . $ip;
        $rateLimitAttempts = cache()->get($rateLimitKey, 0);

        if ($rateLimitAttempts >= 500) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }

        $tokenString = $request->bearerToken();

        if (!$tokenString) {
            return response()->json(['error' => 'JWT token not found in headers.'], 401);
        }

        $tokenContent = $this->decodeToken($tokenString);

        // Check if token content is a JSON response (indicating token error)
        if ($tokenContent instanceof JsonResponse) {
            return $tokenContent; // Return the JSON response
        }

        // Increment rate limit attempts
        cache()->put($rateLimitKey, $rateLimitAttempts + 1, now()->addMinutes(1));

        $request->attributes->set('jwt_payload', $tokenContent);

        $userId = $tokenContent->id;

        if (!$userId) {
            return response()->json(['error' => 'User ID not found in token'], 401);
        }

        $user = User::with(['company', 'detail'])->find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        // Check if the user's account is deactivated
        if ($user->status === 'token') {
            return response()->json(['error' => 'Account is deactivated'], 403);
        }

        $request->attributes->set('user', $user);
        $request->attributes->set('company', $user);

        return $next($request);
    }
}
