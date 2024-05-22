<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Stkrequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MpesaController extends Controller
{
    public function stkQuery(){
        $accessToken=$this->token();
        $BusinessShortCode=174379;

        $url = env('MPESA_STK_PUSH_URL');
        $PassKey = env('MPESA_PASS_KEY');

        $Timestamp=Carbon::now()->format('YmdHis');
        $Password=base64_encode($BusinessShortCode.$PassKey.$Timestamp);
        $CheckoutRequestID='ws_CO_25122022133748874712650518';

        $response=Http::withToken($accessToken)->post($url,[
            'BusinessShortCode'=>$BusinessShortCode,
            'Timestamp'=>$Timestamp,
            'Password'=>$Password,
            'CheckoutRequestID'=>$CheckoutRequestID
        ]);

        return $response;
    }

    public function registerUrl(){
        $accessToken=$this->token();
        $url='https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
        $ShortCode=600997;
        $ResponseType='Completed'; //Cancelled

        $ConfirmationURL='https://a80a-197-156-137-172.sa.ngrok.io/payments/confirmation';
        $ValidationURL='https://a80a-197-156-137-172.sa.ngrok.io/payments/validation';

        $response=Http::withToken($accessToken)->post($url,[
            'ShortCode'=>$ShortCode,
            'ResponseType'=>$ResponseType,
            'ConfirmationURL'=>$ConfirmationURL,
            'ValidationURL'=>$ValidationURL
        ]);

        return $response;
    }

    public function Simulate(){
        $accessToken=$this->token();
        $url='https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';
        $ShortCode=600997;
        $CommandID='CustomerPayBillOnline'; //CustomerBuyGoodsOnline
        $Amount=1;
        $Msisdn=254708374149;
        $BillRefNumber='00000';

        $response=Http::withToken($accessToken)->post($url,[
            'ShortCode'=>$ShortCode,
            'CommandID'=>$CommandID,
            'Amount'=>$Amount,
            'Msisdn'=>$Msisdn,
            'BillRefNumber'=>$BillRefNumber
        ]);

        return $response;


    }

    public function Validation(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('validation.txt',$data);

        //validation logic

        return response()->json([
            'ResultCode'=>0,
            'ResultDesc'=>'Accepted'
        ]);

        /*
        return response()->json([
            'ResultCode'=>'C2B00011',
            'ResultDesc'=>'Rejected'
        ])
        */
    }

    public function Confirmation(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('confirmation.txt',$data);
        //save data to DB
        $response=json_decode($data);
        $TransactionType=$response->TransactionType;
        $TransID=$response->TransID;
        $TransTime=$response->TransTime;
        $TransAmount=$response->TransAmount;
        $BusinessShortCode=$response->BusinessShortCode;
        $BillRefNumber=$response->BillRefNumber;
        $InvoiceNumber=$response->InvoiceNumber;
        $OrgAccountBalance=$response->OrgAccountBalance;
        $ThirdPartyTransID=$response->ThirdPartyTransID;
        $MSISDN=$response->MSISDN;
        $FirstName=$response->FirstName;
        $MiddleName=$response->MiddleName;
        $LastName=$response->LastName;

        $c2b=new C2brequest;
        $c2b->TransactionType=$TransactionType;
        $c2b->TransID=$TransID;
        $c2b->TransTime=$TransTime;
        $c2b->TransAmount=$TransAmount;
        $c2b->BusinessShortCode=$BusinessShortCode;
        $c2b->BillRefNumber=$BillRefNumber;
        $c2b->InvoiceNumber=$InvoiceNumber;
        $c2b->OrgAccountBalance=$OrgAccountBalance;
        $c2b->ThirdPartyTransID=$ThirdPartyTransID;
        $c2b->MSISDN=$MSISDN;
        $c2b->FirstName=$FirstName;
        $c2b->MiddleName=$MiddleName;
        $c2b->LastName=$LastName;
        $c2b->save();


        return response()->json([
            'ResultCode'=>0,
            'ResultDesc'=>'Accepted'
        ]);

    }

    public function qrcode(){
        $consumerKey=\config('safaricom.consumer_key');
        $consumerSecret=\config('safaricom.consumer_secret');

        $authUrl='https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $request=Http::withBasicAuth($consumerKey,$consumerSecret)->get($authUrl);

        $accessToken=$request['access_token'];

        $MerchantName='ESKULI REVISION';
        $RefNo='gggsgsgg';
        $Amount=1;
        $TrxCode='PB'; //BG-buy goods till, WA-mpesa agent, SM-send money, SB-send to business
        $CPI=572555;

        $url='https://api.safaricom.co.ke/mpesa/qrcode/v1/generate';

        $response=Http::withToken($accessToken)->post($url,[
            'MerchantName'=>$MerchantName,
            'RefNo'=>$RefNo,
            'Amount'=>$Amount,
            'TrxCode'=>$TrxCode,
            'CPI'=>$CPI
        ]);

        $data=$response['QRCode'];

        return view('welcome')->with('qrcode',$data);

    }

    public function b2c(){
        $accessToken=$this->token();
        //return $accessToken;
        $InitiatorName='testapi';
        $InitiatorPassword='Safaricom123!';
        $path=Storage::disk('local')->get('SandboxCertificate.cer');
        $pk=openssl_pkey_get_public($path);

        openssl_public_encrypt(
            $InitiatorPassword,
            $encrypted,
            $pk,
            $padding=OPENSSL_PKCS1_PADDING

        );

        //$encrypted
        $SecurityCredential=base64_encode($encrypted);
        $CommandID='SalaryPayment'; //BusinessPayment PromotionPayment
        $Amount=3000;
        $PartyA=600998;
        $PartyB=254708374149;
        $Remarks='remarks';
        $QueueTimeOutURL='https://142f-102-166-158-51.eu.ngrok.io/payments/b2ctimeout';
        $ResultURL='https://142f-102-166-158-51.eu.ngrok.io/payments/b2cresult';
        $Occassion='occassion';
        $url='https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $response=Http::withToken($accessToken)->post($url,[
            'InitiatorName'=>$InitiatorName,
            'SecurityCredential'=>$SecurityCredential,
            'CommandID'=>$CommandID,
            'Amount'=>$Amount,
            'PartyA'=>$PartyA,
            'PartyB'=>$PartyB,
            'Remarks'=>$Remarks,
            'QueueTimeOutURL'=>$QueueTimeOutURL,
            'ResultURL'=>$ResultURL,
            'Occassion'=>$Occassion

        ]);

        return $response;



    }

    public function b2cResult(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('b2cresponse.txt',$data);

    }

    public function b2cTimeout(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('b2ctimeout.txt',$data);
    }

    public function Reversal(){
        $accessToken=$this->token();
        $InitiatorPassword='Safaricom123!';
        $path=Storage::disk('local')->get('SandboxCertificate.cer');
        $pk=openssl_pkey_get_public($path);

        openssl_public_encrypt(
            $InitiatorPassword,
            $encrypted,
            $pk,
            $padding=OPENSSL_PKCS1_PADDING

        );

        //$encrypted
        $SecurityCredential=base64_encode($encrypted);
        $CommandID='TransactionReversal';
        $TransactionID='RAF31LULEV';
        $TransactionAmount=3000;
        $ReceiverParty=600998;
        $ReceiverIdentifierType=11;
        $ResultURL='https://a80a-197-156-137-172.sa.ngrok.io/payments/reversalResult';
        $QueueTimeOutURL='https://a80a-197-156-137-172.sa.ngrok.io/payments/reversalTimeout';
        $Remarks='remarks';
        $Occassion='occassion';
        $Initiator='testapi';

        $url='https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request';

        $response=Http::withToken($accessToken)->post($url,[
            'Initiator'=>$Initiator,
            'SecurityCredential'=>$SecurityCredential,
            'CommandID'=>$CommandID,
            'TransactionID'=>$TransactionID,
            'Amount'=>$TransactionAmount,
            'ReceiverParty'=>$ReceiverParty,
            'ReceiverIdentifierType'=>$ReceiverIdentifierType,
            'ResultURL'=>$ResultURL,
            'QueueTimeOutURL'=>$QueueTimeOutURL,
            'Remarks'=>$Remarks,
            'Occassion'=>$Occassion
        ]);

        return $response;

    }

    public function reversalResult(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('reversalResult.txt',$data);
    }
    public function reversalTimeout(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('reversalTimeout.txt',$data);
    }



}
