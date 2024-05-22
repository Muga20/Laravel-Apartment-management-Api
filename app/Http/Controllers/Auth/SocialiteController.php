<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Traits\AuthTrait;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class SocialiteController extends Controller
{
    use AuthTrait;

    public function autoAuth($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function userAuth(Request $request, $provider)
    {
        try {
            $data = $this->loadCommonData($request);
            $user = $data['user'];
            $companySlug = $data['company']->slug;

            $request->session()->put(['user_id' => $user->id, 'company_slug' => $companySlug]);

            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            // Handle exceptions
        }
    }

    public function autoAuthCallBack($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            $request = request();

            $userId = $request->session()->pull('user_id');
            $companySlug = $request->session()->pull('company_slug');

            if ($userId) {
                $existingUser = User::where('id', $userId)
                    ->where('provider_id', $socialUser->id)
                    ->first();

                if ($existingUser) {
                    return redirect()->route('userSettings', $companySlug)->with('error', 'Details already exist.');
                }

                $userInstance = User::find($userId);

                $userInstance->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->id,
                    'provider_token' => $socialUser->token,
                ]);

                return redirect()->route('userSettings', $companySlug)->with('success', 'Login Type Successfully Updated.');
            } else {
                $user = User::where('provider_id', $socialUser->id)->first();

                if (!$user) {
                    return redirect()->route('login')->with('error', 'Account not found. Please contact the admin.');
                }

                return $this->handleAuthentication($user, $request);
            }
        } catch (\Exception $e) {
            $companySlug = $request->session()->get('company_slug', '');

            return redirect()->route('userSettings', $companySlug)->withErrors(['error' => 'Failed to Update Login Type: ' . $e->getMessage()]);
        }
    }
}
