<?php

namespace App\EloquentClass\Property;

use App\Models\Property\MCapitalValueRate;
use App\Models\Property\MPropBuildingRentalconst;
use App\Models\Property\MPropBuildingRentalrate;
use App\Models\Property\MPropCvRate;
use App\Models\Property\MPropMultiFactor;
use App\Models\Property\MPropRentalValue;
use App\Models\Property\MPropVacantRentalrate;
use App\Models\Property\PropApartmentDtl;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * | --------- Saf Calculation Class -----------------
 * | Created On - 12-10-2022 
 * | Created By - Anshu Kumar
 * | Status-Closed
 */
class SafCalculation
{

    private array $_GRID;
    public array $_propertyDetails;
    public array $_floors;
    public $_wardNo;
    private $_isResidential;
    private $_ruleSets;
    public $_ulbId;
    public $_rentalValue;
    public $_rentalRates;
    private $_virtualDate;
    public $_effectiveDateRule2;
    public $_effectiveDateRule3;
    public array $_readRoadType;
    private bool $_rwhPenaltyStatus = false;
    public $_mobileTowerArea;
    private $_mobileTowerInstallDate;
    public array $_hoardingBoard;
    public array $_petrolPump;
    private $_mobileQuaterlyRuleSets;
    private $_hoardingQuaterlyRuleSets;
    private $_petrolPumpQuaterlyRuleSets;
    public $_vacantRentalRates;
    private $_vacantPropertyTypeId;
    private $_currentQuarterDate;
    private $_loggedInUserType;
    public $_redis;
    private $_citizenRebatePerc;
    private $_jskRebatePerc;
    private $_speciallyAbledRebatePerc;
    private $_seniorCitizenRebatePerc;
    private $_citizenRebateID;
    private $_jskRebateID;
    private $_speciallyAbledRebateID;
    private $_seniorCitizenRebateID;
    private $_currentQuarterDueDate;
    private $_penaltyRebateCalc;
    public $_capitalValueRateMPH;
    public $_multiFactors;
    public $_capitalValueRate;
    public $_paramRentalRate;
    public $_areaOfPlotInSqft;
    public $_point20TaxedUsageTypes;
    public $_lateAssessmentStatus;
    public $_isTrust;
    public $_trustType;
    public $_isTrustVerified;
    private $_isPropPoint20Taxed = false;
    private $_rwhAreaOfPlot;
    public $_ulbType;
    private $_religiousPlaceUsageType;
    private $_individualPropTypeId;

