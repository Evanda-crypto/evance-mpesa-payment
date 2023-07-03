<?php

namespace EvanceOdhiambo\MpesaPayment\Controllers;

use App\Http\Controllers\Controller;

class MpesaResponseController extends Controller
{
    public function confrimCallBack()
    {
        return file_get_contents('php://input');
    }

    public function validateCallBack()
    {
        return file_get_contents('php://input');
    }

    public function CallBack()
    {
        return file_get_contents('php://input');
    }

}
