<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleRepository;
use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar 
 */

class WorkflowRoleController extends Controller
{
    protected $eloquentRole;

    // Initializing Construct function
    public function __construct(iWorkflowRoleRepository $eloquentRole)
    {
        $this->EloquentRole = $eloquentRole;
    }

    //list all roles
    public function getAllRoles()
    {
        return $this->EloquentRole->getAllRoles();
    }

    // create new role
    public function create(Request $request)
    {
        $request->validate([
            'roleName' => 'required',
        ]);
        return $this->EloquentRole->create($request);
    }

    // list role by id
    public function getRole(Request $request)
    {
        return $this->EloquentRole->getRole($request);
    }


    //update role
    public function editRole(Request $request)
    {
        $request->validate([
            'roleName' => 'required',
        ]);
        return $this->EloquentRole->editRole($request);
    }

    //delete role
    public function deleteRole(Request $request)
    {
        return $this->EloquentRole->deleteRole($request);
    }
}
