<?php

use Illuminate\Support\Facades\Route;
use EvanceOdhiambo\MpesaPayment\Controllers\MpesaResponseController;

Route::prefix('evance-mpesa')
    ->controller(MpesaResponseController::class)
    ->group(function () {
        Route::post('/c2b_confirm',  'c2bConfrimCallBack');
        Route::post('/c2b_validate', 'c2bValidateCallBack');
        Route::post('/c2b_callback', 'c2bCallBack');
        Route::post('/b2c_callback', 'b2cCallback');
        Route::post('/queue_timeout_url', 'queueTimeoutCallback');
    });