<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafsFloor;
use App\Models\Property\PropSafsOwner;
use App\Models\Property\PropSafTax;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * | Calculate Saf By Saf Id Service
 * | Created By-Anshu Kumar
 * | Created On-29-03-2023 
 * | Status-Closed
 */

class CalculateSafById
{
    use SAF;
    private $_mPropActiveSaf;
    private $_mPropSaf;
    private $_mPropActiveSafFloors;
    private $_mPropActiveSafOwner;
    private $_penaltyRebateCalc;
    public $_safId;
    public $_safDetails;
    private $_safFloorDetails;
    private $_safCalculation;
    public $_safCalculationReq;
    public $_calculatedDemand;
    public $_generatedDemand = array();
    public $_demandDetails;
    private $_todayDate;
    public $_currentQuarter;
    public $_holdingNo;
    public $_firstOwner;
    public $_mPropActiveSafOwners;
    private $_adjustmentAssessmentTypes;
    private $_mPropSafDemand;
    private $_mPropSafTax;
    private $_totalDemand;
    private $_REQUEST;

    public function __construct()
    {
        $this->_mPropActiveSafOwner = new PropActiveSafsOwner();
        $this->_mPropActiveSafFloors = new PropActiveSafsFloor();
        $this->_mPropActiveSaf = new PropActiveSaf();
        $this->_mPropSaf = new PropSaf();
        $this->_safCalculation = new SafCalculation;
        $this->_penaltyRebateCalc = new PenaltyRebateCalculation;
        $this->_todayDate = Carbon::now();
        $this->_mPropActiveSafOwners = new PropActiveSafsOwner();
        $this->_adjustmentAssessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
        $this->_mPropSafDemand = new PropSafsDemand();
        $this->_mPropSafTax = new PropSafTax();
    }

    /**
     * | Calculation Function (1)
     */

    public function calculateTax(Request $req)
    {
        $this->_safId = $req->id;
        $this->_holdingNo = $req->holdingNo;
        $this->_REQUEST = $req;

        $this->readMasters();                          // Function (1.1)

        if ($this->_safDetails->is_gb_saf == true)      // For GB Saf
        {
            $this->_safDetails['prop_type_mstr_id'] = 2;
            $this->generateFloorCalcReq();
            $this->calculateGbSafDemand();
        }

        if ($this->_safDetails->is_gb_saf == false)      // For Normal Saf
        {
            if ($this->_safDetails->payment_status == 1)   // If Payment Already done
                $this->readGeneratedDemand();

            if ($this->_safDetails->payment_status != 1)   // If Payment Not done
            {
                $this->generateFloorCalcReq();
                $this->generateCalculationReq();                                        // (Function 1.3)
                // Saf Calculation
                $reqCalculation = $this->_safCalculationReq;
                $calculation = $this->_safCalculation->calculateTax($reqCalculation);
                // Throw Exception on Calculation Error
                if ($calculation->original['status'] == false)
                    throw new Exception($calculation->original['message']);

                $this->_calculatedDemand = $calculation->original['data'];
                $this->generateSafDemand();   // (1.2)

            }
        }

        return $this->_generatedDemand;
    }

    /**
     * | Read All Master Data (1.1)
     */

    public function readMasters()
    {
        $this->_safDetails = $this->_mPropActiveSaf::find($this->_safId);
        if (collect(($this->_safDetails))->isEmpty()) {
            $this->_safDetails = $this->_mPropSaf::find($this->_safId);
            $this->_mPropActiveSafFloors = new PropSafsFloor();
            $this->_mPropActiveSafOwner = new PropSafsOwner();
        }

        $this->_currentQuarter = calculateQtr($this->_todayDate->format('Y-m-d'));

        // Read Owner
        $this->readOwnerDetails();
    }

    /**
     * | ======================================= Functions Calculating unpaid Demands ==========================
     */

    /**
     * | Read Owner Details
     */
    public function readOwnerDetails()
    {
        $mPropSafsOwners = $this->_mPropActiveSafOwner;
        $this->_firstOwner = $mPropSafsOwners->getOwnerDtlsBySafId1($this->_safId);
    }

