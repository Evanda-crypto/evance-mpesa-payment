<?php

namespace EvanceOdhiambo\MpesaPayment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class MpesaResponseController extends Controller
{
    public function c2bConfrimCallBack(Request $request)
    {
        return $request;
    }

    public function c2bValidateCallBack(Request $request)
    {
        return $request;
    }

    public function c2bCallBack(Request $request)
    {
        return $request;
    }


    public function b2cCallback(Request $request)
    {
        return $request;
    }
    public function queueTimeoutCallback(Request $request)
    {
        return $request;
    }

}
