<?php

namespace App\Services;

use App\Models\Units;
use Carbon\Carbon;

class RentService
{
    public function calculateFees(Units $units): array
    {
        if ($units->status !== 'occupied') {
            return [
                'rent' => 0,
                'garbage' => 0,
                'water' => 0
            ];
        }

        $rentFee = $units->payableRent;
        $lastGarbageBill = $units->lastGarbageBill;
        $lastWaterBill = $units->lastWaterBill;

        $dateOfOccupation = Carbon::parse($units->dateOfOccupation);

        $currentDate = Carbon::now();

        // Calculate the number of full months since occupation
        $fullMonthsOccupied = $dateOfOccupation->diffInMonths($currentDate);

        // Initialize total variables
        $totalRent = 0;
        $totalGarbage = 0;
        $totalWater = 0;

        if ($fullMonthsOccupied == 0) {
            // Calculate the start and end dates of the current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Check if the date of occupation is within the current month
            if ($dateOfOccupation->between($startOfMonth, $endOfMonth)) {
                $daysOccupied = $currentDate->diffInDays($dateOfOccupation);

                // Calculate the number of days in the current month
                $daysInMonth = (int) date('t');

                // Calculate prorated fees based on the days occupied
                $proratedRent = ($rentFee / $daysInMonth) * $daysOccupied;
                $proratedGarbage = ($lastGarbageBill / $daysInMonth) * $daysOccupied;
                $proratedWater = $this->calculateProratedWater($units, $daysOccupied);

                return [
                    'rent' => $proratedRent,
                    'garbage' => $proratedGarbage,
                    'water' => $proratedWater
                ];
            }
        }


        // If one or more months have passed since occupation, calculate full fees for each month
        $totalRent = $fullMonthsOccupied * $rentFee;
        $totalGarbage = $fullMonthsOccupied * $lastGarbageBill;
        $totalWater = $fullMonthsOccupied * $lastWaterBill;

        // Check if there are any remaining days in the current month
        $remainingDays = $currentDate->diffInDays($dateOfOccupation->addMonths($fullMonthsOccupied));
        if ($remainingDays > 0) {
            // Add prorated fees for the remaining days in the current month
            $proratedRent = ($rentFee / 30) * $remainingDays;
            $proratedGarbage = ($lastGarbageBill / 30) * $remainingDays;
            $proratedWater = $this->calculateProratedWater($units, $remainingDays);
            $totalRent += $proratedRent;
            $totalGarbage += $proratedGarbage;
            $totalWater += $proratedWater;
        }

        return [
            'rent' => $totalRent,
            'garbage' => $totalGarbage,
            'water' => $totalWater
        ];
    }

    private function calculateProratedWater(Units $units, $days): float
    {
        $prevWaterReading = $units->previousMeterReading;
        $currentWaterReading = $units->currentMeterReading;
        $waterUsage = max($currentWaterReading - $prevWaterReading, 0);
        return $waterUsage * $units->lastWaterBill * ($days / 30);
    }
}
