<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRole;
use Exception;

class RoleController extends Controller
{
    //create master
    public function createRole(Request $req)
    {
        try {
            $create = new WfRole();
            $create->addRole($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update master
    public function editRole(Request $req)
    {
        try {
            $update = new WfRole();
            $list  = $update->updateRole($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //master list by id
    public function getRole(Request $req)
    {
        try {

            $listById = new WfRole();
            $list  = $listById->rolebyId($req);

            return responseMsg(true, "Role List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all master list
    public function getAllRoles()
    {
        try {

            $list = new WfRole();
            $masters = $list->roleList();

            return responseMsg(true, "All Role List", $masters);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete master
    public function deleteRole(Request $req)
    {
        try {
            $delete = new WfRole();
            $delete->deleteRole($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
