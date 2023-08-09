<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\WorkflowMaster\MasterController;
use App\Http\Controllers\WorkflowMaster\RoleController;
use App\Http\Controllers\WorkflowMaster\WardUserController;
use App\Http\Controllers\WorkflowMaster\WorkflowController as WfController;
use App\Http\Controllers\WorkflowMaster\WorkflowMap;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleMapController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleUserMapController;
use App\Http\Controllers\Workflows\WfDocumentController;
use Illuminate\Support\Facades\Route;


/**
 * Creation Date: 06-10-2022
 * Created By  :- Mrinal Kumar
 * Modified On :- 17-12-2022
 * Modified By :- Mrinal Kumar
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger', 'expireBearerToken']], function () {

    /**
     * workflow Master CRUD operation
     */

    Route::controller(MasterController::class)->group(function () {
        Route::post('master/save', 'createMaster');                     // Save Master
        Route::post('master/edit', 'updateMaster');                     // Edit Master 
        Route::post('master/byId', 'masterbyId');                       // Get Master By Id
        Route::post('master/list', 'getAllMaster');                     // Get All Master
        Route::post('master/delete', 'deleteMaster');                   // Delete Master
    });


    /**
     * Wf workflow CRUD operation
     */

    Route::controller(WfController::class)->group(function () {
        Route::post('wfworkflow/save', 'createWorkflow');                     // Save Workflow
        Route::post('wfworkflow/edit', 'updateWorkflow');                     // Edit Workflow 
        Route::post('wfworkflow/byId', 'workflowbyId');                       // Get Workflow By Id
        Route::post('wfworkflow/list', 'getAllWorkflow');                     // Get All Workflow
        Route::post('wfworkflow/delete', 'deleteWorkflow');                   // Delete Workflow
    });


    /**
     * workflow roles CRUD operation
     */
    // Route::controller(WorkflowRoleController::class)->group(function () {
    //     Route::post('crud/roles/save-role', 'create');                      // Save Role
    //     Route::put('crud/roles/edit-role', 'editRole');                     // edit Role
    //     Route::post('crud/roles/get-role', 'getRole');                      // Get Role By Id
    //     Route::get('crud/roles/get-all-roles', 'getAllRoles');              // Get All Roles
    //     Route::delete('crud/roles/delete-role', 'deleteRole');              // Delete Role
    // });


    /**
     * ============== To be replaced with upper api  =================
     */
    Route::controller(RoleController::class)->group(function () {
        Route::post('roles/save', 'createRole');                   // Save Role
        Route::post('roles/edit', 'editRole');                     // edit Role
        Route::post('roles/get', 'getRole');                       // Get Role By Id
        Route::post('roles/list', 'getAllRoles');                  //Get All Roles          
        Route::post('roles/delete', 'deleteRole');                 // Delete Role
    });
    /**
     * ===================================================================
     */


    /**
     * Ward User CRUD operation
     */
    Route::controller(WardUserController::class)->group(function () {
        Route::post('ward-user/save', 'createWardUser');                     // Save Workflow
        Route::post('ward-user/edit', 'updateWardUser');                     // Edit Workflow 
        Route::post('ward-user/byId', 'WardUserbyId');                       // Get Workflow By Id
        Route::post('ward-user/list', 'getAllWardUser');                     // Get All Workflow
        Route::post('ward-user/delete', 'deleteWardUser');                   // Delete Workflow
        Route::post('ward-user/list-tc', 'tcList');
    });


    /**
     * Role User Map CRUD operation
     */
    Route::controller(WorkflowRoleUserMapController::class)->group(function () {
        Route::post('role-user-maps/get-roles-by-id', 'getRolesByUserId');                        // Get Permitted Roles By User ID
        Route::post('role-user-maps/update-user-roles', 'updateUserRoles');                       // Enable or Disable User Role
    });


    /**
     * Workflow Role Map CRUD operation
     */

    Route::controller(WorkflowRoleMapController::class)->group(function () {
        Route::post('role-map/save', 'createRoleMap');                     // Save Workflow
        Route::post('role-map/edit', 'updateRoleMap');                     // Edit Workflow 
        Route::post('role-map/byId', 'roleMapbyId');                       // Get Workflow By Id
        Route::post('role-map/list', 'getAllRoleMap');                     // Get All Workflow
        Route::post('role-map/delete', 'deleteRoleMap');                   // Delete Workflow
        Route::post('role-map/workflow-info', 'workflowInfo');
    });


    /**
     * Workflow Mapping CRUD operation
     */

    Route::controller(WorkflowMap::class)->group(function () {

        //Mapping
        Route::post('getroledetails', 'getRoleDetails');
        Route::post('getUserById', 'getUserById');
        Route::post('getWorkflowNameByUlb', 'getWorkflowNameByUlb');
        Route::post('getRoleByUlb', 'getRoleByUlb');
        Route::post('getWardByUlb', 'getWardByUlb');
        Route::post('getUserByRole', 'getUserByRole');

        //mapping
        Route::post('getRoleByWorkflow', 'getRoleByWorkflow');
        Route::post('getUserByWorkflow', 'getUserByWorkflow');
        Route::post('getWardsInWorkflow', 'getWardsInWorkflow');
        Route::post('getUlbInWorkflow', 'getUlbInWorkflow'); //
        Route::post('getWorkflowByRole', 'getWorkflowByRole');
        Route::post('getUserByRoleId', 'getUserByRoleId');
        Route::post('getWardByRole', 'getWardByRole');
        Route::post('getUlbByRole', 'getUlbByRole');
        Route::post('getUserInUlb', 'getUserInUlb');
        Route::post('getRoleInUlb', 'getRoleInUlb');
        Route::post('getWorkflowInUlb', 'getWorkflowInUlb');

        Route::post('getRoleByUserUlbId', 'getRoleByUserUlbId');
        Route::post('getRoleByWardUlbId', 'getRoleByWardUlbId');

        Route::post('get-ulb-workflow', 'getWorkflow');
    });
});

// for unautheticated citizen
Route::controller(WorkflowMap::class)->group(function () {
    Route::post('wardByUlb', 'getWardByUlb');
});
