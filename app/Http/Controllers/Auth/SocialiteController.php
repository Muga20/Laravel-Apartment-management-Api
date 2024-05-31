<!--

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Traits\AuthTrait;

class SocialiteController extends Controller
{
    use AuthTrait;

    public function autoAuth($provider)
    {
        $authUrl = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
        return response()->json(['authUrl' => $authUrl]);
    }

    public function userAuth(Request $request, $provider)
    {
        try {
            $data = $this->loadCommonData($request);
            $user = $data['user'];

            $authUrl = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

            return response()->json(['authUrl' => $authUrl]);

        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'Failed to authenticate user'], 500);
        }
    }

    public function autoAuthCallBack($provider)
    {
        try {

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $request = request();


            $userId = $request->input('user_id');

            if ($userId) {
                $existingUser = User::where('id', $userId)
                    ->where('provider_id', $socialUser->id)
                    ->first();

                if ($existingUser) {
                    return response()->json(['error' => 'Details already exist.'], 400);
                }

                $userInstance = User::find($userId);

                $userInstance->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->id,
                    'provider_token' => $socialUser->token,
                ]);

                return response()->json(['success' => 'Login Type Successfully Updated.'], 200);

            } else {

                $user = User::where('provider_id', $socialUser->id)->first();

                if (!$user) {
                    return response()->json(['error' => 'Account not found. Please contact the admin.'], 404);
                }

                $redirectUrl = 'http://localhost:5173/auth/verify';

                if ($redirectUrl){
                    return redirect()->to($redirectUrl);
                }

                return $this->handleAuthentication($user, $request);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to Update Login Type: ' . $e->getMessage()], 500);
        }
    }

} -->
