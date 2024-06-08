<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use App\Services\STKPushService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected $stkPushService;

    public function __construct(STKPushService $stkPushService)
    {
        $this->stkPushService = $stkPushService;
    }

    public function initiatePayment(Request $request)
    {
        // Retrieve company data
        $company = $request->input('companyId');
        $BusinessShortCode = 174379;
        $PhoneNumber = $request->input('phoneNumber');
        $planId = $request->input('planId');
        $plan = Plans::findOrFail($planId);
        $amount = $plan->price;

        // Initiate STK Push
        $response = $this->stkPushService->initiateStkPush($request, $BusinessShortCode, $company, $amount, $PhoneNumber);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 400);
        }

        return response()->json(['success' => $response['success']], 200);
    }
}
