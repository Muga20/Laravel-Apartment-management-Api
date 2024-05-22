<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Traits\DecodeTokenTrait;
use App\Traits\RoleRequirements;
use App\Traits\UserCompanyTrait;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Roles;




class RegisterController extends Controller
{

    protected $userService;

    use UserCompanyTrait;
    use DecodeTokenTrait;
    use RoleRequirements;

    private function getRequiredRoles($tier)
    {
        return $this->rolesThatMustHave($tier);
    }

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function register(Request $request)
    {
        list($user, $company, $userRoles) = $this->getUserAndCompany($request);

        $requiredRoles = $this->getRequiredRoles(1);
        $requiredRolesLvTwo = $this->getRequiredRoles(2);
        $requiredRolesLvThree = $this->getRequiredRoles(3);

        $companies = Company::all();
        $role = Roles::all();

        return view('auth.register' ,
            compact('companies' , 'role',
                'company','user' , 'requiredRoles',
                    'requiredRolesLvTwo' , 'requiredRolesLvThree'
            ));
    }


    /**
     * Store a newly created resource in storage.
     */


}
