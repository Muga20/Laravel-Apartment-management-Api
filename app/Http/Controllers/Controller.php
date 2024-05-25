<?php

namespace App\Http\Controllers;

use App\Traits\HasRequiredRoles;
use App\Traits\ImageTrait;
use App\Traits\RoleRequirements;
use App\Traits\SearchTrait;
use App\Traits\SubscriptionCheckTrait;
use App\Traits\TenantSearchTrait;
use App\Traits\UnreadMessagesCountTrait;
use App\Traits\UserCompanyTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    use UserCompanyTrait;
    use RoleRequirements;
    use UnreadMessagesCountTrait;
    use HasRequiredRoles;
    use SearchTrait;
    use TenantSearchTrait;
    use SubscriptionCheckTrait;
    use ImageTrait;

    private function getRequiredRoles($tier)
    {
        return $this->rolesThatMustHave($tier);
    }

    protected function loadCommonData(Request $request)
    {
        list($user, $company, $userRoles) = $this->getUserAndCompany($request);

        $requiredRoles = $this->getRequiredRoles(1);
        $requiredRolesLvTwo = $this->getRequiredRoles(2);
        $requiredRolesLvThree = $this->getRequiredRoles(3);
        $requiredRolesLvFour = $this->getRequiredRoles(4);
        $requiredRolesLvFive = $this->getRequiredRoles(5);

        if ($user) {
            return [
                'user' => $user,
                'company' => $company,
                'userRoles' => $userRoles,
            ];
        } else {
            // Handle the case where user data is not available
            abort(404, 'User or company not found or unauthorized');
        }
    }

}
