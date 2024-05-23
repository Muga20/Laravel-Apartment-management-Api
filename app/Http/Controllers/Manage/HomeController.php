<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Home;
use App\Models\HomePaymentTypes;
use App\Models\PaymentType;
use App\Models\unitRecords;
use App\Models\Units;
use App\Models\User;
use App\Models\UserDetails;
use App\Services\CompressionService;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    use ImageTrait;
    protected $compressionService;

    public function __construct(CompressionService $compressionService)
    {
        $this->compressionService = $compressionService;
    }

    public function HomeProfile(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);
        $home = Home::withCount('units')->where('slug', $unit)->firstOrFail();
        $unavailableEmptyCount = $home->units()->where('status', 'vacant')->count();

        $homeUnits = $this->getHomeUnits($home, $request->input('keyword'));
        $agentUsers = $this->getAgentUsers($data['company']);
        $paymentTypes = $this->getHomePaymentType($home->id);
        $countsStats = $this->getCountsStats($home);
        $monthlyProfitLossData = $this->getMonthlyProfitLoss($home);

        // Separate the monthly profit/loss data and available months array
        $monthlyProfitLoss = $monthlyProfitLossData['monthlyProfitLoss'];
        $availableMonths = $monthlyProfitLossData['availableMonths'];

        return view('pages.Management.Home.index', compact('homeUnits', 'home', 'unavailableEmptyCount', 'agentUsers', 'paymentTypes', 'countsStats', 'monthlyProfitLoss', 'availableMonths') + $data);
    }

    protected function getHomeUnits($home, $keyword)
    {
        $homeUnits = $home->units();

        if ($keyword) {
            $homeUnits->where('unit_name', 'like', "%{$keyword}%");
        }

        return $homeUnits->latest()->paginate(12);
    }

    protected function getAgentUsers($company)
    {
        return User::where('company_id', $company->id)
            ->join('useroles', 'users.id', '=', 'useroles.user_id')
            ->join('roles', 'useroles.role_id', '=', 'roles.id')
            ->where('roles.name', 'agent')
            ->select('users.*')
            ->get();
    }

    protected function getCountsStats($home)
    {
        $unitIds = Units::where('home_id', $home->id)->pluck('id');
        $today = Carbon::today();

        $allUnitRecords = UnitRecords::whereIn('unit_id', $unitIds)
            ->whereDate('created_at', $today)
            ->get();

        $pendingCount = $allUnitRecords->where('isApproved', 'pending')->count();
        $rejectedCount = $allUnitRecords->where('isApproved', 'rejected')->count();
        $completedCount = $allUnitRecords->where('isApproved', 'completed')->count();

        // Calculate profit or loss for last month
        $lastMonthFirstDay = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthLastDay = Carbon::now()->subMonth()->endOfMonth();

        $lastMonthRecords = UnitRecords::whereIn('unit_id', $unitIds)->whereBetween('created_at', [$lastMonthFirstDay, $lastMonthLastDay])->get();
        $totalExpectedLastMonthAmount = Units::whereIn('id', $unitIds)->sum('payableRent');
        $totalActualLastMonthAmount = $lastMonthRecords->sum('rentFee');

        if ($totalExpectedLastMonthAmount != 0) {
            $percentageProfitLossLastMonth = (($totalActualLastMonthAmount - $totalExpectedLastMonthAmount) / $totalExpectedLastMonthAmount) * 100;
        } else {
            $percentageProfitLossLastMonth = 0;
        }

        // Calculate profit or loss for this month
        $currentMonthFirstDay = Carbon::now()->startOfMonth();
        $totalExpectedThisMonthAmount = Units::whereIn('id', $unitIds)->sum('payableRent');
        $thisMonthRecords = UnitRecords::whereIn('unit_id', $unitIds)->where('created_at', '>=', $currentMonthFirstDay)->get();
        $totalActualThisMonthAmount = $thisMonthRecords->sum('rentFee');

        // Calculate the percentage profit or loss for last month

        if ($totalActualLastMonthAmount != 0) {
            $percentageProfitLossThisMonth = (($totalActualThisMonthAmount - $totalActualLastMonthAmount) / $totalActualLastMonthAmount) * 100;
        } else {
            $percentageProfitLossThisMonth = 0;
        }

        $allCounts = $pendingCount + $rejectedCount + $completedCount;

        return compact('pendingCount', 'rejectedCount', 'completedCount', 'allCounts', 'totalActualThisMonthAmount', 'percentageProfitLossLastMonth', 'percentageProfitLossThisMonth');
    }

    protected function getMonthlyProfitLoss($home)
    {
        $unitIds = Units::where('home_id', $home->id)->pluck('id');
        $monthlyProfitLoss = [];
        $availableMonths = [];
        $currentMonth = Carbon::now()->month;
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        for ($i = 0; $i < $currentMonth; $i++) { // Adjust loop condition
            $firstDayOfMonth = Carbon::now()->startOfMonth()->month($i + 1);
            $lastDayOfMonth = Carbon::now()->endOfMonth()->month($i + 1);

            $records = UnitRecords::whereIn('unit_id', $unitIds)
                ->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])
                ->get();

            $totalExpectedAmount = Units::whereIn('id', $unitIds)->sum('payableRent');
            $totalActualAmount = $records->sum('rentFee');

            if ($totalExpectedAmount != 0) {
                $percentageProfitLoss = (($totalActualAmount - $totalExpectedAmount) / $totalExpectedAmount) * 100;
            } else {
                $percentageProfitLoss = 0;
            }

            $monthlyProfitLoss[] = $percentageProfitLoss;
            $availableMonths[] = $months[$i];
        }

        return [
            'monthlyProfitLoss' => $monthlyProfitLoss,
            'availableMonths' => $availableMonths,
        ];
    }

    public function getHomePaymentType($homeId)
    {
        $paymentTypes = HomePaymentTypes::with('paymentType')->where('home_id', $homeId)->get();

        return $paymentTypes;
    }

    public function showHomes(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);
            $keyword = $request->input('keyword');
            $companyId = $data['company']->id;

            $query = Home::withCount('units')
                ->where('company_id', $companyId)
                ->latest();

            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('location', 'like', "%{$keyword}%");
                });
            }

            $allHouses = $query->paginate(10);
            $allHouses->appends(['keyword' => $keyword]);

            foreach ($allHouses as $house) {
                if ($house->units->where('status', 'occupied')->count() == $house->units->count()) {
                    $house->status = 'fully occupied';
                } else {
                    $house->status = 'available';
                }
            }

            return response()->json(['allHouses' => $allHouses], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the houses data.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function createHome(Request $request)
    {
        $data = $this->loadCommonData($request);
        $company = $data['company'];

        $users = UserDetails::whereHas('user.roles', function ($query) {
            $query->where('name', 'agent');
        })->whereHas('user', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->selectRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name, user_id")->pluck('full_name', 'user_id');

        $decompressedUsers = $users;

        return view('pages.Management.create', compact('decompressedUsers') + $data);
    }

    public function storeHome(Request $request)
    {
        $data = $this->loadCommonData($request);
        try {

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|min:10',

            ]);

            DB::beginTransaction();

            $company = $data['company'];

            $subscriptionCheck = $this->checkSubscription($company);
            if ($subscriptionCheck) {
                return $subscriptionCheck;
            }

            if (strlen($request->input('phone')) < 10) {
                return redirect()->back()->with('error', 'Phone number must have at least 10 characters');
            }

            if (Home::where('phone', $request->input('phone'))->exists()) {
                return redirect()->back()->with('error', 'Phone number already exists');
            }

            if (Home::where('email', $request->input('email'))->exists()) {
                return redirect()->back()->with('error', 'Email already exists');
            }

            $ownerId = $data['user'];

            if (!$company->status || $company->status !== 'active') {
                $company->update(['status' => 'active']);
            }

            // Store uploaded images
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = 'storage/' . $image->store('HomeImage', 'public');
                    $imagePaths[] = $imagePath;
                }
            }

            $home = Home::create([
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'houseCategory' => $request->input('houseCategory'),
                'images' => json_encode($imagePaths),
                'stories' => $request->input('stories'),
                'status' => 'available',
                'description' => $request->input('description'),
                'company_id' => $company->id,
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'slug' => Str::slug($request->input('name'), '-') . '-' . (Home::count() + 1),
                'landlord_id' => $ownerId->id,
                'agent_id' => $request->input('agent_id'),
            ]);

            // Create units
            $numberOfUnits = $request->input('number_of_units');
            $totalUnits = $numberOfUnits * $home->stories;

            $units = [];

            for ($i = 1; $i <= $totalUnits; $i++) {
                $uniqueIdentifier = Str::uuid();
                $unitName = substr($home->name, 0, 2) . '-' . ceil($i / $numberOfUnits) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $units[] = [
                    'status' => 'vacant',
                    'slug' => $uniqueIdentifier,
                    'unit_name' => $unitName,
                    'home_id' => $home->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Units::insert($units);

            DB::commit();

            return redirect()->back()->with('success', 'Home and units stored successfully');
        } catch (\Exception $e) {
            //Rollback the transaction in case of an error
            DB::rollBack();
            return back()->with('error', 'Failed to store home and units :' . $e->getMessage());
        }
    }

    public function editHome(Request $request, $dummy, $slug)
    {
        $data = $this->loadCommonData($request);

        $requiredRoles = $this->rolesThatMustHave(2);

        if (!$this->hasRequiredRoles($data, $requiredRoles)) {
            return $this->unauthorizedResponse();
        }

        $house = Home::where('slug', $slug)->first();

        $company = $data['company']->id;

        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'agent');
        })->where('company_id', $company)->pluck('email', 'id');

        return view('pages.Management.Home.editHome', compact('house', 'users') + $data);
    }

    public function storeEditedHome(Request $request, $dummy, $slug)
    {
        $data = $this->loadCommonData($request);

        $userRoles = collect($data['userRoles']);
        $requiredRoles = $this->rolesThatMustHave(2);

        if (!$userRoles->intersect($requiredRoles)->isNotEmpty()) {
            return response()->view('error.error401', ['error' => 'Unauthorized: Insufficient privileges to perform this action.'], 401);
        }

        $request->validate([
            'name' => 'required|string',
            'location' => 'required|string',
            'houseCategory' => 'required|string',
            'stories' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'rentPaymentDay' => 'required|string',
        ]);

        try {
            $home = Home::where('slug', $slug)->firstOrFail();

            if (strlen($request->input('phone')) < 10) {
                return redirect()->back()->with('error', 'Phone number must have at least 10 characters');
            }

            if ($request->input('phone') != $home->phone && Home::where('phone', $request->input('phone'))->exists()) {
                return redirect()->back()->with('error', 'Phone number already exists');
            }

            if ($request->input('email') != $home->email && Home::where('email', $request->input('email'))->exists()) {
                return redirect()->back()->with('error', 'Email already exists');
            }

            $home->update([
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'houseCategory' => $request->input('houseCategory'),
                'stories' => $request->input('stories'),
                'description' => $request->input('description'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'agent_id' => $request->input('agent_id'),
                'rentPaymentDay' => $request->input('rentPaymentDay'),
            ]);

            // Update images using ImageTrait
            $this->updateImage($request, $home, 'images');

            $home->update();

            return redirect()->back()->with('success', 'Home details updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update home details');
        }
    }

    public function createHomePaymentInfo(Request $request, $dummy, $slug)
    {
        $data = $this->loadCommonData($request);

        $requiredRoles = $this->rolesThatMustHave(2);

        if (!$this->hasRequiredRoles($data, $requiredRoles)) {
            return $this->unauthorizedResponse();
        }

        $paymentMode = PaymentType::all()->pluck('name', 'id');

        $house = Home::where('slug', $slug)->first();

        return view('pages.Management.Home.paymentInfo', compact('house', 'paymentMode') + $data);
    }

    public function storeHomePaymentInfo(Request $request, $dummy, $slug)
    {
        try {
            $data = $this->loadCommonData($request);

            $requiredRoles = $this->rolesThatMustHave(2);

            if (!$this->hasRequiredRoles($data, $requiredRoles)) {
                return $this->unauthorizedResponse();
            }

            $home = Home::where('slug', $slug)->firstOrFail();

            $request->validate([
                'account_name' => 'required|string',
                'account_number' => 'required|string',
                'payment_type_id' => 'required',
            ]);

            $paymentTypesCount = HomePaymentTypes::where('home_id', $home->id)->count();
            if ($paymentTypesCount >= 3) {
                return back()->with('error', 'A home can only have up to three Payment types.');
            }

            $paymentAccount = new HomePaymentTypes();
            $paymentAccount->uuid = Str::uuid();
            $paymentAccount->account_name = $request->input('account_name');
            $paymentAccount->account_payBill = $request->input('account_payBill');
            $paymentAccount->account_number = $request->input('account_number');
            $paymentAccount->payment_type_id = $request->input('payment_type_id');
            $paymentAccount->home_id = $home->id;

            $paymentAccount->save();

            return redirect()->back()->with('success', 'Home details updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update home details');
        }
    }

    public function updatePaymentInfo(Request $request, $dummy, $slug, $payment)
    {
        $data = $this->loadCommonData($request);

        $requiredRoles = $this->rolesThatMustHave(2);

        if (!$this->hasRequiredRoles($data, $requiredRoles)) {
            return $this->unauthorizedResponse();
        }

        $paymentMode = PaymentType::all()->pluck('name', 'id');

        $house = Home::where('slug', $slug)->first();

        $paymentData = HomePaymentTypes::where('uuid', $payment)->firstOrFail();

        return view('pages.Management.Home.updatePaymentInfo', compact('house', 'paymentMode', 'paymentData') + $data);

    }

    public function updateHomePaymentInfo(Request $request, $dummy, $slug, $payment)
    {
        try {
            $paymentData = HomePaymentTypes::where('uuid', $payment)->firstOrFail();

            $paymentData->fill($request->only(['account_name', 'account_payBill', 'account_number', 'payment_type_id']));

            $paymentData->save();

            return redirect()->back()->with('success', 'Payment details updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update payment details');
        }
    }

    public function deletePaymentInfo($dummy, $payment)
    {
        try {
            $paymentData = HomePaymentTypes::where('uuid', $payment)->firstOrFail();

            $paymentData->delete();

            return redirect()->back()->with('success', 'Payment details deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete payment details');
        }
    }

}
