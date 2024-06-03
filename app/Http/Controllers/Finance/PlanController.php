<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $plans = Plans::all();
            return response()->json(['plans' => $plans], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching plans data'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function CreatePlan(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'plan_name' => 'required',
                'duration' => 'required',
                'price' => 'required|numeric',
                'number_of_homes' => 'required',
                'number_of_agents' => 'required',
            ]);

            $plan = new Plans();
            $plan->plan_name = $validatedData['plan_name'];
            $plan->duration = $validatedData['duration'];
            $plan->price = $validatedData['price'];
            $plan->number_of_homes = $validatedData['number_of_homes'];
            $plan->number_of_agents = $validatedData['number_of_agents'];
            $plan->status = 'inactive';
            $plan->slug = Str::slug($validatedData['plan_name'], '-');

            $plan->save();

            return response()->json(['message' => 'Plan Created Successfully'], 200);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create plan. Please try again.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $dummy
     * @param  int $plan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePlan(Request $request, $dummy, $plan)
    {
        try {
            $validatedData = $request->validate([
                'plan_name' => 'required',
                'duration' => 'required',
                'price' => 'required|numeric',
                'number_of_homes' => 'required',
                'number_of_agents' => 'required',
            ]);

            $selectedPlan = Plans::findOrFail($plan);

            $selectedPlan->plan_name = $validatedData['plan_name'];
            $selectedPlan->duration = $validatedData['duration'];
            $selectedPlan->price = $validatedData['price'];
            $selectedPlan->number_of_homes = $validatedData['number_of_homes'];
            $selectedPlan->number_of_agents = $validatedData['number_of_agents'];

            $selectedPlan->save();

            return response()->json(['message' => 'Plan Updated Successfully'], 200);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update plan. Please try again.'], 500);
        }
    }

    /**
     * Toggle the status of the specified resource.
     *
     * @param  string $dummy
     * @param  int $plan
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePlanStatus($plan)
    {
        try {
            $selectedPlan = Plans::findOrFail($plan);

            // Toggle the status
            if ($selectedPlan->status === 'active') {
                $selectedPlan->status = 'inactive';
            } else {
                $selectedPlan->status = 'active';
            }

            $selectedPlan->save();

            $action = ($selectedPlan->status === 'active') ? 'activated' : 'deactivated';

            return response()->json(['message' => "Plan $action successfully"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to toggle plan status. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage if status is not active.
     *
     * @param  string $dummy
     * @param  int $plan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deletePlan($plan)
    {
        try {
            $plan = Plans::findOrFail($plan);

            // Check if the plan status is not active
            if ($plan->status !== 'active') {
                $plan->delete();
                return response()->json(['message' => 'Plan deleted successfully'], 200);
            } else {
                return response()->json(['error' => 'Cannot delete plan with active status'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete plan. Please try again.'], 500);
        }
    }

}
