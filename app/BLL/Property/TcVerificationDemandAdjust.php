<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Property\ApplySafController;
use App\Models\Property\PropAdvance;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTransaction;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;


/**
 * | Created On-27-03-2023 
 * | Created by- Anshu Kumar
 * | Handle TC Verifications Datas
 * | Status-Closed
 */
class TcVerificationDemandAdjust
{
    use SAF;
    public $_mPropSafsDemands;
    public $_safCalculation;
    public $_reqs;
    public $_activeSafDtls;
    public $_quaterlyTax;
    public $_mPropDemands;
    public $_propertyId;
    public $_propNewDemand;
    public $_adjustmentType;
    public $_propAdvDemand;
    public $_mPropAdvance;
    private $_mPropTransactions;
    protected $_tcId;
    protected $_calculateSafByid;
    private $_postSafPropTaxes;
    private $_adjustmentAssessmentTypes;

    public function __construct()
    {
        $this->_mPropSafsDemands = new PropSafsDemand();
        $this->_safCalculation = new SafCalculation;
        $this->_mPropDemands = new PropDemand();
        $this->_adjustmentType = Config::get('PropertyConstaint.ADJUSTMENT_TYPES.ULB_ADJUSTMENT');
        $this->_mPropAdvance = new PropAdvance();
        $this->_mPropTransactions = new PropTransaction();
        $this->_calculateSafByid = new CalculateSafById;
        $this->_postSafPropTaxes = new PostSafPropTaxes;
        $this->_adjustmentAssessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
    }

    /** 
     * | Generate Tc Verification Demand (1)
     */
    public function generateTcVerifiedDemand($req)
    {
        $this->_reqs = $req;
        $this->_activeSafDtls = $req['activeSafDtls'];
        $this->_tcId = collect($req['fieldVerificationDtls'])->first()->user_id;
        $this->_quaterlyTax = $this->calculateQuaterlyTax();           // (1.1)
        $this->adjustVerifiedDemand();
        $this->tblUpdateDemandAdjust();
        $this->_postSafPropTaxes->postPropTaxes($this->_reqs['propId'], $this->_quaterlyTax->toArray());                // Add Property Taxes
    }

    /**
     * | Calculate Quaterly Tax (1.1)
     */
    public function calculateQuaterlyTax()
    {
        $activeSafDtls = $this->_activeSafDtls;
        $floors = array();
        $fieldVerifiedSafs = collect($this->_reqs['fieldVerificationDtls']);
        if ($fieldVerifiedSafs->first()->prop_type_id != 4) {
            foreach ($fieldVerifiedSafs as $item) {
                $floorDtl = [
                    "floorNo" => $item->floor_mstr_id,
                    "useType" => $item->usage_type_id,
                    "constructionType" => $item->construction_type_id,
                    "occupancyType" => $item->occupancy_type_id,
                    "buildupArea" => $item->builtup_area,
                    "dateFrom" => $item->date_from,
                    "dateUpto" => $item->date_to
                ];
                array_push($floors, $floorDtl);
            }
        }
        $fieldVerifiedSaf = $fieldVerifiedSafs->first();
        $calculationReq = [
            "assessmentType" => $this->_reqs['assessmentType'],
            "ulbId" => $this->_reqs['ulbId'],
            "ward" => $fieldVerifiedSaf->ward_id,
            "propertyType" => $fieldVerifiedSaf->prop_type_id,
            "landOccupationDate" => $activeSafDtls->land_occupation_date,
            "ownershipType" => $activeSafDtls->ownership_type,
            "apartmentId" => $activeSafDtls->apartment_details_id,
            "roadType" => $fieldVerifiedSaf->road_width,
            "areaOfPlot" => $fieldVerifiedSaf->area_of_plot,
            "isMobileTower" => $fieldVerifiedSaf->has_mobile_tower,
            "mobileTower" => [
                "area" => $fieldVerifiedSaf->tower_area,
                "dateFrom" => $fieldVerifiedSaf->tower_installation_date
            ],
            "isHoardingBoard" => $fieldVerifiedSaf->has_hoarding,
            "hoardingBoard" => [
                "area" => $fieldVerifiedSaf->hoarding_area,
                "dateFrom" => $fieldVerifiedSaf->hoarding_installation_date
            ],
            "isPetrolPump" => $fieldVerifiedSaf->is_petrol_pump,
            "petrolPump" => [
                "area" => $fieldVerifiedSaf->underground_area,
                "dateFrom" => $fieldVerifiedSaf->petrol_pump_completion_date
            ],
            "isWaterHarvesting" => $fieldVerifiedSaf->has_water_harvesting,
            "zone" => $activeSafDtls->zone_mstr_id,
            "floor" => $floors,
            "isTrust" => $activeSafDtls->is_trust,
            "trustType" => $activeSafDtls->trust_type,
            "isTrustVerified" => $activeSafDtls->is_trust_verified,
            "rwhDateFrom" => $fieldVerifiedSaf->rwh_date_from ?? null
        ];

        $calculationReq = new Request($calculationReq);
        $calculation = $this->_safCalculation->calculateTax($calculationReq);
        if ($calculation->original['status'] == false)
            throw new Exception($calculation->original['message']);
        $calculation = $calculation->original['data'];
        $demandDetails = $calculation['details'];
        $quaterlyTax = $this->generateSafDemand($demandDetails);

        if (in_array($this->_reqs['assessmentType'], $this->_adjustmentAssessmentTypes)) {
            $this->_calculateSafByid->_demandDetails = $quaterlyTax;
            $this->_calculateSafByid->_holdingNo = $activeSafDtls->holding_no;
            $this->_calculateSafByid->_safDetails['previous_holding_id'] = $activeSafDtls->previous_holding_id;
            $quaterlyTax = $this->_calculateSafByid->adjustAmount();
        }
        return $quaterlyTax;
    }

