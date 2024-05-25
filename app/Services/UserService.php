<?php

namespace App\Services;

use App\Models\Units;
use App\Models\User;
use App\Models\UserDetails;
use App\Traits\ImageTrait;
use App\Traits\RoleRequirements;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserService
{
    use RoleRequirements;
    use ImageTrait;

    private function getRequiredRoles($tier)
    {
        return $this->rolesThatMustHave($tier);
    }

    public function createUser(Request $request, $data)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users',
                'first_name' => 'string|required',
                'last_name' => 'string|required',
            ]);

            $userRoles = $data['userRoles'];
            $requiredRolesLvTwo = $this->rolesThatMustHave(2);

            if (count(array_intersect($requiredRolesLvTwo, $userRoles)) === 0) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $company = $data['company'];
            $user = $data['user'];

            $userCount = $company->users()->count();

            if ($userCount >= 20) {
                return response()->json(['error' => 'Maximum number of users reached for this company'], 403);
            }

            $compressionService = new CompressionService();
            $compressedEmail = $compressionService->compressAttribute($request->input('email'));

            $existingUser = User::where('email', $compressedEmail)->first();

            if ($existingUser) {
                return response()->json(['error' => 'Email already exists.'], 400);
            }

            $user = new User();

            $user->fill([
                'email' => $request->input('email'),
                'company_id' => $request->input('company_id'),
                'uuid' => Str::uuid(),
            ]);

            $user->authType = 'otp';
            $user->status = 'inactive';
            $user->company_id = $company->id;

            $user->save();

            $usernameBase = substr(Str::slug($request->input('first_name')), 0, 3);
            $username = $this->generateUniqueUsername($usernameBase);

            UserDetails::create([
                'user_id' => $user->id,
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'username' => $username,
                'is_verified' => 'false',
            ]);

            RoleService::assignDefaultRole($user, 'user');

            return response()->json(['success' => 'User created successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create user: ' . $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function generateUniqueUsername($usernameBase)
    {
        $username = $usernameBase . '-' . Str::random(5);

        while (UserDetails::where('username', $username)->exists()) {
            $username = $usernameBase . '-' . Str::random(5);
        }

        return $username;
    }

    public function updateUser(Request $request, $data)
    {

        try {

            $userData = $request->only([
                'first_name', 'middle_name', 'last_name', 'date_of_birth', 'id_number',
                'username', 'address', 'phone', 'gender', 'location', 'about_the_user',
            ]);

            $this->updateImage($request, $userData, 'profileImage');

            $company_id = $data['company']->id;
            $user = $data['user'];

            $userData['company_id'] = $company_id;
            $userData['status'] = $user->status;

            $userDetails = UserDetails::where('user_id', $user->id)->firstOrFail();

            $existingIdNumberTenant = UserDetails::where('id_number', $request->input('id_number'))->first();
            if ($existingIdNumberTenant && $existingIdNumberTenant->user_id != $user->id) {
                return response()->json(['error' => 'ID number already exists'], 400);
            }

            $dob = new \DateTime($request->input('date_of_birth'));
            $today = new \DateTime();
            $age = $dob->diff($today)->y;

            if ($age < 18) {
                return response()->json(['error' => 'User must be 18 years or older'], 400);
            }

            $userDetails->update($userData);

            return response()->json(['success' => 'User details updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    public function storeTenant(Request $request, $dummy, $unit)
    {
        list($user, $company, $userRoles) = $this->getUserAndCompany($request);

        try {
            $compressionService = new CompressionService();
            $compressedEmail = $compressionService->compressAttribute($request->input('email'));

            $existingEmailTenant = User::where('email', $compressedEmail)->first();
            if ($existingEmailTenant) {
                return response()->json(['error' => 'Email already exists'], 400);
            }

            $existingIdNumberTenant = UserDetails::where('id_number', $request->input('id_number'))->first();
            if ($existingIdNumberTenant) {
                return response()->json(['error' => 'ID number already exists'], 400);
            }

            $dob = new \DateTime($request->input('date_of_birth'));
            $today = new \DateTime();
            $age = $dob->diff($today)->y;

            if ($age < 18) {
                return response()->json(['error' => 'Tenant must be 18 years or older'], 400);
            }

            $company_id = $company->id;

            $newTenant = new User();

            $newTenant->authType = 'otp';
            $newTenant->uuid = Str::uuid();
            $newTenant->status = 'active';
            $newTenant->company_id = $company_id;
            $newTenant->email = $request->input('email');
            $newTenant->two_fa_status = 'inactive';

            $newTenant->save();

            RoleService::assignDefaultRole($newTenant, 'user');
            RoleService::assignDefaultRole($newTenant, 'tenant');

            $usernameBase = substr(Str::slug($request->input('first_name')), 0, 3);
            $username = $this->generateUniqueUsername($usernameBase);

            $tenant = new UserDetails();
            $tenant->user_id = $newTenant->id;
            $tenant->first_name = $request->input('first_name');
            $tenant->middle_name = $request->input('middle_name');
            $tenant->last_name = $request->input('last_name');
            $tenant->phone = $request->input('phone');
            $tenant->is_verified = "false";
            $tenant->username = $username;
            $tenant->date_of_birth = $request->input('date_of_birth');
            $tenant->id_number = $request->input('id_number');
            $tenant->country = $request->input('country');
            $tenant->gender = $request->input('gender');

            $tenant->save();

            try {
                $unit = Units::where('unit_name', $unit)->firstOrFail();

                if ($unit->tenant_id) {
                    return response()->json(['error' => 'This unit is already occupied.'], 400);
                }

                $unit->update([
                    'tenant_id' => $newTenant->id,
                    'status' => 'occupied',
                ]);

                return response()->json(['success' => 'Tenant rented successfully.'], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to rent tenant. Please try again.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create tenant'], 500);
        }
    }
}
