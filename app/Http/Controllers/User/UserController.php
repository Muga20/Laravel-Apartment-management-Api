<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Roles;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function allUsers(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);
            $allRoles = Roles::all();

            // Fetch users with details
            $allUsers = $this->search($request)->with('detail')->with('company')->paginate(3);
            $allUsers->appends(['keyword' => $request->input('keyword')]);

            return response()->json(['users' => $allUsers, 'roles' => $allRoles], 200);
        } catch (\Exception $e) {
            // Return a JSON response with the error message and a 500 status code
            return response()->json(['error' => 'Failed to fetch users: ' . $e->getMessage()], 500);
        }
    }

    public function getAuthenticatedUser(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $user = $data['user'];
            $userRoles = $data['userRoles'];

            return response()->json(['user' => $user, 'roles' => $userRoles], 200);
        } catch (\Exception $e) {

            // Return a JSON response with the error message and a 500 status code
            return response()->json(['error' => 'Failed to your profile: ' . $e->getMessage()], 500);
        }
    }



    public function storeNewUser(Request $request)
    {
        $data = $this->loadCommonData($request);

        $response = $this->userService->createUser($request , $data );

        if ($response instanceof User) {
            $user = $response;
            try {
                // Uncomment the following lines once you have the necessary imports and implementations
                // $company = Company::findOrFail($validatedData['company_id']);
                // Mail::to($validatedData['email'])->send(new NewAccount($user, $company));
                return response()->json(['success' => 'User Created Successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'Company not found.'], 404);
            }
        } else {
            return $response;
        }
    }

    public function editUser(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.User.profile.profile', $data);
    }

    public function userSettings(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.User.profile.settings', $data);
    }

    public function userSecurity(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.User.profile.security', $data);
    }

    public function userDeactivate(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.User.profile.deactivate', $data);
    }

    public function updateUser(Request $request)
    {
        $data = $this->loadCommonData($request);

        return $this->userService->updateUser($request, $data);
    }
    public function setAuthType(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $request->validate([
                'auth_type' => 'required|in:otp,password',
            ]);

            $authType = $request->input('auth_type');
            $user = $data['user'];
            $company = $data['company']->name;

            // Check if user is trying to set OTP as auth type while two_fa_status is active
            if ($authType === 'otp') {
                if ($user->two_fa_status === 'active') {
                    return response()->json(['error' => 'Two-factor authentication is already active. Please deactivate it first before changing to OTP.'], 400);
                }
            }

            // Check if user is trying to set password without a password being set
            if ($authType === 'password') {
                if (!$user->password) {
                    return response()->json(['error' => 'You need to set a password first on the security option.'], 400);
                }
            }

            $user->authType = $authType;
            $user->save();

            return response()->json(['message' => 'Authentication type updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update Authentication: ' . $e->getMessage()], 500);
        }
    }

    public function twoFaSetup(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $smsNumber = $request->input('sms_number');
            $twoFaStatus = $request->input('two_fa_status');
            $user = $data['user'];

            if (!preg_match('/^07\d{8}$/', $smsNumber)) {
                return response()->json(['error' => 'The phone number must start with "07" and be followed by 8 digits.'], 400);
            }

            $existingUser = User::where('sms_number', $smsNumber)->first();
            if ($existingUser && $existingUser->id !== $user->id) {
                return response()->json(['error' => 'This phone number is already in use.'], 400);
            }

            if ($user->authType === 'password') {
                if ($twoFaStatus === 'inactive') {
                    if (!$smsNumber) {
                        return response()->json(['error' => 'You need to provide an SMS number for two-factor authentication'], 400);
                    } else {
                        $user->sms_number = $smsNumber;
                    }
                    $user->two_fa_status = 'active';
                } elseif ($twoFaStatus === 'active') {
                    if ($user->two_fa_status === 'active') {
                        $user->two_fa_status = 'inactive';
                        $user->sms_number = null;
                    } else {
                        return response()->json(['error' => 'Two-factor authentication is already inactive.'], 400);
                    }
                }

                $user->save();

                return response()->json(['success' => 'Two-factor authentication settings updated successfully.'], 200);
            } else {
                return response()->json(['error' => 'Two-factor authentication can only be managed for users with password authentication.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update two-factor authentication settings: ' . $e->getMessage()], 500);
        }
    }

}