    /**
     * | Adjust Demand (1.2)
     */
    public function adjustVerifiedDemand()
    {
        $newDemand = collect();
        $collectAdvanceAmt = collect();
        $mPropSafsDemands = $this->_mPropSafsDemands;
        $mPropDemands = $this->_mPropDemands;
        $quaterlyTax  = $this->_quaterlyTax;
        $propSafsDemands = $mPropSafsDemands->getPaidDemandBySafId($this->_activeSafDtls['id']);
        $propDemands = $mPropDemands->getFullDemandsByPropId($this->_reqs['propId']);
        foreach ($quaterlyTax as $tax) {
            $safQtrDemand = $propSafsDemands->where('due_date', $tax['dueDate'])->first();
            $propQtrDemand = $propDemands->where('due_date', $tax['dueDate'])->first();
            // For Saf
            if (collect($safQtrDemand)->isNotEmpty()) {
                if ($tax['totalTax'] > $safQtrDemand->amount) {                                         // Case IF The Demand is Increasing
                    $adjustAmt = roundFigure($safQtrDemand->amount - $safQtrDemand->adjust_amount);
                    $balance = roundFigure($tax['balance'] - $adjustAmt);
                    $taxes = $this->generatePropDemandTax($tax, $adjustAmt, $balance);                  // Saf Demand details generation to be saved on table (1.2.3)
                    $newDemand->push($taxes);
                }
                if ($tax['totalTax'] < $safQtrDemand->amount) {                                       // Case if the Demand is Decreasing
                    $advanceAmt = roundFigure($safQtrDemand->amount - $tax['totalTax']);
                    $collectAdvanceAmt->push($advanceAmt);
                }
            }
            // For Property Demand
            if (collect($propQtrDemand)->isNotEmpty()) {
                if ($tax['totalTax'] > $propQtrDemand->amount) {
                    if ($propQtrDemand->paid_status == 0) {                                         // Deactivate the unpaid Demand for Creating New
                        $propQtrDemand->status = 0;
                        $propQtrDemand->save();
                    }
                    $adjustAmt = roundFigure($propQtrDemand->balance - $propQtrDemand->adjust_amount);
                    $balance = roundFigure($tax['balance'] - $adjustAmt);
                    if ($balance > 0) {
                        $taxes = $this->generatePropDemandTax($tax, $adjustAmt, $balance);              // Saf Demand details generation to be saved on table (1.2.3)
                        $newDemand->push($taxes);
                    }
                }
                if ($tax['totalTax'] < $propQtrDemand->amount) {                                       // Case if the Demand is Decreasing
                    $advanceAmt = roundFigure($propQtrDemand->amount - $tax['totalTax']);
                    if ($advanceAmt > 0)
                        $collectAdvanceAmt->push($advanceAmt);
                }
            }
        }

        $this->_propNewDemand = $newDemand;
        $this->_propAdvDemand = $collectAdvanceAmt;
    }

    /**
     * | tblUpdDemandAdvance
     */
    public function tblUpdateDemandAdjust()
    {
        if ($this->_propNewDemand->isNotEmpty())
            $this->storeDemand();               // (Function Store Demand) 1.2.1

        if ($this->_propAdvDemand->isNotEmpty())
            $this->storeAdvance();              // (Function Advance Demand Store)  1.2.2
    }


    /**
     * | Store New Demand (1.2.1)
     */
    public function storeDemand()
    {
        $propNewDemands = $this->_propNewDemand;        // Created Demand
        $mPropDemands = $this->_mPropDemands;            // Model Prop Demands
        $mPropDemands->store($propNewDemands->toArray());
    }

    /**
     * | Store Advance Demand (1.2.2)
     */
    public function storeAdvance()
    {
        $mPropAdvance = $this->_mPropAdvance;
        $mPropTransactions = $this->_mPropTransactions;
        $tranDtls = $mPropTransactions->getLastTranByKeyId('saf_id', $this->_reqs['safId']);
        $tranId = $tranDtls->id;

        $advanceAmtCollection = $this->_propAdvDemand;
        $advanceAmt = collect($advanceAmtCollection)->sum();
        $advanceAmt = roundFigure($advanceAmt);
        if ($advanceAmt > 0) {
            $advReq = [
                'prop_id' => $this->_reqs['propId'],
                'tran_id' => $tranId,
                'amount' => $advanceAmt,
                'remarks' => "Field Verification Adjustment",
                'user_id' => $this->_tcId,
                'ulb_id' => $this->_reqs['ulbId'],
            ];
            $mPropAdvance->store($advReq);
        }
    }

    /**
     * | Generate Property Demands Tax (1.2.3)
     */
    public function generatePropDemandTax($tax, $adjustAmt, $balance)
    {
        return [
            'property_id' => $this->_reqs['propId'],
            'qtr' => $tax['qtr'],
            'holding_tax' => $tax['holdingTax'],
            'water_tax' => $tax['waterTax'],
            'education_cess' => $tax['educationTax'],
            'health_cess' => $tax['healthCess'],
            'latrine_tax' => $tax['latrineTax'],
            'additional_tax' => $tax['additionTax'],
            'ward_mstr_id' => $this->_activeSafDtls->ward_mstr_id,
            'fyear' => $tax['quarterYear'],
            'due_date' => $tax['dueDate'],
            'amount' => $tax['totalTax'],
            'arv' => $tax['arv'],
            'adjust_amt' => $adjustAmt,
            'balance' => $balance,
            'adjust_type' => $this->_adjustmentType,
            'ulb_id' => $this->_reqs['ulbId'],
            'user_id' => $this->_tcId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
