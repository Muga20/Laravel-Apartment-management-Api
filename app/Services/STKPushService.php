<?php

namespace App\Services;

use App\Models\Stkrequest;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class STKPushService
{
    public function getToken()
    {
        $consumerKey = env('MPESA_CONSUMER_KEY');
        $consumerSecret = env('MPESA_CONSUMER_SECRET');
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withBasicAuth($consumerKey, $consumerSecret)->get($url);
        return $response['access_token'];
    }

    public function initiateStkPush(Request $request, $BusinessShortCode, $company, $amount, $PhoneNumber)
    {
        $accessToken = $this->getToken();

        $callbackUrl = env('CALL_BACK_URL');
        $url = env('MPESA_STK_PUSH_URL');
        $PassKey = env('MPESA_PASS_KEY');

        $BusinessShortCode = $BusinessShortCode;
        $Timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode($BusinessShortCode . $PassKey . $Timestamp);
        $TransactionType = 'CustomerPayBillOnline';
        $PartyA = $PhoneNumber;
        $PartyB = $BusinessShortCode;
        $PhoneNumber = $PhoneNumber;
        $AccountReference = 'Coders base';
        $TransactionDesc = 'Payment for goods';

        try {
            $response = Http::withToken($accessToken)->post($url, [
                'BusinessShortCode' => $BusinessShortCode,
                'Password' => $password,
                'Timestamp' => $Timestamp,
                'TransactionType' => $TransactionType,
                'Amount' => $amount,
                'PartyA' => $PartyA,
                'PartyB' => $PartyB,
                'PhoneNumber' => $PhoneNumber,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $AccountReference,
                'TransactionDesc' => $TransactionDesc,
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['error' => 'Error in Payment request. Response status code: ' . $response->getStatusCode()];
            }

            $res = $response->json();

            if (!isset($res['ResponseCode'])) {
                return ['error' => 'ResponseCode is missing from the response.'];
            }

            $ResponseCode = $res['ResponseCode'];

            if ($ResponseCode == 0) {
                $MerchantRequestID = $res['MerchantRequestID'];
                $CheckoutRequestID = $res['CheckoutRequestID'];
                $CustomerMessage = $res['CustomerMessage'];

                // Save to database
                $payment = new Stkrequest;
                $payment->phone = $PhoneNumber;
                $payment->amount = $amount;
                $payment->reference = $AccountReference;
                $payment->description = $TransactionDesc;
                $payment->MerchantRequestID = $MerchantRequestID;
                $payment->CheckoutRequestID = $CheckoutRequestID;
                $payment->status = 'Requested';
                $payment->save();

                // Save to subscriptions if necessary
                if ($request->has('planId')) {
                    $subscription = new Subscription;
                    $subscription->stkPush_id = $payment->id;
                    $subscription->plan_id = $request->input('planId');
                    $subscription->company_id = $company;
                    $subscription->save();
                }

                return ['success' => $CustomerMessage];
            }
        } catch (\Throwable $e) {
            return ['error' => 'Payment initiation failed: ' . $e->getMessage()];
        }
    }

    public function handleStkCallback()
    {
        $data = file_get_contents('php://input');
        Storage::disk('local')->put('stk.txt', $data);

        $response = json_decode($data);

        $ResultCode = $response->Body->stkCallback->ResultCode;

        if ($ResultCode == 0) {
            $MerchantRequestID = $response->Body->stkCallback->MerchantRequestID;
            $CheckoutRequestID = $response->Body->stkCallback->CheckoutRequestID;
            $ResultDesc = $response->Body->stkCallback->ResultDesc;
            $Amount = $response->Body->stkCallback->CallbackMetadata->Item[0]->Value;
            $MpesaReceiptNumber = $response->Body->stkCallback->CallbackMetadata->Item[1]->Value;
            $TransactionDate = $response->Body->stkCallback->CallbackMetadata->Item[3]->Value;
            $PhoneNumber = $response->Body->stkCallback->CallbackMetadata->Item[3]->Value;

            $payment = Stkrequest::where('CheckoutRequestID', $CheckoutRequestID)->firstOrFail();
            $payment->status = 'Paid';
            $payment->TransactionDate = $TransactionDate;
            $payment->MpesaReceiptNumber = $MpesaReceiptNumber;
            $payment->ResultDesc = $ResultDesc;
            $payment->save();
        } else {
            $CheckoutRequestID = $response->Body->stkCallback->CheckoutRequestID;
            $ResultDesc = $response->Body->stkCallback->ResultDesc;
            $payment = Stkrequest::where('CheckoutRequestID', $CheckoutRequestID)->firstOrFail();
            $payment->ResultDesc = $ResultDesc;
            $payment->status = 'Failed';
            $payment->save();
        }
    }
}
