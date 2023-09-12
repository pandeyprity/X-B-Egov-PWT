<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use App\Models\Payment\IciciPaymentReq;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    // Generation of Referal url for payment for Testing
    public function getReferalUrl(Request $req)
    {
        try {
            $getRefUrl = new GetRefUrl;
            $mIciciPaymentReq = new IciciPaymentReq();
            $url = $getRefUrl->generateRefUrl();
            $paymentReq = [
                "user_id" => $req->userId,
                "workflow_id" => $req->workflowId,
                "req_ref_no" => $req->_refNo,
                "amount" => $req->amount,
                "application_id" => $req->applicationId,
                "module_id" => $req->moduleId,
                "ulb_id" => $req->ulbId,
                "referal_url" => $url['encryptUrl']
            ];
            $mIciciPaymentReq->create($paymentReq);
            return responseMsgs(true,  $url['plainUrl'], $url['encryptUrl']);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }

    // Module Referal Urls

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
