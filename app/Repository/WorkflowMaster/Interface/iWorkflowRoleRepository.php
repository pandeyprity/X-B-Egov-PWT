<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentWorkflowRoleRepository
 * @return ChildRepository App\Repository\WorkflowMaster\EloquentWorkflowRoleRepository
 */


interface iWorkflowRoleRepository
{
    public function create(Request $request);
    public function getAllRoles();
    public function deleteRole($request);
    public function editRole(Request $request);
    public function getRole($request);
}
