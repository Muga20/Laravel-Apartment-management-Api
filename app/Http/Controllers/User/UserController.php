<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\NewAccount;
use App\Models\ChannelUsers;
use App\Models\Company;
use App\Models\NewUserSession;
use App\Models\Roles;
use App\Models\User;
use App\Models\UserDetails;
use App\Services\UserService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

            // Eager load the 'channel' relationship to fetch channels directly
            $userChannels = ChannelUsers::with('channel')->where('user_id', $user->id)->get();

            $channels = $userChannels->map(function ($channelUser) {
                return [
                    'channel_id' => $channelUser->channel->id,
                    'channel_name' => $channelUser->channel->channel_name,
                    'event' => $channelUser->channel->event,
                ];
            });

            return response()->json([
                'user' => $user,
                'roles' => $userRoles,
                'channels' => $channels,
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response with the error message and a 500 status code
            return response()->json(['error' => 'Failed to fetch user profile: ' . $e->getMessage()], 500);
        }
    }

    public function storeNewUser(Request $request)
    {
        $data = $this->loadCommonData($request);

        $response = $this->userService->createUser($request, $data);

        if ($response instanceof User) {
            try {
                $request->validate([
                    'company_id' => 'required|exists:companies,id',
                ]);

                $user = User::where('email', $request->input('email'))->first();

                $user->update(['' => null]);

                $company = Company::findOrFail($request->input('company_id'));

                //$this->sendNewAccountEmail($user, $company);

                return response()->json(['success' => 'User Created Successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'Company not found.'], 404);
            }
        } else {
            return $response;
        }
    }

    private function sendNewAccountEmail(User $user, Company $company)
    {
        //$authToken = sha1($user->id);

        $authLink = str_replace('.', '_', base64_encode($user->id . '|' . now()->timestamp . '|' . Str::random(40)));

        NewUserSession::create([
            'email' => $user->email,
            'token' => $authLink,
            //'otp_code' => $authToken,
        ]);

        $resetLink = 'http://localhost:5173/auth/new-account/' . $authLink;

        Mail::to($user->email)->queue(new NewAccount($user, $company, $resetLink));

    }

    public function updateUser(Request $request)
    {
        $data = $this->loadCommonData($request);

        return $this->userService->updateUser($request, $data);
    }

    public function updateProfileImage(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'profileImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // Get the authenticated user
            $user = auth()->user();

            // Find the user details
            $userDetails = UserDetails::where('user_id', $user->id)->firstOrFail();

            // Handle profile image upload
            if ($request->hasFile('profileImage')) {
                $image = $request->file('profileImage');
                $uploadedImageUrl = Cloudinary::upload($image->getRealPath())->getSecurePath();
                // Assuming $company is the instance where you want to store the image URL
                $userDetails->profileImage = $uploadedImageUrl;
                $userDetails->save();
            }

            // Return a success response
            return response()->json(['message' => 'Profile image updated successfully.'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
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
