<?php

namespace App\Http\Controllers\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\ReqCitizenAddRecorde;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Trade\RejectedTradeLicence;
use App\Models\Trade\TradeFineRebete;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeRazorPayRequest;
use App\Models\Trade\TradeRazorPayResponse;
use App\Models\Trade\TradeRenewal;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITradeCitizen;
use App\Repository\Trade\Trade;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TradeCitizenController extends Controller
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022    
    use Razorpay;
    /**
     * | Created On-22-12-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository

    protected $_MODEL_WARD;
    protected $_COMMON_FUNCTION;
    protected $_REPOSITORY;
    protected $_REPOSITORY_TRADE;
    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;

    protected $_META_DATA;
    protected $_QUERY_RUN_TIME;
    protected $_API_ID;

    public function __construct(ITradeCitizen $TradeRepository)
    {
        $this->_REPOSITORY = $TradeRepository;
        $this->_MODEL_WARD = new ModelWard();
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_REPOSITORY_TRADE = new Trade();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];

        $this->_QUERY_RUN_TIME = 0.00;
        $this->_META_DATA = [
            "apiId" => 1.1,
            "version" => 1.1,
            'queryRunTime' => $this->_QUERY_RUN_TIME,
        ];
    }

    public function getWardList(Request $request)
    {
        $this->_META_DATA["apiId"] = "c1";
        $this->_META_DATA["queryRunTime"] = 2.48;
        $this->_META_DATA["action"]    = $request->getMethod();
        $this->_META_DATA["deviceId"] = $request->ip();
        try {
            $rules["ulbId"] = "required|digits_between:1,9223372036854775807";
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return responseMsgs(
                    false,
                    $validator->errors(),
                    $request->all(),
                    $this->_META_DATA["apiId"],
                    $this->_META_DATA["version"],
                    $this->_META_DATA["queryRunTime"],
                    $this->_META_DATA["action"],
                    $this->_META_DATA["deviceId"]
                );
            }
            $mWardList = $this->_MODEL_WARD->getAllWard($request->ulbId)->map(function ($val) {
                $val["ward_no"] = $val["ward_name"];
                return $val;
            });
            $mWardList = remove_null($mWardList);
            return responseMsgs(
                true,
                "",
                $mWardList,
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        } catch (Exception $e) {
            return responseMsgs(
                false,
                $e->getMessage(),
                $request->all(),
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        }
    }

    public function applyApplication(ReqCitizenAddRecorde $request)
    {        
        $this->_META_DATA["apiId"] = "c2";
        $this->_META_DATA["queryRunTime"] = 2.48;
        $this->_META_DATA["action"]    = $request->getMethod();
        $this->_META_DATA["deviceId"] = $request->ip();
        try {
            if(!$this->_COMMON_FUNCTION->checkUsersWithtocken("active_citizens"))
            {
                throw New Exception("Counter User Not Allowed");
            }
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $request->ulbId;
            
            $wardId = $request->firmDetails["wardNo"];
            $wardId = $this->_MODEL_WARD->getAllWard($request->ulbId)->filter(function ($item) use ($wardId) {
                if ($item->id == $wardId) {
                    return $item;
                }
            });
            $refWorkflowId      = $this->_WF_MASTER_Id;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $refWorkflows       = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $mApplicationTypeId =  $this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$request->applicationType];
            if (sizeOf($wardId) < 1) {
                throw new Exception("Invalide Ward Id Pase");
            }
            if (!in_array(strtoupper($mUserType), ["ONLINE"])) {
                throw new Exception("You Are Not Authorized For This Action. Please Apply From Counter");
            }
            if (!$mApplicationTypeId) {
                throw new Exception("Invalide Application Type");
            }
            if (!$refWorkflows) {
                throw new Exception("Workflow Not Available");
            }
            if (!$refWorkflows['initiator']) {
                throw new Exception("Initiator Not Available");
            }
            if (!$refWorkflows['finisher']) {
                throw new Exception("Finisher Not Available");
            }
            if (in_array($mApplicationTypeId, ["2", "3", "4"]) && (!$request->licenseId || !is_numeric($request->licenseId))) {
                throw new Exception("Old licence Id Requird");
            }
            return $this->_REPOSITORY->addRecord($request);
        } catch (Exception $e) {            
            return responseMsgs(
                false,
                $e->getMessage(),
                $request->all(),
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        }
    }
    # Serial No : 03 
    /**
     * | Get Notice Data
     */
    public function getDenialDetails(Request $request)
    {
        $this->_META_DATA["apiId"] = "c3";
        $this->_META_DATA["queryRunTime"] = 2.48;
        $this->_META_DATA["action"]    = $request->getMethod();
        $this->_META_DATA["deviceId"] = $request->ip();

        $data = (array)null;
        $refUser = Auth()->user();
        $refUlbId = $request->ulbId;
        $mNoticeNo = null;
        $mNowDate = Carbon::now()->format('Y-m-d'); // todays date
        try {
            $rules = [
                "noticeNo" => "required|string",
                "ulbId"    => "required|digits_between:1,92"
            ];
            if(!$this->_COMMON_FUNCTION->checkUsersWithtocken("active_citizens"))
            {
                throw New Exception("Counter User Not Allowed");
            }
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $mNoticeNo = $request->noticeNo;

            $refDenialDetails =  $this->_REPOSITORY_TRADE->getDenialFirmDetails($refUlbId, strtoupper(trim($mNoticeNo)));
            if ($refDenialDetails) {
                $notice_date = Carbon::parse($refDenialDetails->noticedate)->format('Y-m-d'); //notice date
                $denialAmount =  $this->_REPOSITORY_TRADE->getDenialAmountTrade($notice_date, $mNowDate);
                $data['denialDetails'] = $refDenialDetails;
                $data['denialAmount'] = $denialAmount;
                return responseMsgs(
                    true,
                    "",
                    $data,
                    $this->_META_DATA["apiId"],
                    $this->_META_DATA["version"],
                    $this->_META_DATA["queryRunTime"],
                    $this->_META_DATA["action"],
                    $this->_META_DATA["deviceId"]
                );
            } else {
                $response = "no Data";
                return responseMsgs(
                    false,
                    $response,
                    $request->all(),
                    $this->_META_DATA["apiId"],
                    $this->_META_DATA["version"],
                    $this->_META_DATA["queryRunTime"],
                    $this->_META_DATA["action"],
                    $this->_META_DATA["deviceId"]
                );
            }
        } catch (Exception $e) {
            return responseMsgs(
                false,
                $e->getMessage(),
                $request->all(),
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        }
    }

    # Serial No : 04
    public function handeRazorPay(Request $request)
    {
        $this->_META_DATA["apiId"] = "c4";
        $this->_META_DATA["queryRunTime"] = 4.00;
        $this->_META_DATA["action"]    = $request->getMethod();
        $this->_META_DATA["deviceId"] = $request->ip();
        try {
            $request->validate(
                [                  
                    "licenceId" => "required|digits_between:1,9223372036854775807",
                ]
            );
            if(!$this->_COMMON_FUNCTION->checkUsersWithtocken("active_citizens"))
            {
                throw New Exception("Counter User Not Allowed");
            }
            #------------------------ Declaration-----------------------
            $refUser            = Auth()->user();
            $refNoticeDetails   = null;
            $refWorkflowId      = $this->_WF_MASTER_Id;
            $mNoticeDate        = null;
            #------------------------End Declaration-----------------------
            $refLecenceData = $this->_REPOSITORY_TRADE->getActiveLicenseById($request->licenceId);
            if (!$refLecenceData) {
                throw new Exception("Licence Data Not Found !!!!!");
            } elseif ($refLecenceData->application_type_id == 4) {
                throw new Exception("Surender Application Not Pay Anny Amount");
            } elseif (in_array($refLecenceData->payment_status, [1, 2])) {
                throw new Exception("Payment Already Done Of This Application");
            }
            if ($refLecenceData->tobacco_status == 1 && $request->licenseFor > 1) 
            {
                throw new Exception("Tobaco Application Not Take Licence More Than One Year");
            }
            $request->request->add(['applicationId'=>$request->licenceId]);
            $doc = $this->_REPOSITORY_TRADE->getLicenseDocLists($request);
            if($doc->original['status'] && !$doc->original['data']['docUploadStatus'])
            {
                throw new Exception("Upload Document First");
            }
            
            if ($refNoticeDetails = $this->_REPOSITORY_TRADE->readNotisDtl($refLecenceData->id)) 
            {
                $mNoticeDate = date('Y-m-d', strtotime($refNoticeDetails['created_on'])); //notice date 
            }
            

            #-----------End validation-------------------
            #-------------Calculation-----------------------------                
            $args['areaSqft']            = (float)$refLecenceData->area_in_sqft;
            $args['application_type_id'] = $refLecenceData->application_type_id;
            $args['firmEstdDate'] = !empty(trim($refLecenceData->valid_from)) ? $refLecenceData->valid_from : $refLecenceData->apply_date;
            if ($refLecenceData->application_type_id == 1) {
                $args['firmEstdDate'] = $refLecenceData->establishment_date;
            }
            $args['tobacco_status']      = $refLecenceData->tobacco_status;
            $args['application_no']      = $refLecenceData->application_no;
            $args['licenseFor']          = $refLecenceData->licence_for_years;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->_REPOSITORY_TRADE->cltCharge($args);
            if ($chargeData['response'] == false || $chargeData['total_charge'] == 0) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType = $this->_TRADE_CONSTAINT["APPLICATION-TYPE-BY-ID"][$refLecenceData->application_type_id];

            $totalCharge = $chargeData['total_charge'];

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['amount' => $totalCharge]);
            $myRequest->request->add(['workflowId' => $refWorkflowId]);
            $myRequest->request->add(['id' => $request->licenceId]);
            $myRequest->request->add(['departmentId' => 3]);
            $myRequest->request->add(['ulbId' => $refLecenceData->ulb_id]);
            $temp = $this->saveGenerateOrderid($myRequest);
            DB::beginTransaction();
            $TradeRazorPayRequest = new TradeRazorPayRequest();
            $TradeRazorPayRequest->temp_id   = $request->licenceId;
            $TradeRazorPayRequest->tran_type = $transactionType;
            $TradeRazorPayRequest->amount       = $totalCharge;
            $TradeRazorPayRequest->ip_address   = $request->ip();
            $TradeRazorPayRequest->order_id        = $temp["orderId"];
            $TradeRazorPayRequest->department_id = $temp["departmentId"];
            $TradeRazorPayRequest->save();

            $temp["requestId"]  = $TradeRazorPayRequest->id;
            $temp["applicationNo"]  = $refLecenceData->application_no;
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            $temp['ulbId']      = $refLecenceData->ulb_id;
            $temp['firmName']   = $refLecenceData->firm_name;
            $temp['wardNo']     = $refLecenceData->ward_no;
            $temp['newWardNo']  = $refLecenceData->new_ward_no;
            $temp['applyDate']  = $refLecenceData->apply_date;
            $temp['licenceForYears']  = $refLecenceData->licence_for_years;
            $temp['applicationType']  =  $this->_TRADE_CONSTAINT["APPLICATION-TYPE-BY-ID"][$refLecenceData->application_type_id];
            DB::commit();
            return responseMsgs(
                true,
                "",
                $temp,
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(
                false,
                $e->getMessage(),
                $request->all(),
                $this->_META_DATA["apiId"],
                $this->_META_DATA["version"],
                $this->_META_DATA["queryRunTime"],
                $this->_META_DATA["action"],
                $this->_META_DATA["deviceId"]
            );
        }
    }
    public function razorPayResponse($args)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id ?? $args["userId"];
            $refUlbId       = $refUser->ulb_id ?? $args["ulbId"];
            $refWorkflowId  = $this->_WF_MASTER_Id;
            $refWorkflows   = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $refNoticeDetails = null;
            $refDenialId    = null;
            $refUlbDtl      = UlbMaster::find($refUlbId);
            $refUlbName     = explode(' ', $refUlbDtl->ulb_name);
            $mNowDate       = Carbon::now()->format('Y-m-d');
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $mDenialAmount  = 0;
            $mPaymentStatus = 1;
            $mNoticeDate    = null;
            $mShortUlbName  = "";
            $mWardNo        = "";
            foreach ($refUlbName as $val) {
                $mShortUlbName .= $val[0];
            }

            #-----------valication-------------------   
            $RazorPayRequest = TradeRazorPayRequest::select("*")
                ->where("order_id", $args["orderId"])
                ->where("temp_id", $args["id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            $refLecenceData = ActiveTradeLicence::find($args["id"]);
            $licenceId = $args["id"];
            $refLevelData = $this->_REPOSITORY_TRADE->getWorkflowTrack($licenceId);
            if (!$refLecenceData) {
                throw new Exception("Licence Data Not Found !!!!!");
            } elseif ($refLecenceData->application_type_id == 4) {
                throw new Exception("Surender Application Not Pay Anny Amount");
            } elseif (in_array($refLecenceData->payment_status, [1, 2])) {
                throw new Exception("Payment Already Done Of This Application");
            }
            if ($refNoticeDetails = $this->readNotisDtl($refLecenceData->id)) {
                $refDenialId = $refNoticeDetails->dnialid;
                $mNoticeDate = date("Y-m-d", strtotime($refNoticeDetails['created_on'])); //notice date 
            }

            $ward_no = UlbWardMaster::select("ward_name")
                ->where("id", $refLecenceData->ward_mstr_id)
                ->first();
            $mWardNo = $ward_no['ward_name'];

            #-----------End valication-------------------

            #-------------Calculation-----------------------------                
            $args['areaSqft']            = (float)$refLecenceData->area_in_sqft;
            $args['application_type_id'] = $refLecenceData->application_type_id;
            $args['firmEstdDate'] = !empty(trim($refLecenceData->valid_from)) ? $refLecenceData->valid_from : $refLecenceData->apply_date;
            if ($refLecenceData->application_type_id == 1) {
                $args['firmEstdDate'] = $refLecenceData->establishment_date;
            }
            $args['tobacco_status']      = $refLecenceData->tobacco_status;
            $args['licenseFor']          = $refLecenceData->licence_for_years;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->_REPOSITORY_TRADE->cltCharge($args);
            if ($chargeData['response'] == false || round($args['amount']) != round($chargeData['total_charge'])) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType =  $this->_TRADE_CONSTAINT["APPLICATION-TYPE-BY-ID"] [$refLecenceData->application_type_id];

            $rate_id = $chargeData["rate_id"];
            $totalCharge = $chargeData['total_charge'];
            $mDenialAmount = $chargeData['notice_amount'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();

            $RazorPayResponse = new TradeRazorPayResponse;
            $RazorPayResponse->temp_id      = $RazorPayRequest->related_id;
            $RazorPayResponse->request_id   = $RazorPayRequest->id;
            $RazorPayResponse->amount       = $args['amount'];
            $RazorPayResponse->merchant_id  = $args['merchantId'] ?? null;
            $RazorPayResponse->order_id     = $args["orderId"];
            $RazorPayResponse->payment_id   = $args["paymentId"];
            $RazorPayResponse->save();

            $RazorPayRequest->status = 1;
            $RazorPayRequest->update();

            $Tradetransaction = new TradeTransaction;
            $Tradetransaction->temp_id          = $licenceId;
            $Tradetransaction->ward_id          = $refLecenceData->ward_id;
            $Tradetransaction->tran_type        = $transactionType;
            $Tradetransaction->tran_date        = $mNowDate;
            $Tradetransaction->payment_mode     = "Online";
            $Tradetransaction->rate_id          = $rate_id;
            $Tradetransaction->paid_amount      = $totalCharge;
            $Tradetransaction->penalty          = $chargeData['penalty'] + $mDenialAmount + $chargeData['arear_amount'];
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->transaction_no   = $args["transactionNo"]; //$this->createTransactionNo($transaction_id);//"TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
            $Tradetransaction->update();

            $TradeFineRebet = new TradeFineRebete;
            $TradeFineRebet->tran_id        = $transaction_id;
            $TradeFineRebet->type           = 'Delay Apply License';
            $TradeFineRebet->amount         = $chargeData['penalty'];
            $TradeFineRebet->created_at     = $mTimstamp;
            $TradeFineRebet->save();

            $mDenialAmount = $mDenialAmount + $chargeData['arear_amount'];
            if ($mDenialAmount > 0) {
                $TradeFineRebet2 = new TradeFineRebete;
                $TradeFineRebet2->tran_id   = $transaction_id;
                $TradeFineRebet2->type      = 'Denial Apply';
                $TradeFineRebet2->amount         = $mDenialAmount;
                $TradeFineRebet2->created_on     = $mTimstamp;
                $TradeFineRebet2->save();
            }
            $request = new Request(["applicationId"=>$licenceId]);
            if ($mPaymentStatus == 1 && $this->_REPOSITORY_TRADE->checkWorckFlowForwardBackord($request) && $refLecenceData->pending_status == 0 ) {
                $refLecenceData->current_role = $refWorkflows['initiator']['forward_id'];
                $refLecenceData->document_upload_status = 1;
                $refLecenceData->pending_status  = 1;
                $args["sender_role_id"] = $refWorkflows['initiator']['id'];
                $args["receiver_role_id"] = $refWorkflows['initiator']['forward_id'];
                $args["citizen_id"] = $refUserId;;
                $args["ref_table_dot_id"] = "active_trade_licences";
                $args["ref_table_id_value"] = $licenceId;
                $args["workflow_id"] = $refWorkflowId;
                $args["module_id"] = $this->_MODULE_ID;

                $tem =  $this->_REPOSITORY_TRADE->insertWorkflowTrack($args);
            }
            if(!$refLecenceData->provisional_license_no)
            {
                $provNo = $this->_REPOSITORY_TRADE->createProvisinalNo($mShortUlbName, $mWardNo, $licenceId);
                $refLecenceData->provisional_license_no = $provNo;
            }
            $refLecenceData->payment_status         = $mPaymentStatus;
            $refLecenceData->save();

            if ($refNoticeDetails) {
                $this->_REPOSITORY_TRADE->updateStatusFine($refDenialId, $chargeData['notice_amount'], $licenceId, 1); //update status and fineAmount                     
            }
            $counter = new Trade;
            $counter->postTempTransection($Tradetransaction,$refLecenceData,$mWardNo);
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id; #config('app.url') .
            $res['paymentReceipt'] =  "/api/trade/application/payment-receipt/" . $licenceId . "/" . $transaction_id;
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $args);
        }
    }
    public function conformRazorPayTran(Request $request)
    {
        try {
            if(!$this->_COMMON_FUNCTION->checkUsersWithtocken("active_citizens"))
            {
                throw New Exception("Counter User Not Allowed");
            }
            $refUser     = Auth()->user();
            $application = null;
            $transection = null;
            $path = "/api/trade/paymentReceipt/";
            $rules = [
                'orderId'    => 'required|string',
                'paymentId'  => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $TradeRazorPayResponse = TradeRazorPayResponse::select("trade_razor_pay_responses.*", "trade_razor_pay_requests.tran_type")
                ->join("trade_razor_pay_requests", "trade_razor_pay_requests.id", "trade_razor_pay_responses.request_id")
                ->where("trade_razor_pay_responses.order_id", $request->orderId)
                ->where("trade_razor_pay_responses.payment_id", $request->paymentId)
                ->where("trade_razor_pay_requests.status", 1)
                ->first();
            if (!$TradeRazorPayResponse) {
                throw new Exception("Not Transection Found...");
            }
            $application = ActiveTradeLicence::find($TradeRazorPayResponse->temp_id);
            $transection = TradeTransaction::select("*")
                ->where("temp_id", $TradeRazorPayResponse->temp_id)
                ->where("response_id", $TradeRazorPayResponse->id)
                ->first();

            if (!$application) {
                throw new Exception("Application Not Found....");
            }
            if (!$transection) {
                throw new Exception("Not Transection Data Found....");
            }
            $data["amount"]            = $TradeRazorPayResponse->amount;
            $data["applicationId"]     = $TradeRazorPayResponse->temp_id;
            $data["applicationNo"]     = $application->application_no;
            $data["tranType"]          = $TradeRazorPayResponse->tran_type;
            $data["transectionId"]     = $transection->id;
            $data["transectionNo"]     = $transection->tran_no;
            $data["transectionDate"]   = $transection->tran_date;
            $data['paymentRecipt']     = config('app.url') . $path . $TradeRazorPayResponse->temp_id . "/" . $transection->id;
            return responseMsg(
                true,
                "",
                $data,
            );
        } catch (Exception $e) {
            return responseMsg(
                false,
                $e->getMessage(),
                $request->all(),
            );
        }
    }
    # Serial No : 27
    public function citizenApplication(Request $request)
    {
        return $this->_REPOSITORY->citizenApplication($request);
    }
    # Serial No : 28
    public function readCitizenLicenceDtl(Request $request)
    {       
        $request->validate([
        'id' => 'required|digits_between:1,9223372036854775807'
        ]);
        
        return $this->_REPOSITORY->readCitizenLicenceDtl($request);
    }

    # Serial No
    public function expiredLicence(Request $request)
    {
        try {
            $citizenId = Auth()->user()->id;
            $mApplicationTypeId = $request->applicationType;
            $mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');

            if ($mApplicationTypeId == 1) {
                throw new Exception("You Can Not Apply New Licence");
            }

            $data = TradeLicence::select('*')
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "trade_licences.ward_id")
                ->where('trade_licences.is_active', TRUE)
                ->where('trade_licences.user_id', $citizenId)
                ->where('trade_licences.valid_upto', '<', Carbon::now())
                ->get();

            if (!$data) {
                throw new Exception("No Data Found");
            }
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No
    public function renewalList()
    {
        $citizenId = Auth()->user()->id;
        $mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');

        $data = TradeLicence::select('trade_licences.*')
            // ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "trade_licences.ward_id")
            ->where('trade_licences.is_active', TRUE)
            ->where('trade_licences.citizen_id', $citizenId)
            ->where('trade_licences.valid_upto', '<', $mNextMonth)
            // ->orWhere('trade_licences.valid_upto', '>', Carbon::now())
            ->where('trade_licences.application_type_id', '!=', 4)
            ->get();

        if (!$data) {
            throw new Exception("No Data Found");
        }

        return responseMsg(true, "", remove_null($data));
    }

    # Serial No
    public function amendmentList()
    {
        try {
            $citizenId = Auth()->user()->id;
            $mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
            // DB::enableQueryLog();
            $data = TradeLicence::select('*')
                // ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "trade_licences.ward_id")
                ->where('trade_licences.is_active', TRUE)
                ->where('trade_licences.citizen_id', $citizenId)
                ->where('trade_licences.valid_upto', '<', $mNextMonth)
                ->get();

            // return (DB::getQueryLog());
            if (!$data) {
                throw new Exception("No Data Found");
            }
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            // dd($e->getFile(), $e->getLine(), $e->getMessage());
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    # Serial No
    public function surrenderList()
    { 
        try {
            $citizenId = Auth()->user()->id;
            $mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');

            $data = TradeLicence::select('*')
                // ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "trade_licences.ward_id")
                ->where('trade_licences.is_active', TRUE)
                ->where('trade_licences.citizen_id', $citizenId)
                ->where('trade_licences.valid_upto', '>=', Carbon::now())
                ->get();

            if (!$data) {
                throw new Exception("No Data Found");
            }
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            // dd($e->getFile(), $e->getLine(), $e->getMessage());
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    #
    public function readAtachedLicenseDtl(Request $request)
    {
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refWorkflowId      = $this->_WF_MASTER_Id;  
            $data = (array)null;
            $licenseNo = (new ActiveCitizenUndercare())->getDetailsByCitizenId()
                        ->WHERENOTNULL("license_id"); 

            $licenseNo = $licenseNo->implode("license_id",',');
            if($licenseNo)
            { 
                $licenseNo = explode(",",$licenseNo);
                $rowLicenseNo = collect($licenseNo)->map(function($val){
                    return "'".$val."'";
                });
                $rowLicenseNo = ($rowLicenseNo->implode(","));
                $select = [
                    "licences.id",
                    "licences.application_no",
                    "licences.provisional_license_no",
                    "licences.license_no",
                    "licences.license_date",
                    "licences.valid_from",
                    "licences.valid_upto",
                    "licences.document_upload_status",
                    "licences.payment_status",
                    "licences.pending_status",
                    "licences.firm_name",
                    "licences.application_date",
                    "licences.apply_from",
                    "licences.application_type_id",
                    "licences.ulb_id",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                    "ulb_masters.ulb_name",
                    DB::RAW("TO_CHAR( CAST(licences.license_date AS DATE), 'DD-MM-YYYY') as license_date,
                            TO_CHAR( CAST(licences.valid_from AS DATE), 'DD-MM-YYYY') as valid_from,
                            TO_CHAR( CAST(licences.valid_upto AS DATE), 'DD-MM-YYYY') as valid_upto,
                            TO_CHAR( CAST(licences.application_date AS DATE), 'DD-MM-YYYY') as application_date
                    "),
                ];
    
                $ActiveSelect = $select;
                $ActiveSelect[] = DB::raw("'active' as license_type");
                $ActiveLicence = DB::TABLE("active_trade_licences AS licences")
                    ->select($ActiveSelect)
                    ->join("ulb_masters","ulb_masters.id","licences.ulb_id")
                    ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        active_trade_owners.temp_id
                                        FROM active_trade_owners 
                                        JOIN active_trade_licences on active_trade_licences.id = active_trade_owners.temp_id
                                            AND (
                                                active_trade_licences.application_no IN($rowLicenseNo)
                                                OR  active_trade_licences.license_no IN($rowLicenseNo)
                                             )
                                        WHERE active_trade_owners.is_active = true
                                        GROUP BY active_trade_owners.temp_id
                                        )owner"), function ($join) {
                        $join->on("owner.temp_id", "licences.id");
                    })
                    ->where("licences.is_active", true)
                    ->where("licences.citizen_id","<>",$refUserId)
                    ->WHERE(FUNCTION($where) use( $licenseNo){
                        $where->WHEREIN("licences.application_no", $licenseNo)
                        ->ORWHEREIN("licences.license_no", $licenseNo);
                    });

                $RejectedSelect = $select;        
                $RejectedSelect[] = DB::raw("'rejected' as license_type");
                $RejectedLicence = DB::TABLE("rejected_trade_licences AS licences")
                    ->select($RejectedSelect)
                    ->join("ulb_masters","ulb_masters.id","licences.ulb_id")
                    ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        rejected_trade_owners.temp_id
                                        FROM rejected_trade_owners
                                        JOIN rejected_trade_licences on rejected_trade_licences.id = rejected_trade_owners.temp_id 
                                            AND(
                                                rejected_trade_licences.application_no IN($rowLicenseNo)
                                                OR  rejected_trade_licences.license_no IN($rowLicenseNo)
                                            )
                                        WHERE rejected_trade_owners.is_active = true
                                        GROUP BY rejected_trade_owners.temp_id
                                        )owner"), function ($join) {
                        $join->on("owner.temp_id", "licences.id");
                    })
                    ->where("licences.is_active", true)
                    ->where("licences.citizen_id","<>",$refUserId)
                    ->WHERE(FUNCTION($where) use( $licenseNo){
                        $where->WHEREIN("licences.application_no", $licenseNo)
                        ->ORWHEREIN("licences.license_no", $licenseNo);
                    });
                    
                    // ->get();

                $ApprovedSelect = $select;        
                $ApprovedSelect[] = DB::raw("'approved' as license_type");
                $ApprovedLicence = DB::TABLE("trade_licences AS licences")
                    ->select($ApprovedSelect)
                    ->join("ulb_masters","ulb_masters.id","licences.ulb_id")
                    ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            trade_owners.temp_id
                                            FROM trade_owners
                                            JOIN trade_licences on trade_licences.id = trade_owners.temp_id 
                                                AND(
                                                    trade_licences.application_no IN($rowLicenseNo)
                                                    OR  trade_licences.license_no IN($rowLicenseNo)
                                                )
                                            WHERE trade_owners.is_active = true
                                            GROUP BY trade_owners.temp_id
                                            )owner"), function ($join) {
                        $join->on("owner.temp_id", "licences.id");
                    })
                    ->where("licences.is_active", true)    
                    ->where("licences.citizen_id","<>",$refUserId)                
                    ->WHERE(FUNCTION($where) use( $licenseNo){
                        $where->WHEREIN("licences.application_no", $licenseNo)
                        ->ORWHEREIN("licences.license_no", $licenseNo);
                    });

                $OldSelect = $select;        
                $OldSelect[] = DB::raw("'old' as license_type");
                $OldLicence = DB::TABLE("trade_renewals AS licences")
                    ->select($OldSelect)
                    ->join("ulb_masters","ulb_masters.id","licences.ulb_id")
                    ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            trade_owners.temp_id
                                            FROM trade_owners
                                            JOIN trade_renewals on trade_renewals.id = trade_owners.temp_id 
                                                AND(
                                                    trade_renewals.application_no IN($rowLicenseNo)
                                                    OR  trade_renewals.license_no IN($rowLicenseNo)
                                                )
                                            WHERE trade_owners.is_active = true
                                            GROUP BY trade_owners.temp_id
                                            )owner"), function ($join) {
                        $join->on("owner.temp_id", "licences.id");
                    })
                    ->where("licences.is_active", true)
                    ->where("licences.citizen_id","<>",$refUserId)
                    ->WHERE(FUNCTION($where) use( $licenseNo){
                        $where->WHEREIN("licences.application_no", $licenseNo)
                        ->ORWHEREIN("licences.license_no", $licenseNo);
                    });
            
                $data = $ActiveLicence->union($RejectedLicence)
                        ->union($ApprovedLicence)->union($OldLicence)
                        ->get();
                $data->map(function($val){
                    $option = [];
                    $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                    $validUpto="";
                    if($val->valid_upto)
                    {
                        $validUpto = Carbon::createFromFormat("d-m-Y",$val->valid_upto)->format('Y-m-d');
                    }
                    if(trim($val->license_type)=="approved" && $val->pending_status == 5 && $validUpto < $nextMonth)
                    {
                        $option[]="RENEWAL";
                    }
                    if(trim($val->license_type)=="approved" && $val->pending_status == 5 && $validUpto >= Carbon::now()->format('Y-m-d'))
                    {
                        $option[]="AMENDMENT";
                        $option[]="SURRENDER";
                    }
                    if(trim($val->license_type)=="approved" && $val->pending_status == 5 && $val->application_type_id == 4 && $validUpto >= Carbon::now()->format('Y-m-d'))
                    {                    
                        $option=[];
                    }
                    $val->option = $option;
                    return $val;
                });
            }
            
            return responseMsg(true, "", remove_null($data));
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
