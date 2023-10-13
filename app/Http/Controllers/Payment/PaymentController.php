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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-27-09-2023 
 * | Author - Anshu Kumar
 * | Updated - Sam kerketta
 * | Status-Open
 */

class PaymentController extends Controller
{
    private $_paymentStatus;
    protected $_safRepo;

    public function __construct(iSafRepository $safRepo)
    {
        $this->_paymentStatus = Config::get('payment-constants.PAYMENT_STATUS');
        $this->_safRepo = $safRepo;
    }

    // Generation of Referal url for payment for Testing for ICICI payent gateway
    public function getReferalUrl(Request $req)
    {
        $getRefUrl          = new GetRefUrl;
        $mIciciPaymentReq   = new IciciPaymentReq();
        try {
            $url = $getRefUrl->generateRefUrl();
            $paymentReq = [
                "user_id"           => $req->userId,
                "workflow_id"       => $req->workflowId,
                "req_ref_no"        => $getRefUrl->_refNo,
                "amount"            => $req->amount,
                "application_id"    => $req->applicationId,
                "module_id"         => $req->moduleId,
                "ulb_id"            => $req->ulbId,
                "referal_url"       => $url['encryptUrl']
            ];
            $mIciciPaymentReq->create($paymentReq);
            return responseMsgs(true,  ["plainUrl" => $url['plainUrl'], "req_ref_no" => $getRefUrl->_refNo], $url['encryptUrl']);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }

    /**
     * | Collect callback url details for payment
        | Under con  
     */
    public function getCallbackDetial(Request $req)
    {
        $mIciciPaymentReq = new IciciPaymentReq();
        $mIciciPaymentRes = new IciciPaymentResponse();

        try {

            $reqBody = [
                "Response_Code"         => "E000",               // Payment status
                "Unique_Ref_Number"     => "2310131666814",      // Tran no
                "Service_Tax_Amount"    => "0.0",
                "Processing_Fee_Amount" => "0.00",
                "Total_Amount"          => "100",
                "Transaction_Amount"    => "100",
                "Transaction_Date"      => "13-10-2023 12:34:35",
                "Interchange_Value"     => null,
                "TDR"                   => null,
                "Payment_Mode"          => "NET_BANKING",
                "SubMerchantId"         => "45",
                "ReferenceNo"           => "1697180633788010986", // Refno
                "ID"                    => "136082",
                "RS"                    => "73b4de05181599bf5809e4bc37edc9c32612e0bbedff71f09f8db68d5a0f9e29bc44be8d8fc7d9d5a5446c9b07674bdf6093a90b18a75b1758dc1ee77d044a6d",
                "TPS"                   => "Y",
                "mandatory_fields"      => "1697180633788010986|45|100|13/Oct/2023|0123456789|xy|xy",
                "optional_fields"       => "X|X|X",
                "RSV"                   => "8c988a820acc67ee8b0ebd2c525e3e4c88575cc2fef7f9c7dc1f2dbbad9002c86471ed446500deb4b6f1e25b11b091f7575469c0d603bccf1ba361c30f83f1a7",


            ];

            $dbData = [
                "response_code",
                "unique_ref_number",
                "service_tax_amount",
                "processing_fee_amount",
                "total_amount",
                "transaction_amount",
                "transaction_date",
                "interchange_value",
                "tdr",
                "payment_mode",
                "sub_merchant_id",
                "reference_no",
                "icici_id",
                "rs",
                "tps",
                "mandatory_fields",
                "optional_fields",
                "rsv",
            ];



            Storage::disk('public')->put('icici/webhook/' . "testV1" . '.json', json_encode($req->all()));
            $reqRefNo           = $req->ReferenceNo;
            $paymentReqsData    = $mIciciPaymentReq->findByReqRefNoV2($reqRefNo);
            if (!$paymentReqsData) {
                throw new Exception("Payment request dont exist for $reqRefNo");
            }

            if ($req->Response_Code == 'E000')  // Status of success
            {
                # Update the icici request table data for payamet success
                $updReqs = [
                    'payment_status' => 1
                ];
                $paymentReqsData->update($updReqs);

                # Save the request data to the icici resposne table 
                // $resPayReqs = [
                //     "payment_req_id"    => $paymentReqsData->id,
                //     "req_ref_id"        => $reqRefNo,
                //     "res_ref_id"        => $resRefNo,
                //     "icici_signature"   => $req->signature,
                //     "payment_status"    => 1
                // ];
                // $mIciciPaymentRes->create($resPayReqs);
            }
        } catch (Exception $e) {
        }
    }


    /**
     * | Get Webhook data
     */
    public function getWebhookData(Request $req)
    {
        $mIciciPaymentReq = new IciciPaymentReq();
        $mIciciPaymentRes = new IciciPaymentResponse();

        try {
            Storage::disk('public')->put('icici/webhook/' . $req->reqRefNo . '.json', json_encode($req->all()));
            $data               = $req->all();
            $reqRefNo           = $req->reqRefNo;
            $resRefNo           = $req->resRefNo;
            $paymentReqsData    = $mIciciPaymentReq->findByReqRefNoV2($reqRefNo);
            if (!$paymentReqsData) {
                throw new Exception("Payment request dont exist for $reqRefNo");
            }

            if ($req->Status == 'Success') {
                $updReqs = [
                    'res_ref_no'        => $resRefNo,
                    'payment_status'    => 1
                ];
                DB::connection('pgsql_master')->beginTransaction();
                $paymentReqsData->update($updReqs);                 // Table Updation
                $resPayReqs = [
                    "payment_req_id"    => $paymentReqsData->id,
                    "req_ref_id"        => $reqRefNo,
                    "res_ref_id"        => $resRefNo,
                    "icici_signature"   => $req->signature,
                    "payment_status"    => 1
                ];
                $mIciciPaymentRes->create($resPayReqs);             // Response Data 
            }
            // ❗❗ Pending for Module Specific Table Updation / Dont user to transfer data to module ❗❗
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
     * | Used to verify the payment and to transfer data to module
        | Under Cons
     */
    public function getPaymentDataByRefNo(Request $req)     // Request body will give ref no
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
        | Under Con
        | Make this a Helper or in microservice 
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

    /**
     * | Generate Order Id
        | Close
        | Make this a Helper or in microservice 
     */
    protected function getOrderId(int $modeuleId)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        $orderId = (("Order_" . $modeuleId . date('dmyhism') . $randomString));
        $orderId = explode("=", chunk_split($orderId, 30, "="))[0];
        return $orderId;
    }

    /**
     * | Save Pine lab Request
        | Close
     */
    public function initiatePayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "workflowId"    => "nullable|int",
            "amount"        => "required|numeric",
            "moduleId"      => "nullable|int",
            "applicationId" => "required|int",
            "paymentType"   => "nullable|"
        ]);
        if ($validator->fails())
            return validationError($validator);

