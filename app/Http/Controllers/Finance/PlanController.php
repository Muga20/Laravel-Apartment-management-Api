<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->loadCommonData($request, true);
            $plans = Plans::all();

            return response()->json(['plans' => $plans], 200);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'An error occurred while fetching companies data',
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $data = $this->loadCommonData($request, true);

        return view('pages.Plan.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'plan_name' => 'required',
                'duration' => 'required',
                'price' => 'required|numeric',
                'number_of_companies' => 'required',
                'number_of_agents' => 'required',
            ]);

            $plan = new Plans();
            $plan->plan_name = $validatedData['plan_name'];
            $plan->duration = $validatedData['duration'];
            $plan->price = $validatedData['price'];
            $plan->number_of_companies = $validatedData['number_of_companies'];
            $plan->number_of_agents = $validatedData['number_of_agents'];

            $plan->slug = Str::slug($validatedData['plan_name'], '-');

            $plan->save();

            return redirect()->back()->with('success', 'Plan Created Successfully');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create plan. Please try again.');

        }
    }

    public function editPlan(Request $request, $dummy, $plan)
    {
        $data = $this->loadCommonData($request, true);

        $selectedPlan = Plans::where('slug', $plan)->firstOrfail();

        return view('pages.Plan.edit', compact('selectedPlan') + $data);
    }

    public function updatePlan(Request $request, $dummy, $plan)
    {

        try {
            $validatedData = $request->validate([
                'plan_name' => 'required',
                'duration' => 'required',
                'price' => 'required|numeric',
                'number_of_companies' => 'required',
                'number_of_agents' => 'required',
            ]);

            $selectedPlan = Plans::findOrFail($plan);

            $selectedPlan->plan_name = $validatedData['plan_name'];
            $selectedPlan->duration = $validatedData['duration'];
            $selectedPlan->price = $validatedData['price'];
            $selectedPlan->number_of_companies = $validatedData['number_of_companies'];
            $selectedPlan->number_of_agents = $validatedData['number_of_agents'];

            $selectedPlan->save();

            return redirect()->back()->with('success', 'Plan Updated Successfully');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update plan. Please try again.');
        }
    }

    public function deletePlan($dummy, $plan)
    {
        try {
            $plan = Plans::findOrFail($plan);
            $plan->delete();

            return redirect()->back()->with('success', 'Plan deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete plan. Please try again.');
        }
    }

}
