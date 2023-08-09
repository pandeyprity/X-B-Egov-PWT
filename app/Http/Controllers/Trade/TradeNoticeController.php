<?php

namespace App\Http\Controllers\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\ReqApplyDenail;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITradeNotice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TradeNoticeController extends Controller
{
    /**
     * | Created On-01-10-2022 
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
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;

    protected $_META_DATA;
    protected $_QUERY_RUN_TIME;
    protected $_API_ID;

    public function __construct(ITradeNotice $TradeRepository)
    {
        $this->_REPOSITORY = $TradeRepository;
        $this->_MODEL_WARD = new ModelWard();
        $this->_COMMON_FUNCTION = new CommonFunction();
        
        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_NOTICE_REF_TABLE"];
    }
    public function applyDenail(Request $request)
    {
        try {
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
            $rules["firmName"]="required|regex:$regex";
            $rules["ownerName"]="required|regex:$regex";
            $rules["wardNo"]="required|int";
            $rules["holdingNo"]="required";
            $rules["address"]="required|regex:$regex";
            $rules["landmark"]="required|regex:$regex";
            $rules["city"]="required|regex:$regex";
            $rules["pinCode"]="required|digits:6";
            $rules["mobileNo"]="digits:10";
            $rules["comment"]="required|regex:$regex|min:10";
            $rules["document"]="required|mimes:pdf,jpg,jpeg,png|max:2048";
            $request->validate($rules);

            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $refWorkflowId);
            // dd($role);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            $userType = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            if (!in_array(strtoupper($userType), ["TC", "UTC"])) {
                throw new Exception("You Are Not Authorize For Apply Denial");
            }
            return $this->_REPOSITORY->addDenail($request);
        } catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function inbox(Request $request)
    {
       return  $this->_REPOSITORY->inbox($request);
    }
    public function outbox(Request $request)
    {
        return  $this->_REPOSITORY->outbox($request);
    }
    public function specialInbox(Request $request)
    {
        return  $this->_REPOSITORY->specialInbox($request);
    }
    public function btcInbox(Request $request)
    {
        return  $this->_REPOSITORY->btcInbox($request);
    }
    public function postNextLevel(Request $request)
    {

        $request->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // Trade Notice Application Update Current Role Updation
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id;
            $workflowId = WfWorkflow::where('id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            
            $application = ActiveTradeNoticeConsumerDtl::find($request->applicationId);
            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            $allRolse = collect($this->_COMMON_FUNCTION->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
            $receiverRole = array_values(objToArray($allRolse->where("id",$request->receiverRoleId)))[0]??[];
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            
            
            if($application->current_role != $role->role_id)
            {
                throw new Exception("You Have Not Pending This Application");
            }
            $sms ="Application BackWord To ".$receiverRole["role_name"]??"";
            
            if($role->serial_no  < $receiverRole["serial_no"]??0)
            {
                $sms ="Application Forward To ".$receiverRole["role_name"]??"";
            }
            

            DB::beginTransaction();

            $application->max_level_attained = ($application->max_level_attained < ($receiverRole["serial_no"]??0)) ? ($receiverRole["serial_no"]??0) : $application->max_level_attained;
            $application->current_role = $request->receiverRoleId;
            $application->update();


            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $application->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences';
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, $sms, "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function denialView(Request $request)
    {
        return  $this->_REPOSITORY->denialView($request);
    }
    public function approveReject(Request $request)
    {
        return  $this->_REPOSITORY->approveReject($request);
    }
}