    /**
     * | Function Generate Floor Calculation Reqs
     */
    public function generateFloorCalcReq()
    {
        $safFloors = array();
        // Building Case
        if ($this->_safDetails['prop_type_mstr_id'] != 4) {
            $floors = $this->_mPropActiveSafFloors->getSafFloorsBySafId($this->_safId);
            foreach ($floors as $floor) {
                $floorReq = [
                    "floorNo" => $floor['floor_mstr_id'],
                    "useType" => $floor['usage_type_mstr_id'],
                    "constructionType" => $floor['const_type_mstr_id'],
                    "occupancyType" => $floor['occupancy_type_mstr_id'],
                    "buildupArea" => $floor['builtup_area'],
                    "dateFrom" => $floor['date_from'],
                    "dateUpto" => $floor['date_upto'],
                    "carpetArea" => $floor['carpet_area'],
                    "propFloorDetailId" => $floor['prop_floor_details_id']
                ];
                array_push($safFloors, $floorReq);
            }
            $this->_safFloorDetails = $safFloors;
        }
    }

    /**
     * | Function (1.3)
     */
    public function generateCalculationReq()
    {
        $safDetails = $this->_safDetails;
        $calculationReq = [
            "ulbId" => $safDetails['ulb_id'],
            "ward" => $safDetails['ward_mstr_id'],
            "propertyType" => $safDetails['prop_type_mstr_id'],
            "landOccupationDate" => $safDetails['land_occupation_date'],
            "ownershipType" => $safDetails['ownership_type_mstr_id'],
            "roadType" => $safDetails['road_width'],
            "areaOfPlot" => $safDetails['area_of_plot'],
            "isMobileTower" => $safDetails['is_mobile_tower'],
            "mobileTower" => [
                "area" => $safDetails['tower_area'],
                "dateFrom" => $safDetails['tower_installation_date']
            ],
            "isHoardingBoard" => $safDetails['is_hoarding_board'],
            "hoardingBoard" => [
                "area" => $safDetails['hoarding_area'],
                "dateFrom" => $safDetails['hoarding_installation_date']
            ],
            "isPetrolPump" => $safDetails['is_petrol_pump'],
            "petrolPump" => [
                "area" => $safDetails['under_ground_area'],
                "dateFrom" => $safDetails['petrol_pump_completion_date']
            ],
            "isWaterHarvesting" => $safDetails['is_water_harvesting'],
            "zone" => $safDetails['zone_mstr_id'],
            "floor" => $this->_safFloorDetails,
            "isGBSaf" => $safDetails['is_gb_saf'],
            "apartmentId" => $safDetails['apartment_details_id'],
            "isTrust" => $safDetails['is_trust'],
            "trustType" => $safDetails['trust_type'],
            "isTrustVerified" => $safDetails['is_trust_verified'],
            "rwhDateFrom" => $safDetails['rwh_date_from'],
        ];
        $this->_safCalculationReq = new Request($calculationReq);
    }

    /**
     * | Generated SAF Demand to push the value in propSafsDemand Table // (1.2)
     * | Used in Apply Saf , Review Calculation
     */
    public function generateSafDemand()
    {
        $this->generateDemand();

        if (in_array($this->_safDetails['assessment_type'], $this->_adjustmentAssessmentTypes))     // In Case of Reassessment Adjust the Amount
            $this->adjustAmount();         // (1.2.1)

        $this->calculateOnePercPenalty();   // (1.2.2)

        $demandDetails = $this->_demandDetails;
        $dueFrom = "Quarter " . $demandDetails->first()['qtr'] . '/' . 'Year ' . $demandDetails->first()['fyear'];
        $dueTo = "Quarter " . $demandDetails->last()['qtr'] . '/' . 'Year ' . $demandDetails->last()['fyear'];

        $totalTax = roundFigure($demandDetails->sum('balance'));
        $totalOnePercPenalty = roundFigure($demandDetails->sum('onePercPenaltyTax'));
        $this->_totalDemand = $totalTax + $totalOnePercPenalty + $this->_calculatedDemand['demand']['lateAssessmentPenalty'];

        $totalPenaltiesAmt = $totalOnePercPenalty + $this->_calculatedDemand['demand']['lateAssessmentPenalty'];

        $this->_generatedDemand['demand'] = [
            'dueFromFyear' => $demandDetails->first()['fyear'],
            'dueToFyear' => $demandDetails->last()['fyear'],
            'dueFromQtr' => $demandDetails->first()['qtr'],
            'dueToQtr' => $demandDetails->last()['qtr'],
            'totalTax' => $totalTax,
            'totalOnePercPenalty' => $totalOnePercPenalty,
            'totalQuarters' => $demandDetails->count(),
            'duesFrom' => $dueFrom,
            'duesTo' => $dueTo,
            'lateAssessmentStatus' => $this->_calculatedDemand['demand']['lateAssessmentStatus'],
            'lateAssessmentPenalty' => $this->_calculatedDemand['demand']['lateAssessmentPenalty'],
            'totalDemand' => roundFigure($this->_totalDemand),
            'totalPenaltiesAmt' => $totalPenaltiesAmt
        ];

        $this->_generatedDemand['details'] = $this->_demandDetails->values();

        $this->readRebates();                                               // (1.2.3)

        $this->calculatePayableAmt();

        $this->generateTaxDtls();        // (1.2.3)
    }

