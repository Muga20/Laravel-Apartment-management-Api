<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('token');

        try {
            $user = JWTAuth::setToken($refreshToken)->toUser();

            $customClaims = [
                'user_id' => $user->id,
                'exp' => now()->addMinutes(config('jwt.ttl'))->timestamp,
            ];

            $newToken = JWTAuth::claims($customClaims)->refresh($refreshToken);

            return response()->json([
                'token' => $newToken,
            ]);

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Refresh token has expired'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to refresh token'], 401);
        }
    }
}
