<?php

namespace EvanceOdhiambo\MpesaPayment;

use Illuminate\Support\Facades\Cache;
use EvanceOdhiambo\MpesaPayment\Controllers\MpesaResponseController;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Str;

class MpesaPayment
{
    public $base_url;

    public $consumer_key;

    public $consumer_secret;

    public $paybill;

    public $shortcode;

    public $passkey;

    public $c2bvalidate;

    public $c2bconfirm;

    public $access_token;

    public $callbackurl;

    protected $callback_results;

    public $app_base_url;

    public $b2c_callbackurl;

    public $queue_timeout_url;

    public function __construct()
    {
        $this->app_base_url = url('/') . '/evance-mpesa';

        $this->base_url = (config('evance-mpesa.mpesa_env') == 'sandbox') ? 'https://sandbox.safaricom.co.ke/' : 'https://api.safaricom.co.ke/';

        $this->consumer_key = config('evance-mpesa.consumer_key'); // Consumer Key from your daraja app

        $this->consumer_secret = config('evance-mpesa.consumer_secret'); //Consumer secrete from Your daraja app

        $this->paybill = config('evance-mpesa.paybill'); //Paybill number registered or use daraja's test

        $this->shortcode = config('evance-mpesa.shortcode');

        $this->passkey = config('evance-mpesa.passkey');

        $this->callbackurl = (!empty(config('evance-mpesa.callbackurl'))) ? config('evance-mpesa.callbackurl') : $this->app_base_url . '/c2b_callback';

        // c2b the urls
        $this->c2bvalidate = (!empty(config('evance-mpesa.c2b_validate_callback'))) ? config('evance-mpesa.c2b_validate_callback') : $this->app_base_url . '/c2b_validate';

        $this->c2bconfirm = (!empty(config('evance-mpesa.c2b_confirm_callback'))) ? config('evance-mpesa.c2b_confirm_callback') : $this->app_base_url . '/c2b_confirm';


        // b2c the urls
        $this->b2c_callbackurl = (!empty(config('evance-mpesa.b2c_callbackurl'))) ? config('evance-mpesa.b2c_callbackurl') : $this->app_base_url . '/b2c_callbackurl';

        $this->queue_timeout_url = (!empty(config('evance-mpesa.queue_timeout_url'))) ? config('evance-mpesa.queue_timeout_url') : $this->app_base_url . '/queue_timeout_url';

        $this->access_token = $this->accessTken(); //Set up access token

        $this->callback_results = new MpesaResponseController();
    }

    private function accessTken()
    {

        $cacheKey = 'evance_mpesa_access_token';

        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            return $cachedToken;
        }

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->get($this->base_url . '/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            $results = json_decode($response->body(), false);

            $accessToken = $results->access_token;
            $expiresIn = (int) $results->expires_in;

            Cache::put($cacheKey, $accessToken, $expiresIn);
            return $accessToken;
        }