    /**
     * | Calculation of Total Payable amount
     */
    public function calculatePayableAmt()
    {
        $this->_generatedDemand['demand']['totalRebatesAmt'] = $this->_generatedDemand['demand']['rebateAmt'] + $this->_generatedDemand['demand']['specialRebateAmt'];
        $payableAmount = $this->_totalDemand - ($this->_generatedDemand['demand']['rebateAmt'] + $this->_generatedDemand['demand']['specialRebateAmt']);   // Final Payable Amount Calculation
        $this->_generatedDemand['demand']['payableAmount'] = round($payableAmount);
        if ((int)$payableAmount < 1)
            $this->_generatedDemand['demand']['isPayable'] = false;
        else
            $this->_generatedDemand['demand']['isPayable'] = true;
    }


    /**
     * | Generate Demand
     */

    public function generateDemand()
    {
        $collection = $this->_calculatedDemand['details'];
        $filtered = collect($collection)->map(function ($value) {
            return collect($value)->only([
                'qtr', 'holdingTax', 'waterTax', 'educationTax',
                'healthTax', 'latrineTax', 'quarterYear', 'dueDate', 'totalTax', 'arv', 'rwhPenalty', 'onePercPenalty', 'onePercPenaltyTax', 'ruleSet'
            ]);
        });

        $groupBy = $filtered->groupBy(['quarterYear', 'qtr']);

        $taxes = $groupBy->map(function ($values) {
            return $values->map(function ($collection) {
                $amount = roundFigure($collection->sum('totalTax'));
                return collect([
                    'qtr' => $collection->first()['qtr'],
                    'holding_tax' => roundFigure($collection->sum('holdingTax')),
                    'water_tax' => roundFigure($collection->sum('waterTax')),
                    'education_cess' => roundFigure($collection->sum('educationTax')),
                    'health_cess' => roundFigure($collection->sum('healthTax')),
                    'latrine_tax' => roundFigure($collection->sum('latrineTax')),
                    'additional_tax' => roundFigure($collection->sum('rwhPenalty')),
                    'fyear' => $collection->first()['quarterYear'],
                    'due_date' => $collection->first()['dueDate'],
                    'amount' => $amount,
                    'arv' => roundFigure($collection->sum('arv')),
                    'adjust_amount' => 0,
                    'ruleSet' => $collection->first()['ruleSet'],
                    'balance' => $amount,
                    'rwhPenalty' => roundFigure($collection->sum('rwhPenalty'))
                ]);
            });
        });

        $demandDetails = $taxes->values()->collapse();

        $this->_demandDetails = $demandDetails;
    }

