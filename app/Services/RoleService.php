<?php

// RoleService.php

namespace App\Services;

use App\Models\User;
use App\Models\UseRoles;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public static function assignDefaultRole(User $user, string $defaultRoleName)
    {
        try {
            $defaultRole = DB::table('roles')->where('name', $defaultRoleName)->first();

            if ($defaultRole) {
                $userRole = new UseRoles();
                $userRole->user_id = $user->id;
                $userRole->role_id = $defaultRole->id;
                $userRole->save();
            } else {
                throw new \Exception('Default role not found.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign default role: ' . $e->getMessage());
        }
    }
}
