<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestLimitMiddleware
{
    protected $redis;

    public function __construct(\Predis\Client $redis)
    {
        $this->redis = $redis;
    }

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $key = "requests:{$ip}";

        $requestCount = $this->redis->get($key);

        $response = $next($request);

        $successful = $response->isSuccessful();

        if ($successful) {
            $this->redis->incr($key);
            $this->redis->expire($key, 60);
        }

        if (!$successful && $requestCount && $requestCount >= 10) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Too many requests. Please try again later.'], 429);
            }
            return response()->view('error.error500', ['error' => 'Too many requests. Please try again later.'], 500);
        }

        return $response;
    }
}
