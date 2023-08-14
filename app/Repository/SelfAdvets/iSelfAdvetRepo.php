<?php

namespace App\Repository\SelfAdvets;

/**
 * | Created On-15-12-2022 
 * | Created By-Anshu Kumar
 * | Interface for Self Advertisement Repository
 */
interface iSelfAdvetRepo
{
    public function specialInbox($workflowIds);
    public function specialVehicleInbox($workflowIds);
    public function specialAgencyInbox($workflowIds);

    public function specialPrivateLandInbox($workflowIds);
    public function specialAgencyLicenseInbox($workflowIds);
}
