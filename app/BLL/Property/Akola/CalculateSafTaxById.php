<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\PropActiveSafsFloor;
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

    public function __construct($safDtls)
    {
        $this->_mPropActiveSafFloors = new PropActiveSafsFloor();
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
            [
                "propertyType" => $this->_safDtls->property_type,
                "areaOfPlot" => $this->_safDtls->area_of_plot,
                "category" => $this->_safDtls->category,
                "dateOfPurchase" => $this->_safDtls->date_of_purchase,
                "floor" => [],
                "owner" => []
                // "floor": [
                //     {
                //         "floorNo": "2",
                //         "constructionType": "1",
                //         "usageType": "1",
                //         "buildupArea": "100",
                //         "dateFrom": "2015-05-01"
                //     },
                //     {
                //         "floorNo": "2",
                //         "constructionType": "1",
                //         "usageType": "1",
                //         "buildupArea": "100",
                //         "dateFrom": "2015-05-01"
                //     }
                // ],
                // "owner": [
                //     {
                //         "ownerName": "aaraman",
                //         "gender": "Male",
                //         "dob": "1994-04-29",
                //         "guardianName": "ankit kumar",
                //         "relation": "S/O",
                //         "mobileNo": "1234567890",
                //         "aadhar": "123456789011",
                //         "pan": "1212121212",
                //         "email": "admin@gmail.com",
                //         "isArmedForce": "0",
                //         "isSpeciallyAbled": "0"
                //     }
                // ]
            ]
        ];

        if ($this->_safDtls->property_type != 4) {
            $propFloors = $this->_mPropActiveSafFloors->getSafFloorsBySafId($this->_safDtls->id);
        }

        $this->_REQUEST = new Request($calculationReq);
    }
}
