<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;


class WorkflowMap extends Controller
{
    protected $wfMap;
    // Initializing Construct function
    public function __construct(iWorkflowMapRepository $wfMap)
    {
        $this->wfMap = $wfMap;
    }

    //Mapping 
    public function getRoleDetails(Request $req)
    {
        return $this->wfMap->getRoleDetails($req);
    }

    public function getUserById(Request $request)
    {
        return $this->wfMap->getUserById($request);
    }

    public function getWorkflowNameByUlb(Request $request)
    {
        // return 'Hii';
        return $this->wfMap->getWorkflowNameByUlb($request);
    }

    public function getRoleByUlb(Request $request)
    {
        return $this->wfMap->getRoleByUlb($request);
    }

    public function getWardByUlb(Request $request)
    {
        return $this->wfMap->getWardByUlb($request);
    }

    public function getUserByRole(Request $request)
    {
        return $this->wfMap->getUserByRole($request);
    }

    //============================================================
    //============================================================
    public function getRoleByWorkflow(Request $request)
    {
        return $this->wfMap->getRoleByWorkflow($request);
    }

    public function getUserByWorkflow(Request $request)
    {
        return $this->wfMap->getUserByWorkflow($request);
    }

    public function getWardsInWorkflow(Request $request)
    {
        return $this->wfMap->getWardsInWorkflow($request);
    }

    public function getUlbInWorkflow(Request $request)
    {
        return $this->wfMap->getUlbInWorkflow($request);
    }

    public function getWorkflowByRole(Request $request)
    {
        return $this->wfMap->getWorkflowByRole($request);
    }

    public function getUserByRoleId(Request $request)
    {
        return $this->wfMap->getUserByRoleId($request);
    }

    public function getWardByRole(Request $request)
    {
        return $this->wfMap->getWardByRole($request);
    }

    public function getUlbByRole(Request $request)
    {
        return $this->wfMap->getUlbByRole($request);
    }

    public function getUserInUlb(Request $request)
    {
        return $this->wfMap->getUserInUlb($request);
    }

    public function getRoleInUlb(Request $request)
    {
        return $this->wfMap->getRoleInUlb($request);
    }

    public function getWorkflowInUlb(Request $request)
    {
        return $this->wfMap->getWorkflowInUlb($request);
    }

    public function getRoleByUserUlbId(Request $request)
    {
        return $this->wfMap->getRoleByUserUlbId($request);
    }

    public function getRoleByWardUlbId(Request $request)
    {
        return $this->wfMap->getRoleByWardUlbId($request);
    }

    //
    public function getWorkflow(Request $request)
    {
        return $this->wfMap->getWorkflow($request);
    }
}
