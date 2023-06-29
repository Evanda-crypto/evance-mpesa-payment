<?php

namespace EvanceOdhiambo\MpesaPayment;

use Illuminate\Support\Facades\Cache;

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

    public function __construct()
    {

        $this->base_url = (config('evance-mpesa.mpesa_env') == 'sandbox') ? 'https://sandbox.safaricom.co.ke/mpesa/' : 'https://api.safaricom.co.ke/mpesa/';

        $this->consumer_key = config('evance-mpesa.consumer_key'); // Consumer Key from your daraja app

        $this->consumer_secret = config('evance-mpesa.consumer_secret'); //Consumer secrete from Your daraja app

        $this->paybill = config('evance-mpesa.paybill'); //Paybill number registered or use daraja's test

        $this->shortcode = config('evance-mpesa.shortcode');
        $this->passkey = config('evance-mpesa.passkey');

        $this->callbackurl = config('evance-mpesa.callbackurl');

        // c2b the urls
        $this->c2bvalidate = config('evance-mpesa.c2b_validate_callback');
        $this->c2bconfirm = config('evance-mpesa.c2b_confirm_callback');

        $this->access_token = $this->generateSandboxToken(); //Set up access token
    }

    public function generateSandboxToken()
    {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $credentials = base64_encode('' . $this->consumer_key . ':' . $this->consumer_secret . '');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);

        $json_response = json_decode($curl_response, false);

        return $json_response->access_token;
    }

    public function registerUrls()
    {
        $request_data = array(
            'ShortCode' => $this->paybill,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->c2bconfirm,
            'ValidationURL' => $this->c2bvalidate,
        );
        $data = json_encode($request_data);

        $ch = curl_init('' . $this->base_url . 'c2b/v1/registerurl');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->cachedToken(),
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;

    }

    private function generalCurlRequest($url, $data)
    {

        $access_token = isset($this->access_token) ? $this->access_token : $this->cachedToken();

        if ($access_token != '' || $access_token !== false) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        }
        return false;

    }

    /**
     * C2B Simulation
     * Used to simulate a C2B Transaction to test your ConfirmURL and ValidationURL in the Client to Business method
     */

    public function simulatec2b($amount, $msisdn, $ref)
    {
        $data = array(
            'ShortCode' => $this->paybill,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'Msisdn' => $msisdn,
            'BillRefNumber' => $ref,
        );
        $data = json_encode($data);
        $url = $this->base_url . 'c2b/v1/simulate';
        $response = $this->generalCurlRequest($url, $data);

        return $response;
    }

    public function express($amount, $phone, $ac_ref, $remark = null)
    {

        $final_phone = '254' . substr($phone, -9);
        $timestamp = date('YmdHis');

        $password = base64_encode($this->paybill . $this->passkey . $timestamp);
        $url = config('evance-mpesa.mpesa_env') ? '' . $this->base_url . 'stkpush/v1/processrequest' : '' . $this->base_url . 'stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=UTF-8', 'Authorization:Bearer ' . $this->access_token));

        $curl_post_data = array(
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
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $response = curl_exec($curl);

        return $response;
    }

    public function cachedToken()
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

    public function test()
    {
        return 'Package working';
    }
}
