<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\HomePaymentTypes;
use App\Models\PaymentType;
use App\Models\unitRecords;
use App\Models\Units;
use App\Services\PaymentService;
use App\Services\RentService;
use App\Traits\ExtractPaymentInfo;
use App\Traits\UnitDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Traits\ImageTrait;

class UnitPaymentController extends Controller
{
    use UnitDataTrait;
    use ExtractPaymentInfo;
    use ImageTrait;

    public function RentPaymentUnits(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);
        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);


        return view('pages.Management.Unit.pages.rentPayment', $unitData + $this->loadCommonData($request)+$data);
    }

    public function GarbagePaymentUnits(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);

        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);

        return view('pages.Management.Unit.pages.garbagePayment', $unitData + $this->loadCommonData($request)+$data);
    }

    public function WaterPaymentUnits(Request $request, $dummy, $unit)
    {
        $data = $this->loadCommonData($request);

        $rentService = app(RentService::class);
        $paymentService = app(PaymentService::class);

        $unitData = $this->getCommonUnitData($request, $dummy, $unit, $rentService, $paymentService);

        return view('pages.Management.Unit.pages.waterPayment', $unitData + $this->loadCommonData($request)+$data);
    }


    public function storeUnitPayment(Request $request, $dummy, $unit)
    {
        try {
            $data = $this->loadCommonData($request);

            $unitData = Units::where('slug', $unit)->firstOrFail();
            $paymentMethod = HomePaymentTypes::where('uuid', $request->input('payment_method'))->firstOrFail();


//            if ($data['user']->id !== $unitData->tenant_id) {
//                return response()->view('error.error403',  ['error' => 'Forbidden: You Cannot Perform this action'], 401);
//            }

            $transactionId = strtoupper(Carbon::now()->format('M')) . '-' . Str::random(8);

            $user = $data['user']->id;
            $unitRecord = new UnitRecords();

            $this->updateImage($request, $unitRecord, 'receiptInput');

            $unitRecord->fill([
                'transaction_id' => $transactionId,
                'phone' => $request->input('phone'),
                'acc_number' => $request->input('acc_number'),
                'status' => 'pending',
                'unit_id' =>  $unitData->id,
                'tenant_id' => $user,
                'payment_type_id' => $paymentMethod->id,
                'isApproved' => 'pending',
                'payingFor' => $request->input('paying_for'),
            ]);

            $unitRecord->save();

            return redirect()->back()->with('success', 'Payment successful');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to initiate Payment');
        }
    }
}
