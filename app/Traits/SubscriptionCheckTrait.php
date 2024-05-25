<?php

namespace App\Traits;

use App\Models\Plans;
use App\Models\Subscription;
use App\Models\Home;
use Illuminate\Support\Facades\Redirect;


trait SubscriptionCheckTrait
{
    public function checkSubscription($company)
    {
        $subscription = Subscription::where('company_id', $company->id)->first();

        if (!$subscription) {
            return Redirect::back()->with('error', 'The company has not subscribed to any plan.');
        }

        $plan = Plans::find($subscription->plan_id);

        if (!$plan || !$plan->number_of_homes) {

            return Redirect::back()->with('error', 'The subscription plan is not properly configured.');
        }

        $numberOfHomes = Home::where('company_id', $company->id)->count();

        if ($numberOfHomes >= $plan->number_of_homes) {

            return response()->json(['error' => 'The company has reached the limit of allowed homes according to their plan.'], 403);

        }

        return null;
    }

}
