<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OTPemail;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Traits\AuthTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;

class LoginController extends Controller
{
    use AuthTrait;

    public function authenticate(Request $request)
    {
        $email = $request->input('email');

        $user = User::where('email', $email)->orWhere('sms_number', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email not found. Please contact admin.'], 404);
        }

        $authType = $user->authType;
        $encodedAuthType = base64_encode($authType);

        return response()->json(['authType' => $encodedAuthType]);
    }

    public function receiveOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $otp = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $email = $request->input('email');

        try {
            Mail::to($email)->send(new OTPemail($otp));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please try again.'], 500);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $encodedOTP = base64_encode($otp);
            $user->otp = $encodedOTP;
            $user->save();

            return response()->json(['success' => true, 'message' => 'OTP sent successfully.'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    public function authenticateOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $credentials = $request->only('email', 'otp');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid credentials'], 401);
        }

        if ($user->authType !== 'otp' && !empty($user->authType)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid authentication type. Please reach out to the admin.'], 401);
        }

        $decodedOTP = base64_decode($user->otp);

        if ($decodedOTP !== $credentials['otp']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid OTP'], 401);
        }

        $user->otp = null;
        $user->save();

        // return $this->handleAuthentication($user, $request);

        // Implement the logic for successful authentication (e.g., generating a token, etc.)
        return response()->json(['success' => true, 'message' => 'Authentication successful', 'user' => $user], 200);
    }

    public function logInUser(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->orWhere('sms_number', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid credentials'], 401);
        }

        if ($user->authType !== 'password' && !empty($user->authType)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid authentication type. Please reach out to the admin'], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Invalid credentials'], 401);
        }

        if ($user->two_fa_status === 'active') {
            $twoFactorService = new TwoFactorService();
            $token = $twoFactorService->generateToken();
            $twoFactorService->sendToken($user->sms_number, $token);
            $user->two_factor_code = $token;
            $user->two_factor_expires_at = now()->addMinutes(config('auth.two_factor_expiration_minutes'));
            $user->save();
            return response()->json(['success' => true, 'message' => '2FA code sent successfully.'], 200);
        }

        if (empty($user->authType)) {
            $user->authType = 'password';
            $user->save();
        }

        return $this->handleAuthentication($user, $request);
    }

    // private function handleAuthentication($user, $request)
    // {
    //     return response()->json(['message' => 'Authentication successful'], 200);
    // }

    public function verifyTwoFaCode(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|integer',
            'linkedUserUuid' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $user = User::where('id', $request->input('linkedUserUuid'))
                ->where('two_fa_status', 'active')
                ->first();

            if ($user) {
                if ($user->two_factor_code === $request->two_factor_code) {
                    if ($user->two_factor_expires_at > Carbon::now()) {

                        // Clear two-factor code and expiration
                        $user->two_factor_code = null;
                        $user->two_factor_expires_at = null;

                        $user->save();

                        // Forget session data
                        $request->session()->forget('linkedUserUuid');

                        // Commit transaction
                        DB::commit();

                        // Handle successful authentication
                        return $this->handleAuthentication($user, $request);

                    } else {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'The verification code has expired.'], 400);
                    }
                } else {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Invalid verification code.'], 400);
                }
            } else {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'User not found or Two-Factor Authentication is not active.'], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function logout(Request $request)
    {
        $tokenString = $request->cookie('xx_tgk'); // Retrieve token from cookie

        if ($tokenString) {
            try {
                // Decode token to get user_id
                $token = new Token($tokenString);
                $tokenContent = JWTAuth::decode($token);
                $userId = $tokenContent['user_id'];

                // Clear the cookie
                $cookie = Cookie::forget('xx_tgk');

                // Return success JSON response
                return response()->json(['message' => 'Successfully logged out.'], 200)->withCookie($cookie);
            } catch (\Exception $e) {
                // Handle any exceptions
                return response()->json(['error' => 'Failed to logout: ' . $e->getMessage()], 500);
            }
        } else {
            // If token is not found in cookie, return failure JSON response
            return response()->json(['error' => 'No token found in cookie.'], 500);
        }
    }

}
