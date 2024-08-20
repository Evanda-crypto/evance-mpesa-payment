<?php

return [

    //Specify the environment mpesa is running, sandbox or production
    'mpesa_env' => env('MPESA_ENV', 'sandbox'),
    /*-----------------------------------------
   |The App consumer key
   |------------------------------------------
   */
    'consumer_key'   => env('CONSUMER_KEY'),

    /*-----------------------------------------
   |The App consumer Secret
   |------------------------------------------
   */
    'consumer_secret' => env('CONSUMER_SECRET'),

    /*-----------------------------------------
   |The paybill number
   |------------------------------------------
   */
    'paybill' => env('PAYBILL'),

    /*-----------------------------------------
   |Lipa Na Mpesa Online Shortcode
   |------------------------------------------
   */
    'shortcode'  => env("SHORTCODE"),

    /*-----------------------------------------
   |Lipa Na Mpesa Online Passkey
   |------------------------------------------
   */
    'passkey' => env('PASSKEY'),

    /*-----------------------------------------
   |C2B  Validation url
   |------------------------------------------
   */
    'c2b_validate_callback' => env('C2B_VALIDATE_CALLBACK', url('/') . '/api/evance-mpesa/c2b_validate'),

    /*-----------------------------------------
   |C2B confirmation url
   |------------------------------------------
   */
    'c2b_confirm_callback' => env('C2B_CONFIRM_CALLBACK', url('/') . '/api/evance-mpesa/c2b_validate'),

    /*-----------------------------------------
   |C2B Callback url
   |------------------------------------------
   */
    'c2b_callbackurl' => env('C2B_CALLBACK_URL', url('/') . '/api/evance-mpesa/c2b_callback'),

    /*-----------------------------------------
   |B2C Callback url
   |------------------------------------------
   */
    'b2c_callbackurl' => env('B2C_CALLBACK_URL', url('/') . '/api/evance-mpesa/b2c_callback'),

    /*-----------------------------------------
   |B2C Queue Timeout url
   |------------------------------------------
   */

    'queue_timeout_url' => env('QUEUE_TIMEOUT_URL', url('/') . '/api/evance-mpesa/queue_timeout_url')

];
