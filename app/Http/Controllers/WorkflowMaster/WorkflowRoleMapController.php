<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\ObjectionController;
use App\Models\User;
use App\Models\Workflows\WfWorkflow;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\Property\Interfaces\iConcessionRepository;
use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Workflow\Workflow;
use Exception;

/**
 * Created On-14-10-2022 
 * Created By-Mrinal Kumar
 */

class WorkflowRoleMapController extends Controller
{
    use Workflow;
    //create master
    private $_safRepository;
    private $_concession;
    private $_objection;
    public function __construct(iSafRepository $saf_repository, iConcessionRepository $concession, iObjectionRepository $objection)
    {
        $this->_safRepository = new ActiveSafController($saf_repository);
        $this->_concession = new ConcessionController($concession);
        $this->_objection = new ObjectionController($objection);
    }

    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'workflowId' => 'required',
                'wfRoleId' => 'required',
                // 'forwardRoleId' => 'required',
                // 'backwardRoleId' => 'required',
            ]);

            $create = new WfWorkflowrolemap();
            $create->addRoleMap($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update master
    public function updateRoleMap(Request $req)
    {
        try {
            $update = new WfWorkflowrolemap();
            $list  = $update->updateRoleMap($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //master list by id
    public function roleMapbyId(Request $req)
    {
        try {

            $listById = new WfWorkflowrolemap();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "Role Map List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all master list
    public function getAllRoleMap()
    {
        try {

            $list = new WfWorkflowrolemap();
            $masters = $list->roleMaps();

            return responseMsg(true, "All Role Map List", $masters);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete master
    public function deleteRoleMap(Request $req)
    {
        try {
            $delete = new WfWorkflowrolemap();
            $delete->deleteRoleMap($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //Workflow Info
    public function workflowInfo(Request $req)
    {
        try {
            //workflow members
            $mWorkflowMap = new WorkflowMap();
            $mWfWorkflows = new WfWorkflow();
            $mWfWorkflowrolemap = new WfWorkflowrolemap();
            $ulbId = authUser()->ulb_id;
            // $wfMasterId = $req->workflowId;                  // wfMasterId from frontend in the key of wokflowId
            $workflowId = $req->workflowId;                  // wfMasterId from frontend in the key of wokflowId
            // $ulbWorkflow = $mWfWorkflows->getulbWorkflowId($wfMasterId, $ulbId);
            // $workflowId  =  $ulbWorkflow->id;

            $mreqs = new Request(["workflowId" => $workflowId]);
            $role = $mWorkflowMap->getRoleByWorkflow($mreqs);

            $data['members'] = collect($role)['original']['data'];

            //logged in user role
            $role = $this->getRole($mreqs);
            if ($role->isEmpty())
                throw new Exception("You are not authorised");
            $roleId  = collect($role)['wf_role_id'];

            //members permission
            $data['permissions'] = $this->permission($workflowId, $roleId);

            // pseudo users
            $data['pseudoUsers'] = $this->pseudoUser($ulbId);

            return responseMsgs(true, "Workflow Information", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // tabs permission
    public function permission($workflowId, $roleId)
    {
        $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
            ->where('wf_workflowrolemaps.workflow_id', $workflowId)
            ->where('wf_workflowrolemaps.wf_role_id', $roleId)
            ->first();

        $data = [
            'allow_full_list' => $permission->allow_full_list,
            'can_escalate' => $permission->can_escalate,
            'can_btc' => $permission->is_btc,
            'is_enabled' => $permission->is_enabled,
            'can_view_document' => $permission->can_view_document,
            'can_upload_document' => $permission->can_upload_document,
            'can_verify_document' => $permission->can_verify_document,
            'allow_free_communication' => $permission->allow_free_communication,
            'can_forward' => $permission->can_forward,
            'can_backward' => $permission->can_backward,
            'can_approve' => $permission->is_finisher,
            'can_reject' => $permission->is_finisher,
            'is_pseudo' => $permission->is_pseudo,
            'show_field_verification' => $permission->show_field_verification,
            'can_view_form' => $permission->can_view_form,
            'can_see_tc_verification' => $permission->can_see_tc_verification,
            'can_edit' => $permission->can_edit,
            'can_send_sms' => $permission->can_send_sms,
            'can_comment' => $permission->can_comment,
            'is_custom_enabled' => $permission->is_custom_enabled,
            'je_comparison' => $permission->je_comparison,
            'technical_comparison' => $permission->technical_comparison,
            'can_view_technical_comparison' => $permission->can_view_technical_comparison,
        ];

        return $data;
    }

    public function pseudoUser($ulbId)
    {
        $pseudo = User::select(
            'id',
            'user_name'
        )
            ->where('user_type', 'Pseudo')
            ->where('ulb_id', $ulbId)
            ->where('suspended', false)
            ->get();
        return $pseudo;
    }
}
