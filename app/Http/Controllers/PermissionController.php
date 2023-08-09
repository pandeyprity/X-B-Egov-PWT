<?php

namespace App\Http\Controllers;

use App\Models\Permissions\ActionMaster;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRoleusermap;
use App\Pipelines\ModulePermissions;
use Exception;
use Illuminate\Pipeline\Pipeline;

/**
 * | Controller for giving Controller
 * | Created On-06-03-2023 
 * | Created By-Mrinal Kumar
 * | Status-Closed
 * 
 * | Modified Function getUserPermission() By Anshu Kumar On 10-03-2023 
 */

class PermissionController extends Controller
{

    // /**
    //  * | Get Permission by User
    //  */
    // public function getUserPermission(Request $req)
    // {
    //     $req->validate([
    //         'module' => 'required'
    //     ]);
    //     try {
    //         // Variable Assignments
    //         $userId = authUser($req)->id;
    //         $mWfRoleUserMap = new WfRoleusermap();
    //         $mActionMaster = new ActionMaster();

    //         // Derivative Assignments
    //         $wfRoles = $mWfRoleUserMap->getRoleIdByUserId($userId);
    //         $roleIds = collect($wfRoles)->map(function ($item) {
    //             return $item->wf_role_id;
    //         });
    //         $mActionMaster->_roleIds = $roleIds;
    //         $baseQuery = $mActionMaster->getPermissionsByRoleId();
    //         $permissions = app(Pipeline::class)
    //             ->send($baseQuery)
    //             ->through([
    //                 ModulePermissions::class,
    //             ])
    //             ->thenReturn()
    //             ->get();
    //         return responseMsgs(true, "Permissions", remove_null($permissions), '100101', '1.0', '', 'POST', $req->deviceId ?? "");
    //     } catch (Exception $e) {
    //         return responseMsgs(false, $e->getMessage(), '', '100101', '1.0', '', 'POST', $req->deviceId ?? "");
    //     }
    // }
}
