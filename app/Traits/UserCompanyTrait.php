<?php

namespace App\Traits;

use App\Models\User;

trait UserCompanyTrait
{
    private function getUserAndCompany($request)
    {
        $payload = $request->attributes->get('jwt_payload');
        $userId = $payload->id;

        $user = User::with(['company', 'detail', 'channelUsers'])->find($userId);

        if (!$user || !$user->company || !$user->detail) {
            return response()->json(['error' => 'User or company not found'], 404);
        }

        $userRoles = $user->roles()->pluck('name');

        if ($userRoles->isNotEmpty()) {
            $company = $user->company;
            return [$user, $company, $userRoles->toArray()];
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
