<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use App\Traits\RoleRequirements;
use App\Traits\UserCompanyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CheckoutController extends Controller
{

    public function checkout(Request $request, $dummy, $plan)
    {
        $data = $this->loadCommonData($request, true);

        $selectedPlan = Plans::where('slug', $plan)->firstOrfail();

        $totalAmount = $selectedPlan->price;

        $subCookie = cookie('selectedPlanId', $plan, 1440);

        return response()
            ->view('pages.Cart.checkout', compact('selectedPlan', 'totalAmount') + $data)
            ->cookie($subCookie);
    }


    public function RemoveFromCart(Request $request)
    {
        $data = $this->loadCommonData($request, true);

        $company = $data['company']->name;

        // Expire the 'selectedPlanId' cookie to remove it
        $cookie = Cookie::forget('selectedPlanId');

        // Redirect back or to any other page after removing the cookie
        return redirect()->route('plan.index',['company' =>  $company])->withCookie($cookie);
    }

    public function companySubscription()
    {

    }
}


