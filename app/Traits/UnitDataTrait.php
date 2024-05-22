<?php

namespace App\Traits;

use App\Models\HomePaymentTypes;
use App\Models\unitRecords;
use App\Models\Units;
use App\Services\PaymentService;
use App\Services\RentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

trait UnitDataTrait
{

    protected function getCommonUnitData($request, $dummy, $unit, RentService $rentService, PaymentService $paymentService)
    {

        $unitData = Units::where('slug', $unit)->firstOrFail();
        $home = $unitData->home;

        $feeDetails = $rentService->calculateFees($unitData);

        $payableRent = $feeDetails['rent'];
        $payableGarbage = $feeDetails['garbage'];
        $payableWater = $feeDetails['water'];

        $isFullyPaid = $paymentService->isFullyPaid($unitData);

        $totalRentPayments = $isFullyPaid['totalRent'];
        $totalWaterPayments = $isFullyPaid['totalWater'];
        $totalGarbagePayments = $isFullyPaid['totalGarbage'];

        // Calculate remaining balance for each type of fee
        $remainingRentBalance = $payableRent - $totalRentPayments;
        $remainingWaterBalance = $payableWater - $totalWaterPayments;
        $remainingGarbageBalance = $payableGarbage - $totalGarbagePayments;

        // Format remaining balances
        $payableRentFormatted = $this->formatPayment($remainingRentBalance);
        $payableWaterFormatted = $this->formatPayment($remainingWaterBalance);
        $payableGarbageFormatted = $this->formatPayment($remainingGarbageBalance);


        $hasNullPayable = is_null($unitData->payableRent) || is_null($unitData->lastGarbageBill) || is_null($unitData->lastWaterBill);

        $payable = compact('payableRentFormatted', 'payableGarbageFormatted', 'payableWaterFormatted');

        $isFullyPaid = $paymentService->isFullyPaid($unitData);

        $unitName = $unitData->unit_name;

        $paymentTypes = $this->getHomePaymentType($home->id);

        $timeDifference = $this->calculateTimeDifference($unitData);

        return compact('unitData', 'payable', 'home', 'unitName', 'hasNullPayable', 'paymentTypes', 'timeDifference', 'isFullyPaid');
    }

    public function getHomePaymentType($homeId)
    {
        $paymentTypes = HomePaymentTypes::with('paymentType')->where('home_id', $homeId)->get();

        return $paymentTypes;
    }

    private function formatPayment($amount)
    {
        if ($amount == 0) {
            return '<span style="color: green;">Fully Paid</span>';
        } elseif ($amount < 0) {
            return '<span style="color: blue;">' . abs($amount) . ' Overpaid</span>';
        } else {
            return '<span style="color: red;">Ksh ' . number_format($amount, 2) . ' Unpaid</span>';
        }
    }

    protected function calculateTimeDifference($unitData)
    {
        $currentDate = Carbon::now();
        $dateOfOccupation = Carbon::parse($unitData->dateOfOccupation);

        $timeDifferenceInDays = $dateOfOccupation->diffInDays($currentDate);
        $years = floor($timeDifferenceInDays / 365);
        $remainingDays = $timeDifferenceInDays % 365;
        $months = floor($remainingDays / 30);
        $remainingDays %= 30;
        $weeks = floor($remainingDays / 7);
        $days = $remainingDays % 7;

        // Format time difference
        $timeDifference = sprintf('%s%s%s%s',
            $years > 0 ? $years . ' Year' . ($years > 1 ? 's ' : ' ') : '',
            $months > 0 ? $months . ' Month' . ($months > 1 ? 's ' : ' ') : '',
            $weeks > 0 ? $weeks . ' Week' . ($weeks > 1 ? 's ' : ' ') : '',
            $days > 0 ? $days . ' Day' . ($days > 1 ? 's ' : '') : ''
        );

        return $timeDifference;
    }
}
