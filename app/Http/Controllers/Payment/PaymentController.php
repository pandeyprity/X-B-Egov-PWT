<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    // Generation of Referal url for payment
    public function getReferalUrl()
    {
        try {
            $getRefUrl = new GetRefUrl;
            $url = $getRefUrl->generateRefUrl();
            return responseMsgs(true, "", $url['encryptUrl']);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }

    /**
     * | Get Webhook data
     */
    public function getWebhookData(Request $req)
    {
        try {
            $data = $req->all();
            $filename = time() . "webhook.json";
            Storage::disk('local')->put($filename, json_encode($data));
            return responseMsgs(true, "Data Received Successfully", []);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }
}
