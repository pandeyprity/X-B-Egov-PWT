<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropAdvance;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropTax;
use App\Models\Property\PropTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalculatePropById extends CalculateSafById
{
    private $_mPropProperty;
    private $_mPropFloor;
    private $_mPropOwner;
    private $_mPropDemand;
    private $_mPropTax;
    private $_mPropTransaction;
    private $_mPropAdvance;
    private $_propertyId;
    private $_propDtls;
    private $_propOwnerDtls;
    private $_propFloorDtls;
    private $_propCalculationReqs;
    private $_safCalculation;
    private $_propDemandDetails;
    private $_newTax;
    private $_REQUEST;
    private $_propNewDemand;
    private $_propAdvDemand;

    public function __construct()
    {
        $this->_mPropProperty              = new PropProperty();
        $this->_mPropFloor                 = new PropFloor();
        $this->_mPropOwner                 = new PropOwner();
        $this->_mPropDemand                = new PropDemand();
        $this->_mPropTax                   = new PropTax();
        $this->_mPropTransaction           = new PropTransaction();
        $this->_mPropAdvance               = new PropAdvance();
        $this->_safCalculation             = new SafCalculation;
    }

    public function calculatePropTax($req)
    {
        $this->_propertyId = $req->property_id;
        $this->_REQUEST   = $req;
        $this->readMasters();
        $this->generatePropFloorCalcReq();
        $this->readPropGeneratedDemand();
        $this->generatePropCalculationReq();
        return $this->_newTax = $this->_safCalculation->calculateTax($this->_propCalculationReqs);
        $this->adjustPropDemand();
        return $this->tblUpdateDemandAdjust();
    }

    public function readMasters()
    {
        $this->_propDtls = PropProperty::find($this->_propertyId);
        $this->_propOwnerDtls = $this->_mPropOwner->firstOwner($this->_propertyId);
    }

    public function generatePropFloorCalcReq()
    {
        $propFloors = array();

        if ($this->_propDtls['prop_type_mstr_id'] != 4) {
            $floors = collect($this->_mPropFloor->getFloorsByPropId($this->_propertyId));
            foreach ($floors as $floor) {
                $floorReq = [
                    "floorNo" => $floor->floor_mstr_id,
                    "useType" => $floor->usage_type_mstr_id,
                    "constructionType" => $floor->const_type_mstr_id,
                    "occupancyType" => $floor->occupancy_type_mstr_id,
                    "buildupArea" => $floor->builtup_area,
                    "dateFrom" => $floor->date_from,
                    "dateUpto" => $floor->date_upto,
                    "carpetArea" => $floor->carpet_area,
                    "propFloorDetailId" => $floor->prop_floor_details_id
                ];
                array_push($propFloors, $floorReq);
            }
            $this->_propFloorDtls = $propFloors;
        }
    }

    public function readPropGeneratedDemand()
    {
        $this->_propDemandDetails = $this->_mPropDemand->getFullDemandsByPropId($this->_propertyId);
        $taxDetails = $this->_mPropTax->getPropTaxesByPropId($this->_propertyId);
    }

    public function generatePropCalculationReq()
    {
        $propDetails = $this->_propDtls;
        $reqCalculation = [
            "ulbId" => $propDetails['ulb_id'],
            "ward" => $propDetails['ward_mstr_id'],
            "propertyType" => $propDetails['prop_type_mstr_id'],
            "landOccupationDate" => $propDetails['land_occupation_date'],
            "ownershipType" => $propDetails['ownership_type_mstr_id'],
            "roadType" => $propDetails['ulb_id'],
            "areaOfPlot" => $propDetails['area_of_plot'],
            "isMobileTower" => $propDetails['is_mobile_tower'],
            "mobileTower" => [
                "area" => $propDetails['tower_area'],
                "dateFrom" => $propDetails['tower_installation_date']
            ],
            "petrolPump" => [
                "area" => $propDetails['under_ground_area'],
                "dateFrom" => $propDetails['petrol_pump_completion_date']
            ],
            "isHoardingBoard" => $propDetails['is_hoarding_board'],
            "hoardingBoard" => [
                "area" => $propDetails['hoarding_area'],
                "dateFrom" => $propDetails['hoarding_installation_date']
            ],
            "isPetrolPump" => $propDetails['is_petrol_pump'],
            "isWaterHarvesting" => $propDetails['is_water_harvesting'],
            "rwhDateFrom" => $propDetails['rwh_date_from'],
            "floor" => $this->_propFloorDtls,
            "isGBSaf" => $propDetails['is_gb_saf'],
            "zone" => $propDetails['zone_mstr_id'],
            "apartmentId" => $propDetails['apartment_details_id'],
            "isTrust" => $propDetails['is_trust'],
            "trustType" => $propDetails['trust_type'],
            "isTrustVerified" => $propDetails['is_trust_verified'],
        ];

        $this->_propCalculationReqs = new Request($reqCalculation);
    }

    /**
     * | Difference Between new demand and property demand
     */
    public function adjustPropDemand()
    {
        $newDemand  = collect();
        $collectAdvanceAmt = collect();

        $propTaxes = $this->_newTax->original['data']['details'];
        $this->_calculatedDemand['details'] = $propTaxes;
        $this->generateDemand();
        $propTaxes = $this->_demandDetails->toArray();

        foreach ($propTaxes as $propTax) {
            $propQtrDemand = $this->_propDemandDetails->where('due_date', $propTax['due_date']);
            $propQtrDemand = collect($propQtrDemand->toArray())->first();
            // $propQtrDemand = (object) $propQtrDemand;

            if (!empty($propQtrDemand)) {
                if ($propTax['amount'] > $propQtrDemand['amount']) {
                    if ($propQtrDemand['paid_status'] == 0) {
                        $propQtrDemand['status'] = 0;
                        $propQtrDemand->save();
                    }
                    $adjustAmt = round($propQtrDemand['balance'] - $propQtrDemand['adjust_amt'], 2);
                    $balance   = round($propTax['balance'] - $adjustAmt, 2);

                    if ($balance > 0) {
                        $taxes = $this->generatePropDemandTax($propTax, $adjustAmt, $balance);
                        $newDemand->push($taxes);
                    }
                }

                if ($propTax['amount'] < $propQtrDemand['amount']) {
                    $advanceAmt = round(($propQtrDemand['amount'] - $propTax['amount']), 2);
                    $collectAdvanceAmt->push($advanceAmt);
                }
            }
        }
        $this->_propNewDemand = $newDemand;
        $this->_propAdvDemand = $collectAdvanceAmt;
    }

    public function tblUpdateDemandAdjust()
    {
        if (!empty($this->_propNewDemand))
            $this->storeDemand();

        if (!empty($this->_propAdvDemand))
            $this->storeAdvance();
    }

    public function storeDemand()
    {
        $this->_mPropDemand->store(($this->_propNewDemand)->toArray());
    }

    public function storeAdvance()
    {
        $tranDtls = $this->_mPropTransaction->getLastTranByKeyId('property_id', $this->_propertyId);
        $tranId   = $tranDtls->id;
        $advanceAmt = round(collect($this->_propAdvDemand)->sum(), 2);

        if ($advanceAmt > 0) {
            $advReq = [
                'prop_id' => $this->_propertyId,
                'tran_id' => $tranId,
                'amount'  => $advanceAmt,
                'remarks' => $this->_REQUEST->remarks,
                'user_id' => $this->_REQUEST->userId,
                'ulb_id'  => $this->_propDtls['ulb_id'],
            ];
            $this->_mPropAdvance->store($advReq);
        }
    }

    /**
     * | Genrate Prop Demand Tax
     */
    public function generatePropDemandTax($tax, $adjustAmt, $balance)
    {
        return [
            'property_id'    => $this->_propertyId,
            'qtr'            => $tax['qtr'],
            'holding_tax'    => $tax['holding_tax'],
            'water_tax'      => $tax['water_tax'],
            'education_cess' => $tax['education_cess'],
            'health_cess'    => $tax['health_cess'],
            'latrine_tax'    => $tax['latrine_tax'],
            'additional_tax' => $tax['additional_tax'],
            'ward_mstr_id'   => $this->_propDtls->ward_mstr_id,
            'fyear'          => $tax['fyear'],
            'due_date'       => $tax['due_date'],
            'amount'         => $tax['amount'],
            'arv'            => $tax['arv'],
            'adjust_amt'     => $adjustAmt,
            'balance'        => $balance,
            'ulb_id'         => $this->_propDtls['ulb_id'],
            'adjust_type'    => $this->_REQUEST->adjustmentType,
            'user_id'        => $this->_REQUEST->userId,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now()
        ];
    }
}
