<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\MicroServices\DocUpload;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Trade\RejectedTradeNoticeConsumerDtl;
use App\Models\Trade\TradeNoticeConsumerDtl;
use App\Models\Workflows\WfActiveDocument;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\WardPermission;
use App\Traits\Trade\TradeTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TradeNotice implements ITradeNotice
{
    use Auth;               // Trait 
    use WardPermission;
    use Razorpay;
    use TradeTrait;

    /**
     * | Created On-09-02-2023 
     * | Created By-Sandeep Bara
     * | Status (open)
     * |
     * |----------------------
     * | Applying For Trade License
     * | Proper Validation will be applied 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $_modelWard;
    protected $_parent;
    protected $_wardNo;
    protected $_licenceId;
    protected $_shortUlbName;

    protected $_MODEL_WARD;
    protected $_COMMON_FUNCTION;
    protected $_REPOSITORY_TRADE;
    protected $_WF_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;
    protected $_DOC_PATH;

    protected $_META_DATA;
    protected $_QUERY_RUN_TIME;
    protected $_API_ID;

    public function __construct()
    {
        $this->_MODEL_WARD = new ModelWard();
        $this->_COMMON_FUNCTION = new CommonFunction();

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_NOTICE_REF_TABLE"];
        $this->_DOC_PATH = $this->_TRADE_CONSTAINT["TRADE_NOTICE_RELATIVE_PATH"];
    }
    public function addDenail(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        try {
            $data = array();
            $refWorkflowId = $this->_WF_MASTER_Id;
            $refWorkflows       = $this->_COMMON_FUNCTION->iniatorFinisher($userId, $ulbId, $refWorkflowId);
            DB::beginTransaction();
            $denialConsumer = new ActiveTradeNoticeConsumerDtl();
            $denialConsumer->firm_name  = $request->firmName;
            $denialConsumer->owner_name = $request->ownerName;
            $denialConsumer->ward_id    = $request->wardNo;
            $denialConsumer->ulb_id     = $ulbId;
            $denialConsumer->holding_no = $request->holdingNo;
            $denialConsumer->address    = $request->address;
            $denialConsumer->landmark   = $request->landmark;
            $denialConsumer->city       = $request->city;
            $denialConsumer->pin_code    = $request->pinCode;
            $denialConsumer->license_no = $request->licenceNo ?? null;
            $denialConsumer->ip_address = $request->ip();
            $getloc = json_decode(file_get_contents("http://ipinfo.io/"));
            $coordinates = explode(",", $getloc->loc);
            $denialConsumer->latitude   = $coordinates[0]; // latitude
            $denialConsumer->longitude  = $coordinates[1]; // longitude
            $denialConsumer->mobile_no = $request->mobileNo??null;
            $denialConsumer->remarks = $request->comment;
            $denialConsumer->user_id = $userId;

            $denialConsumer->workflow_id  = $refWorkflowId; 
            $denialConsumer->current_role = $refWorkflows['initiator']['id'];
            $denialConsumer->initiator_role = $refWorkflows['initiator']['id'];
            $denialConsumer->finisher_role = $refWorkflows['initiator']['id'];
            
            $denialConsumer->save();
            $denial_id = $denialConsumer->id;

            
            $docUpload = new DocUpload;
            $relativePath = $this->_DOC_PATH;
            $refImageName = $request->docCode;
            $refImageName = $denial_id . '-' . str_replace(' ', '_', $refImageName);
            $document = $request->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);
            if ($denial_id) 
            {
                $denialConsumer->document_path = $relativePath."/".$imageName;
                $denialConsumer->update();

            }
            $metaReqs["applicationId"] = $denial_id;
            $metaReqs["status"] = 1;
            $metaReqs = new Request($metaReqs);
            $response = $this->approveReject($metaReqs);
            $message = $response->original["message"];
            if(!$response->original["status"])
            {
                throw new Exception($message);
            }
            $data = $response->original["data"];
            DB::commit();

            return  responseMsg(true, $message, $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function inbox(Request $request)
    {
        try {
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = $this->_WF_MASTER_Id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id);
            $role_id = $role->role_id ?? -1;
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }

            $wardList = $this->_COMMON_FUNCTION->WardPermission($user_id);
            $data['wardList'] = $wardList;
            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $wardList);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_at::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->whereIn("active_trade_notice_consumer_dtls.ward_id", $ward_ids)
                ->where("active_trade_notice_consumer_dtls.is_parked", FALSE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->where("active_trade_notice_consumer_dtls.current_role", $role_id)
                ->where("active_trade_notice_consumer_dtls.workflow_id", $workflow_id)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_at", "DESC")
                ->get();
            $data['denila_consumer'] = $denila_consumer;
            return responseMsg(false, "", remove_null($denila_consumer));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id;
            $mUserType = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $ward_permission = $this->_COMMON_FUNCTION->WardPermission($user_id);
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            if ($role->is_initiator || in_array(strtoupper($mUserType), ["JSK", "SUPER ADMIN", "ADMIN", "TL", "PMU", "PM"])) 
            {
                $ward_permission = $this->_MODEL_WARD->getAllWard($ulb_id)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = objToArray($ward_permission);
            } 
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_at::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->whereIn("active_trade_notice_consumer_dtls.ward_id", $ward_ids)
                ->where("active_trade_notice_consumer_dtls.is_parked", FALSE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->where("active_trade_notice_consumer_dtls.current_role","<>", $role_id)
                ->where("active_trade_notice_consumer_dtls.workflow_id", $refWorkflowId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_at", "DESC")
                ->get();
            $data['denila_consumer'] = $denila_consumer;
            return responseMsg(true, "", $denila_consumer);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function specialInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id;
            
            $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->where("active_trade_notice_consumer_dtls.is_escalate", TRUE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $refUlbId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            // $data = [
            //     "wardList" => $mWardPermission,
            //     "denila_consumer" => $denila_consumer,
            // ];
            return responseMsg(true, "", $denila_consumer);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function btcInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id;
            $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            $mRole = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);

            if (!$mRole->is_initiator) {
                throw new Exception("You Are Not Authorized For This Action");
            }
            if ($mRole->is_initiator) {
                $mWardPermission = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
            }

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->where("active_trade_notice_consumer_dtls.is_parked", TRUE)
                ->whereIn('active_trade_notice_consumer_dtls.ward_id', $ward_ids)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $refUlbId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            // $data = [
            //     "wardList" => $mWardPermission,
            //     "denila_consumer" => $denila_consumer,
            // ];
            return responseMsg(true, "", $denila_consumer);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

     # Serial No : 25
    /**
     * Apply Denail View Data And (Approve Or Reject) By EO
     * | @var data local data storage
     * |+ @var user  login user DATA 
     * |+ @var user_id login user ID
     * |+ @var ulb_id login user ULBID
     * |+ @var workflow_id owrflow id 229 for trade **$this->_WF_MASTER_Id
     * |+ @var role_id login user ROLEID **this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1
     * | @var mUserType login user sort role name **$this->_COMMON_FUNCTION->userType(workflow_id)
     * |
     * |+ @var denial_details  apply denial detail **this->getDenialDetailsByID($id,$ulb_id)
     * |+ @var denialID =  denial_details->id
     * |     
     */
    public function denialView(Request $request)
    {
        $request->validate([
            "applicationId" => "required|digits_between:1,9223372036854775807",
        ]);
        try {
            $applicationId = $request->applicationId;
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = $this->_WF_MASTER_Id;
            $role_id = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id)->role_id ?? -1;
            $mUserType = $this->_COMMON_FUNCTION->userType($workflow_id);

            $denial_details  = $this->getDenialDetailsByID($applicationId);            
            $data["denial_details"] = $denial_details;
            return responseMsg(true, "", remove_null($data));
            
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function approveReject(Request $req)
    {
        try {
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id;
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            $application = ActiveTradeNoticeConsumerDtl::find($req->applicationId);
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            if ($application->finisher_role != $role->role_id) 
            {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) 
            {
                // Objection Application replication
                $approvedApplication = $application->replicate();
                $approvedApplication->setTable('trade_notice_consumer_dtls');
                $approvedApplication->id = $application->id;
                $approvedApplication->status=5;
                $approvedApplication->notice_no = $this->generateNoticNo($approvedApplication->id);
                $approvedApplication->notice_date = Carbon::now()->format("Y-m-d");
                $approvedApplication->save();
                $application->forceDelete();

                $msg =  "Notice Successfully Generated !!. Your Notice No. ".$approvedApplication->notice_no;
                $data["notice_no"]=$approvedApplication->notice_no;
            }

            // Rejection
            if ($req->status == 0) 
            {
                // Objection Application replication
                $rejectedApplication = $application->replicate();
                $rejectedApplication->setTable('rejected_trade_notice_consumer_dtls');
                $rejectedApplication->id = $application->id;
                $rejectedApplication->status = 4;
                $rejectedApplication->save();
                $application->forcedelete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            return responseMsgs(true, $msg, $data, '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $data, '010811', '01', '474ms-573', 'Post', '');
        }
    }

    /**
     * |-------------------------------------------------
     * |  NOT/      |    11222        |  1234          |
     * |    (4)     |   date('dmy')   | uniqueNo       |
     * |________________________________________________
     */
    public function generateNoticNo($applicationId)
    {
        $noticeNO = "NOT/" . date('dmy') . $applicationId;
        return $noticeNO;
    }

    public function getDenialDetailsByID($applicationId)
    {
        $application = ActiveTradeNoticeConsumerDtl::find($applicationId);
        if(!$application)
        {
            $application = TradeNoticeConsumerDtl::find($applicationId);
        }
        if(!$application)
        {
            $application = RejectedTradeNoticeConsumerDtl::find($applicationId);
        }
        return $application;
    }
}