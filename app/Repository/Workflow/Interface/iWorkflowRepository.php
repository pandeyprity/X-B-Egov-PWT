<?php

namespace App\Repository\Workflow\Interface;

interface iWorkflowRepository
{
    public function workflowCurrentUser($req);
    public function workflowInitiatorData($req);
    public function roleIdByUserId();
    public function wardByUserId();
    public function getRole($req);
    public function initiatorId($req);
    public function finisherId($req);
}