    /**
     * | Adjust Amount In Case of Reassessment (1.2.1)
     */
    public function adjustAmount()
    {
        $propDemandList = array();
        $mSafDemand = new PropSafsDemand();
        $propProperty = new PropProperty();
        $mPropDemands = new PropDemand();
        $generatedDemand = $this->_demandDetails;
        $previousHoldingId = $this->_safDetails->previous_holding_id ?? $this->_safDetails['previous_holding_id'];

        $propDtls = $propProperty->getPropById($previousHoldingId);
        $propertyId = $propDtls->id;
        $safId = $propDtls->saf_id;

        if (is_null($safId))
            throw new Exception("Previous Saf Id Not Available");

        $safDemandList = $mSafDemand->getFullDemandsBySafId($safId);
        if ($safDemandList->isEmpty())
            throw new Exception("Previous Saf Demand is Not Available");

        $propDemandList = $mPropDemands->getPaidDemandByPropId($propertyId);            // Get Full Demand which is paid by Property id
        $generatedDemand = $generatedDemand->sortBy('due_date');

        // Demand Adjustment
        foreach ($generatedDemand as $item) {
            $itemDueDate = $item['due_date'] ?? $item['dueDate'];
            $demand = $propDemandList->where('due_date', $itemDueDate)->first();            // Checking first in Property Table 
            if (collect($demand)->isEmpty())                                                // If Demand not available in property then check in saf Table
                $demand = $safDemandList->where('due_date', $itemDueDate)->first();

            if (collect($demand)->isEmpty())
                $item['adjust_amount'] = 0;
            else
                $item['adjust_amount'] = $demand->amount - $demand->balance;

            $itemAmt = $item['amount'] ?? $item['totalTax'];                 // In Case of TC key is Total Tax
            if ($item['adjust_amount'] > $itemAmt)                           // If the adjust amount is going high 
                $item['adjust_amount'] = $itemAmt;

            $item['balance'] = roundFigure($itemAmt - $item['adjust_amount']);
            if ($item['balance'] == 0)
                $item['onePercPenaltyTax'] = 0;
        }
        return $this->_demandDetails = $generatedDemand;
    }

    /**
     * | One Percent Penalty Calculation (1.2.2)
     */
    public function calculateOnePercPenalty()
    {
        $penaltyRebateCalculation = $this->_penaltyRebateCalc;
        $demandDetails = $this->_demandDetails;
        foreach ($demandDetails as $demandDetail) {
            $penaltyPerc = $penaltyRebateCalculation->calcOnePercPenalty($demandDetail['due_date']);
            $penaltyTax = roundFigure(($demandDetail['balance'] * $penaltyPerc) / 100);
            $demandDetail['onePercPenalty'] = $penaltyPerc;
            $demandDetail['onePercPenaltyTax'] = $penaltyTax;
        }

        $this->_demandDetails = $demandDetails;
    }

    /**
     * | Calculation for Read Rebates (1.2.3)
     */
    public function readRebates()
    {
        $penaltyRebateCalculation = $this->_penaltyRebateCalc;
        $currentQuarter = $this->_currentQuarter;
        $loggedInUserType = auth()->user()->user_type ?? 'Citizen';
        $currentFYear = getFY();
        $lastQuarterDemand = $this->_generatedDemand['details']->where('fyear', $currentFYear)->sum('balance');
        $ownerDetails = $this->_firstOwner;
        $totalDemand = $this->_generatedDemand['demand']['totalTax'];
        $totalDuesList = $this->_generatedDemand['demand'];
        $this->_generatedDemand['demand'] = $penaltyRebateCalculation->readRebates(
            $currentQuarter,
            $loggedInUserType,
            $lastQuarterDemand,
            $ownerDetails,
            $totalDemand,
            $totalDuesList
        );
    }

    /**
     * | Generation of Tax Details(1.2.3)
     */
    public function generateTaxDtls()
    {
        $taxDetails = collect();
        $demandDetails = $this->_generatedDemand['details'];
        $groupByDemands = collect($demandDetails)->groupBy('amount');
        $currentTax = $groupByDemands->last()->first()['amount'];          // Get Current Demand Arv Rate
        foreach ($groupByDemands as $key => $item) {
            $firstTax = collect($item)->first();
            if ($key == $currentTax)
                $firstTax['status'] = "Current";
            else
                $firstTax['status'] = "Old";

            $taxDetails->push($firstTax);
        }
        $this->_generatedDemand['taxDetails'] = $taxDetails;
    }


    /**
     * | ========================= Functions calculating Paid Demands ==================
     */
    public function readGeneratedDemand()
    {
        $readDemands = $this->_mPropSafDemand->getFullDemandsBySafId($this->_safId);
        $this->_demandDetails['details'] = $readDemands;
        $taxDetails = $this->_mPropSafTax->getSafTaxesBySafId($this->_safId);
        foreach ($taxDetails as $key => $item) {
            $lastElement = end($taxDetails);
            $item->status = "Old";
            if ($key == $lastElement)                               // Check last Element of an array
                $item->status = "Current";
        }
        $this->_demandDetails['taxDetails'] = $taxDetails;
        $this->_generatedDemand = $this->_demandDetails;
    }


