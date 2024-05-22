<?php

namespace App\Services;

use App\Models\User;
use App\Traits\RoleRequirements;
use App\Traits\UserCompanyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CompanyOwner
{
    use UserCompanyTrait;
    use RoleRequirements;

    private function getRequiredRoles($tier)
    {
        return $this->rolesThatMustHave($tier);
    }

    public function createUser(array $userData, Request $request)
    {
        list($user, $company, $userRoles) = $this->getUserAndCompany($request);

        $requiredRolesLvTwo = $this->rolesThatMustHave(2);

        if (count(array_intersect($requiredRolesLvTwo, $userRoles)) === 0) {
            return response()->view('error.error403', ['error' => 'Unauthorized'], 403);
        }

        try {
            if (User::where('email', $userData['email'])->exists()) {
                return back()->with('error', 'Email already exists');
            }

            $user = new User();

            $user->fill($userData);
            $user->authType = 'otp';
            $user->status = 'inactive';
            $user->theme = '#0000';

            $username = Str::random(8);
            while (User::where('username', $username)->exists()) {
                $username = Str::random(8);
            }

            $user->save();
            RoleService::assignDefaultRole($user, 'staff');
            return $user;

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }


}




