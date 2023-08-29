<?php

namespace App\BLL\Water;

use App\Models\Water\WaterApplication;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On :- 29-08-2023 
 * | Author     :- Sam kerketta
 * | Status     :- Open
 */
class WaterApplyCall
{
    private $_mWaterApplication;
    # Class cons
    public function __construct()
    {
        $this->_mWaterApplication = new WaterApplication();
    }


}
