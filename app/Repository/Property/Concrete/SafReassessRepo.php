<?php

namespace App\Repository\Property\Concrete;


use App\Repository\Property\Interfaces\iSafReassessRepo;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;

/**
 * | Created On - 17-11-2022
 * | Created By - Anshu Kumar
 * | Property SAF Reassessment Repository
 */

class SafReassessRepo implements iSafReassessRepo
{
    use SAF;
    use Workflow;
}
