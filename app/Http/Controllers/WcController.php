<?php

namespace App\Http\Controllers;

use App\Repository\Workflow\Interface\iWorkflowRepository;
use Illuminate\Http\Request;

/**
 * Controller for Calling Workflow Trait
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 */

class WcController extends Controller
{
    public function __construct(iWorkflowRepository $repo)
    {
        $this->_repo = $repo;
    }

    /*
    * Get Workflow Current User
    */
    public function workflowCurrentUser(Request $request)
    {
        return $this->_repo->workflowCurrentUser($request);
    }

    /*
    * Get Workflow Initiator Data
    */
    public function workflowInitiatorData(Request $request)
    {
        return $this->_repo->workflowInitiatorData($request);
    }

    /*
    * Get Role id by User Id
    */
    public function roleIdByUserId()
    {
        return $this->_repo->roleIdByUserId();
    }

    /*
    * Get Ward  by User Id
    */
    public function wardByUserId()
    {
        return $this->_repo->wardByUserId();
    }

    /*
    * Get Role
    */
    public function getRole(Request $request)
    {
        return $this->_repo->getRole($request);
    }

    /*
    * Get Initiator
    */
    public function initiatorId(Request $request)
    {
        return $this->_repo->initiatorId($request);
    }

    /*
    * Get Finisher
    */
    public function finisherId(Request $request)
    {
        return $this->_repo->finisherId($request);
    }
}
