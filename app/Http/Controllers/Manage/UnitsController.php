<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Home;
use App\Models\unitRecords;
use App\Models\Units;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\RentService;
use App\Traits\UnitDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnitsController extends Controller
{
    use UnitDataTrait;

    public function myHouse(Request $request)
{
    $commonData = $this->loadCommonData($request);

    $unit = Units::where('tenant_id', $tenantId = $commonData['user']->id )->first();

    return redirect()->route('singleUnit', [
        'company' => $commonData['company']->slug,
        'unit' => $unit->slug,
        'home' => $unit->home->slug
    ]);
}




    public function manageUnits(Request $request, $dummy, $unit)
    {

        $data = $this->loadCommonData($request);

        $home = Home::with('units')->where('slug', $unit)->firstOrFail();
        $unavailableEmptyCount = $home->units()->where('status', 'empty')->count();

        $homeUnits = $home->units();

        $keyword = $request->input('keyword');

        if ($keyword) {
            $homeUnits->where('unit_name', 'like', "%{$keyword}%");
        }

        $homeUnits = $homeUnits->latest()->paginate(15);
        $homeUnits->appends(['keyword' => $keyword]);

        return view('pages.Management.manage', compact('homeUnits', 'home', 'unavailableEmptyCount') + $data);
    }



    public function singleUnit(Request $request, $dummy, $unit)
    {
        $commonData = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);

        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);

        return view('pages.Management.Unit.index', $unitData + $commonData);
    }


    public function rentTenant($dummy, $tenant, $unit)
    {
        try {
            // Start a transaction
            DB::beginTransaction();

            $tenantId = $tenant;
            $unitId = $unit;

            $tenant = User::findOrFail($tenantId);
            $unit = Units::findOrFail($unitId);

            if ($tenant->status !== 'active') {
                return redirect()->back()->with('error', 'Failed to rent tenant. The tenant is inactive.');
            }

            $unit->refresh();

            if ($unit->status === 'occupied') {
                return redirect()->back()->with('error', 'Failed to rent tenant. The unit is already occupied.');
            }

            $entryDate = now()->toDateString();

            // Update the unit and mark it as occupied
            $unit->update([
                'tenant_id' => $tenantId,
                'dateOfOccupation' =>$entryDate,
                'status' => 'occupied',
            ]);

            // Commit the transaction
            DB::commit();

            return redirect()->back()->with('success', 'Tenant rented successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to rent tenant. Please try again.');
        }
    }

    public function removeTenant($dummy , $unit)
    {
        try {
            DB::beginTransaction();

            $unitData = Units::findOrFail($unit);

            $unitData->update([
                'tenant_id' => null,
                'dateOfOccupation' => null,
                'status' => 'vacant',
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Tenant removed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to remove tenant. Please try again.');
        }
    }

    public function paymentUnits(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);
        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);

        return view('pages.Management.Unit.payment', $unitData + $this->loadCommonData($request) +$data);
    }


    public function editUnits(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);
        $unitData = Units::where('slug', $unit)->firstOrFail();
        $home = $unitData->home()->firstOrFail();

        return view('pages.Management.Unit.pages.editUnit', compact('unitData', 'home') + $data);

    }

    public function storeEditedUnit(Request $request, $dummy, $unit)
    {
        try {
            $validatedData = $request->validate([
                'unit_name' => 'required|string|max:255',
                'number_of_rooms' => 'required|integer',
                'payableRent' => 'required|numeric',
                'paymentPeriod' => 'required|string|max:255',
                'lastWaterBill' => 'nullable|numeric',
                'damages' => 'nullable|string|max:255',
                'lastGarbageBill' => 'nullable|string|max:255'
            ]);

            $unitData = Units::where('slug', $unit)->firstOrFail();

            $unitData->update($validatedData);

            return redirect()->back()->with('success', 'Unit Updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update Unit. Please try again.');
        }
    }


    public function paymentTransactions(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);
        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);

        $unitDataOne = Units::where('slug', $unit)->firstOrFail();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate) {
            $startDate = Carbon::today()->subDays(2)->toDateString();
        }

        if (!$endDate) {
            $endDate = Carbon::today()->toDateString();
        }

        $unitRecordsQuery = unitRecords::where('unit_id', $unitDataOne->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->latest();

        $unitRecords = $unitRecordsQuery->paginate(5);
        $unitName = $unitDataOne->unit_name;

        // Customize unit records
        foreach ($unitRecords as $unitRecord) {
            $unitRecord->feeType = $unitRecord->rentFee ? 'Rent' : ($unitRecord->garbageFee ? 'Garbage' : ($unitRecord->waterFee ? 'Water' : '--'));
            $unitRecord->feeAmount = $unitRecord->rentFee ? $unitRecord->rentFee : ($unitRecord->garbageFee ? $unitRecord->garbageFee : ($unitRecord->waterFee ? $unitRecord->waterFee : '--'));
            $unitRecord->accountInfo = $unitRecord->phone ? $unitRecord->phone : ($unitRecord->account_no ? $unitRecord->account_no : '--');
        }

        return view('pages.Management.Unit.Transactions.transactions', compact('unitRecords', 'unitName') + $unitData + $this->loadCommonData($request) + $data);
    }

    public function transactionStatement(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);
        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);
        $unitDataOne = Units::where('slug', $unit)->firstOrFail();

        $unitRecords = unitRecords::where('unit_id', $unitDataOne->id);

        foreach ($unitRecords as $unitRecord) {
            $unitRecord->feeType = $unitRecord->rentFee ? 'Rent' : ($unitRecord->garbageFee ? 'Garbage' : ($unitRecord->waterFee ? 'Water' : '--'));
            $unitRecord->feeAmount = $unitRecord->rentFee ? $unitRecord->rentFee : ($unitRecord->garbageFee ? $unitRecord->garbageFee : ($unitRecord->waterFee ? $unitRecord->waterFee : '--'));
            $unitRecord->accountInfo = $unitRecord->phone ? $unitRecord->phone : ($unitRecord->account_no ? $unitRecord->account_no : '--');
        }

        return view('pages.Management.Unit.Transactions.transactions', compact('unitRecords') + $unitData + $this->loadCommonData($request) + $data);

    }

    public function updateWaterReading(Request $request, $dummy, $unit)
    {
        try {
            $unitData = Units::where('slug', $unit)->firstOrFail();

            $currentReading = $request->input('currentMeterReading');
            $previousReading = $unitData->currentMeterReading;

            if ($currentReading <= $previousReading) {
                return redirect()->back()->with('error', 'Current meter reading must be greater than the previous reading.');
            }

            $unitData->update([
                'currentMeterReading' => $currentReading,
                'previousMeterReading' => $previousReading,
            ]);

            return redirect()->back()->with('success', 'Unit updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update unit. Please try again.');
        }
    }



}
