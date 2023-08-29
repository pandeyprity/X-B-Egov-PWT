<?php

namespace App\BLL\Water;

use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterParamDemandCharge;
use App\Models\Water\WaterParamFreeUnit;
use App\Models\Water\WaterSecondConsumer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * | Created On :- 29-08-2023 
 * | Author     :- Sam kerketta
 * | Status     :- Open
 */
class WaterMonthelyCall
{
    private $_consumerId;
    private $_mWaterConsumer;
    private $_mWaterParamDemandCharge;
    private $_mWaterParamFreeUnit;
    private $_consumerCharges;
    private $_consumerFreeUnits;
    private $_consuemrDetails;
    # Class cons
    public function __construct(int $consumerId)
    {
        $this->_consumerId              = $consumerId;
        $this->_mWaterConsumer          = new WaterSecondConsumer();
        $this->_mWaterParamDemandCharge = new WaterParamDemandCharge();
        $this->_mWaterParamFreeUnit     = new WaterParamFreeUnit();
    }

    /**
     * | params for calculation 
     */
    public function readParamsForCall()
    {
        # Check the existence of consumer 
        $this->_consuemrDetails = $this->_mWaterConsumer->getConsumerDetailsById($this->_consumerId)
            ->where('status', 4)                                                                            // Static
            ->first();
        # Get the charges for call 
        $chargesParams = new Request([
            "propertyType"      => $this->_consuemrDetails->property_type_id,
            "areaCatagory"      => $this->_consuemrDetails->area_category_id,
            "connectionSize"    => $this->_consuemrDetails->tab_size,
            "meterState"        => $this->_consuemrDetails->meter_state
        ]);
        $this->_consumerCharges = $this->_mWaterParamDemandCharge->getConsumerCharges($chargesParams);
        $this->_consumerFreeUnits = $this->_mWaterParamFreeUnit->getFreeUnits($chargesParams);
        
        // $this->monthelyDemandCall()
    }

    /**
     * | Consumer callculation 
     */
    public function monthelyDemandCall()
    {
        switch ($this->_consumerId) {
            case (!$this->_consuemrDetails):
                throw new Exception("consumer Details not found!");
                break;

            case (!$this->_consumerCharges):
                throw new Exception("consumer charges not found!");
                break;

            case (!$this->_consumerFreeUnits):
                throw new Exception("consumer free units not found!");
                break;
        }

        
    }
}
