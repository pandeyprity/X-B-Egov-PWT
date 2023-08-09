<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar 
 */

class WorkflowRoleUserMapController extends Controller
{
    protected $eloquentRoleUserMap;

    // Initializing Construct function
    public function __construct(iWorkflowRoleUserMapRepository $eloquentRoleUserMap)
    {
        $this->EloquentRoleUserMap = $eloquentRoleUserMap;
    }
    public function index()
    {
        return $this->EloquentRoleUserMap->list();
    }


    public function create()
    {
        //
    }

    //create
    public function store(Request $request)
    {
        return $this->EloquentRoleUserMap->create($request);
    }

    //list by id
    public function show($id)
    {
        return $this->EloquentRoleUserMap->view($id);
    }


    public function edit($id)
    {
        //
    }

    //update
    public function update(Request $request, $id)
    {
        return $this->EloquentRoleUserMap->update($request, $id);
    }

    //delete
    public function destroy($id)
    {
        return $this->EloquentRoleUserMap->delete($id);
    }


    // Get Permitted Roles By User ID
    public function getRolesByUserId(Request $req)
    {
        $req->validate([
            'userId' => 'required'
        ]);
        return $this->EloquentRoleUserMap->getRolesByUserId($req);
    }

    // Enable or Disable User Roles
    public function updateUserRoles(Request $req)
    {
        $req->validate([
            'roleId' => 'required|int',
            'status' => 'required|bool',
            'userId' => 'required|int'
        ]);
        return $this->EloquentRoleUserMap->updateUserRoles($req);
    }
}
