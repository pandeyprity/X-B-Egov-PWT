<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\UlbWardMaster;
use App\Models\User;
use Exception;


/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class WorkflowMap implements iWorkflowMapRepository
{
    //get role details by 
    public function getRoleDetails(Request $request)
    {
        // $ulbId = authUser($request)->ulb_id;
        $request->validate([
            'workflowId' => 'required|int'

        ]);
        $roleDetails = DB::table('wf_workflowrolemaps')
            ->select(
                'wf_workflowrolemaps.id',
                'wf_workflowrolemaps.workflow_id',
                'wf_workflowrolemaps.wf_role_id',
                'wf_workflowrolemaps.forward_role_id',
                'wf_workflowrolemaps.backward_role_id',
                'wf_workflowrolemaps.is_initiator',
                'wf_workflowrolemaps.is_finisher',
                'r.role_name as forward_role_name',
                'rr.role_name as backward_role_name'
            )
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->where('workflow_id', $request->workflowId)
            ->where('wf_role_id', $request->wfRoleId)
            ->first();
        return responseMsg(true, "Data Retrived", remove_null($roleDetails));
    }


    //getting data of user & ulb  by selecting  ward user id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserById(Request $request)
    {
        $request->validate([
            'wardUserId' => 'required|int'
        ]);
        $users = WfWardUser::where('wf_ward_users.id', $request->wardUserId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_ward_users.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get(['users.*', 'ulb_ward_masters.*']);
        return responseMsg(true, "Data Retrived", $users);
    }


    // tables = wf_workflows + wf_masters
    // ulbId -> workflow name
    // workflows in a ulb
    public function getWorkflowNameByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'required|int'
        ]);

        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->select('wf_masters.id', 'wf_masters.workflow_name')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    // tables = wf_workflows + wf_workflowrolemap + wf_roles
    // ulbId -> rolename
    // roles in a ulb 
    public function getRoleByUlb(Request $request)
    {
        //validating

        $request->validate([
            'ulbId' => 'required|int'
        ]);
        try {
            $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)

                ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
                ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
                ->get('wf_roles.role_name');
            return responseMsg(true, "Data Retrived", $workkFlow);
        } catch (Exception $e) {
            return $e;
        }
    }

    //workking
    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'nullable'
        ]);
        $ulbId = $request->ulbId ?? authUser()->ulb_id;
        $wards = collect();
        $workkFlow = UlbWardMaster::select(
            'id',
            'ulb_id',
            'ward_name',
            'old_ward_name'
        )
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->orderby('id')
            ->get();

        $groupByWards = $workkFlow->groupBy('ward_name');
        foreach ($groupByWards as $ward) {
            $wards->push(collect($ward)->first());
        }
        $wards->sortBy('ward_name')->values();
        return responseMsg(true, "Data Retrived", remove_null($wards));
    }

    // table = 6 & 7
    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $workkFlow = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.user_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }

    //============================================================================================
    //=============================       NEW MAPPING          ===================================
    //============================================================================================


    //role in a workflow
    public function getRoleByWorkflow(Request $request)
    {
        $ulbId = authUser()->ulb_id;
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $roles = WfWorkflowrolemap::select('wf_roles.id as role_id', 'wf_roles.role_name')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_workflows', 'wf_workflows.id', 'wf_workflowrolemaps.workflow_id')
            ->where('wf_workflows.ulb_id', $ulbId)
            ->where('workflow_id', $request->workflowId)
            ->where(function ($where) {
                $where->orWhereNotNull("wf_workflowrolemaps.forward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.backward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.serial_no");
            })
            ->orderBy('serial_no')
            ->get();

        return responseMsg(true, "Data Retrived", $roles);
    }

    //get user by workflowId
    public function getUserByWorkflow(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //wards in a workflow
    public function getWardsInWorkflow(Request $request)
    {
        $users = WfWorkflowrolemap::select('ulb_ward_masters.ward_name', 'ulb_ward_masters.id')
            ->where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }


    //ulb in a workflow
    public function getUlbInWorkflow(Request $request)
    {
        $users = WfWorkflow::where('wf_master_id', $request->id)
            ->select('ulb_masters.*')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }



    //get wf by role id
    public function getWorkflowByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->select('workflow_name')
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    // get users in a role
    public function getUserByRoleId(Request $request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //get wards by role
    public function getWardByRole(Request $request)
    {
        try {
            $users = WfRoleusermap::where('wf_role_id', $request->roleId)
                ->select('ulb_masters.*')
                ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
                ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
                ->get();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data Available", "");
        } catch (Exception $e) {
            return $e;
        }
    }

    //get ulb by role
    public function getUlbByRole(Request $request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get('ulb_masters.*');
        return responseMsg(true, "Data Retrived", $users);
    }


    //users in a ulb
    public function getUserInUlb(Request $request) //
    {
        $users = User::select('users.*')
            ->where('users.ulb_id', $request->ulbId)
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //role in ulb
    public function getRoleInUlb(Request $request)
    {
        $users = WfWorkflow::where('ulb_id', $request->ulbId)
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->get('role_name');
        return responseMsg(true, "Data Retrived", $users);
    }

    // working
    // workflow in ulb
    public function getWorkflowInUlb(Request $request)
    {
        $users = WfWorkflow::select('wf_masters.workflow_name', 'wf_workflows.id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->where('wf_workflows.ulb_id', $request->ulbId)
            ->where('wf_masters.is_suspended',  false)
            ->where('wf_workflows.is_suspended',  false)
            ->get();
        return responseMsg(true, "Data Retrived", $users);
    }

    //get role by ulb & user id
    public function getRoleByUserUlbId(Request $request)
    {
        try {
            $users = WfRole::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('wf_roleusermaps.user_id', $request->userId)
                ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', 'wf_roles.id')
                ->join('wf_ward_users', 'wf_ward_users.user_id', 'wf_roleusermaps.user_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'wf_ward_users.ward_id')
                ->first();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data Available", "");
        } catch (Exception $e) {
            return $e;
        }
    }

    //get role by ward & ulb id
    public function getRoleByWardUlbId(Request $request)
    {

        try {
            $users = UlbWardMaster::select('wf_roles.*')
                ->where('ulb_ward_masters.ulb_id', $request->ulbId)
                ->where('ulb_ward_masters.id', $request->wardId)
                ->join('wf_ward_users', 'wf_ward_users.ward_id', 'ulb_ward_masters.id')
                ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', 'wf_ward_users.user_id')
                ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
                ->first();
            if ($users) {
                return responseMsg(true, "Data Retrived", $users);
            }
            return responseMsg(false, "No Data available", "");
        } catch (Exception $e) {
            return $e;
        }
    }

    //working
    //get workflow by ulb and master id
    public function getWorkflow(Request $request)
    {
        $request->validate([
            "ulbId" => "required|numeric",
            "workflowMstrId" => "required|numeric",

        ]);
        try {
            $workflow = WfWorkflow::select('wf_workflows.*')
                ->where('ulb_id', $request->ulbId)
                ->where('wf_master_id', $request->workflowMstrId)
                ->where('is_suspended', false)
                ->first();
            if ($workflow) {
                return remove_null($workflow);
            }
            return responseMsg(false, "No Data available", "");
        } catch (Exception $e) {
            return $e;
        }
    }
}
