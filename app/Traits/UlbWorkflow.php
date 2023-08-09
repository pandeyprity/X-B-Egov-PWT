<?php

namespace App\Traits;

use App\Models\UlbWorkflowMaster;
use App\Models\WorkflowCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Trait for saving,editing,fetching and deleting UlbWorkflow
 */
trait UlbWorkflow
{
    /**
     * Checking if the ModuleID is already existing for the ULBID or not
     */
    public function checkUlbModuleExistance($request)
    {
        return UlbWorkflowMaster::where('ulb_id', $request->ulbID)
            ->where('module_id', $request->moduleID)
            ->first();
    }

    /**
     * Saving and editing the ulbworkflow
     * @param UlbWorkflowMasters Model $ulb_workflow
     * @param Request $request
     * #ref_cand = contains the value of request Candidate IDs in a array
     * Save Candidates ID in Workflow Candidates using loop
     * @return Response
     */
    public function saving($ulb_workflow, $request)
    {
        $ulb_workflow->workflow_id = $request->workflowID;
        $ulb_workflow->module_id = $request->moduleID;
        $ulb_workflow->ulb_id = $request->ulbID;
        $ulb_workflow->initiator = $request->initiator;
        $ulb_workflow->finisher = $request->finisher;
        $ulb_workflow->one_step_movement = $request->oneStepMovement;
        $ulb_workflow->remarks = $request->remarks;
        $ulb_workflow->save();

        $this->storeNewCandidates($ulb_workflow, $request);   // Save Candidate ID with array with looping
        return response()->json([
            'workflow_id' => $request->workflowID,
            'module_id' => $request->moduleID,
            'ulb_id' => $request->ulbID
        ], 200);
    }

    /**
     * Function for Fresh Add or Update the Workflow Candidates
     */
    public function storeNewCandidates($ulb_workflow, $request)
    {
        // Save Candidate ID with array with looping
        $ref_cand = $request['candidates'];
        foreach ($ref_cand as $ref_cands) {
            $wc = new WorkflowCandidate;
            $wc->ulb_workflow_id = $ulb_workflow->id;
            $wc->user_id = $ref_cands;
            $wc->save();
        }
    }

    /**
     * @desc Function for deleting the existing Candidates which are required to delete 
     */
    public function deleteExistingCandidates($id)
    {
        $query = "select * from workflow_candidates where ulb_workflow_id=$id";
        $candidate = DB::select($query);
        foreach ($candidate as $candidates) {
            $workflow_candidates = WorkflowCandidate::find($candidates->id);
            $workflow_candidates->forceDelete();
        }
    }

    // Sql query Statement for getting all Ulb Workflows
    public function queryStatement()
    {
        $stmt = "SELECT uwm.*,
                um.ulb_name,
                w.workflow_name,
                mm.module_name,
                u.user_name AS initiator_name,
                u1.user_name AS finisher_name,
                String_Agg(cast(wc.user_id AS VARCHAR),',') AS candidate_id,
                String_Agg(cast(u2.user_name AS VARCHAR),',') AS candidate_name
                FROM ulb_workflow_masters uwm
                
                INNER JOIN ulb_masters um ON um.id=uwm.ulb_id
                INNER JOIN workflows w ON w.id=uwm.workflow_id
                INNER JOIN module_masters mm ON mm.id=uwm.module_id
                INNER JOIN users u ON u.id=uwm.initiator
                INNER JOIN users u1 ON u1.id=uwm.finisher
                INNER JOIN workflow_candidates wc ON wc.ulb_workflow_id=uwm.id
                INNER JOIN users u2 ON u2.id=wc.user_id";
        return $stmt;
    }

    // Fetch ulb Workflow in array
    public function fetchUlbWorkflow($ulb_workflow, $arr)
    {
        foreach ($ulb_workflow as $ulb_workflows) {
            $val['id'] = $ulb_workflows->id ?? '';
            $val['ulb_id'] = $ulb_workflows->ulb_id ?? '';
            $val['ulb_name'] = $ulb_workflows->ulb_name ?? '';
            $val['module_id'] = $ulb_workflows->module_id ?? '';
            $val['module_name'] = $ulb_workflows->module_name ?? '';
            $val['workflow_id'] = $ulb_workflows->workflow_id ?? '';
            $val['workflow_name'] = $ulb_workflows->workflow_name ?? '';
            $val['initiator'] = $ulb_workflows->initiator ?? '';
            $val['initiator_name'] = $ulb_workflows->initiator_name ?? '';
            $val['finisher'] = $ulb_workflows->finisher ?? '';
            $val['finisher_name'] = $ulb_workflows->finisher_name ?? '';
            $val['one_step_movement'] = $ulb_workflows->one_step_movement ?? '';
            $val['remarks'] = $ulb_workflows->remarks ?? '';
            $val['deleted_at'] = $ulb_workflows->deleted_at ?? '';
            $val['created_at'] = $ulb_workflows->created_at ?? '';
            $val['updated_at'] = $ulb_workflows->updated_at ?? '';

            $arr_canid = explode(',', $ulb_workflows->candidate_id);                // Exploding Candidate id as an array format
            $arr_canname = explode(',', $ulb_workflows->candidate_name);            // Exploding Candidate id as array format

            $val['candidate_id'] = $arr_canid ?? '';
            $val['candidate_name'] = $arr_canname ?? '';


            array_push($arr, $val);
        }
        return response($arr);
    }
}
