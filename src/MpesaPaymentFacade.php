<?php

namespace EvanceOdhiambo\MpesaPayment;

use Illuminate\Support\Facades\Facade;

/**
 * @see \EvanceOdhiambo\MpesaPayment\Skeleton\SkeletonClass
 */
class MpesaPaymentFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mpesa-payment';
    }
}
