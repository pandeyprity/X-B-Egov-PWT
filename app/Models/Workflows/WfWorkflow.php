<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfWorkflow extends Model
{
    use HasFactory;


    /**
     * |---------------------------- Get workflow id by workflowId and ulbId -----------------------|
     * | @param workflowID
     * | @param ulbId
     * | @return  
     */
    public function getulbWorkflowId($wfMstId, $ulbId)
    {
        return  WfWorkflow::where('wf_master_id', $wfMstId)
            ->where('ulb_id', $ulbId)
            ->where('is_suspended', false)
            ->first();
    }

    //create workflow
    public function addWorkflow($req)
    {
        $createdBy = Auth()->user()->id;
        $data = new WfWorkflow;
        $data->wf_master_id = $req->wfMasterId;
        $data->ulb_id = $req->ulbId;
        $data->alt_name = $req->altName;
        $data->is_doc_required = $req->isDocRequired;
        $data->created_by = $createdBy;
        $data->initiator_role_id = $req->initiatorRoleId;
        $data->finisher_role_id = $req->finisherRoleId;
        $data->stamp_date_time = Carbon::now();
        $data->created_at = Carbon::now();
        $data->save();
    }

    //update workflow
    public function updateWorkflow($req)
    {
        $data = WfWorkflow::find($req->id);
        $data->wf_master_id = $req->wfMasterId;
        $data->ulb_id = $req->ulbId;
        $data->alt_name = $req->altName;
        $data->is_doc_required = $req->isDocRequired;
        $data->initiator_role_id = $req->initiatorRoleId;
        $data->finisher_role_id = $req->finisherRoleId;
        $data->save();
    }

    //list workflow by id
    public function listbyId($req)
    {
        $data = WfWorkflow::where('id', $req->id)
            ->where('is_suspended', false)
            ->first();
        return $data;
    }

    //All workflow list
    public function listWorkflow()
    {
        $data = WfWorkflow::select(
            'wf_workflows.*',
            'wf_masters.workflow_name',
            'ulb_masters.ulb_name',
            'wf_roles.role_name as initiator_role_name',
            'frole.role_name as finisher_role_name'
        )
            ->join('wf_masters', 'wf_masters.id', 'wf_workflows.wf_master_id')
            ->join('ulb_masters', 'ulb_masters.id', 'wf_workflows.ulb_id')
            ->leftJoin('wf_roles', 'wf_roles.id', 'wf_workflows.initiator_role_id')
            ->leftJoin('wf_roles as frole', 'frole.id', 'wf_workflows.finisher_role_id')
            ->where('wf_workflows.is_suspended', false)
            ->orderByDesc('wf_workflows.id')
            ->get();
        return $data;
    }

    /**
     * Delete workflow
     */
    public function deleteWorkflow($req)
    {
        $data = WfWorkflow::find($req->id);
        $data->is_suspended = "true";
        $data->save();
    }

    /**
     * | Get Wf master id by Workflow id
     */
    public function getWfMstrByWorkflowId($workflowId)
    {
        return WfWorkflow::select('wf_master_id')
            ->where('id', $workflowId)
            ->firstOrFail();
    }

    /**
     * | Get Wf Dtls
     */
    public function getWfDetails($ulbWorkflowId)
    {
        return WfWorkflow::findOrFail($ulbWorkflowId);
    }
}
