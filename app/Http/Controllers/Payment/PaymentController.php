<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use App\Models\Payment\IciciPaymentReq;
use App\Models\Payment\IciciPaymentResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    // Generation of Referal url for payment for Testing
    public function getReferalUrl(Request $req)
    {
        $getRefUrl = new GetRefUrl;
        $mIciciPaymentReq = new IciciPaymentReq();
        try {
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
        $mIciciPaymentReq = new IciciPaymentReq();
        $mIciciPaymentRes = new IciciPaymentResponse();

        try {
            $data = $req->all();
            $reqRefNo = $req->reqRefNo;
            if ($req->Status == 'Success') {
                $resRefNo = $req->resRefNo;
                $paymentReqsData = $mIciciPaymentReq->findByReqRefNo($reqRefNo);
                $updReqs = [
                    'res_ref_no' => $resRefNo,
                    'payment_status' => 1
                ];
                DB::connection('pgsql_master')->beginTransaction();
                $paymentReqsData->update($updReqs);                 // Table Updation
                $resPayReqs = [
                    "payment_req_id" => $paymentReqsData->id,
                    "req_ref_id" => $reqRefNo,
                    "res_ref_id" => $resRefNo,
                    "icici_signature" => $req->signature,
                    "payment_status" => 1
                ];
                $mIciciPaymentRes->create($resPayReqs);             // Resonse Data 
            }
            $filename = time() . "webhook.json";
            Storage::disk('local')->put($filename, json_encode($data));
            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Data Received Successfully", []);
        } catch (Exception $e) {
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), []);
        }
    }
}
