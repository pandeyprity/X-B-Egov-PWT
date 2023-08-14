<?php

namespace App\Repository\Markets;

/**
 * | Created On-13-02-2023 
 * | Created By-Bikash Kumar
 * | Interface for Markets Repository
 */
interface iMarketRepo
{
    public function specialInbox($workflowIds);
    public function specialInboxHostel($workflowIds);
    public function specialInboxLodge($workflowIds);
    public function specialInboxmDharamshala($workflowIds);
}