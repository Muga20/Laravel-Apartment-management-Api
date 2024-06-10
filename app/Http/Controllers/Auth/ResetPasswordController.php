<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPassword;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use AuthTrait;

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
            $data = $request->all();

            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            if ($user->two_fa_status === 'active') {
                $user->two_fa_status = 'inactive';
                $user->save();
            }

            // Generate unique token
            $token = str_replace('.', '_', base64_encode($user->id . '|' . now()->timestamp . '|' . Str::random(40)));

            PasswordResetToken::create([
                'email' => $user->email,
                'token' => $token,
            ]);

            $resetLink = 'http://localhost:5173/auth/new-password/' . $token;

            Mail::to($user->email)->queue((new ForgotPassword($user, $resetLink)));

            return response()->json(['success' => 'Password reset email sent successfully. Please check your inbox.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send password reset email: ' . $e->getMessage()], 500);
        }
    }

    public function newPassword(Request $request, $token)
    {
        try {
            $request->validate([
                'newPassword' => 'required|min:6',
                'confirmPassword' => 'required|same:newPassword',
                'authStatus' => 'required',
            ]);

            $newPassword = $request->input('newPassword');
            $authStatus = $request->input('authStatus');

            if (isset($authStatus) && $authStatus === 'authOn') {

                $data = $this->loadCommonData($request);
                $loggedInUser = $data['user'];

                $loggedInUser->password = Hash::make($newPassword);
                $loggedInUser->save();

                return response()->json(['success' => 'Password updated successfully.'], 200);

            } elseif (isset($authStatus) && $authStatus === 'authOff') {

                // Find the token in the database
                $tokenData = PasswordResetToken::where('token', $token)->first();

                if (!$tokenData) {
                    return response()->json(['error' => 'Invalid or expired token.'], 400);
                }

             
                $user = User::where('email', $tokenData->email)->first();

                if (!$user) {
                    return response()->json(['error' => 'User not found.'], 404);
                }

                $user->password = Hash::make($newPassword);
                $user->save();

                // Invalidate the token
                $tokenData->delete();

                return $this->handleAuthentication($user, $request);
            } else {
                return response()->json(['error' => 'Invalid authentication status.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
        }
    }

}
