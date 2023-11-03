<?php

/*
 * Set Your configurations from .env
 */
return [

    //Specify the environment mpesa is running, sandbox or production
    'mpesa_env' => env('MPESA_ENV'),
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
   'paybill'         => env('PAYBILL'),

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
   'c2b_validate_callback' => env('C2B_VALIDATE_CALLBACK'),

   /*-----------------------------------------
   |C2B confirmation url
   |------------------------------------------
   */
   'c2b_confirm_callback' => env('C2B_CONFIRM_CALLBACK'),

      /*-----------------------------------------
   |C2B Callback url
   |------------------------------------------
   */
  'callbackurl' => env('CALLBACK_URL'),
];