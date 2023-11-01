<?php

namespace App\Observers;

use App\Models\Masters\IdGenerationParam;
use App\Models\Property\PropPropertyUpdateRequest;
use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\Config;

class PropUpdateRequestObserver
{
    private $_mUlbWardMasters;
    private $_mIdGenerationParams;
    private $_wardDtls;
    public function __construct()
    {
        $this->_mUlbWardMasters = new UlbWardMaster();
        $this->_mIdGenerationParams = new IdGenerationParam();
    }

    public function created(PropPropertyUpdateRequest $updateRequest)
    {
        if (!$updateRequest->request_no) {
            $request_no = $this->generatePropUpdateNo($updateRequest->is_full_update);
            $updateRequest->request_no = $request_no;
            $updateRequest->update();
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╗
    // ║                                                ✅ Prop Update No Generation ✅                                                          ║ 
    // ╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╝ 

    /**
     * | Generate Prop Update No
     * | created By Sandeep
     */
    public function generatePropUpdateNo($updateType, $fyear=null): string
    {
        $fyear = $fyear?$fyear:getFY();
        $paramId = ($updateType) ? Config::get('PropertyConstaint.PROP_FULL_UPDATE_ID') : Config::get('PropertyConstaint.PROP_BASIC_UPDATE_ID');
        $counter = $this->_mIdGenerationParams->where('id', $paramId)->first();
        if (collect($counter)->isEmpty())
            throw new Exception("Counter Not Available");
      
        $updateNo = str_pad($counter->int_val, 4, "0", STR_PAD_LEFT);
        $counter->int_val += 1;    
        if ($updateType) {                                               
            $updateNo = str_pad($counter->int_val, 4, "0", STR_PAD_LEFT);
        }

        $counter->save();
        $memoNo = $counter->string_val . '/' . $updateNo. '/' . $fyear;
        return $memoNo;
    }
}
