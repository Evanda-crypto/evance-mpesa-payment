<?php

namespace EvanceOdhiambo\MpesaPayment;

use Illuminate\Support\Facades\Cache;
use EvanceOdhiambo\MpesaPayment\Controllers\MpesaResponseController;
use Illuminate\Support\Facades\Http;

class MpesaPayment
{

    private $base_url;

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

    public function __construct()
    {
        $this->app_base_url = url('/') . '/evance';

        $this->base_url = (config('evance-mpesa.mpesa_env') == 'sandbox') ? 'https://sandbox.safaricom.co.ke/mpesa/' : 'https://api.safaricom.co.ke/mpesa/';

        $this->consumer_key = config('evance-mpesa.consumer_key'); // Consumer Key from your daraja app

        $this->consumer_secret = config('evance-mpesa.consumer_secret'); //Consumer secrete from Your daraja app

        $this->paybill = config('evance-mpesa.paybill'); //Paybill number registered or use daraja's test

        $this->shortcode = config('evance-mpesa.shortcode');
        $this->passkey = config('evance-mpesa.passkey');

        $this->callbackurl = (!empty(config('evance-mpesa.callbackurl'))) ? config('evance-mpesa.callbackurl') : $this->app_base_url . '/callback';

        // c2b the urls
        $this->c2bvalidate = (!empty(config('evance-mpesa.c2b_validate_callback'))) ? config('evance-mpesa.c2b_validate_callback') : $this->app_base_url . '/validate';

        $this->c2bconfirm = (!empty(config('evance-mpesa.c2b_confirm_callback'))) ? config('evance-mpesa.c2b_confirm_callback') : $this->app_base_url . '/confirm';

        $this->access_token = $this->generateSandboxToken(); //Set up access token

        $this->callback_results = new MpesaResponseController();


    }

    public function generateSandboxToken()
    {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->withoutVerifying()->get($url);

        $json_response = $response->json();

        return $json_response['access_token'];
    }


    public function registerUrls()
    {
        $request_data = [
            'ShortCode' => $this->paybill,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->c2bconfirm,
            'ValidationURL' => $this->c2bvalidate,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken(),
            'Content-Type' => 'application/json',
        ])->post($this->base_url . 'c2b/v1/registerurl', $request_data);

        return $response->body();
    }

    public function simulatec2b($amount, $msisdn, $ref)
    {
        $data = array(
            'ShortCode' => $this->paybill,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'Msisdn' => $msisdn,
            'BillRefNumber' => $ref,
        );

        $access_token = isset($this->access_token) ? $this->access_token : $this->accessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])->post($this->base_url . 'c2b/v1/simulate', $data);

        return $response->body();
    }

    public function express($amount, $phone, $ac_ref, $remark = 'Evance Mpesa Package')
    {
        $final_phone = '254' . substr($phone, -9);
        $timestamp = now()->format('YmdHis');

        $password = base64_encode($this->paybill . $this->passkey . $timestamp);
        $url = config('evance-mpesa.mpesa_env') ? $this->base_url . 'stkpush/v1/processrequest' : $this->base_url . 'stkpush/v1/processrequest';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=UTF-8',
            'Authorization' => 'Bearer ' . $this->access_token,
        ])->post($url, [
                    'BusinessShortCode' => intval($this->paybill),
                    'Timestamp' => $timestamp,
                    'Password' => $password,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $final_phone,
                    'PartyB' => $this->paybill,
                    'PhoneNumber' => $final_phone,
                    'CallBackURL' => $this->callbackurl,
                    'AccountReference' => $ac_ref,
                    'TransactionDesc' => $remark,
                    'Remark' => $remark,
                ]);

        return $response->body();
    }

    public function accessToken()
    {
        $token = Cache::get('access_token');
        $tokenTimestamp = Cache::get('token_timestamp');

        // Check if the token is expired or not present in the cache
        if ($token === null || $this->isTokenExpired($tokenTimestamp)) {
            // Refresh the token
            $token = $this->refreshToken();
        }

        return $token;
    }

    private function isTokenExpired($tokenTimestamp)
    {
        $expirationTime = 3600; // Token expiration time in seconds (1 hour)

        return (time() - $tokenTimestamp) >= $expirationTime;
    }

    private function refreshToken()
    {
        // Perform token refresh logic
        $newToken = $this->generateSandboxToken();
        $newTokenTimestamp = time();

        // Store the new token and its timestamp in the cache
        $expirationTimeInSeconds = 3600; // Cache expiration time in seconds (1 hour)
        Cache::put('access_token', $newToken, $expirationTimeInSeconds);
        Cache::put('token_timestamp', $newTokenTimestamp, $expirationTimeInSeconds);

        return $newToken;
    }

    public function validationResults()
    {
        return $this->callback_results->validateCallBack();
    }

    public function confirmationResults()
    {
        return $this->callback_results->confrimCallBack();
    }

    public function callBackResults()
    {
        return $this->callback_results->CallBack();
    }
}
