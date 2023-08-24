<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\Water\WaterSiteInspection;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Traits\Ward;
use App\Traits\Water\WaterTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module | Consumer Workflow
 * |-----------------------------------------------------------------------------------
 * | Created On- 17-07-2023
 * | Created By- Sam kerketta 
 * | Created For- Water consumer workflow related operations
 */

class WaterConsumerWfController extends Controller
{
    use Ward;
    use Workflow;
    use WaterTrait;

    private $_waterRoles;
    private $_waterModuleId;

    public function __construct()
    {
        $this->_waterRoles      = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterModuleId   = Config::get('module-constants.WATER_MODULE_ID');
    }


    /**
     * | List the consumer request inbox details 
        | Serial No : 01
        | Working
     */
    public function consumerInbox(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'perPage' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user                   = authUser($req);
            $pages                  = $req->perPage ?? 10;
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereIn('water_consumer_active_requests.current_role', $roleId)
                ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->where('water_consumer_active_requests.is_escalate', false)
                ->where('water_consumer_active_requests.parked', false)
                ->orderByDesc('water_consumer_active_requests.id')
                ->paginate($pages);

            $isDataExist = collect($inboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Consumer Outbox 
     * | Get Consumer Active outbox details 
        | Serial No :
        | Working 
     */
    public function consumerOutbox(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'perPage' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user                   = authUser($req);
            $pages                  = $req->perPage ?? 10;
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereNotIn('water_consumer_active_requests.current_role', $roleId)
                ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->orderByDesc('water_consumer_active_requests.id')
                ->paginate($pages);

            $isDataExist = collect($inboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get details of application for displaying 
        | Serial No :
        | Under Con
     */
    public function getConApplicationDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $returDetails = $this->getConActiveAppDetails($request->applicationId)
                ->where('wc.status', 2)
                ->first();
            if (!$returDetails) {
                throw new Exception("Application Details Not found!");
            }
            return responseMsgs(true, "Application Detials!", remove_null($returDetails), '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Get the Citizen applied applications 
     * | Application list according to citizen 
        | Serial No :
        | Under Con
     */
    public function getRequestedApplication(Request $request)
    {
        try {
            $user                           = authUser($request);
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
            $refUserType                    = Config::get('waterConstaint.REF_USER_TYPE');

            # User type changes 
            $detailsDisconnections = $mWaterConsumerActiveRequest->getApplicationByUser($user->id)->get();
            if (!collect($detailsDisconnections)->first()) {
                throw new Exception("Data not found!");
            }
            return responseMsgs(true, "list of disconnection ", remove_null($detailsDisconnections), "", "1.0", "350ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", $e->getCode(), "1.0", "", 'POST', "");
        }
    }
     /**
     * postnext level water Disconnection
     * 
     */
    public function consumerPostNextLevel(Request $request)
    {
        $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
         $request->validate([
            'applicationId'     => 'required',
            'senderRoleId'      => 'required',
            'receiverRoleId'    => 'required',
            'action'            => 'required|In:forward,backward',
            'comment'           => $request->senderRoleId == $wfLevels['DA'] ? 'nullable' : 'required',

        ]);
        try {
            return $this->postNextLevelRequest($request);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * post next level for water consumer other request 
     */

     public function postNextLevelRequest($req){
        
        $mWfWorkflows        = new WfWorkflow();
        $mWfRoleMaps         = new WfWorkflowrolemap();
        
        $current             = Carbon::now();
        $wfLevels            = Config::get('waterConstaint.ROLE-LABEL');
        $waterConsumerActive = WaterConsumerActiveRequest::find($req->applicationId);

        # Derivative Assignments
        $senderRoleId   = $waterConsumerActive->current_role;
        $ulbWorkflowId  = $waterConsumerActive->workflow_id;
       $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
        $roleMapsReqs   = new Request([
            'workflowId' => $ulbWorkflowMaps->id,
            'roleId' => $senderRoleId
        ]);
        $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

        DB::beginTransaction();
        if ($req->action == 'forward') {
            // $this->checkPostCondition($req->senderRoleId, $wfLevels, $waterConsumerActive);            // Check Post Next level condition
            if ($waterConsumerActive->current_role == $wfLevels['JE']) {
                $waterConsumerActive->is_field_verified = true;
            }
            $metaReqs['verificationStatus'] = 1;
            $waterConsumerActive->current_role = $forwardBackwardIds->forward_role_id;
            $waterConsumerActive->last_role_id =  $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id

        }
        if ($req->action == 'backward') {
            $waterConsumerActive->current_role = $forwardBackwardIds->backward_role_id;
        }

        $waterConsumerActive->save();
        $metaReqs['moduleId']           =  $this->_waterModuleId;
        $metaReqs['workflowId']         = $waterConsumerActive->workflow_id;
        $metaReqs['refTableDotId']      = 'water_consumer_active_requests.id';
        $metaReqs['refTableIdValue']    = $req->applicationId;
        $metaReqs['user_id']            = authUser($req)->id;
        $req->request->add($metaReqs);
        $waterTrack         = new WorkflowTrack();
        $waterTrack->saveTrack($req);

        # check in all the cases the data if entered in the track table 
        // Updation of Received Date
        $preWorkflowReq = [
            'workflowId'        => $waterConsumerActive->workflow_id,
            'refTableDotId'     => "water_consumer_active_requests.id",
            'refTableIdValue'   => $req->applicationId,
            'receiverRoleId'    => $senderRoleId
        ];

        $previousWorkflowTrack = $waterTrack->getWfTrackByRefId($preWorkflowReq);
        $previousWorkflowTrack->update([
            'forward_date' => $current,
            'forward_time' => $current
        ]);
        DB::commit();
        return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
     }
     
    public function checkPostCondition($senderRoleId, $wfLevels, $application)
    {
        $mWaterSiteInspection = new WaterSiteInspection();
        
        $refRole = Config::get("waterConstaint.ROLE-LABEL");
        switch ($senderRoleId) {
            case $wfLevels['DA']:                                                                       // DA Condition
                if ($application->payment_status != 1)
                    throw new Exception("payment Not Fully paid");
                break;
            case $wfLevels['JE']:                                                                       // JE Coditon in case of site adjustment
                if ($application->doc_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false) {
                    throw new Exception("Document Not Fully Uploaded");
                }
                $siteDetails = $mWaterSiteInspection->getSiteDetails($application->id)
                    ->where('order_officer', $refRole['JE'])
                    ->where('payment_status', 1)
                    ->first();
                if (!$siteDetails) {
                    throw new Exception("Site Not Verified!");
                }
                break;
            case $wfLevels['SH']:                                                                       // SH conditional checking
                if ($application->doc_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false || $application->is_field_verified == false) {
                    throw new Exception("Document Not Fully Uploaded or site inspection not done!");
                }
                break;
            case $wfLevels['AE']:                                                                       // AE conditional checking
                if ( $application->payment_status != 1)
                    throw new Exception(" Payment in not Done!");
                
                break;
        }
    }
    /**
     * water disconnection approval or reject 
     */
    public function consumerApprovalRejection(Request $request)
    {
        $request->validate([
            "applicationId" => "required",
            "status"        => "required",
            "comment"       => "required"
        ]);
        try {
            $mWfRoleUsermap = new WfRoleusermap();
            $waterDetails = WaterConsumerActiveRequest::find($request->applicationId);

            # check the login user is AE or not
            $userId = authUser($request)->id;
            $workflowId = $waterDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $waterDetails->finisher) {
                throw new Exception("You are not the Finisher!");
            }
            DB::beginTransaction();
             $this->approvalRejectionWater($request, $roleId);
            DB::commit();
            return responseMsg(true, "Request approved/rejected successfully", ""); ;
        }   catch (Exception $e) {
            // DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function approvalRejectionWater($request, $roleId) {
             
            $mWaterConsumerActive  =  new WaterConsumerActiveRequest();
            $this->preApprovalConditionCheck($request, $roleId);
            
            # Approval of water application 
        if ($request->status == 1) {

            $mWaterConsumerActive->finalApproval($request);
            $msg = "Application Successfully Approved !!";
        }
        # Rejection of water application
        if ($request->status == 0) {
          $mWaterConsumerActive->finalRejectionOfAppication($request);
            $msg = "Application Successfully Rejected !!";
        }
        return responseMsgs(true, $msg, $request ?? "Empty", '', 01, '.ms', 'Post', $request->deviceId);
    
     
}
/**
 * function for check pre condition for 
 * approval and reject 
 */
         
    public function preApprovalConditionCheck($request, $roleId)
    {
        $waterDetails = WaterConsumerActiveRequest::find($request->applicationId);
        if ($waterDetails->finisher != $roleId) {
            throw new Exception("You're Not the finisher ie. AE!");
        }
        if ($waterDetails->current_role != $roleId) {
            throw new Exception("Application has not Reached to the finisher ie. AE!");
        }
     
        // if ($waterDetails->payment_status != 1) {
        //     throw new Exception("Payment Not Done or not verefied!");
        // }
        // if ($waterDetails->is_field_verified == 0) {
        //     throw new Exception("Field Verification Not Done!!");
        // }
        // $this->checkDataApprovalCondition($request, $roleId, $waterDetails);   // Reminder
        return $waterDetails;
    }
}
