<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPassword;
use App\Models\User;
use App\Services\CompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
            $data = $request->all();

            $compressionService = new CompressionService();
            $compressedEmail = $compressionService->compressAttribute($data['email']);

            $user = User::where('email', $compressedEmail)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            if ($user->two_fa_status === 'active') {
                $user->two_fa_status === 'inactive';
            }

            // Generate JWT token
            $tokenG = base64_encode($user->id . '|' . now()->addHours(24)->timestamp);

            $token = 'http://localhost:5173/auth/new-password/' . str_replace('.', '_', $tokenG);

            Mail::to($user->email)->send(new ForgotPassword($user, $token));

            return response()->json(['success' => 'Password reset email sent successfully. Please check your inbox.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send password reset email: ' . $e->getMessage()], 500);
        }
    }

    public function newPassword(Request $request, $token)
    {
        try {

            $request->validate([
                'newPassword' => 'required',
                'authStatus' => 'required',
            ]);

            $decodedToken = base64_decode(str_replace('_', '.', $token));

            list($userId, $timestamp) = explode('|', $decodedToken);

            if (time() > $timestamp) {
                return response()->json(['error' => 'Token has expired.'], 400);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found or invalid token.'], 404);
            }

            $newPassword = $request->input('newPassword');
            $authStatus = $request->input('authStatus');

            $data = $this->loadCommonData($request);

            if (isset($authStatus) && $authStatus === 'authOn') {

                $user->password = Hash::make($newPassword);
                $user->save();

                return response()->json(['success' => 'Password updated successfully.'], 200);

            } elseif (isset($authStatus) && $authStatus === 'authOff') {

                $user->password = Hash::make($newPassword);
                $user->save();

                return $this->handleAuthentication($user, $request);

            } else {
                return response()->json(['error' => 'Invalid authentication status.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
        }
    }

}
