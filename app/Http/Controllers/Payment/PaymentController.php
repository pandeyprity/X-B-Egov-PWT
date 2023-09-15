<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use App\Models\Payment\IciciPaymentReq;
use App\Models\Payment\IciciPaymentResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    private $_paymentStatus;

    public function __construct()
    {
        $this->_paymentStatus = Config::get('payment-constants.PAYMENT_STATUS');
    }

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
            // ❗❗ Pending for Module Specific Table Updation ❗❗

            $filename = time() . "webhook.json";
            Storage::disk('local')->put($filename, json_encode($data));
            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Data Received Successfully", []);
        } catch (Exception $e) {
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), []);
        }
    }

    /**
     * | Get data by reference no 
     */
    public function getPaymentDataByRefNo(Request $req)
    {
        $getPayemntDetails  = new GetRefUrl;
        $mIciciPaymentReq   = new IciciPaymentReq();
        $mIciciPaymentRes   = new IciciPaymentResponse();
        try {
            $user               = authUser($req);
            $resRefNo           = $req->referencNo;
            $confPaymentStatus  = $this->_paymentStatus;
            $paymentReqData     = $mIciciPaymentReq->findByReqRefNo($resRefNo);
            if (!$paymentReqData) {
                throw new Exception("Payment request of $resRefNo not found!");
            }

            $paymentJsonData = $this->filterReqReqData($req);
            # Get the payment req for refNo
            switch ($paymentReqData->payment_status) {
                case ($confPaymentStatus['PENDING']):
                    $PaymentHistory = $getPayemntDetails->getPaymentStatusByUrl($resRefNo);
                    break;
                default:

                    break;
            }


            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Payment Received Successfully", []);
        } catch (Exception $e) {
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), []);
        }
    }

    /**
     * | filter the string data into json
     */
    public function filterReqReqData($req)
    {
        $string         = "status=NotInitiated&ezpaytranid=NA&amount=NA&trandate=NA&pgreferenceno=null&sdt=&BA=null&PF=null&TAX=null&PaymentMode=null";
        $keyValuePairs  = explode('&', $string);
        $data           = [];

        foreach ($keyValuePairs as $pair) {
            list($key, $value) = explode('=', $pair);
            $value = ($value === 'null') ? null : $value;
            $data[$key] = $value;
        }

        $jsonData = json_encode($data);
        if ($jsonData === false) {
            throw new Exception("JSON encoding failed!");
        } else {
            return $jsonData;
        }
    }
}
