<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use Exception;

class PaymentController extends Controller
{
    // Generation of Referal url for payment
    public function getReferalUrl()
    {
        try {
            $getRefUrl = new GetRefUrl;
            $url = $getRefUrl->generateRefUrl();
            return responseMsgs(true, "", $url);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }
}
