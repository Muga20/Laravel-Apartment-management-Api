<?php

namespace App\Services;

use App\Jobs\GenerateAvatar;
use App\Jobs\UploadImage;
use App\Models\Units;
use App\Models\User;
use App\Models\UserDetails;
use App\Traits\HandlesNotificationCreation;
use App\Traits\ImageTrait;
use App\Traits\RoleRequirements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class UserService
{
    use RoleRequirements;
    use ImageTrait;
    use HandlesNotificationCreation;

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
                'company_id' => 'required|exists:companies,id',
            ]);

            $userRoles = $data['userRoles'];
            $requiredRolesLvTwo = $this->rolesThatMustHave(2);

            if (count(array_intersect($requiredRolesLvTwo, $userRoles)) === 0) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $company = $data['company'];
            $authUser = $data['user'];
            $userRoles = $data['userRoles'];

            $userCount = $company->users()->count();

            if ($userCount >= 300) {
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
            $user->company_id = $request->input('company_id');

            $user->save();

            $usernameBase = substr(Str::slug($request->input('first_name')), 0, 3);
            $username = $this->generateUniqueUsername($usernameBase);

            $userDetails = UserDetails::create([
                'user_id' => $user->id,
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'username' => $username,
                'is_verified' => 'false',
            ]);

            if (!$userDetails->profileImage) {
                Queue::push(new GenerateAvatar($userDetails));
            }

            RoleService::assignDefaultRole($user, 'user');

            $this->createNotification($user, $authUser, $userRoles);

            return $user;

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
            // Validate request data
            $validatedData = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
                'id_number' => 'nullable|string|max:255',
                'username' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:15',
                'gender' => 'nullable|string|in:male,female,other',
                'location' => 'nullable|string|max:255',
                'about_the_user' => 'nullable|string',
                //'profileImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // Get user and company information from data
            $company_id = $data['company']->id;
            $user = $data['user'];

            // Prepare user data for updating
            $userData = array_merge($validatedData, [
                'company_id' => $company_id,
                'status' => $user->status,
            ]);

            // Check for existing user details
            $userDetails = UserDetails::where('user_id', $user->id)->firstOrFail();

            // Check if ID number already exists for a different user
            $existingIdNumberTenant = UserDetails::where('id_number', $request->input('id_number'))->first();
            if ($existingIdNumberTenant && $existingIdNumberTenant->user_id != $user->id) {
                return response()->json(['error' => 'ID number already exists'], 400);
            }

            // Convert date format for date of birth
            if ($request->has('date_of_birth')) {
                $dateOfBirth = date('Y-m-d', strtotime($request->input('date_of_birth')));
                $userData['date_of_birth'] = $dateOfBirth;

                // Calculate user's age and validate
                $dob = new \DateTime($dateOfBirth);
                $today = new \DateTime();
                $age = $dob->diff($today)->y;

                if ($age < 18) {
                    return response()->json(['error' => 'User must be 18 years or older'], 400);
                }
            }

            // Update user details without the profile image
            unset($userData['profile_image']);
            $userDetails->update($userData);

            return response()->json(['success' => 'User details updated successfully.'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
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

            $userDetails = new UserDetails();
            $userDetails->user_id = $newTenant->id;
            $userDetails->first_name = $request->input('first_name');
            $userDetails->middle_name = $request->input('middle_name');
            $userDetails->last_name = $request->input('last_name');
            $userDetails->phone = $request->input('phone');
            $userDetails->is_verified = "false";
            $userDetails->username = $username;
            $userDetails->date_of_birth = $request->input('date_of_birth');
            $userDetails->id_number = $request->input('id_number');
            $userDetails->country = $request->input('country');
            $userDetails->gender = $request->input('gender');

            $userDetails->save();

            GenerateAvatar::dispatch($userDetails)->onQueue('avatars');

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