    /** 
     * | For Building
     * | ======================== calculateTax (1) as base function ========================= |
     *  
     * | ------------------ Initialization -------------- |
     * | @var refPropertyType the Property Type id
     * | @var collection collects all the Rulesets and others in an array
     * | @var refFloors[] > getting all the floors in array
     * | @var floorsInstallDate > Contains Floor Install Date in array
     * | @var floorDateFrom > Installation Date for particular floor
     * | @var refRuleSet > get the Rule Set by the current object method readRuleSet()
     * | Query Run Time - 5
     */
    public function calculateTax(Request $req)
    {
        try {

            $this->_propertyDetails = $req->all();

            $this->readPropertyMasterData();                                                        // Make all master data as global(1.1)

            $this->calculateMobileTowerTax();                                                       // For Mobile Towers(1.2)

            $this->calculateHoardingBoardTax();                                                     // For Hoarding Board(1.3)

            $this->calculateBuildingTax();                                                          // Means the Property Type is a Building(1.4)

            $this->calculateVacantLandTax();                                                        // If The Property Type is the type of Vacant Land(1.5)

            $this->calculateFinalPayableAmount();                                                   // Adding Total Final Tax with fine and Penalties(1.6)

            $collection = collect($this->_GRID)->reverse();                                         // Final Collection of the Contained Grid
            return responseMsg(true, $this->summarySafCalculation(), remove_null($collection));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Make All Master Data in a Global Variable (1.1)
     */

    public function readPropertyMasterData()
    {
        $this->_religiousPlaceUsageType = Config::get('PropertyConstaint.RELIGIOUS_PLACE_USAGE_TYPE_ID');
        $this->_redis = Redis::connection();
        $propertyDetails = $this->_propertyDetails;

        $this->_effectiveDateRule2 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE2");
        $this->_effectiveDateRule3 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE3");
        $this->_rwhAreaOfPlot = Config::get('PropertyConstaint.RWH_AREA_OF_PLOT');

        $todayDate = Carbon::now();
        $this->_virtualDate = $todayDate->subYears(12)->format('Y-m-d');
        $this->_floors = $propertyDetails['floor'] ?? [];
        $this->_ulbId = ($propertyDetails['ulbId']) ?? ($this->_propertyDetails['auth']['ulb_id']);

        $ulbMstrs = UlbMaster::findOrFail($this->_ulbId);
        $this->_ulbType = $ulbMstrs->category;

        $this->_vacantPropertyTypeId = Config::get("PropertyConstaint.VACANT_PROPERTY_TYPE");               // Vacant Property Type Id

        // Ward No
        $this->_wardNo = Redis::get('ulbWardMaster:' . $propertyDetails['ward']);                           // Ward No Value from Redis
        if (!$this->_wardNo) {
            $this->_wardNo = UlbWardMaster::find($propertyDetails['ward'])->ward_name;
            $this->_redis->set('ulbWardMaster:' . $propertyDetails['ward'], $this->_wardNo);
        }

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        $readAreaOfPlot =  decimalToSqFt($this->_propertyDetails['areaOfPlot']);                              // (In Decimal To SqFt)
        $this->_areaOfPlotInSqft = $readAreaOfPlot;
        // Check Rain Water Harvesting Status
        if ($propertyDetails['propertyType'] != $this->_vacantPropertyTypeId && $propertyDetails['isWaterHarvesting'] == 0 && $readAreaOfPlot > $this->_rwhAreaOfPlot) {
            $this->_rwhPenaltyStatus = true;
        }

        $this->readParamRentalRate();                                                           // Read Rental Rate (1.1.3)

        $this->ifPropLateAssessed();

        $this->_rentalValue = $this->readRentalValue();
        $this->_multiFactors = $this->readMultiFactor();                                                            // Calculation of Rental rate and Storing in Global Variable (function 1.1.1)
        if ($this->_propertyDetails['propertyType'] == 3) {                                                         // Means the Property is Apartment or Flat
            $this->getAptRoadType();                                                                                // Function (1.1.5)
        }

        if ($this->_propertyDetails['propertyType'] != 3) {
            $this->_readRoadType[$this->_effectiveDateRule2] = $this->readRoadType($this->_effectiveDateRule2);         // Road Type ID According to ruleset2 effective Date
            $this->_readRoadType[$this->_effectiveDateRule3] = $this->readRoadType($this->_effectiveDateRule3);         // Road Type id according to ruleset3 effective Date
        }

        $this->_rentalRates = $this->calculateRentalRates();

        if ($this->_propertyDetails['propertyType'] != 4)                     // Property Should not be Vacant Land for Reading Capital Value Rate
        {
            $this->_capitalValueRate = $this->readCapitalvalueRate();        // Calculate Capital Value Rate 
            if (!$this->_capitalValueRate)
                throw new Exception("CV Rate Not Available for this ward");
        }

        if ($propertyDetails['isMobileTower'] == 1 || $propertyDetails['isHoardingBoard'] == 1 || $propertyDetails['isPetrolPump'] == 1)
            $this->_capitalValueRateMPH = $this->readCapitalValueRateMHP();                                         // Capital Value Rate for MobileTower, PetrolPump,HoardingBoard


        $this->_individualPropTypeId = Config::get('PropertyConstaint.INDEPENDENT_PROP_TYPE_ID');

        if (in_array($this->_propertyDetails['propertyType'], [$this->_vacantPropertyTypeId, $this->_individualPropTypeId]))     // i.e for Vacant Land and Independent Building
            $this->_vacantRentalRates = $this->readVacantRentalRates();

        $this->_penaltyRebateCalc = new PenaltyRebateCalculation();
        // Current Quarter End Date and Start Date for 1% Penalty
        $current = Carbon::now()->format('Y-m-d');
        $currentQuarterDueDate = Carbon::parse(calculateQuaterDueDate($current))->floorMonth();
        $this->_currentQuarterDueDate = $currentQuarterDueDate;                                                     // Quarter Due Date
        $currentQuarterDate = Carbon::parse($current)->floorMonth();
        $this->_currentQuarterDate = $currentQuarterDate;                                                           // Quarter Current Date
        $this->_loggedInUserType = auth()->user()->user_type ?? 'Citizen';                                            // User Type of current Logged In User

        // Types of Rebates and Rebate Percentages
        $this->_citizenRebatePerc = Config::get('PropertyConstaint.REBATES.CITIZEN.PERC');                  // 5%
        $this->_jskRebatePerc = Config::get('PropertyConstaint.REBATES.JSK.PERC');                          // 2.5%
        $this->_speciallyAbledRebatePerc = Config::get('PropertyConstaint.REBATES.SPECIALLY_ABLED.PERC');   // 5%
        $this->_seniorCitizenRebatePerc = Config::get('PropertyConstaint.REBATES.SERIOR_CITIZEN.PERC');     // 5%

        $this->_citizenRebateID = Config::get('PropertyConstaint.REBATES.CITIZEN.ID');                  // 5
        $this->_jskRebateID = Config::get('PropertyConstaint.REBATES.JSK.ID');                          // 2.5
        $this->_speciallyAbledRebateID = Config::get('PropertyConstaint.REBATES.SPECIALLY_ABLED.ID');   // 5
        $this->_seniorCitizenRebateID = Config::get('PropertyConstaint.REBATES.SERIOR_CITIZEN.ID');     // 5
        $this->_point20TaxedUsageTypes = Config::get('PropertyConstaint.POINT20-TAXED-COMM-USAGE-TYPES'); // The Type of Commercial Usage Types which have taxes 0.20 Perc

        $this->isPropertyTrust();            // Check If the Property is Religious or Educational Trust(1.1.6)

        if (isset($this->_propertyDetails['isTrustVerified']))
            $this->_isTrustVerified = ($this->_propertyDetails['isTrustVerified'] == 0) ? 0 : 1;
        else
            $this->_isTrustVerified = $this->_propertyDetails['isTrustVerified'] = 1;

        $this->ifPropPoint20Taxed();   // Check if the Property consists 0.20 % Tax Percentage or Not      // (1.1.7)

    }

    /**
     * | Check if Property Late Assessed Or Not
     */
    public function ifPropLateAssessed()
    {
        // Check If the one of the floors is commercial for Building
        if ($this->_propertyDetails['propertyType'] != $this->_vacantPropertyTypeId) {
            $readCommercial = collect($this->_floors)
                ->where('useType', '!=', 1)
                ->where('useType', '!=', $this->_religiousPlaceUsageType);
            $this->_isResidential = $readCommercial->isEmpty();
        }

        // Check if the vacant land is residential or not
        if ($this->_propertyDetails['propertyType'] == $this->_vacantPropertyTypeId) {
            $condition = $this->_propertyDetails['isMobileTower'] == true || $this->_propertyDetails['isHoardingBoard'] == true;
            $this->_isResidential = $condition ? false : true;
        }
    }

    /**
     * | Rental Value Calculation (1.1.1)
     */
    public function readRentalValue()
    {
        $readZoneId = $this->_propertyDetails['zone'];
        $refRentalValue = json_decode(Redis::get('propMRentalValue-z-' . $readZoneId . '-u-' . $this->_ulbId));         // Get Rental Value from Redis
        if (!$refRentalValue) {
            $refRentalValue = MPropRentalValue::select('usage_types_id', 'zone_id', 'construction_types_id', 'rate')    // Get Rental value from DB
                ->where('zone_id', $readZoneId)
                ->where('ulb_id', $this->_ulbId)
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMRentalValue-z-' . $readZoneId . '-u-' . $this->_ulbId, json_encode($refRentalValue));
        }
        return $refRentalValue;
    }

    /**
     * | MultiFactor Calculation (1.1.2)
     */
    public function readMultiFactor()
    {
        $refMultiFactor = json_decode(Redis::get('propMUsageTypeMultiFactor'));                                      // Get Usage Type Multi Factor From Redis
        if (!$refMultiFactor) {
            $refMultiFactor = MPropMultiFactor::select('usage_type_id', 'multi_factor', 'effective_date')   // Get Usage Type Multi Factor From DB
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMUsageTypeMultiFactor', json_encode($refMultiFactor));
        }
        return $refMultiFactor;
    }

    /**
     *  Read Rental Rate (1.1.3)
     */
    public function readParamRentalRate()
    {
        $refParamRentalRate = json_decode(Redis::get('propMBuildingRentalConst:' . $this->_ulbId));         // Get Building Rental Constant From Redis

        if (!$refParamRentalRate) {                                                                         // Get Building Rental Constant From Database
            $refParamRentalRate = MPropBuildingRentalconst::where('ulb_id', $this->_ulbId)->firstOrFail();
            $this->_redis->set('propMBuildingRentalConst:' . $this->_ulbId, json_encode($refParamRentalRate));
        }
        $this->_paramRentalRate = $refParamRentalRate->x;
    }

    /**
     * | Read Road Type
     * | @param effectiveDate according to the RuleSet
     * | @return roadTypeId
     */
    public function readRoadType($effectiveDate)
    {
        $readRoadWidth = $this->_propertyDetails['roadType'];

        if (is_null($readRoadWidth))
            throw new Exception("Road Width Not Available");
        $refRoadType = json_decode(Redis::get('roadType-effective-' . $effectiveDate . 'roadWidth-' . $readRoadWidth));
        if (!$refRoadType) {
            $queryRoadType = "SELECT * FROM m_prop_road_types
                                WHERE range_from_sqft<=ROUND($readRoadWidth)
                                AND effective_date = '$effectiveDate'
                                ORDER BY range_from_sqft DESC LIMIT 1";

            $refRoadType = collect(DB::select($queryRoadType))->first();
            $this->_redis->set('roadType-effective-' . $effectiveDate . 'roadWidth-' . $readRoadWidth, json_encode($refRoadType));
        }

        $roadTypeId = $refRoadType->prop_road_typ_id;
        return $roadTypeId;
    }

    /**
     * | Get Flat Road Type
     */
    public function getAptRoadType()
    {
        $ulbId = $this->_ulbId;
        $aptId = $this->_propertyDetails['apartmentId'];
        $aptDtls = json_decode(Redis::get('apt-dtl-ulb-' . $ulbId . '-apt-' . $aptId));
        if (!$aptDtls) {
            $mPropApartmentDtls = new PropApartmentDtl();
            $aptDtls = $mPropApartmentDtls->getAptRoadTypeById($aptId, $ulbId);
            $this->_redis->set('apt-dtl-ulb-' . $ulbId . '-apt-' . $aptId, json_encode($aptDtls));
        }
        $roadTypeId = $aptDtls->road_type_mstr_id;
        $this->_readRoadType[$this->_effectiveDateRule2] = $roadTypeId;         // Road Type ID According to ruleset2 effective Date
        $this->_readRoadType[$this->_effectiveDateRule3] = $roadTypeId;         // Road Type id according to ruleset3 effective Date
    }

    /**
     * | Calculation Rental Rate (1.1.3)
     * | @var refParamRentalRate Rental Rate Parameter to calculate rentalRate for the Property
     * | @return readRentalRate final Calculated Rental Rate
     */
    public function calculateRentalRates()
    {
        $refParamRentalRate = json_decode(Redis::get('propMBuildingRentalRate'));
        if (!$refParamRentalRate) {
            $refParamRentalRate = MPropBuildingRentalrate::select('id', 'prop_road_type_id', 'construction_types_id', 'rate', 'effective_date', 'status')
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMBuildingRentalRate', json_encode($refParamRentalRate));
        }
        return $refParamRentalRate;
    }

    /**
     * | Read Capital Value Rate for the calculation of Building RuleSet 3
     */
    public function readCapitalValueRate()
    {
        $readFloors = $this->_floors;
        // Capital Value Rate
        $readRoadType = ($this->_readRoadType[$this->_effectiveDateRule3] == 1) ? 1 : 0;
        $mCapitalValueRates = new MCapitalValueRate();
        $capitalValue = array();

        foreach ($readFloors as $readFloor) {
            $constType = $readFloor['constructionType'] == 1 ? 'PAKKA' : 'KACCHA';

            if ($this->_propertyDetails['propertyType'] == 3)
                $propertyType = "DLX_APARTMENT";
            else
                $propertyType = "BUILDING_" . $constType;

            $usageType = $readFloor['useType'] == 1 ? 'RESIDENTIAL' : 'COMMERCIAL';
            $capitalValueReq = new Request(
                [
                    'roadTypeMstrId' => $readRoadType,
                    'propertyType' => $propertyType,
                    'wardNo' => $this->_wardNo,
                    'usageType' => $usageType,
                    'ulbId' => $this->_ulbId
                ]
            );
            $capitalValueRate = Redis::get('cv_rate-road-' . $readRoadType . 'propertyType' . $propertyType . 'wardNo-' . $this->_wardNo . 'usageType-' . $usageType . 'ulbId' . $this->_ulbId);
            $capitalValueRate = json_decode($capitalValueRate);
            if (!$capitalValueRate) {
                $capitalValueRate = $mCapitalValueRates->getCVRate($capitalValueReq);
                $this->_redis->set(
                    'cv_rate-road-' . $readRoadType . 'propertyType' . $propertyType . 'wardNo-' . $this->_wardNo . 'usageType-' . $usageType . 'ulbId' . $this->_ulbId,
                    json_encode($capitalValueRate)
                );
            }
            $capitalValueRate->rate = $capitalValueRate->max_rate;
            array_push($capitalValue, $capitalValueRate->rate);
        }

        return $capitalValue;
    }

    /**
     * | Read Capital Value Rate for mobile tower, Hoarding Board, Petrol Pump
     */
    public function readCapitalValueRateMHP()
    {
        $propertyType = "BUILDING_PAKKA";
        $usageType = "COMMERCIAL";

        $readRoadType = ($this->_readRoadType[$this->_effectiveDateRule3] == 1) ? 1 : 0;
        $mCapitalValueRates = new MCapitalValueRate();
        $capitalValueReq = new Request(
            [
                'roadTypeMstrId' => $readRoadType,
                'propertyType' => $propertyType,
                'wardNo' => $this->_wardNo,
                'usageType' => $usageType,
                'ulbId' => $this->_ulbId
            ]
        );
        $capitalValueRate = Redis::get('cv_rate-road-' . $readRoadType . 'propertyType' . $propertyType . 'wardNo-' . $this->_wardNo . 'usageType-' . $usageType . 'ulbId' . $this->_ulbId);
        $capitalValueRate = json_decode($capitalValueRate);
        if (!$capitalValueRate) {
            $capitalValueRate = $mCapitalValueRates->getCVRate($capitalValueReq);
            $this->_redis->set(
                'cv_rate-road-' . $readRoadType . 'propertyType' . $propertyType . 'wardNo-' . $this->_wardNo . 'usageType-' . $usageType . 'ulbId' . $this->_ulbId,
                json_encode($capitalValueRate)
            );
        }
        return $capitalValueRate->rate = $capitalValueRate->max_rate;
    }

    /**
     * | Calculate Vacant Rental Rate 
     */
    public function readVacantRentalRates()
    {
        $rentalRate = json_decode(Redis::get('propMVacantRentalRate'));
        if (!$rentalRate) {
            $rentalRate = MPropVacantRentalrate::select('id', 'prop_road_type_id', 'rate', 'ulb_type_id', 'effective_date')
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMVacantRentalRate', json_encode($rentalRate));
        }
        return $rentalRate;
    }

    /**
     * | Check if the Property is Religious or Educational Trust(1.1.6)
     */
    public function isPropertyTrust()
    {
        $trustUsageType = Config::get('PropertyConstaint.TRUST_USAGE_TYPE_ID');
        if ($this->_propertyDetails['propertyType'] != 4) {
            $floors = $this->_floors;
            $usageTypes = collect($floors)->pluck('useType');
            if (isset($this->_propertyDetails['isTrust']))
                $this->_isTrust = $this->_propertyDetails['isTrust'];
            else
                $this->_isTrust = $usageTypes->contains($trustUsageType) ? true : false;
            $this->_trustType = $this->_propertyDetails['trustType'] ?? "";
        }
    }


    /**
     * | Check If the Property Contains 0.20 % Tax Or Not
     */
    public function ifPropPoint20Taxed()
    {
        $point20TaxedUseTypes = $this->_point20TaxedUsageTypes;
        if ($this->_propertyDetails['propertyType'] != 4) {                 // The Property Should not be Vacant Land
            $usageTypes = collect($this->_floors)->pluck('useType');
            foreach ($usageTypes as $usageType) {
                if (in_array($usageType, $point20TaxedUseTypes)) {             // If The Property Usage types lies between these usage types
                    $totalBuildupArea = collect($this->_floors)->sum('buildupArea');
                    if ($totalBuildupArea >= 25000) {
                        $this->_isPropPoint20Taxed = true;
                        break;
                    }
                }
            }
        }
    }

    //--------------------------------------- Calculation Algorithms Starts Here -----------------------------------------------

    /**
     * | Calculate Mobile Tower (1.2)
     */
    public function calculateMobileTowerTax()
    {
        if ($this->_propertyDetails['isMobileTower'] == 1) {
            $this->_mobileTowerInstallDate = $this->_propertyDetails['mobileTower']['dateFrom'];
            $this->_mobileTowerArea = $this->_propertyDetails['mobileTower']['area'];
            $this->_mobileQuaterlyRuleSets = $this->calculateQuaterlyRulesets("mobileTower");
        }
    }

    /**
     * | In Case of the Property Have Hoarding Board(1.3)
     */
    public function calculateHoardingBoardTax()
    {
        if ($this->_propertyDetails['isHoardingBoard'] == 1) {                                                                      // For Hoarding Board
            $this->_hoardingBoard['installDate'] = $this->_propertyDetails['hoardingBoard']['dateFrom'];
            $this->_hoardingBoard['area'] = $this->_propertyDetails['hoardingBoard']['area'];
            $this->_hoardingQuaterlyRuleSets = $this->calculateQuaterlyRulesets("hoardingBoard");
        }
    }

    /**
     * | In Case of the Property is a Building or SuperStructure (1.4)
     */
    public function calculateBuildingTax()
    {
        $readPropertyType = $this->_propertyDetails['propertyType'];

        if ($readPropertyType != $this->_vacantPropertyTypeId) {
            if ($this->_propertyDetails['isPetrolPump'] == 1) {                                                                     // For Petrol Pump
                $this->_petrolPump['installDate'] = $this->_propertyDetails['petrolPump']['dateFrom'];
                $this->_petrolPump['area'] = $this->_propertyDetails['petrolPump']['area'];
                $this->_petrolPumpQuaterlyRuleSets = $this->calculateQuaterlyRulesets("petrolPump");
            }

            $floors = $this->_floors;
            // readTaxCalculation Floor Wise
            $calculateFloorTaxQuaterly = collect($floors)->map(function ($floor, $key) {
                $calculateQuaterlyRuleSets = $this->calculateQuaterlyRulesets($key);
                return $calculateQuaterlyRuleSets;
            });

            // Collapsion of the all taxes which contains saperately array collection
            $readFinalFloorTax = collect($calculateFloorTaxQuaterly)->collapse();                                                       // Collapsable collections with all Floors
            $readFinalFloorWithMobileTower = collect($this->_mobileQuaterlyRuleSets)->merge($readFinalFloorTax);                        // Collapsable Collection With Mobile Tower and Floors
            $readFinalWithMobileHoarding = collect($this->_hoardingQuaterlyRuleSets)->merge($readFinalFloorWithMobileTower);            // Collapsable Collection with mobile tower floors and Hoarding
            $readFinalWithMobilHoardingPetrolPump = collect($this->_petrolPumpQuaterlyRuleSets)->merge($readFinalWithMobileHoarding);   // Collapsable Collection With Mobile floors Hoarding and Petrol Pump
            $this->_GRID['details'] = $readFinalWithMobilHoardingPetrolPump;
        }
    }

    /**
     * | Calculate Vacant Land Tax (1.5)
     */
    public function calculateVacantLandTax()
    {
        $readPropertyType = $this->_propertyDetails['propertyType'];
        if (in_array($readPropertyType, [$this->_vacantPropertyTypeId, $this->_individualPropTypeId])) {                                             // Vacant Land condition with independent building
            $calculateQuaterlyRuleSets = $this->calculateQuaterlyRulesets("vacantLand");
            $ruleSetsWithMobileTower = collect($this->_mobileQuaterlyRuleSets)->merge($calculateQuaterlyRuleSets);        // Collapse with mobile tower
            $ruleSetsWithHoardingBoard = collect($this->_hoardingQuaterlyRuleSets)->merge($ruleSetsWithMobileTower);      // Collapse with hoarding board
            $this->_GRID['vacantDemandDetails'] = $ruleSetsWithHoardingBoard;
            $this->_GRID['details'] = $this->_GRID['vacantDemandDetails']->merge($this->_GRID['details'] ?? collect());
        }
    }

    /**
     * | Calculate Quaterly Rulesets (1.2)
     * | @param key the key of the array from function 1 to distribute by floor details
     * ----------------------------------------------------------------
     * | @var array ruleSet contains all the QuaterlyRuleSets in array
     * | @var virtualDate is the 12 Years back date from the today's Date
     * | @var floorDetail all the floor details in case of Property Building Structured
     * | @var carbonDateFrom the Date from of the Property floor Details in Carbon format
     * | @var carbonDateUpto the Date Upto of the propety floor details In Carbon format
     * | @var collectRuleSets is the Collection of all the arrayRuleSets in laravel Collection
     * | @var uniqueRuleSets make our collection unique by due date and quater
     * | Query Run Time - 4
     * 
     */

    public function calculateQuaterlyRulesets($key)
    {
        if (is_string($key)) {                                                          // For Mobile Tower, hoarding board or petrol pump
            $arrayRuleSet = [];
            switch ($key) {
                case "mobileTower";
                    $dateFrom = $this->_mobileTowerInstallDate;
                    $carbonDateUpto = Carbon::now()->endOfYear()->addMonths(3);           // Get The Full Financial Year
                    $carbonDateUpto = $carbonDateUpto->format('Y-m-d');
                    break;
                case "hoardingBoard";
                    $dateFrom = $this->_hoardingBoard['installDate'];
                    $carbonDateUpto = Carbon::now()->endOfYear()->addMonths(3);           // Get The Full Financial Year
                    $carbonDateUpto = $carbonDateUpto->format('Y-m-d');
                    break;
                case "petrolPump";
                    $dateFrom = $this->_petrolPump['installDate'];
                    $carbonDateUpto = Carbon::now()->endOfYear()->addMonths(3);           // Get The Full Financial Year
                    $carbonDateUpto = $carbonDateUpto->format('Y-m-d');
                    break;
                case "vacantLand";
                    $dateFrom = $this->_propertyDetails['landOccupationDate'];
                    break;
            }

            if ($dateFrom < '2016-04-01')
                $dateFrom = '2016-04-01';

            if ($this->_propertyDetails['propertyType'] == 2) {             // For Independent Building
                $leastDatedFloor = collect($this->_floors)->sortBy('dateFrom');
                $floorCalculationStartedDate = $leastDatedFloor->first()['dateFrom'];
                $carbonDateUpto = Carbon::parse($floorCalculationStartedDate)->format('Y-m-d');
            }

            if ($this->_propertyDetails['propertyType'] == $this->_vacantPropertyTypeId) {   // Vacant Land
                $dateTo = Carbon::now();
                $carbonDateUpto = $dateTo->endOfYear()->addMonths(3);           // Get The Full Financial Year
                $carbonDateUpto = $carbonDateUpto->format('Y-m-d');
            }

            $readRuleSet = $this->readRuleSet($dateFrom, $key);
            $carbonDateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
        }

        if (is_numeric($key)) {                                                 // i.e. Floors
            $readDateFrom = $this->_propertyDetails['floor'][$key]['dateFrom'];
            $readDateUpto = $this->_propertyDetails['floor'][$key]['dateUpto'];
            $arrayRuleSet = [];

            $carbonDateFrom = Carbon::parse($readDateFrom)->format('Y-m-d');

            if ($readDateUpto == null) {
                $readDateUpto = Carbon::now()->endOfYear()->addMonths(3);           // Get The Full Financial Year
                $readDateUpto = $readDateUpto->format('Y-m-d');
            }

            $carbonDateUpto = Carbon::parse($readDateUpto)->format('Y-m-d');

            if ($readDateFrom >= $this->_virtualDate)
                $carbonDateFrom = $readDateFrom;


            if ($readDateFrom < $this->_virtualDate)                                // Get Back to 12 Years
                $carbonDateFrom = $this->_virtualDate;
        }

        // Itteration for the RuleSets dateFrom wise 
        while ($carbonDateFrom < $carbonDateUpto) {
            $readRuleSet = $this->readRuleSet($carbonDateFrom, $key);
            $carbonDateFrom = Carbon::parse($carbonDateFrom)->addMonth()->format('Y-m-d');              // CarbonDateFrom = CarbonDateFrom + 1 (add one months)
            array_push($arrayRuleSet, $readRuleSet);
        }

        $collectRuleSets = collect($arrayRuleSet);

        // if ($collectRuleSets->isEmpty())
        //     throw new Exception("No Demand Generated due to invalid Date Range");

        $uniqueRuleSets = $collectRuleSets->unique('dueDate');
        $ruleSet = $uniqueRuleSets->values();
        return $ruleSet;
    }

    /**
     * | Get Rule Set (1.2.1)
     * | --------------------- Initialization ---------------------- | 
     * | @param dateFrom Installation Date of floor or Property
     * | @var ruleSets contains the ruleSet in an array
     * | Query Run Time - 3
     */
    public function readRuleSet($dateFrom, $key)
    {
        if (is_string($key)) {                                                                          // Mobile Tower or Hoarding Board Or Petrol Pump
            switch ($key) {
                case "mobileTower":
                    $readFloorDetail = [
                        'floorNo' => "MobileTower",
                        'buildupArea' => $this->_mobileTowerArea,
                        'dateFrom' => $this->_mobileTowerInstallDate,
                        'mFloorNo' => 'Mobile Tower',
                    ];
                    break;
                case "hoardingBoard":
                    $readFloorDetail = [
                        'floorNo' => "hoardingBoard",
                        'buildupArea' => $this->_hoardingBoard['area'],
                        'dateFrom' => $this->_hoardingBoard['installDate'],
                        'mFloorNo' => 'Hoarding Board',
                        'mUsageType' => 'Commercial',
                    ];
                    break;
                case "petrolPump":
                    $readFloorDetail = [
                        'floorNo' => "petrolPump",
                        'buildupArea' => $this->_petrolPump['area'],
                        'dateFrom' => $this->_petrolPump['installDate'],
                        'mFloorNo' => 'Petrol Pump',
                    ];
                    break;
                case "vacantLand":
                    $readFloorDetail = [
                        'propertyType' => "vacantLand",
                        'buildupArea' => $this->_propertyDetails['areaOfPlot'],
                        'dateFrom' => $this->_propertyDetails['landOccupationDate'],
                        'mFloorNo' => 'Vacant Land',
                    ];
                    break;
            }
        }

        if (is_numeric($key)) {                                                                 // For Floors
            $floorNo = $this->_floors[$key]['floorNo'];
            $useType = $this->_floors[$key]['useType'];
            $readFloorDetail =
                [
                    'floorNo' => $floorNo,
                    'useType' => $useType,
                    'constructionType' => $this->_floors[$key]['constructionType'],
                    'buildupArea' => $this->_floors[$key]['buildupArea'],
                    'dateFrom' => $this->_floors[$key]['dateFrom'],
                    'dateTo' => $this->_floors[$key]['dateUpto'],
                    'mFloorNo' => Config::get("PropertyConstaint.FLOOR-TYPE.$floorNo"),
                    'mUsageType' => Config::get("PropertyConstaint.USAGE-TYPE.$useType.TYPE"),
                    'floorKey' => $this->_floors[$key]['floorKey'] ?? null              // Used Only for Review Calculation
                ];
        }

        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule2) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);                    // One Percent Penalty
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),                              // Calculate Financial Year means to Calculate the FinancialYear
                "ruleSet" => "RuleSet1",
                "qtr" => calculateQtr($dateFrom),                                        // Calculate Quarter from the date
                "dueDate" => $quarterDueDate                                             // Calculate Quarter Due Date of the Date
            ];
            $tax = $this->calculateRuleSet1($key, $onePercPenalty);                      // Tax Calculation
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule3) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);                   // One Percent Penalty
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet2",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => $quarterDueDate
            ];
            $tax = $this->calculateRuleSet2($key, $onePercPenalty, $dateFrom);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }

        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        if ($dateFrom >= $this->_effectiveDateRule3) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);                        // One Percent Penalty
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet3",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            $tax = $this->calculateRuleSet3($key, $onePercPenalty, $dateFrom);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
    }

    /**
     * | One Perc Penalty(1.2.1.1)
     * | @param quarterDueDate Floor Quaterly Due Date
     */
    public function onePercPenalty($quarterDueDate)
    {
        $onePercPenalty = $this->_penaltyRebateCalc->calcOnePercPenalty($quarterDueDate);
        return $onePercPenalty;
    }

    /**
     * | Calculation of Property Tax By RuleSet 1 (1.2.1.1)
     * ------------------------------------------------------------------
     * | @param key keyvalue of the array of The Floor
     * ------------------ Initialization --------------------------------
     * | @var readBuildupArea buildup area for the floor 
     * | @var readFloorInstallationDate Floor's Installation Date
     * | @var readUsageType floor Usage Type ID
     * | @var readOccupancyType floorOccupancy Type
     * | @var readPropertyType Property type 
     * | @var readRentalValue the rental value for the floor
     * | @var tempArv the temporary arv value for the calculation of Actual ARV
     * | @var arvCalcPercFactor the percentage factor to determine the ARV
     * | @var arv the quaterly ARV
     * ------------------ Calculation -----------------------------------
     * | $arv = ($tempArv * $arvCalPerFactor)/100;
     * | $arv=$tempArv-$arv;
     * | $latrineTax = ($arv * 7.5) / 100;
     * | $waterTax = ($arv * 7.5) / 100;
     * | $healthTax = ($arv * 6.25) / 100;
     * | $educationTax = ($arv * 5.0) / 100;
     * | $rwhPenalty = 0;
     * | $totalTax = $holdingTax + $latrineTax + $waterTax + $healthTax + $educationTax + $rwhPenalty;
     * | @return Tax totalTax/4 (Quaterly)
     * | Query RunTime=1
     */
    public function calculateRuleSet1($key, $onePercPenalty)
    {
        $readBuildupArea =  $this->_floors[$key]['buildupArea'];
        $readFloorInstallationDate =  $this->_floors[$key]['dateFrom'];
        $readUsageType = $this->_floors[$key]['useType'];
        $readOccupancyType = $this->_floors[$key]['occupancyType'];
        $readPropertyType = $this->_propertyDetails['propertyType'];

        if ($readUsageType == 1)
            $usageTypeId = 1;       // For Residential Usage Type
        else
            $usageTypeId = 2;       // For the Type of Property which is not Residential

        $readRentalValue = collect($this->_rentalValue)->where('usage_types_id', $usageTypeId)
            ->where('construction_types_id', $this->_floors[$key]['constructionType'])
            ->first();

        if (!$readRentalValue) {
            throw new Exception("Rental Value Not Available for this Usage Type");
        }

        $tempArv = $readBuildupArea * (float)$readRentalValue->rate;
        $arvCalcPercFactor = 0;

        if ($readUsageType == 1 && $readOccupancyType == 1) {                         // Occupancy Type 1 for Self 
            $arvCalcPercFactor += 30;
            // Condition if the property is Independent Building and installation date is less than 1942
            if ($readFloorInstallationDate < '1942-04-01' && $readPropertyType == 2) {
                $arvCalcPercFactor += 10;
            }
        } else                                                                         // If The Property floor is not residential
            $arvCalcPercFactor += 15;
        // Total ARV and other Taxes
        $arv = ($tempArv * $arvCalcPercFactor) / 100;
        $arv = $tempArv - $arv;

        $holdingTax = ($arv * 12.5) / 100;
        $latrineTax = ($arv * 7.5) / 100;
        $waterTax = ($arv * 7.5) / 100;
        $healthTax = ($arv * 6.25) / 100;
        $educationTax = ($arv * 5.0) / 100;
        $rwhPenalty = 0;

        // Quaterly Taxes
        $quaterHoldingTax = roundFigure($holdingTax / 4);
        $quaterLatrineTax = roundFigure($latrineTax / 4);
        $quaterWaterTax = roundFigure($waterTax / 4);
        $quaterHealthTax = roundFigure($healthTax / 4);
        $quaterEducationTax = roundFigure($educationTax / 4);
        $quaterlyTax = roundFigure($quaterHoldingTax + $quaterLatrineTax + $quaterWaterTax + $quaterHealthTax + $quaterEducationTax);
        $onePercPenaltyTax = ($readUsageType != $this->_religiousPlaceUsageType) ? ($quaterlyTax * $onePercPenalty) / 100 : 0;       // For Religious Place One Perc is 0

        // Tax Calculation Quaterly
        $tax = [
            "arv" => roundFigure($arv),
            "calculationPercFactor" => $arvCalcPercFactor,
            "rentalValue" => $readRentalValue->rate,
            "holdingTax" => $quaterHoldingTax,
            "latrineTax" => $quaterLatrineTax,
            "waterTax" => $quaterWaterTax,
            "healthTax" => $quaterHealthTax,
            "educationTax" => $quaterEducationTax,
            "rwhPenalty" => roundFigure($rwhPenalty),
            "yearlyTax" => roundFigure($quaterlyTax * 4),
            "totalTax" => $quaterlyTax,
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax)
        ];
        return $tax;
    }

    /**
     * | RuleSet 2 Calculation (1.2.1.2)
     * ---------------------- Initialization -------------------
     * | @param key array key index
     * | dateFrom
     * | @var readFloorUsageType Floor Usage Type(Residential or Other)
     * | @var readFloorBuildupArea Floor Buildup Area(SqFt)
     * | @var readFloorOccupancyType Floor Occupancy Type (Self or Tenant)
     * | @var readAreaOfPlot Property Road Width
     * | @var refConstructionType Floor Construction Type ID
     * | @var paramCarpetAreaPerc (70% -> Residential || 80% -> Commercial)
     * | @var paramOccupancyFactor (Self-1 || Rent-1.5)
     * | @var readMultiFactor Get the MultiFactor Using PropUsageTypeMultiFactor Table
     * | @var tempArv = temperory ARV for the Reference to calculate @var arv
     * ---------------------- Calculation ----------------------
     * | $reAreaOfPlot = areaOfPlot * 435.6 (In SqFt)
     * | $refParamCarpetAreaPerc (Residential-70%,Commercial-80%)
     * | $carpetArea = $refFloorBuildupArea x $paramCarpetAreaPerc %
     * | $rentalRate Calculation of RentalRate Using Current Object Function
     * | $tempArv = $carpetArea * ($readMultiFactor->multi_factor) * $paramOccupancyFactor * $rentalRate;
     * | $arv = ($tempArv * 2) / 100;
     * | $rwhPenalty = $arv/2
     * | $totalTax = $arv + $rwhPenalty;
     */
    public function calculateRuleSet2($key, $onePercPenalty, $dateFrom)
    {
        $paramRentalRate = $this->_paramRentalRate;
        // Vacant Land RuleSet2
        if ($key == "vacantLand") {
            $plotArea = $this->_propertyDetails['areaOfPlot'];
            $roadTypeId = $this->_readRoadType[$this->_effectiveDateRule2];
            if ($roadTypeId == 4)                                                // i.e. No Road
                $area = decimalToAcre($plotArea);
            else
                $area = decimalToSqMt($plotArea);

            if (collect($this->_vacantRentalRates)->isEmpty())
                throw new Exception("Vacant Land Rental Rate Not Available");

            $rentalRate = collect($this->_vacantRentalRates)->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('ulb_type_id', $this->_ulbType)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();

            $rentalRate = $rentalRate->rate;
            $occupancyFactor = 1;
            $tax = $area * $rentalRate * $occupancyFactor;

            $onePercPenaltyTax = ($tax * $onePercPenalty) / 100;                                // One Perc Penalty Tax
            $quaterlyTax = roundFigure($tax / 4);
            $taxQuaterly = [
                "area" => roundFigure($area),
                "rentalRate" => $rentalRate,
                "occupancyFactor" => $occupancyFactor,
                "onePercPenalty"   => $onePercPenalty,
                "yearlyTax" => roundFigure($tax),
                "totalTax" => $quaterlyTax,
                "holdingTax" => $quaterlyTax,
                "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
            ];
            return $taxQuaterly;
        }

        // Mobile Tower, Hoarding Board, Petrol Pump
        if ($key == "mobileTower" || $key == "hoardingBoard" || $key == "petrolPump") {
            switch ($key) {
                case "mobileTower";
                    $carpetArea = $this->_mobileTowerArea;
                    break;
                case "hoardingBoard":
                    $carpetArea = $this->_hoardingBoard['area'];
                    break;
                case "petrolPump":
                    $carpetArea = $this->_petrolPump['area'];
                    break;
            }

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', 45)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $multiFactor = (float)$readMultiFactor->multi_factor;

            $paramOccupancyFactor = 1.5;
            // Rental Rate Calculation
            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('construction_types_id', 1)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $rentalRate = round($rentalRates->rate * $paramRentalRate);
        }

        if (is_numeric($key)) {                                                             // Applicable For Floors
            $readFloorUsageType = $this->_floors[$key]['useType'];
            $readFloorBuildupArea = $this->_floors[$key]['buildupArea'];
            $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
            $paramCarpetAreaPerc = ($readFloorUsageType == 1) ? 70 : 80;
            $paramOccupancyFactor = ($readFloorOccupancyType == 1) ? 1 : 1.5;

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', $readFloorUsageType)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            if (collect($readMultiFactor)->isEmpty())
                throw new Exception("Multi Factor Not Available");
            $multiFactor = (float)$readMultiFactor->multi_factor;

            $carpetArea = $this->_floors[$key]['carpetArea'] ?? "";

            $carpetArea = !empty($carpetArea) ? $carpetArea : ($readFloorBuildupArea * $paramCarpetAreaPerc) / 100;

            // Rental Rate Calculation
            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('construction_types_id', $this->_floors[$key]['constructionType'])
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $rentalRate = round($rentalRates->rate * $paramRentalRate);
        }

        $rwhPenalty = 0;

        $tempArv = $carpetArea * $multiFactor * $paramOccupancyFactor * (float)$rentalRate;
        $arv = ($tempArv * 2) / 100;

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        if ($this->_rwhPenaltyStatus == true && $dateFrom > '2017-03-31') {                 // RWH Applicable from 2017-2018
            $rwhPenalty = $arv / 2;
        }

        if ($this->_rwhPenaltyStatus == false && $dateFrom > '2017-03-31' && $this->_areaOfPlotInSqft > $this->_rwhAreaOfPlot && $dateFrom < $this->_propertyDetails['rwhDateFrom'])
            $rwhPenalty = $arv / 2;

        $totalTax = $arv + $rwhPenalty;
        $onePercPenaltyTax = ($totalTax * $onePercPenalty) / 100;
        // All Taxes Quaterly
        $tax = [
            "arv" => roundFigure($tempArv),
            "buildupArea" => $readFloorBuildupArea ?? $this->_mobileTowerArea ?? 0,
            "carpetArea" => $carpetArea,
            "multiFactor" => $multiFactor,
            "arvTotalPropTax" => $arv,
            "rentalRate" => roundFigure($rentalRate),
            "occupancyFactor" => $paramOccupancyFactor,

            "holdingTax" => roundFigure($arv / 4),
            "latrineTax" => 0,
            "waterTax" => 0,
            "healthTax" => 0,
            "educationTax" => 0,

            "rwhPenalty" => roundFigure($rwhPenalty / 4),
            "yearlyTax" => roundFigure($totalTax),
            "totalTax" => roundFigure($totalTax / 4),
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
        ];
        return $tax;
    }

    /**
     * | RuleSet 3 Calculation (1.2.1.3)
     * | @param key arrayKey value
     */
    public function calculateRuleSet3($key, $onePercPenalty, $dateFrom)
    {
        // Vacant Land RuleSet3
        if ($key == "vacantLand") {
            $plotArea = $this->_propertyDetails['areaOfPlot'];
            $roadTypeId = $this->_readRoadType[$this->_effectiveDateRule3];
            if ($roadTypeId == 4)                                                // i.e. No Road
                $area = decimalToAcre($plotArea);
            else
                $area = decimalToSqMt($plotArea);
            $rentalRate = collect($this->_vacantRentalRates)->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule3])
                ->where('ulb_type_id', $this->_ulbType)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();

            $rentalRate = $rentalRate->rate;
            $occupancyFactor = 1;
            $tax = (float)$area * $rentalRate * $occupancyFactor;
            $onePercPenaltyTax = ($tax * $onePercPenalty) / 100;
            $quaterlyTax = roundFigure($tax / 4);
            $taxQuaterly = [
                "area" => roundFigure($area),
                "rentalRate" => $rentalRate,
                "occupancyFactor" => $occupancyFactor,
                "onePercPenalty"   => $onePercPenalty,
                "totalTax" => $quaterlyTax,
                "holdingTax" => $quaterlyTax,
                "yearlyTax" => roundFigure($tax),
                "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
            ];
            return $taxQuaterly;
        }

        // For Mobile Tower, Hoarding Board, Petrol Pump
        if ($key == "mobileTower" || $key == "hoardingBoard" || $key == "petrolPump") {
            $readCircleRate = $this->_capitalValueRateMPH;
            switch ($key) {
                case "mobileTower";
                    $readBuildupArea = $this->_mobileTowerArea;
                    break;
                case "hoardingBoard":
                    $readBuildupArea = $this->_hoardingBoard['area'];
                    break;
                case "petrolPump":
                    $readBuildupArea = $this->_petrolPump['area'];
                    break;
            }

            $paramOccupancyFactor = 1.5;
            $taxPerc = 0.15;
            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', 45)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();
            $readCalculationFactor = $readMultiFactor->multi_factor;
            $readMatrixFactor = 1;              // Rental Rate 1 fixed for usage type not residential
        }

        // For Floors
        if (is_numeric($key)) {                                                                             // Applicable for floors
            $readCircleRate = $this->_capitalValueRate[$key] ?? "";
            if (empty($readCircleRate))
                throw new Exception("Circle Rate Not Available");

            $readFloorUsageType = $this->_floors[$key]['useType'];
            $readBuildupArea = $this->_floors[$key]['buildupArea'];

            $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
            $paramOccupancyFactor = ($readFloorOccupancyType == 1) ? 1 : 1.5;

            $readUsageType = $this->_floors[$key]['useType'];
            $taxPerc = ($readUsageType == 1) ? 0.075 : 0.15;                                                // 0.075 for Residential and 0.15 for Commercial

            if ($this->_isPropPoint20Taxed == true)
                $taxPerc = 0.20;                                                                            // Tax Perc for the type of Property whose Sqft is > 250000

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', $readFloorUsageType)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();

            $readCalculationFactor = $readMultiFactor->multi_factor;                                        // (Calculation Factor as Multi Factor)
            if ($readUsageType == 1) {
                $rentalRates = collect($this->_rentalRates)
                    ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule3])
                    ->where('construction_types_id', $this->_floors[$key]['constructionType'])
                    ->where('effective_date', $this->_effectiveDateRule3)
                    ->first();
                $readMatrixFactor = $rentalRates->rate;                                                     // (Matrix Factor as Rental Rate)
            } else
                $readMatrixFactor = 1;                      // (Matrix Factor for the Type of Floors which is not Residential)

            // Condition for the Institutional or Educational Trust 
            if (isset($this->_isTrust) && $this->_isTrust == true && $this->_isTrustVerified == true && $readUsageType != $this->_religiousPlaceUsageType) {
                $paramOccupancyFactor = 1;
                $taxPerc = 0.15;
                $readCalculationFactor = ($this->_trustType == 1) ? 0.25 : 0.50;
                $readMatrixFactor = 1;
            }
        }

        $calculatePropertyTax = ($readCircleRate * $readBuildupArea * $paramOccupancyFactor * $taxPerc * (float)$readCalculationFactor) / 100;
        $calculatePropertyTax = $calculatePropertyTax * $readMatrixFactor;                                  // As Holding Tax
        $rwhPenalty = 0;

        // Rain Water Harvesting Penalty
        if ($this->_rwhPenaltyStatus == true)                                                               // RWH Applicable from 2017-2018
            $rwhPenalty = $calculatePropertyTax / 2;

        if ($this->_rwhPenaltyStatus == false && $dateFrom > '2017-03-31' && $this->_areaOfPlotInSqft > $this->_rwhAreaOfPlot && $dateFrom < $this->_propertyDetails['rwhDateFrom'])
            $rwhPenalty = $calculatePropertyTax / 2;

        $totalTax = $calculatePropertyTax + $rwhPenalty;
        $onePercPenaltyTax = ($totalTax * $onePercPenalty) / 100;                                           // One Percent Penalty

        // Quaterly Taxes
        $qHoldingTax = roundFigure($calculatePropertyTax / 4);
        $qRwhPenalty = roundFigure($rwhPenalty / 4);
        $quaterTax = roundFigure($qHoldingTax + $qRwhPenalty);

        // Tax Calculation Quaterly
        $tax = [
            "arv" => roundFigure($calculatePropertyTax),
            "circleRate" => $readCircleRate,
            "buildupArea" => $readBuildupArea,
            "occupancyFactor" => $paramOccupancyFactor,
            "taxPerc" => $taxPerc,
            "calculationFactor" => $readCalculationFactor,
            "matrixFactor" => $readMatrixFactor,

            "holdingTax" => $qHoldingTax,
            "latrineTax" => 0,
            "waterTax" => 0,
            "healthTax" => 0,
            "educationTax" => 0,

            "rwhPenalty" => $qRwhPenalty,
            "yearlyTax" => roundFigure($quaterTax * 4),
            "totalTax" => $quaterTax,
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
        ];
        return $tax;
    }

    /**
     * | Total Final Payable Amount (1.6)
     * | @var demand Sum Collection of TotalTax and TotalOnePercPenalty
     * | @var fine LateAssessment Fine (5000 For Commercial, 2000 For Residential)
     */
    public function calculateFinalPayableAmount()
    {
        $demand = collect($this->_GRID['details'])->pipe(function ($values) {
            return collect([
                'totalTax' => roundFigure($values->sum('totalTax')),
                'totalOnePercPenalty' => roundFigure($values->sum('onePercPenaltyTax'))
            ]);
        });


        $this->_GRID['demand'] = $demand;

        $this->_GRID['demand']['totalQuarters'] = $this->_GRID['details']->count();
        // From Quarter Year and Quarter Month
        $this->_GRID['demand']['fromQuarterYear'] = $this->_GRID['details']->first()['quarterYear'];
        $this->_GRID['demand']['fromQuarter'] = $this->_GRID['details']->first()['qtr'];
        // To Quarter Year and Quarter Month
        $this->_GRID['demand']['toQuarterYear'] = $this->_GRID['details']->last()['quarterYear'];
        $this->_GRID['demand']['toQuarter'] = $this->_GRID['details']->last()['qtr'];

        $this->_GRID['demand']['isResidential'] = $this->_isResidential;

        $fine = 0;

        $fine = $this->calcLateAssessmentFee();
        // No Late Assessment For Property Yearly Holding Tax
        if ($this->_propertyDetails['isProperty'] ?? "" && $this->_propertyDetails['isProperty'] == true) {
            $this->_lateAssessmentStatus = false;
            $fine = 0;
        }

        $this->_GRID['demand']['lateAssessmentStatus'] = $this->_lateAssessmentStatus;
        $this->_GRID['demand']['lateAssessmentPenalty'] = $fine;

        $taxes = collect($this->_GRID['demand'])->only(['totalTax', 'totalOnePercPenalty', 'lateAssessmentPenalty']);   // All Penalties are Added
        $totalDemandAmount = $taxes->sum();                                                                             // Total Demand with Penalty
        $this->_GRID['demand']['adjustAmount'] = 0;
        $this->_GRID['demand']['totalDemand'] = roundFigure($totalDemandAmount);
        $totalDemand = $this->_GRID['demand']['totalDemand'];
        $this->_GRID['demand']['payableAmount'] = number_format(round($totalDemand), 2);
    }

    /**
     * | Check Late Assessment Status
     */
    public function calcLateAssessmentFee()
    {
        $fine = 0;
        // Check Late Assessment Penalty for Building
        if ($this->_propertyDetails['propertyType'] != $this->_vacantPropertyTypeId) {
            $floorDetails = collect($this->_floors);
            $lateAssementFloors = $floorDetails->filter(function ($value, $key) {                           // Collection of floors which have late Assessment
                if (!isset($value['propFloorDetailId'])) {                                                  // For This Floor Which is Not Existing Case of Reassessment
                    $currentDate = Carbon::now()->format('Y-m-d');
                    $toDate = Carbon::parse($currentDate);
                    $dateFrom = Carbon::createFromFormat('Y-m-d', $value['dateFrom']);
                    $fromDate = Carbon::parse($dateFrom->format('Y-m-d'));
                    $usageType = $value['useType'];
                    if ($usageType != $this->_religiousPlaceUsageType) {                                                                     // Late Assessment Not Applicable for the Religious Floors
                        $floorAhead3Months = $fromDate->addMonth(3)->format('Y-m-d');
                        $lateStatus = $toDate >= $floorAhead3Months;
                        return $lateStatus;
                    }
                }
            });
            $this->_lateAssessmentStatus = $lateAssementFloors->isEmpty() == true ? false : true;

            // Late Assessment Penalty
            if ($this->_lateAssessmentStatus == true)
                $fine = $this->_isResidential == true ? 2000 : 5000;
        }

        // Check Late Assessment Penalty for Vacant Land
        if ($this->_propertyDetails['propertyType'] == $this->_vacantPropertyTypeId) {
            $currentDate = Carbon::now();
            if (!isset($this->_propertyDetails['landOccupationDate']))
                throw new Exception("Property Land Occupancy Date Not Available");
            $dateFrom = Carbon::createFromFormat('Y-m-d', $this->_propertyDetails['landOccupationDate']);
            $floorAhead3Months = $dateFrom->addMonth(3)->format('Y-m-d');
            $this->_lateAssessmentStatus = $currentDate >= $floorAhead3Months;
            if ($this->_lateAssessmentStatus == true)
                $fine = $this->_isResidential == true ? 2000 : 5000;
        }
        return $fine;
    }

    /**
     * | Summary Preview for The Saf Tax Calculation
     */
    public function summarySafCalculation()
    {
        $propertyTypeId = $this->_propertyDetails['propertyType'];                                      // i.e Property Type Building
        if ($propertyTypeId != $this->_vacantPropertyTypeId) {
            $ruleSets = [
                "Annual Rental Value - As Per Old Rule (Effect Upto 31-03-2016)" => [                   // RuleSet1
                    "Annual Rental Value(ARV)" => "BuiltUpArea x Rental value",
                    "After calculating the A.R.V. the rebates are allowed in following manner :-" =>
                    [
                        "Holding older than 25 years (as on 1967-68)" => "10% Own occupation",
                        "Residential" => "30%",
                        "Commercial" => "15%"
                    ],
                    "Tax at the following rates are imposed on the claculated ARV as per old rule" => [
                        "Holding tax" => "12.5%",
                        "Latrine tax" => "7.5%",
                        "Water tax" => "7.5%",
                        "Health cess" => "6.25%",
                        "Education cess" => "5.0%"
                    ],
                    "Calculated Quaterly Tax" => "(Yearly Tax) ÷ 4"
                ],
                "Annual Rental Value - As ARV Rule (Effect From 01-04-2016 to 31-03-2022)" => [          // RuleSet2
                    "Carpet Area" => [
                        "Residential" => "70% of Builtup Area",
                        "Commercial" => "80% Of Builtup Area"
                    ],
                    "Annual Rental Value (ARV)" => "Carpet Area X Usage Factor X Occupancy Factor X Rental Rate",
                    "Total Quarterly Tax Details" => "((ARV X 2%) ÷ 4)"
                ],
                "Capital Value - As Per Current Rule (Effect From 01-04-2022)" => [                      // RuleSet 3
                    "Tax Percentage" => [
                        "Residential" => 0.075,
                        "Commercial" => 0.150,
                        "Commercial & greater than 25000 sqft" => 0.20
                    ],
                    "Property Tax" => "Circle Rate X Buildup Area X Occupancy Factor X Tax Percentage X Calculation Factor X Matrix Factor Rate (Only in case of 100% residential property)"
                ]
            ];
        }

        if ($propertyTypeId == $this->_vacantPropertyTypeId) {                              // i.e Property Type is Vacant Land
            $ruleSets = [
                "Tax - As Per Old Rule (Effect From 01-04-2016 to 31-03-2022)" => [         // Rule 1
                    "Tax" => "Area (sqmt) X Rental Rate X Occupancy Factor",
                    "taxes calculated on quarterly basis" => "Yearly Tax ÷ 4"
                ],
                "Tax - As Per Current Rule (Effect From 01-04-2022)" => [                   // Rule 2
                    "Tax" => " Area (sqm) X Rental Rate X Occupancy Factor",
                    "Taxes calculated on quarterly basis" => "(Yearly Tax ÷ 4)"
                ]

            ];
        }
        return $ruleSets;
    }
}
