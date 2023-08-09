<?php

namespace App\Repository\Workflow\Concrete;

use App\Repository\Workflow\Interface\iWorkflowRepository;
use App\Traits\Workflow\Workflow;

/**
 * Repository for Calling Workflow Trait
 * Parent Controller -App\Controllers\WcController
 * -------------------------------------------------------------------------------------------------
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class WorkflowRepository implements iWorkflowRepository
{
    use Workflow;

    /*
    * Get Workflow Current User
    */
    public function workflowCurrentUser($req)
    {
        $currentUser = $this->getWorkflowCurrentUser($req->workflowId);

        if (isset($currentUser)) {
            return responseMsg(true, 'Current User', $currentUser);
        }
    }

    /*
    * Get Workflow Initiator Data
    */
    public function workflowInitiatorData($req)
    {
        $userId = authUser()->id;
        $initiatorData = $this->getWorkflowInitiatorData($userId, $req->workflowId);

        return responseMsg(true, 'Initiator Data', $initiatorData);
    }

    /*
    * Get Role id by User Id
    */
    public function roleIdByUserId()
    {
        $userId = auth()->User();
        $roleId = $this->getRoleIdByUserId($userId['id']);

        return responseMsg(true, 'Workflow Role Id', $roleId);
    }

    /*
    * Get Wards by User Id
    */
    public function wardByUserId()
    {
        $userId = auth()->User();
        $wardId = $this->getWardByUserId($userId['id']);

        return responseMsg(true, 'Workflow Wards', $wardId);
    }

    /*
    * Get Initiator
    */
    public function initiatorId($req)
    {
        $initiatorId = $this->getInitiatorId($req->workflowId);

        return responseMsg(true, 'Initiator', $initiatorId);
    }

    /*
    * Get Initiator
    */
    public function finisherId($req)
    {
        $finisherId = $this->getFinisherId($req->workflowId);

        return responseMsg(true, 'Finisher', $finisherId);
    }
}
