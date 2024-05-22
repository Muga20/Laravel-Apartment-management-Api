<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResetPasswordController extends Controller
{
    public function updateSecurity(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $request->validate([
                'password' => 'required|min:6',
                'newPassword' => 'required|min:6',
                'confirmPassword' => 'required|same:newPassword',
            ]);

            $password = $request->input('password');
            $newPassword = $request->input('newPassword');

            // Check if the newPassword key is present in the data array
            if (!$newPassword) {
                return response()->json(['error' => 'Invalid request data.'], 400);
            }

            $user = $data['user'];

            // Validation
            // Note: Validation can also be performed using FormRequest classes

            if ($user['password'] === null) {
                $user->update([
                    'password' => Hash::make($newPassword),
                    'authType' => 'password',
                    'otp' => null,
                ]);
            } else {
                // Additional validation for existing password
                if (!Hash::check($password, $user['password'])) {
                    return response()->json(['error' => 'Current password is incorrect.'], 400);
                }

                $user->update([
                    'password' => Hash::make($newPassword),
                    'status' => 'active',
                ]);
            }

            // Mail::to($user->email)->send(new PasswordUpdated($user));

            return response()->json(['success' => 'Password updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
        }
    }

    public function updateEmailSecurity(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->loadCommonData($request);

            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->input('email');

            $user = $data['user']; // Assuming the user data is passed in the request

            // Validation
            // Note: Validation can also be performed using FormRequest classes

            // Check if email exists
            $emailExists = User::where('email', $email)->exists();

            if ($emailExists) {
                return response()->json(['error' => 'Email already in use.'], 400);
            }

            $user->update([
                'email' => $email,
                'status' => 'active',
            ]);

            // Mail::to($user->email)->send(new EmailUpdated($user->id));

            DB::commit();

            return response()->json(['success' => 'Email updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to update your email: ' . $e->getMessage()], 500);
        }
    }

    public function sendResetPasswordEmail(Request $request)
    {
        try {
            $data = $request->all(); // Assuming data is sent as JSON

            // Find user by email
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            if ($user->two_fa_status === 'active') {
                $user->two_fa_status === 'inactive';
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user, ['exp' => now()->addHours(24)->timestamp]);

            // Mail::to($user->email)->send(new ForgotPassword($user, $token));

            return response()->json(['message' => 'Password reset email sent successfully. Please check your inbox.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send password reset email: ' . $e->getMessage()], 500);
        }
    }

    public function passwordReset(Request $request, $token)
    {
        // This method can remain unchanged as it's returning a view for resetting the password
        return view('auth.reset-password', compact('token'));
    }

    public function newPassword(Request $request, $token)
    {
        try {
            $tokenPayload = $this->decodeToken($token);
            $userId = $tokenPayload->get('sub');

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found or invalid token.'], 404);
            }

            $data = $request->all(); // Assuming data is sent as JSON

            // Validation
            // Note: Validation can also be performed using FormRequest classes

            $user->password = Hash::make($data['newPassword']);

            $user->save();

            return response()->json(['success' => 'Password updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
        }
    }
}
