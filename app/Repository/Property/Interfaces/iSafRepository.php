<?php

namespace App\Repository\Property\Interfaces;

/**
 * | Created On-28-12-2022 
 * | Created By-Anshu Kumar
 * | Created for- Interface for SAF Repository
 */
interface iSafRepository
{
    public function metaSafDtls($workflowIds);
    public function getSaf($workflowIds);
    public function getPropTransByCitizenUserId($userId, $userType);
}
