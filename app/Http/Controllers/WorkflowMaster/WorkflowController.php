<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWorkflow;
use Exception;

/**
 * Controller for Add, Update, View , Delete of Wf Workflow Table
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022
 * Created By-Mrinal Kumar
 * Modification On: 19-12-2022
 * Status : Open
 * -------------------------------------------------------------------------------------------------
 */

class WorkflowController extends Controller
{
    //create master
    public function createWorkflow(Request $req)
    {
        try {
            $req->validate([
                'wfMasterId' => 'required',
                'ulbId' => 'required',
                'altName' => 'required',
                'isDocRequired' => 'required',
            ]);

            $create = new WfWorkflow();
            $create->addWorkflow($req);

            return responseMsg(true, "Workflow Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update master
    public function updateWorkflow(Request $req)
    {
        try {
            $update = new WfWorkflow();
            $list  = $update->updateWorkflow($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //master list by id
    public function workflowbyId(Request $req)
    {
        try {

            $listById = new WfWorkflow();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "Workflow List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all master list
    public function getAllWorkflow()
    {
        try {

            $list = new WfWorkflow();
            $workflow = $list->listWorkflow();

            return responseMsg(true, "All Workflow List", $workflow);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete master
    public function deleteWorkflow(Request $req)
    {
        try {
            $delete = new WfWorkflow();
            $delete->deleteWorkflow($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
