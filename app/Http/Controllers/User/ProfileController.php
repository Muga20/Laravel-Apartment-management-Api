<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\DecodeTokenTrait;
use App\Traits\RoleRequirements;
use App\Traits\UserCompanyTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;


class ProfileController extends Controller
{
    use UserCompanyTrait;
    use DecodeTokenTrait;
    use RoleRequirements;

    private function getRequiredRoles($tier)
    {
        return $this->rolesThatMustHave($tier);
    }
    /**
     * Display the user's profile form.
     */
    public function profile(Request $request)
    {
        try {

            $data = $this->loadCommonData($request);

            $userID = $data['user']->id;

            $userData = User::findOrFail($userID);

            return view('pages.User.userDetails',compact('userData') + $data);

       } catch (DecryptException $e) {
            return response()->view('error.error500', ['error' => 'Decryption failed'], 500);
       } catch (\Exception $e) {
           return response()->view('error.error500', ['error' => 'An unexpected error occurred'], 500);
       }
    }


    public function profileInfo(Request $request ,$dummy ,$encodedUserIdsString)
    {
        try {

            $data = $this->loadCommonData($request);

            $userData = User::where('uuid', $encodedUserIdsString)->firstOrFail();

            return view('pages.User.userDetails',
                compact('userData') + $data);
       } catch (DecryptException $e) {
            return response()->view('error.error500', ['error' => 'Decryption failed'], 500);
       } catch (\Exception $e) {
           return response()->view('error.error500', ['error' => 'An unexpected error occurred'], 500);
       }
    }

}