        throw new Exception('Failed to generate access token.');
    }

    public function registerUrls()
    {
        $response = Http::withHeaders([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json',
        ])
            ->post($this->base_url . 'mpesa/c2b/v1/registerurl', [
                'ShortCode' => $this->paybill,
                'ResponseType' => 'Completed',
                'ConfirmationURL' => $this->c2bconfirm,
                'ValidationURL' => $this->c2bvalidate,
            ]);

        return $response->body();
    }

    public function simulateC2B(int $amount, $msisdn, $reference)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Basic ',
        ])
            ->post($this->base_url . 'mpesa/c2b/v1/simulate', [
                'ShortCode' => $this->paybill,
                'CommandID' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'Msisdn' => $msisdn,
                'BillRefNumber' => $reference,
            ]);

        return $response->body();
    }

    public function express($amount, $phone, $reference, $remark = null)
    {
        $timestamp = date('YmdHis');

        $password = base64_encode($this->paybill . $this->passkey . $timestamp);
        $url = config('evance-mpesa.mpesa_env') ? $this->base_url . 'mpesa/stkpush/v1/processrequest' : $this->base_url . 'mpesa/stkpush/v1/processrequest';

        $response = Http::withHeaders([
            'Content-Type: application/json; charset=UTF-8',
            'Authorization:Bearer ' . $this->access_token
        ])
            ->post($url, [
                'BusinessShortCode' => intval($this->paybill),
                'Timestamp' => $timestamp,
                'Password' => $password,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => '254' . substr($phone, -9),
                'PartyB' => $this->paybill,
                'PhoneNumber' => '254' . substr($phone, -9),
                'CallBackURL' => $this->callbackurl,
                'AccountReference' => $reference,
                'TransactionDesc' => $remark,
                'Remark' => $remark,
            ]);

        return $response->body();
    }


    public function simulateB2C(
        string $initiator_name,
        string $security_credential,
        $command_id = 'BusinessPayment',
        int $amount,
        int $party_a,
        $party_b,
        $remarks = null,
        $occassion = null
    ) {

        $response = Http::withHeaders([
            'Authorization:Bearer ' . $this->access_token,
            'Content-Type: application/json; charset=UTF-8',

        ])
            ->post($this->base_url . 'mpesa/b2c/v3/paymentrequest', [
                "OriginatorConversationID" => Str::uuid(),
                "InitiatorName" => $initiator_name,
                "SecurityCredential" => $security_credential,
                "CommandID" => $command_id,
                "Amount" => $amount,
                "PartyA" => $party_a,
                "PartyB" => "254" . substr($party_b, -9),
                "Remarks" => $remarks,
                "QueueTimeOutURL" => $this->queue_timeout_url,
                "ResultURL" => $this->callback_results,
                "Occassion" => $occassion
            ]);

        return $response->body();
    }


    public function transactionStatus(
        string $initiator_name,
        string $security_credential,
        int $party_a,
        string $transaction_id,
        string $originator_conversation_id = null
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ',
        ])
            ->post(
                $this->base_url . 'mpesa/transactionstatus/v1/query',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "Command ID" => "TransactionStatusQuery",
                    "Transaction ID" => str($transaction_id)->upper(),
                    "OriginatorConversationID" => $originator_conversation_id,
                    "PartyA" => $party_a,
                    "IdentifierType" => "4",
                    "ResultURL" => $this->callback_results,
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "Remarks" => "OK",
                    "Occasion" => "OK"
                ]
            );

        return $response->body();
    }


    public function dynamicQR(
        string $merchant_name,
        string $reference,
        int $amount,
        string $trx_code = 'BG',
        int $cpi_number = null,
        string $size = "300"
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/qrcode/v1/generate',
                [
                    "MerchantName" => $merchant_name,
                    "RefNo" => $reference,
                    "Amount" => $amount,
                    "TrxCode" => $trx_code,
                    "CPI" => $cpi_number ?? $this->shortcode,
                    "Size" => $size
                ]
            );

        return $response->body();
    }


    public function accountBalance(
        string $initiator_name,
        string $security_credential,
        int $party_a,
    ) {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/accountbalance/v1/query',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "Command ID" => "AccountBalance",
                    "PartyA" => $party_a,
                    "IdentifierType" => "4",
                    "Remarks" => "ok",
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "ResultURL" => $this->callback_results
                ]
            );

        return $response->body();
    }


    public function reversal(
        string $initiator_name,
        string $security_credential,
        string $transaction_id,
        int $receiver_party,
    ) {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/reversal/v1/request',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "CommandID" => "TransactionReversal",
                    "TransactionID" => $transaction_id,
                    "ReceiverParty" => $receiver_party,
                    "RecieverIdentifierType" => "11",
                    "ResultURL" => $this->callback_results,
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "Remarks" => "Ok",
                    "Occasion" => "Ok"
                ]
            );

        return $response->body();
    }


    public function taxRemittance(
        string $initiator_name,
        string $security_credential,
        int $amount,
        int $party_a
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/b2b/v1/remittax',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "Command ID" => "PayTaxToKRA",
                    "SenderIdentifierType" => "4",
                    "RecieverIdentifierType" => "4",
                    "Amount" => $amount,
                    "PartyA" => $party_a,
                    "PartyB" => "572572",
                    "AccountReference" => "353353",
                    "Remarks" => "OK",
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "ResultURL" => $this->callback_results
                ]
            );

        return $response->body();
    }

    public function businessPayBill(
        string $initiator_name,
        string $security_credential,
        int $reference,
        int $amount,
        int $party_a,
        $party_b,
        $remarks = "OK"
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/b2b/v1/paymentrequest',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "Command ID" => "BusinessPayBill",
                    "SenderIdentifierType" => "4",
                    "RecieverIdentifierType" => "4",
                    "Amount" => $amount,
                    "PartyA" => $party_a,
                    "PartyB" => $party_b,
                    "AccountReference" => substr($reference, 13),
                    "Requester" => null,
                    "Remarks" => $remarks,
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "ResultURL" => $this->callback_results
                ]
            );

        return $response->body();
    }


    public function buyGoods(
        string $initiator_name,
        string $security_credential,
        int $reference,
        int $amount,
        int $party_a,
        $party_b,
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/b2b/v1/paymentrequest',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "Command ID" => "BusinessBuyGoods",
                    "SenderIdentifierType" => "4",
                    "RecieverIdentifierType" => "4",
                    "Amount" => $amount,
                    "PartyA" => $party_a,
                    "PartyB" => $party_b,
                    "AccountReference" => substr($reference, 13),
                    "Requester" => null,
                    "Remarks" => "OK",
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "ResultURL" => $this->callback_results
                ]
            );

        return $response->body();
    }


    public function b2bExpressCheckout(
        string $primary_short_code,
        string $receiver_short_code,
        int $amount,
        string $reference,
        string $patner_name
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'v1/ussdpush/get-msisdn',
                [
                    "primaryShortCode" => $primary_short_code,
                    "receiverShortCode" => $receiver_short_code,
                    "amount" => $amount,
                    "paymentRef" => $reference,
                    "callbackUrl" => $this->callback_results,
                    "partnerName" => $patner_name,
                    "RequestRefID" => Str::uuid()
                ]
            );

        return $response->body();
    }


    public function b2cAccountTopUp(
        string $initiator_name,
        string $security_credential,
        int $reference,
        int $amount,
        int $party_a,
        $party_b,
    ) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->access_token,
        ])
            ->post(
                $this->base_url . 'mpesa/b2b/v1/paymentrequest',
                [
                    "Initiator" => $initiator_name,
                    "SecurityCredential" => $security_credential,
                    "CommandID" => "BusinessPayToBulk",
                    "SenderIdentifierType" => "4",
                    "RecieverIdentifierType" => "4",
                    "Amount" => $amount,
                    "PartyA" => $party_a,
                    "PartyB" => $party_b,
                    "AccountReference" => substr($reference, 13),
                    "Requester" => null,
                    "Remarks" => "OK",
                    "QueueTimeOutURL" => $this->queue_timeout_url,
                    "ResultURL" => $this->callback_results
                ]
            );

        return $response->body();
    }
}
