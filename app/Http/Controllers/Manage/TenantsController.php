<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Home;
use App\Models\Roles;
use App\Models\Units;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;


class TenantsController extends Controller
{

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getTenants(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);
        $unitData = Units::where('slug', $unit)->firstOrFail();
        $home = Home::findOrFail($unitData->home_id);

        $allTenants = $this->searchTenants($request)
            ->with(['user.units.home' => function ($query) use ($unit) {
                $query->where('slug', $unit);
            }])
            ->paginate(10);

        $allTenants->appends(['keyword' => $request->input('keyword')]);

        return view('pages.Management.Unit.pages.getTenants', compact('allTenants', 'unitData', 'home') + $data);
    }
    public function allTenants(Request $request)
    {
        $data = $this->loadCommonData($request);

        $allRoles = Roles::all();

        $allTenants = $this->searchTenants($request)->paginate(10);
        $allTenants->appends(['keyword' => $request->input('keyword')]);

        return view('pages.Roles.tenants', compact('allTenants', 'allRoles') + $data);
    }

    public function createTenant (Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);
        $unitData = Units::where('slug', $unit)->firstOrFail();
        return view('pages.Management.Unit.pages.createTenant' ,compact('unitData') + $data);
    }

    public function storeTenant(Request $request, $dummy, $unit)
    {

            $response = $this->userService->storeTenant($request, $dummy, $unit);

            if ($response instanceof User) {
                $user = $response;
                try {
                    // Uncomment the following lines once you have the necessary imports and implementations
                    // $company = Company::findOrFail($validatedData['company_id']);
                    // Mail::to($validatedData['email'])->send(new NewAccount($user, $company));
                    return redirect()->back()->with('success', 'User Created Successfully');
                } catch (ModelNotFoundException $e) {
                    return redirect()->back()->with('error', 'Company not found.');
                }
            } else {
                return $response;
            }
    }

}


