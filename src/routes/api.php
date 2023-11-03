<?php

use Illuminate\Support\Facades\Route;
use EvanceOdhiambo\MpesaPayment\Controllers\MpesaResponseController;

Route::prefix('evance')->group(function () {

    Route::controller(MpesaResponseController::class)->group(function () {
        Route::post('/confirm', 'confrimCallBack');
        Route::post('/validate', 'validateCallBack');
        Route::post('/callback', 'CallBack');
    });
});
