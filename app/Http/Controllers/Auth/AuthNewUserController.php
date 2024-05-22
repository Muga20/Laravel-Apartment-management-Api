<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompressionService;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class AuthNewUserController extends Controller
{
    use AuthTrait;

    public function AuthNewUser(Request $request, $authLink)
    {
        return view("auth.authNewUser", compact('authLink'));
    }

    public function ConfirmAuthNewUser(Request $request, $authLink)
    {
        $email = $request->input('email');

        $compressionService = new CompressionService();
        $compressedEmail = $compressionService->compressAttribute($email);

        $user = User::where('email', $compressedEmail)->first();

        
        if (!$user) {
            return $this->handleAuthenticationFailure('User not found.');
        }

        if (!in_array($user->authType, ['otp', 'token'])) {
            if (empty($user->authType)) {
                return $this->handleAuthenticationFailure('Unauthorized - Invalid authentication type');
            }
        }

        $decodedAuthLink = base64_decode($authLink);
        $parts = explode(':', $decodedAuthLink);
        $receivedToken = $parts[0];
        $receivedUniqueId = $parts[1] ?? null;

        if ($user->otp === $receivedToken) {


            $user->update(['otp' => null,]);

            if (!$user->password) {
                return redirect()->route('userSettings', $user->company->slug)->with('error', 'You need to set a password first.');
            }

            // Handle successful authentication
            return $this->handleAuthentication($user, $request);
        } else {
            return $this->handleAuthenticationFailure('Unauthorized - Invalid credentials');
        }
    }


}
