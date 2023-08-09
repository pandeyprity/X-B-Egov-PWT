<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

/**
 * | Created By-30-06-2023 
 * | Created for- Yearly Demand Generation of Properties
 * | Author - Anshu Kumar
 */
class YearlyDemandGeneration
{
    use SAF;
    public $_propertyDetails;
    /**
     * | Generate Holding Demand(1)
     */
    public function generateHoldingDemand($req)
    {
        $holdingDemand = array();
        $responseDemand = array();
        $propId = $req->propId;
        $mPropProperty = new PropProperty();
        $safCalculation = new SafCalculation;
        $details = $mPropProperty->getPropFullDtls($propId);
        $this->_propertyDetails = $details;
        $calReqs = $this->generateSafRequest($details);                                                   // Generate Calculation Parameters
        $calParams = $this->generateCalculationParams($propId, $calReqs);                                 // (1.1)
        $calParams = array_merge($calParams, ['isProperty' => true]);
        $calParams = new Request($calParams);
        $taxes = $safCalculation->calculateTax($calParams);
        if ($taxes->original['status'] == false)
            throw new Exception($taxes->original['message']);
        $holdingDemand['amount'] = $taxes->original['data']['demand'];
        $holdingDemand['details'] = $this->generateSafDemand($taxes->original['data']['details']);
        $holdingDemand['holdingNo'] = $details['holding_no'];
        $responseDemand['amount'] = $holdingDemand['amount'];
        $responseDemand['details'] = collect($taxes->original['data']['details'])->groupBy('ruleSet');
        return $responseDemand;
    }

    /**
     * | Read the Calculation From Date (1.1)
     */
    public function generateCalculationParams($propertyId, $propDetails)
    {
        $mPropDemand = new PropDemand();
        $mSafDemand = new PropSafsDemand();
        $safId = $this->_propertyDetails->saf_id;
        $todayDate = Carbon::now();
        $propDemand = $mPropDemand->readLastDemandDateByPropId($propertyId);
        if (!$propDemand) {
            $propDemand = $mSafDemand->readLastDemandDateBySafId($safId);
            if (!$propDemand)
                throw new Exception("Last Demand is Not Available for this Property");
        }
        $lastPayDate = $propDemand->due_date;
        if (Carbon::parse($lastPayDate) > $todayDate)
            throw new Exception("Property Last Demand is generated till $lastPayDate Failed to Generate Demand");
        $payFrom = Carbon::parse($lastPayDate)->addDay(1);
        $payFrom = $payFrom->format('Y-m-d');
        if ($propDetails['propertyType'] != 4) {
            if (!isset($propDetails['floor']))
                throw new Exception("Floors not available for this property for Calculation");
            $realFloor = collect($propDetails['floor'])->map(function ($floor) use ($payFrom) {
                $floor['dateFrom'] = $payFrom;
                return $floor;
            });
            $propDetails['floor'] = $realFloor->toArray();
        }

        return $propDetails;
    }
}
