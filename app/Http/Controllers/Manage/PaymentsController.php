<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Home;
use App\Models\unitRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    private function calculateTotalFeeAndColumn()
    {
        return [
            DB::raw('COALESCE(rentFee, 0) + COALESCE(garbageFee, 0) + COALESCE(waterFee, 0) AS totalFee'),
            DB::raw('
                CASE
                    WHEN rentFee > 0 THEN "rentFee"
                    WHEN garbageFee > 0 THEN "garbageFee"
                    WHEN waterFee > 0 THEN "waterFee"
                END AS contributingColumn
            ')
        ];
    }

    private function handlePaymentVerificationView($unitRecords, $house, $data)
    {
        return view('pages.Management.Payment.verifyPayment', compact('unitRecords', 'house') + $data);
    }

    public function paymentIndex(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);
        $today = Carbon::today();

        $house = Home::where('slug', $unit)->firstOrFail();
        $home = Home::with('units')->where('slug', $unit)->firstOrFail();
        $unitIds = $home->units->pluck('id');

        $paymentType = $request->input('payment_type');
        $status = $request->input('status');
        $searchQuery = $request->input('search');

        $unitRecordsQuery = UnitRecords::whereIn('unit_id', $unitIds)
            ->select('unit_records.*', ...$this->calculateTotalFeeAndColumn())
            ->whereDate('created_at', $today)
            ->when($searchQuery, function ($query) use ($searchQuery) {
                return $query->where('phone', 'LIKE', "%$searchQuery%")
                    ->orWhere('receipt', 'LIKE', "%$searchQuery%")
                    ->orWhere('transaction_id', 'LIKE', "%$searchQuery%");
            })
            ->latest();

        if ($paymentType && $paymentType !== 'any') {
            $unitRecordsQuery->where($paymentType, '>', 0);
        }

        if ($status && $status !== 'any') {
            $unitRecordsQuery->where('isApproved', $status);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $unitRecordsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $unitRecords = $unitRecordsQuery->paginate(10);

        return view('pages.Management.Payment.index', compact('unitRecords', 'home', 'house') + $data);
    }

    public function paymentVerification(Request $request, $dummy, $unit, $trans_id)
    {
        $data = $this->loadCommonData($request);

        $unitRecords = UnitRecords::where('transaction_id', $trans_id)
            ->select('unit_records.*', ...$this->calculateTotalFeeAndColumn())
            ->firstOrFail();

        $house = Home::where('slug', $unit)->first();

        return $this->handlePaymentVerificationView($unitRecords, $house, $data);
    }

    public function updateUnitRecordPayment(Request $request, $dummy, $unit)
    {
        try {
            $paymentData = UnitRecords::where('transaction_id', $request->input('transaction_id'))->firstOrFail();

            $currentDate = date('Y-m-d');

            $payingFor = $request->input('paying_for');

            if ($payingFor === 'Water Payment') {
                $paymentData->update(['waterFee' => $request->input('amount')]);
            } elseif ($payingFor === 'Rent Payment') {
                $paymentData->update(['rentFee' => $request->input('amount')]);
            } elseif ($payingFor === 'Garbage Payment') {
                $paymentData->update(['garbageFee' => $request->input('amount')]);
            }

            $receiptCode = $request->input('receipt');

            if (strlen($receiptCode) <= 5) {
                return back()->with('error', 'Receipt code must have more than 5 characters');
            }

            $similarReceipt = UnitRecords::where('receipt', 'like', '%' . $receiptCode . '%')->exists();

            if ($similarReceipt) {
                return back()->with('error', 'Similar receipt code already exists');
            }

            $transactionDate = $request->input('transaction_date');
            if ($transactionDate && $transactionDate > $currentDate) {
                return back()->with('error', 'Transaction date cannot be in the future');
            }

            $paymentData->update([
                'receipt' => $receiptCode,
                'transaction_date' => $transactionDate,
                'status' => 'paid',
                'isApproved' => 'completed',
                'payingFor' => ''
            ]);

            return redirect()->back()->with('success', 'Payment verified successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to verify payment details');
        }
    }

    public function rejectUnitRecordPayment(Request $request, $dummy, $unit)
    {
        try {
            $request->validate(['recommendation' => 'required']);

            $paymentData = UnitRecords::where('transaction_id', $request->input('transaction_id'))->firstOrFail();

            $paymentData->update([
                'recommendation' => $request->input('recommendation'),
                'status' => 'pending',
                'isApproved' => 'rejected',
                'payingFor' => ''
            ]);

            return redirect()->back()->with('success', 'Payment Rejected successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject payment: ' . $e->getMessage());
        }
    }
}
