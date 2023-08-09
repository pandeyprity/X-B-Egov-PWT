<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRole;
use Exception;
use Carbon\Carbon;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 */



class WorkflowRoleRepository implements iWorkflowRoleRepository
{

    public function create(Request $request)
    {
        try {

            $createdBy = Auth()->user()->id;
            $role = new WfRole;
            $role->role_name = $request->roleName;
            $role->created_by = $createdBy;
            $role->stamp_date_time = Carbon::now();
            $role->created_at = Carbon::now();
            $role->save();
            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * GetAll data
     */
    public function getAllRoles()
    {
        try {
            $data = WfRole::where('status', 1)
                ->orderBy('id')
                ->get();
            return responseMsg(true, "Successfully Saved", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * Delete data
     */
    public function deleteRole($request)
    {
        try {
            $data = WfRole::find($request->id);
            $data->status = 0;
            $data->save();
            return responseMsg(true, 'Successfully Deleted', "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * Update data
     */
    public function editRole(Request $request)
    {
        try {
            $createdBy = Auth()->user()->id;
            $role = WfRole::find($request->id);
            $role->role_name = $request->roleName;
            $role->is_suspended = $request->isSuspended;
            $role->created_by = $createdBy;
            $role->updated_at = Carbon::now();
            $role->save();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * list view by IDs
     */

    public function getRole($request)
    {
        try {
            $data = WfRole::where('id', $request->id)
                ->where('status', 1)
                ->get();
            if ($data) {
                return responseMsg(true, 'Succesfully Retrieved', $data);
            } else {
                return response()->json(['Message' => 'Data not found'], 404);
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
