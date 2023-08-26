<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use Exception;
use Illuminate\Http\Request;

/**
 * | Created by-Anshu Kumar
 * | Created For - Calculate Saf Taxes By Saf ID
 * | Status-Closed
 */
class CalculateSafTaxById extends TaxCalculator
{
    private $_safDtls;
    private $_REQUEST;
    private $_mPropActiveSafFloors;
    private $_mPropActiveSafOwners;

    public function __construct($safDtls)
    {
        $this->_mPropActiveSafFloors = new PropActiveSafsFloor();
        $this->_mPropActiveSafOwners = new PropActiveSafsOwner();
        $this->_safDtls = $safDtls;
        $this->generateRequests();
        parent::__construct($this->_REQUEST);
        $this->calculateTax();
    }

    /**
     * | Generate Request for Calculation
     */
    public function generateRequests(): void
    {
        $calculationReq = [
            "propertyType" => $this->_safDtls->prop_type_mstr_id,
            "areaOfPlot" => $this->_safDtls->area_of_plot,
            "category" => $this->_safDtls->category_id,
            "dateOfPurchase" => $this->_safDtls->date_of_purchase,
            "floor" => [],
            "owner" => []
        ];

        // Get Floors
        if ($this->_safDtls->prop_type_mstr_id != 4) {
            $propFloors = $this->_mPropActiveSafFloors->getSafFloorsBySafId($this->_safDtls->id);
            if (collect($propFloors)->isEmpty())
                throw new Exception("Floors not available for this property");

            foreach ($propFloors as $floor) {
                $floorReq =  [
                    "floorNo" => $floor->floor_mstr_id,
                    "constructionType" =>  $floor->const_type_mstr_id,
                    "usageType" => $floor->usage_type_mstr_id,
                    "buildupArea" =>  $floor->builtup_area,
                    "dateFrom" =>  $floor->date_from
                ];
                array_push($calculationReq['floor'], $floorReq);
            }
        }

        // Get Owners
        $propFirstOwners = $this->_mPropActiveSafOwners->getOwnerDtlsBySafId1($this->_safDtls->id);
        $ownerReq = [
            "isArmedForce" => $propFirstOwners->is_armed_force
        ];
        array_push($calculationReq['owner'], $ownerReq);

        $this->_REQUEST = new Request($calculationReq);
    }
}
