<?php

namespace App\Traits;

trait HasRequiredRoles
{
    protected function hasRequiredRoles($data, $requiredRoles)
    {
        $userRoles = collect($data['userRoles']);

        return $userRoles->intersect($requiredRoles)->isNotEmpty();
    }

    protected function unauthorizedResponse()
    {
        return response()->view('error.error401',  ['error' => 'Unauthorized: Insufficient privileges to perform this action.'], 401);
    }
}
