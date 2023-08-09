<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafReassessRepo;

/**
 * | Created On- 17-11-2022 
 * | Created By- Anshu Kumar
 * | Controller for SAF Reassessment Apply Section
 */

class SafReassessmentController extends Controller
{
    protected $_Repo;

    public function __construct(iSafReassessRepo $repo)
    {
        $this->_Repo = $repo;
    }
}
