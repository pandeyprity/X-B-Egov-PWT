<?php

namespace App\Http\Controllers\Payment;

use App\BLL\Payment\GetRefUrl;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\HoldingTaxController;
use App\Http\Requests\Property\ReqPayment;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Payment\IciciPaymentReq;
use App\Models\Payment\IciciPaymentResponse;
use App\Models\Payment\PinelabPaymentReq;
use App\Models\Payment\PinelabPaymentResponse;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\TradeCitizen;
use App\Repository\Water\Concrete\WaterNewConnection;
use Carbon\Carbon;
use Exception;
use Hamcrest\Core\HasToString;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


/**
 * | Created On - 30-09-2023
 * | Author - Sam kerketta
 * | Status-Open
 */

class IciciPaymentController extends Controller
{

    /**
     * | Generation of Referal url for payment for Testing for ICICI payent gateway
        | Serila No :
        | Under Con
     */
    public function getReferalUrl(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "workflowId"    => "nullable|int",
                "amount"        => "required|min:1",
                "id"            => "required",
                // "callBackUrls"  => "required",
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $getRefUrl          = new GetRefUrl();
            $mIciciPaymentReq   = new IciciPaymentReq();
            $url                = $getRefUrl->generateRefUrl($req);
            $paymentReq = [
                "user_id"           => $req->auth->id ?? $req->userId,
                "workflow_id"       => $req->workflowId ?? 0,
                "req_ref_no"        => $getRefUrl->_refNo,
                "amount"            => $req->amount,
                "application_id"    => $req->id,
                "module_id"         => $req->departmentId,
                "ulb_id"            => $req->ulbId,
                "referal_url"       => $url['encryptUrl'],
                // "call_back_url"     => $req->callBackUrls
            ];
            $mIciciPaymentReq->create($paymentReq);
            $returnDetails = [
                "encryptUrl" => $url['encryptUrl'],
                "req_ref_no" => $getRefUrl->_refNo
            ];
            return responseMsgs(true,  ["plainUrl" => $url['plainUrl'], "req_ref_no" => $getRefUrl->_refNo], $returnDetails);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }


    /**
     * | Get Webhook data for icici payament 
        | Serial No :
        | Under Con
     */
    public function getWebhookData(Request $req)
    {
        $mIciciPaymentReq = new IciciPaymentReq();
        $mIciciPaymentRes = new IciciPaymentResponse();

        try {
            # Save the data in file 
            $random = strtotime(Carbon::now());
            $webhoohEncripted = $req->getContent();
            $getRefUrl = new GetRefUrl();
            $webhookData = $getRefUrl->decryptWebhookData($webhoohEncripted);
            Storage::disk('public')->put('icici/webhook/' . $webhookData->TrnId . '.json', json_encode($webhookData));

            # Get the payamen request
            // $paymentReqsData = $mIciciPaymentReq->findByReqRefNoV2($reqRefNo);
            // if (!$paymentReqsData) {
            //     throw new Exception("Payment request dont exist for $reqRefNo");
            // }

            // if ($req->Status == 'Success') {
            //     $updReqs = [
            //         'res_ref_no'        => $resRefNo,
            //         'payment_status'    => 1
            //     ];
            //     DB::connection('pgsql_master')->beginTransaction();
            //     $paymentReqsData->update($updReqs);                 // Table Updation
            //     $resPayReqs = [
            //         "payment_req_id"    => $paymentReqsData->id,
            //         "req_ref_id"        => $reqRefNo,
            //         "res_ref_id"        => $resRefNo,
            //         "icici_signature"   => $req->signature,
            //         "payment_status"    => 1
            //     ];
            //     $mIciciPaymentRes->create($resPayReqs);             // Response Data 
            // }

            // ❗❗ Pending for Module Specific Table Updation / Dont user to transfer data to module ❗❗
            // switch ($paymentReqsData->module_id) {
            //     case '1':
            //         break;

            //     case '2':

            //         break;
            //     case '2':

            //         break;
            // }

            // DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Data Received Successfully", []);
        } catch (Exception $e) {
            // DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), []);
        }
    }


    /**
     * | Collect callback url details for payment
        | Under con  
        | Use in case of webhook not used
     */
    public function getCallbackDetial(Request $req)
    {
        try {
            // $reqBody = [
            //     "Response_Code"         => "E000",               // Payment status
            //     "Unique_Ref_Number"     => "2310131666814",      // Tran no
            //     "Service_Tax_Amount"    => "0.0",
            //     "Processing_Fee_Amount" => "0.00",
            //     "Total_Amount"          => "100",
            //     "Transaction_Amount"    => "100",
            //     "Transaction_Date"      => "13-10-2023 12:34:35",
            //     "Interchange_Value"     => null,
            //     "TDR"                   => null,
            //     "Payment_Mode"          => "NET_BANKING",
            //     "SubMerchantId"         => "45",
            //     "ReferenceNo"           => "1697180633788010986", // Refno
            //     "ID"                    => "136082",
            //     "RS"                    => "73b4de05181599bf5809e4bc37edc9c32612e0bbedff71f09f8db68d5a0f9e29bc44be8d8fc7d9d5a5446c9b07674bdf6093a90b18a75b1758dc1ee77d044a6d",
            //     "TPS"                   => "Y",
            //     "mandatory_fields"      => "1697180633788010986|45|100|13/Oct/2023|0123456789|xy|xy",
            //     "optional_fields"       => "X|X|X",
            //     "RSV"                   => "8c988a820acc67ee8b0ebd2c525e3e4c88575cc2fef7f9c7dc1f2dbbad9002c86471ed446500deb4b6f1e25b11b091f7575469c0d603bccf1ba361c30f83f1a7",
            // ];

            # Save the callback data
            $mIciciPaymentReq = new IciciPaymentReq();
            Storage::disk('public')->put('icici/callback/' . $req->Unique_Ref_Number . '.json', json_encode($req->all()));

            # redirect to 
            $refData = [
                "callBack" => "https://modernulb.com/property/payment-success/87878787"
            ];
            return view('icici_payment_call_back', $refData);

            # Check if the payament is success 
            if ($req->Response_Code == "E000") {

                # Check the transaction initials
                $paymentReqsData = $mIciciPaymentReq->findByReqRefNoV2($req->ReferenceNo);
                if (!$paymentReqsData) {
                    # Redirect to the error page
                    $erroData = [];
                    return view('icici_payment_call_back', $erroData);
                }

                # redirect to 
                $refData = [
                    "callBack" => $paymentReqsData->call_back_url
                ];
                return view('icici_payment_call_back', $refData);
            }
        } catch (Exception $e) {
            $erroData = [];
            return view('icici_payment_call_back', $erroData);
        }
    }
}
