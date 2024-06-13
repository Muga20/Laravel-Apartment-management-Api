<?php

namespace App\Traits;

use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthTrait
{

    protected function handleAuthentication($user, $request)
    {
        $company = $user->company;

        if (!$company) {
            return $this->handleAuthenticationFailure('Unauthorized - Invalid company');
        }

        $roles = $user->roles()->pluck('name');

        if ($roles->isEmpty()) {
            return $this->handleAuthenticationFailure('Unauthorized - User does not have a valid role');
        }


        // Generate access token
        $accessToken = $this->generateJwtToken($user);

        // Generate refresh token (assuming you have a separate function for this)
        $refreshToken = $this->generateRefreshToken($user);


        return response()->json([
            'message' => 'Success',
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);

        // if ($roles->contains('admin') || $roles->contains('sudo') || $roles->contains('landlord')) {
        //     return response()->json([
        //         'message' => 'Success',
        //         'token' => $accessToken,
        //         'refresh_token' => $refreshToken,
        //     ]);
        // }

        // if ($roles->contains('tenant') || $roles->contains('user') || $roles->contains('agent')) {
        //     $tenantId = $user->id;
        //     $unit = Units::where('tenant_id', $tenantId)->first();

        //     if ($unit && $unit->home) {
        //         return response()->json([
        //             'message' => 'Success',
        //             'token' => $accessToken,
        //             'refresh_token' => $refreshToken,
        //         ]);
        //     } else {
        //         return response()->json([
        //             'error' => 'Not Allowed. Please Contact The Admin on the issue',
        //         ], 500);
        //     }
        // }

        return response()->json(['error' => 'Unauthorized Access'], 403);
    }

    private function handleAuthenticationFailure($errorMessage)
    {
        return response()->json(['error' => $errorMessage], 401);
    }

    private function updateLastLogin($user, $location)
    {
        try {
            $user->last_login_at = now();
            $user->last_login_location = $location;
            $user->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update last login: ' . $e->getMessage()], 500);
        }
    }

    protected function generateJwtToken($user)
    {
        $customClaims = [
            'user_id' => $user->id,
            'exp' => now()->addMinutes(config('jwt.ttl'))->timestamp,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        return $token;
    }

    protected function generateRefreshToken($user)
    {
        try {
            $customClaims = [
                'user_id' => $user->id,
                'exp' => now()->addMinutes(config('jwt.refresh_ttl'))->timestamp,
            ];

            $refreshToken = JWTAuth::claims($customClaims)->fromUser($user);

            $user->refreshToken = $refreshToken;
            $user->save();

            return $refreshToken;
        } catch (\Exception $e) {
            return null;
        }
    }

}
