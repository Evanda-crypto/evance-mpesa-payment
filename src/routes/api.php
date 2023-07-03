<?php

use Illuminate\Support\Facades\Route;
use EvanceOdhiambo\MpesaPayment\Controllers\MpesaResponseController;

Route::prefix('evance')->group(function () {
    Route::post('/confirm', [MpesaResponseController::class,'confrimCallBack']);
    Route::post('/validate',[MpesaResponseController::class,'validateCallBack']);
    Route::post('/callback',[MpesaResponseController::class,'CallBack']);
});
