<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Plans;
use App\Models\Stkrequest;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class PaymentController extends Controller
{
    public function token(){
        $consumerKey = env('MPESA_CONSUMER_KEY');
        $consumerSecret = env('MPESA_CONSUMER_SECRET');
        $url='https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response=Http::withBasicAuth($consumerKey,$consumerSecret)->get($url);
        return $response['access_token'];
    }

    public function initiateStkPush(Request $request){
        $accessToken = $this->token();
        $data = $this->loadCommonData($request, true);
        $company = $data['company'];

//        $request->validate([
//            'phone_number' => 'required|numeric|digits:20',
//        ]);


        $callbackUrl = env('CALL_Back_URL');
        $url = env('MPESA_STK_PUSH_URL');
        $PassKey = env('MPESA_PASS_KEY');


        $BusinessShortCode = 174379;
        $Timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode($BusinessShortCode . $PassKey . $Timestamp);
        $TransactionType = 'CustomerPayBillOnline';
        $Amount = 1;
        $PartyA = $request->input('phone_number');
        $PartyB = 174379;
        $PhoneNumber = $request->input('phone_number');
        $CallbackUrl = 'https://4a41-197-248-136-59.ngrok-free.app/stkcallback';
        $AccountReference = 'Coders base';
        $TransactionDesc = 'Payment for goods';

        try {
            $response=Http::withToken($accessToken)->post($url,[
                'BusinessShortCode'=>$BusinessShortCode,
                'Password'=>$password,
                'Timestamp'=>$Timestamp,
                'TransactionType'=>$TransactionType,
                'Amount'=>$Amount,
                'PartyA'=>$PartyA,
                'PartyB'=>$PartyB,
                'PhoneNumber'=>$PhoneNumber,
                'CallBackURL'=>$CallbackUrl,
                'AccountReference'=>$AccountReference,
                'TransactionDesc'=>$TransactionDesc
            ]);

            if ($response->getStatusCode() !== 200) {
                return redirect()->back()->with('error','Error in Payment request. Response status code: ' . $response->getStatusCode());
            }

            $res = $response->json();


            if (!isset($res['ResponseCode'])) {
                return redirect()->back()->with('error', 'ResponseCode is missing from the response.');
            }

            $ResponseCode = $res['ResponseCode'];

            if ($ResponseCode == 0) {
                $MerchantRequestID = $res['MerchantRequestID'];
                $CheckoutRequestID = $res['CheckoutRequestID'];
                $CustomerMessage = $res['CustomerMessage'];

                // Save to database
                $payment = new Stkrequest;
                $payment->phone = $PhoneNumber;
                $payment->amount = $Amount;
                $payment->reference = $AccountReference;
                $payment->description = $TransactionDesc;
                $payment->MerchantRequestID = $MerchantRequestID;
                $payment->CheckoutRequestID = $CheckoutRequestID;
                $payment->status = 'Requested';
                $payment->save();


                $subscription = new Subscription;
                $subscription->stkPush_id = $payment->id;
                $subscription->plan_id = $request->input('plan_id');
                $subscription->company_id = $company->id;


                $subscription->save();

//                return $CustomerMessage;
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('success', 'Payment Successfully Initiated',);
        }
    }




    public function stkCallback(){
        dd("This is being Accessed");
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('stk.txt',$data);

        $response=json_decode($data);

        $ResultCode=$response->Body->stkCallback->ResultCode;

        if($ResultCode==0){
            $MerchantRequestID=$response->Body->stkCallback->MerchantRequestID;
            $CheckoutRequestID=$response->Body->stkCallback->CheckoutRequestID;
            $ResultDesc=$response->Body->stkCallback->ResultDesc;
            $Amount=$response->Body->stkCallback->CallbackMetadata->Item[0]->Value;
            $MpesaReceiptNumber=$response->Body->stkCallback->CallbackMetadata->Item[1]->Value;
            //$Balance=$response->Body->stkCallback->CallbackMetadata->Item[2]->Value;
            $TransactionDate=$response->Body->stkCallback->CallbackMetadata->Item[3]->Value;
            $PhoneNumber=$response->Body->stkCallback->CallbackMetadata->Item[3]->Value;

            $payment=Stkrequest::where('CheckoutRequestID',$CheckoutRequestID)->firstOrfail();
            $payment->status='Paid';
            $payment->TransactionDate=$TransactionDate;
            $payment->MpesaReceiptNumber=$MpesaReceiptNumber;
            $payment->ResultDesc=$ResultDesc;
            $payment->save();

        }else{

            $CheckoutRequestID=$response->Body->stkCallback->CheckoutRequestID;
            $ResultDesc=$response->Body->stkCallback->ResultDesc;
            $payment=Stkrequest::where('CheckoutRequestID',$CheckoutRequestID)->firstOrfail();

            $payment->ResultDesc=$ResultDesc;
            $payment->status='Failed';
            $payment->save();

        }
    }

}

