<?php

namespace App\Repository\UlbWorkflow;

use App\Repository\UlbWorkflow\UlbWorkflow;
use App\Models\UlbWorkflowMaster;
use Illuminate\Http\Request;
use Exception;
use App\Traits\UlbWorkflow as UlbWorkflowTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Repository for Ulb Workflows Store, fetch, edit and destroy
 * Created On-14-07-2022 
 * Created By-Anshu Kumar
 */

class EloquentUlbWorkflow implements UlbWorkflow
{
    use UlbWorkflowTrait;
    /**
     * Storing UlbWorkflows
     * Storing Using Trait-App\Traits\UlbWorkflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     * --------------------------------------------------------------------------------------
     * @desc Check the duplication of Module for the UlbID 
     * #check_ulb_module=Statement for checking the already existance for UlbID and ModuleID
     * --------------------------------------------------------------------------------------
     * Save Using Trait
     */
    public function store(Request $request)
    {
        $request->validate([
            'ulbID' => 'required',
            'workflowID' => "required|int"
        ]);

        try {
            $ulb_workflow = new UlbWorkflowMaster;
            $check_ulb_module = $this->checkUlbModuleExistance($request);    // Checking if the ulbID already existing for the workflowid or not
            if ($check_ulb_module) {
                return response()->json('Module is already existing to this Ulb ID', 200);
            }
            if (!$check_ulb_module) {
                return $this->saving($ulb_workflow, $request);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Show all Ulb Workflows
     * -----------------------------------------------------------------------------------
     * required variable to be used in our functions
     * -----------------------------------------------------------------------------------
     * #ref_stmt= The reference Statement sql query for fetching data using Trait
     * #condition = required condition for data
     * #query = Final query merging both variables
     * 
     */
    public function create()
    {
        $ref_stmt = $this->queryStatement();                                // Fetching Data using Trait
        $condition = "WHERE uwm.deleted_at IS NULL GROUP BY uwm.id,um.ulb_name,w.workflow_name,mm.module_name,u.user_name,u1.user_name";
        $query = $ref_stmt . ' ' . $condition;                              // Final Query
        $ulb_workflow = DB::select($query);
        $arr = array();
        return $this->fetchUlbWorkflow($ulb_workflow, $arr);
    }

    /**
     * Updating UlbWorkflows
     * Store Using App\Traits\UlbWorkflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     * -------------------------------------------------------------------------------------
     * #stmt= Statement for checking already existance of Module ID for UlbID
     * Update Ulb Workflow Masters
     * In case of Workflow Candidates First delete the existing records of UlbWorkflowCandidates and Then add New
     * Delete Redis Data if already existing
     * 
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'ulbID' => 'required',
            'workflowID' => 'required|int'
        ]);

        try {
            $redis = Redis::connection();
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $stmt = $ulb_workflow->module_id == $request->moduleID;
            if ($stmt) {
                // $this->saving($ulb_workflow, $request);
                $this->deleteExistingCandidates($id);
                $redis->del('ulb_workflow:' . $id);
                return $this->saving($ulb_workflow, $request);
            }
            if (!$stmt) {
                $check_module = $this->checkUlbModuleExistance($request);      // Checking if the ulb_workflow already existing or not
                if ($check_module) {
                    return response()->json('Module already Existing for this Ulb', 200);
                } else {
                    $this->deleteExistingCandidates($id);                       // Deleting Existing Candidates
                    $redis->del('ulb_workflow:' . $id);
                    return $this->saving($ulb_workflow, $request);
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Deleting Temporary UlbWorkflows 
     * @param id
     */
    public function destroy($id)
    {
        try {
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $redis = Redis::connection();
            $redis->del('ulb_workflow:' . $id);                 // Redis Flash
            $ulb_workflow->delete();
            return response()->json('Successfully Deleted', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get UlbWorkflows By Id
     * @param id
     * ----------------------------------------------------------------------------------
     * required variable to be used in our functions
     * -----------------------------------------------------------------------------------
     * #redis= Establishing the connection between redis server
     * #redis_existing = Get The Redis Data values if exist
     * #ref_stmt= The reference Statement sql query for fetching data using Trait
     * #condition = required condition for data
     * #query = Final query merging both variables
     * #ulb_workflow = All the query executed Data
     * -----------------------------------------------------------------------------------
     * Check if the data already present on redis 
     * If present give the data from redis server using Trait
     * If Data not present the give the data using sql query and Store the data on redis-server
     */
    public function show($id)
    {
        $redis = Redis::connection();
        $redis_existing = array(json_decode($redis->get('ulb_workflow:' . $id)));
        if ($redis_existing[0]) {
            $arr = array();
            return $this->fetchUlbWorkflow($redis_existing, $arr);          // Fetching Data Using Trait
        }

        $ref_stmt = $this->queryStatement();                                // Fetching Data using Trait
        $condition = "where uwm.id=$id GROUP BY uwm.id,um.ulb_name,w.workflow_name,mm.module_name,u.user_name,u1.user_name";
        $query = $ref_stmt . ' ' . $condition;                              // Final Query
        $ulb_workflow = DB::select($query);
        //  If Data Available
        if ($ulb_workflow) {
            $arr = array();

            // Set data On Redis Server
            $redis->set(
                'ulb_workflow:' . $ulb_workflow[0]->id,
                json_encode([
                    'id' => $ulb_workflow[0]->id,
                    'ulb_id' => $ulb_workflow[0]->ulb_id,
                    'ulb_name' => $ulb_workflow[0]->ulb_name,
                    'module_id' => $ulb_workflow[0]->module_id,
                    'module_name' => $ulb_workflow[0]->module_name,
                    'workflow_id' => $ulb_workflow[0]->workflow_id,
                    'workflow_name' => $ulb_workflow[0]->workflow_name,
                    'initiator' => $ulb_workflow[0]->initiator,
                    'initiator_name' => $ulb_workflow[0]->initiator_name,
                    'finisher' => $ulb_workflow[0]->finisher,
                    'finisher_name' => $ulb_workflow[0]->finisher_name,
                    'one_step_movement' => $ulb_workflow[0]->one_step_movement,
                    'remarks' => $ulb_workflow[0]->remarks,
                    'deleted_at' => $ulb_workflow[0]->deleted_at,
                    'created_at' => $ulb_workflow[0]->created_at,
                    'updated_at' => $ulb_workflow[0]->updated_at,
                    'candidate_id' => $ulb_workflow[0]->candidate_id,
                    'candidate_name' => $ulb_workflow[0]->candidate_name
                ])
            );

            return $this->fetchUlbWorkflow($ulb_workflow, $arr);
        }
        // If Data Not Available
        if (!$ulb_workflow) {
            return response()->json('Data Not Found for this id', 404);
        }
    }

    /**
     * Display the Specific record of Ulb Workflows by their Ulbs
     * 
     * @param int $ulb_id
     * @return \Illuminate\Http\Response
     */

    public function getUlbWorkflowByUlbID($ulb_id)
    {
        $workflow = DB::select("
                                select u.id,
                                um.ulb_name,
                                u.workflow_id,
                                w.workflow_name,
                                u.initiator,
                                u.finisher,
                                u.remarks
                        from ulb_workflow_masters u
                        left join workflows w on w.id=u.workflow_id
                        left join ulb_masters um on um.id=u.ulb_id
                        where u.ulb_id=$ulb_id and u.deleted_at is null
                    ");
        return responseMsg(true, "Data Fetched", remove_null($workflow));
    }
}
