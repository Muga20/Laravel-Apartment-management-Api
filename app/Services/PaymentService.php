<?php

namespace App\Services;

use App\Models\unitRecords;
use App\Models\Units;

class PaymentService
{
    public function isFullyPaid(Units $unit)
    {
        $totalRentPayments = unitRecords::where('unit_id', $unit->id)
            ->where('isApproved', 'completed')
            ->sum('rentFee');

        $totalWaterPayments = unitRecords::where('unit_id', $unit->id)
            ->where('isApproved', 'completed')
            ->sum('waterFee');

        $totalGarbagePayments = unitRecords::where('unit_id', $unit->id)
            ->where('isApproved', 'completed')
            ->sum('garbageFee');

        return [
            'totalRent' => $totalRentPayments,
            'totalGarbage' => $totalGarbagePayments,
            'totalWater' => $totalWaterPayments
        ];
    }
}
