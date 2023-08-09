<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentWorkflowRoleUserMapRepository
 * @return ChildRepository App\Repository\WorkflowMaster\EloquentWorkflowRoleUserMapRepository
 */


interface iWorkflowRoleUserMapRepository
{
    public function create(Request $request);
    public function list();
    public function delete($id);
    public function update(Request $request, $id);
    public function view($id);

    public function getRolesByUserId($req);             // Get Permitted Roles By User ID
    public function updateUserRoles($req);              // Enable or Disable the User Roles
}