    /**
     * | ========================= Functions Generating GB Saf demand ==================
     */
    public function calculateGbSafDemand()
    {
        $this->readGeneratedDemand();
        $allDemands = $this->_demandDetails['details'];
        $unpaidDemand = $this->_demandDetails['details']->where('paid_status', 0);
        $pendingFyears = $unpaidDemand->pluck('fyear')->unique()->values();
        $pendingQtrs = [1, 2, 3, 4];
        if (isset($this->_REQUEST->fYear) && isset($this->_REQUEST->qtr)) {                     // Case Of Part Payment
            $demandTillQtr = $unpaidDemand->where('fyear', $this->_REQUEST->fYear)->where('qtr', $this->_REQUEST->qtr)->first();

            if (collect($demandTillQtr)->isNotEmpty()) {
                $demandDueDate = $demandTillQtr->due_date;
                $unpaidDemand = $unpaidDemand->filter(function ($item) use ($demandDueDate) {
                    return $item->due_date <= $demandDueDate;
                });
                $unpaidDemand = $unpaidDemand->values();
            }

            if (collect($demandTillQtr)->isEmpty())
                $unpaidDemand = collect();                                                  // Demand List blank in case of fyear and qtr  
        }
        $this->_demandDetails = $unpaidDemand;

        if ($this->_demandDetails->isEmpty())
            throw new Exception("Demand Not Available For this Fyear and Qtr");
        $this->calculateOnePercPenalty();                                                   // (1.2.2)
        $this->_safCalculation->_propertyDetails['propertyType'] = 2;                       // Individual building
        $this->_safCalculation->_propertyDetails['isMobileTower'] = $this->_safDetails->is_mobile_tower;
        $this->_safCalculation->_propertyDetails['isHoardingBoard'] = $this->_safDetails->is_hoarding_board;
        $this->_safCalculation->_floors = $this->_safFloorDetails;

        $paidDemand = $allDemands->where('paid_status', 1);
        if ($paidDemand->isNotEmpty()) {                                // If Already Payment Done
            $this->_safCalculation->_lateAssessmentStatus = false;
            $fine = 0;
        }
        if ($paidDemand->isEmpty()) {                                   // If no any payment done
            $this->_safCalculation->ifPropLateAssessed();
            $fine = $this->_safCalculation->calcLateAssessmentFee();
        }

        $dueFromFyear = $unpaidDemand->first()['fyear'];
        $dueToFyear = $unpaidDemand->last()['fyear'];
        $dueFromQtr = $unpaidDemand->first()['qtr'];
        $dueToQtr = $unpaidDemand->last()['qtr'];
        // Payable Taxes
        $totalTax = roundFigure($unpaidDemand->sum('balance'));
        $totalOnePercPenalty = roundFigure($unpaidDemand->sum('onePercPenaltyTax'));
        $lateAssessmentPenalty = $fine;
        $this->_totalDemand = roundFigure($totalTax + $totalOnePercPenalty + $lateAssessmentPenalty);
        $this->_generatedDemand['demand'] = [
            "paymentUptoYrs" => $pendingFyears,
            "paymentUptoQtrs" => $pendingQtrs,
            "dueFromFyear" => $dueFromFyear,
            "dueToFyear" => $dueToFyear,
            "dueFromQtr" => $dueFromQtr,
            "dueToQtr" => $dueToQtr,
            "totalTax" => $totalTax,
            "totalOnePercPenalty" => $totalOnePercPenalty,
            "totalQuarters" => $unpaidDemand->count(),
            "duesFrom" => "Quarter $dueFromQtr / Year $dueFromFyear",
            "duesTo" => "Quarter $dueToQtr / Year $dueToFyear",
            "lateAssessmentStatus" => $this->_safCalculation->_lateAssessmentStatus,
            "lateAssessmentPenalty" => $fine,
            "totalDemand" => $this->_totalDemand
        ];
        $this->readRebates();
        $this->calculatePayableAmt();
        $this->_generatedDemand['details'] = $unpaidDemand;
        $this->_generatedDemand = collect($this->_generatedDemand)->reverse();
    }
}