        try {
            $mPinelabPaymentReq = new PinelabPaymentReq();
            $propertyModuleId   = Config::get('module-constants.PROPERTY_MODULE_ID');
            $moduleId           = $req->moduleId;
            $user               = authUser($req);

            if ($req->paymentType == 'Property' || 'Saf')
                $moduleId = $propertyModuleId;

            $mReqs = [
                "ref_no"          => $this->getOrderId($moduleId),
                "user_id"         => $user->id,
                "workflow_id"     => $req->workflowId ?? 0,
                "amount"          => $req->amount,
                "module_id"       => $moduleId,
                "ulb_id"          => $user->ulb_id ?? $req->ulbId,
                "application_id"  => $req->applicationId,
                "payment_type"    => $req->paymentType

            ];
            $data = $mPinelabPaymentReq->store($mReqs);
            return responseMsgs(true, "Bill id is", ['billRefNo' => $data->ref_no], "", 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Save Pine lab Response
     * | Code by Mrinal Kumar
     * | Edited By-Anshu Kumar(27-09-2023)
        | Close
     */
    public function savePinelabResponse(Request $req)
    {
        $idGeneration = new IdGeneration;
        try {
            Storage::disk('public')->put($req->billRefNo . '.json', json_encode($req->all()));
            $mPinelabPaymentReq         = new PinelabPaymentReq();
            $mPinelabPaymentResponse    = new PinelabPaymentResponse();
            $responseCode               = Config::get('payment-constants.PINELAB_RESPONSE_CODE');
            $propertyModuleId           = Config::get('module-constants.PROPERTY_MODULE_ID');
            $user                       = authUser($req);
            $pinelabData                = $req->pinelabResponseBody;
            $detail                     = (object)($req->pinelabResponseBody['Detail'] ?? []);
            $actualTransactionNo        = $idGeneration->generateTransactionNo($user->ulb_id);

            # Distribution of modules
            if (in_array($req->paymentType, ['Property', 'Saf']))
                $moduleId = $propertyModuleId;

            # Pos payment request data 
            $paymentData = $mPinelabPaymentReq->getPaymentRecord($req);

            if (collect($paymentData)->isEmpty())
                throw new Exception("Payment Data not available");
            if ($paymentData) {
                $mReqs = [
                    "payment_req_id"       => $paymentData->id,
                    "req_ref_no"           => $req->billRefNo,
                    "res_ref_no"           => $actualTransactionNo,                         // flag
                    "response_msg"         => $pinelabData['Response']['ResponseMsg'],
                    "response_code"        => $pinelabData['Response']['ResponseCode'],
                    "description"          => $req->description,
                ];

                $data = $mPinelabPaymentResponse->store($mReqs);
            }

            # data transfer to the respective module's database 
            $moduleData = [
                'id'                        => $req->applicationId,
                'billRefNo'                 => $req->billRefNo,
                'amount'                    => $req->amount,
                'workflowId'                => $req->workflowId,
                'userId'                    => $user->id,
                'ulbId'                     => $user->ulb_id,
                'departmentId'              => $moduleId,         #_Module Id
                'gatewayType'               => "Pinelab",         #_Pinelab Id
                'transactionNo'             => $actualTransactionNo,
                'TransactionDate'           => $detail->TransactionDate ?? null,
                'HostResponse'              => $detail->HostResponse ?? null,
                'CardEntryMode'             => $detail->CardEntryMode ?? null,
                'ExpiryDate'                => $detail->ExpiryDate ?? null,
                'InvoiceNumber'             => $detail->InvoiceNumber ?? null,
                'MerchantAddress'           => $detail->MerchantAddress ?? null,
                'TransactionTime'           => $detail->TransactionTime ?? null,
                'TerminalId'                => $detail->TerminalId ?? null,
                'TransactionType'           => $detail->TransactionType ?? null,
                'CardNumber'                => $detail->CardNumber ?? null,
                'MerchantId'                => $detail->MerchantId ?? null,
                'PlutusVersion'             => $detail->PlutusVersion ?? null,
                'PosEntryMode'              => $detail->PosEntryMode ?? null,
                'RetrievalReferenceNumber'  => $detail->RetrievalReferenceNumber ?? null,
                'BillingRefNo'              => $detail->BillingRefNo ?? null,
                'BatchNumber'               => $detail->BatchNumber ?? null,
                'Remark'                    => $detail->Remark ?? null,
                'AcquiringBankCode'         => $detail->AcquiringBankCode ?? null,
                'MerchantName'              => $detail->MerchantName ?? null,
                'MerchantCity'              => $detail->MerchantCity ?? null,
                'ApprovalCode'              => $detail->ApprovalCode ?? null,
                'CardType'                  => $detail->CardType ?? null,
                'PrintCardholderName'       => $detail->PrintCardholderName ?? null,
                'AcquirerName'              => $detail->AcquirerName ?? null,
                'LoyaltyPointsAwarded'      => $detail->LoyaltyPointsAwarded ?? null,
                'CardholderName'            => $detail->CardholderName ?? null,
                'AuthAmoutPaise'            => $detail->AuthAmoutPaise ?? null,
                'PlutusTransactionLogID'    => $detail->PlutusTransactionLogID ?? null
            ];


            if ($pinelabData['Response']['ResponseCode'] == 00) {                           // Success Response code(00)

                # Updating the payment request data 
                $paymentData->payment_status = 1;
                $paymentData->save();

                # calling function for the modules
                switch ($paymentData->module_id) {
                    case ('1'):
                        $workflowId = $paymentData->workflow_id;
                        if ($workflowId == 0) {
                            $objHoldingTaxController = new HoldingTaxController($this->_safRepo);
                            $moduleData = new Request($moduleData);
                            $objHoldingTaxController->paymentHolding($moduleData);
                        } else {                                            //<------------------ (SAF PAYMENT)
                            $obj = new ActiveSafController($this->_safRepo);
                            $moduleData = new ReqPayment($moduleData);
                            $obj->paymentSaf($moduleData);
                        }
                        break;
                        // case ('2'):                                             //<------------------ (Water)
                        //     $objWater = new WaterNewConnection();
                        //     $objWater->razorPayResponse($moduleData);
                        //     break;
                    case ('3'):                                             //<------------------ (TRADE)
                        $objTrade = new TradeCitizen();
                        $objTrade->pinelabResponse($moduleData);
                        break;
                }
            } else
                throw new Exception("Payment Cancelled");
            return responseMsgs(true, "Data Saved", $data, "", 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
