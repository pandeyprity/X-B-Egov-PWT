<?php

namespace App\Http\Controllers\Property;

use App\BLL\Property\CalculateSafById;
use App\BLL\Property\GenerateSafApplyDemandResponse;
use App\BLL\Property\PostSafPropTaxes;
use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\ReqGBSaf;
use App\Models\Property\PropActiveGbOfficer;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Auth\EloquentAuthRepository;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-16-03-2023 
 * | Created By-Anshu Kumar
 * | Created For
 *      - Apply Saf 
 *      - Apply GB Saf
 * | Status-Closed
 */

class ApplySafController extends Controller
{
    use SAF;
    use Workflow;

    protected $_todayDate;
    protected $_REQUEST;
    protected $_safDemand;
    public $_generatedDemand;
    protected $_propProperty;
    public $_holdingNo;
    protected $_citizenUserType;
    protected $_currentFYear;
    protected $_penaltyRebateCalc;
    protected $_currentQuarter;
    private $_demandAdjustAssessmentTypes;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
        $this->_safDemand = new PropSafsDemand();
        $this->_propProperty = new PropProperty();
        $this->_citizenUserType = Config::get('workflow-constants.USER_TYPES.1');
        $this->_currentFYear = getFY();
        $this->_penaltyRebateCalc = new PenaltyRebateCalculation;
        $this->_currentQuarter = calculateQtr($this->_todayDate->format('Y-m-d'));
        $this->_demandAdjustAssessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
    }
    /**
     * | Created On-17-02-2022 
     * | Created By-Anshu Kumar
     * | --------------------------- Workflow Parameters ---------------------------------------
     * |                                 # SAF New Assessment
     * | wf_master id=4 
     * | wf_workflow_id=4
     * |                                 # SAF Reassessment 
     * | wf_mstr_id=5
     * | wf_workflow_id=3
     * |                                 # SAF Mutation
     * | wf_mstr_id=9
     * | wf_workflow_id=5
     * |                                 # SAF Bifurcation
     * | wf_mstr_id=25
     * | wf_workflow_id=182
     * |                                 # SAF Amalgamation
     * | wf_mstr_id=373
     * | wf_workflow_id=381
     * | Created For- Apply for New Assessment, Reassessment, Mutation, Bifurcation and Amalgamation
     * | Status-Open
     */
    /**
     * | Apply for New Application(2)
     * | Status-Closed
     * | Query Costing-500 ms
     * | Rating-5
     */
    public function applySaf(reqApplySaf $request)
    {
        try {
            // Variable Assignments
            $mApplyDate = Carbon::now()->format("Y-m-d");
            $user = authUser($request);
            $user_id = $user->id;
            $ulb_id = $request->ulbId ?? $user->ulb_id;
            $userType = $user->user_type;
            $metaReqs = array();
            $saf = new PropActiveSaf();
            $mOwner = new PropActiveSafsOwner();
            $safCalculation = new SafCalculation();
            $calculateSafById = new CalculateSafById;
            $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse;
            // Derivative Assignments
            $ulbWorkflowId = $this->readAssessUlbWfId($request, $ulb_id);           // (2.1)
            $roadWidthType = $this->readRoadWidthType($request->roadType);          // Read Road Width Type

            $request->request->add(['road_type_mstr_id' => $roadWidthType]);

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
            $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();
            if (is_null($initiatorRoleId))
                throw new Exception("Initiator Role Not Available");
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
            if (is_null($finisherRoleId))
                throw new Exception("Finisher Role Not Available");

            $metaReqs['roadWidthType'] = $roadWidthType;
            $metaReqs['workflowId'] = $ulbWorkflowId->id;
            $metaReqs['ulbId'] = $ulb_id;
            $metaReqs['userId'] = $user_id;
            $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['role_id'];
            if ($userType == $this->_citizenUserType) {
                $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['forward_role_id'];         // Send to DA in Case of Citizen
                $metaReqs['userId'] = null;
                $metaReqs['citizenId'] = $user_id;
            }
            $metaReqs['finisherRoleId'] = collect($finisherRoleId)['role_id'];
            $safTaxes = $safCalculation->calculateTax($request);

            $metaReqs['isTrust'] = $this->isPropTrust($request['floor']);
            $metaReqs['holdingType'] = $this->holdingType($request['floor']);
            $request->merge($metaReqs);
            $this->_REQUEST = $request;
            $this->mergeAssessedExtraFields();                                          // Merge Extra Fields for Property Reassessment,Mutation,Bifurcation & Amalgamation(2.2)
            // Generate Calculation
            $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
            $calculateSafById->_safDetails['assessment_type'] = $request->assessmentType;
            $calculateSafById->_safDetails['previous_holding_id'] = $request->previousHoldingId;

            if (isset($request->holdingNo))
                $calculateSafById->_holdingNo = $request->holdingNo;
            $calculateSafById->_currentQuarter = calculateQtr($mApplyDate);
            $firstOwner = collect($request['owner'])->first();
            $calculateSafById->_firstOwner = [
                'gender' => $firstOwner['gender'],
                'dob' => $firstOwner['dob'],
                'is_armed_force' => $firstOwner['isArmedForce'],
                'is_specially_abled' => $firstOwner['isSpeciallyAbled'],
            ];
            $calculateSafById->generateSafDemand();
            $generatedDemand = $calculateSafById->_generatedDemand;
            $isResidential = $safTaxes->original['data']['demand']['isResidential'];
            $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);

            DB::beginTransaction();
            $createSaf = $saf->store($request);                                         // Store SAF Using Model function 
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // SAF Owner Details
            if ($request['owner']) {
                $ownerDetail = $request['owner'];
                if ($request->assessmentType == 'Mutation')                             // In Case of Mutation Avert Existing Owner Detail
                    $ownerDetail = collect($ownerDetail)->where('propOwnerDetailId', null);
                foreach ($ownerDetail as $ownerDetails) {
                    $mOwner->addOwner($ownerDetails, $safId, $user_id);
                }
            }

            // Floor Details
            if ($request->propertyType != 4) {
                if ($request['floor']) {
                    $floorDetail = $request['floor'];
                    foreach ($floorDetail as $floorDetails) {
                        $floor = new PropActiveSafsFloor();
                        $floor->addfloor($floorDetails, $safId, $user_id);
                    }
                }
            }
            // Citizen Notification
            // if ($userType == 'Citizen') {
            //     $mreq['userType']  = 'Citizen';
            //     $mreq['citizenId'] = $user_id;
            //     $mreq['category']  = 'Recent Application';
            //     $mreq['ulbId']     = $ulb_id;
            //     $mreq['ephameral'] = 0;
            //     $mreq['notification'] = "Successfully Submitted Your Application Your SAF No. $safNo";
            //     $rEloquentAuthRepository = new EloquentAuthRepository();
            //     $rEloquentAuthRepository->addNotification($mreq);
            // }
            DB::commit();
            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => ymdToDmyDate($mApplyDate),
                "safId" => $safId,
                "demand" => $demandResponse
            ], "010102", "1.0", "1s", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010102", "1.0", "1s", "POST", $request->deviceId);
        }
    }

    /**
     * | Read Assessment Type and Ulb Workflow Id(2.1)
     */
    public function readAssessUlbWfId($request, $ulb_id)
    {
        if ($request->assessmentType == 1) {                                                    // New Assessment 
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
        }

        if ($request->assessmentType == 2) {                                                    // Reassessment
            $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
        }

        if ($request->assessmentType == 3) {                                                    // Mutation
            $workflow_id = Config::get('workflow-constants.SAF_MUTATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.3');
        }

        if ($request->assessmentType == 4) {                                                    // Bifurcation
            $workflow_id = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.4');
        }

        if ($request->assessmentType == 5) {                                                    // Amalgamation
            $workflow_id = Config::get('workflow-constants.SAF_AMALGAMATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.5');
        }

        return WfWorkflow::where('wf_master_id', $workflow_id)
            ->where('ulb_id', $ulb_id)
            ->first();
    }

    /**
     * | Merge Extra Fields in request for Reassessment,Mutation,Etc
     */
    public function mergeAssessedExtraFields()
    {
        $mPropProperty = new PropProperty();
        $req = $this->_REQUEST;
        $assessmentType = $req->assessmentType;

        if (in_array($assessmentType, $this->_demandAdjustAssessmentTypes)) {           // Reassessment,Mutation and Others
            $property = $mPropProperty->getPropById($req->previousHoldingId);
            if (collect($property)->isEmpty())
                throw new Exception("Property Not Found For This Holding");
            $req->holdingNo = $property->new_holding_no ?? $property->holding_no;
            $propId = $property->id;
            $req->merge([
                'hasPreviousHoldingNo' => true,
                'previousHoldingId' => $propId
            ]);
            switch ($assessmentType) {
                case "Reassessment":                                 // Bifurcation
                    $req->merge([
                        'propDtl' => $propId
                    ]);
                    break;
            }
        }

        // Amalgamation
        if (in_array($assessmentType, ["Amalgamation"])) {
            $previousHoldingIds = array();
            $previousHoldingLists = array();

            foreach ($req->holdingNoLists as $holdingNoList) {
                $propDtls = $mPropProperty->getPropertyId($holdingNoList);
                if (!$propDtls)
                    throw new Exception("Property Not Found For the holding");
                $propId = $propDtls->id;
                array_push($previousHoldingIds, $propId);
                array_push($previousHoldingLists, $holdingNoList);
            }

            $req->merge([
                'hasPreviousHoldingNo' => true,
                'previousHoldingId' => implode(",", $previousHoldingIds),
                'holdingNo' => implode(",", $req->holdingNoLists)
            ]);
        }
    }

    /**
     * | Demand Adjustment In Case of Reassessment
     */
    public function adjustDemand()
    {
        $propDemandList = array();
        $mSafDemand = $this->_safDemand;
        $generatedDemand = $this->_generatedDemand;
        $propProperty = $this->_propProperty;
        $holdingNo = $this->_holdingNo;
        $mPropDemands = new PropDemand();
        $propDtls = $propProperty->getSafIdByHoldingNo($holdingNo);
        $propertyId = $propDtls->id;
        $safDemandList = $mSafDemand->getFullDemandsBySafId($propDtls->saf_id);
        if ($safDemandList->isEmpty())
            throw new Exception("Previous Saf Demand is Not Available");

        $propDemandList = $mPropDemands->getPaidDemandByPropId($propertyId);
        $fullDemandList = $safDemandList->merge($propDemandList);
        $generatedDemand = $generatedDemand->sortBy('due_date');

        // Demand Adjustment
        foreach ($generatedDemand as $item) {
            $demand = $fullDemandList->where('due_date', $item['dueDate'])->first();
            if (collect($demand)->isEmpty())
                $item['adjustAmount'] = 0;
            else
                $item['adjustAmount'] = $demand->amount - $demand->balance;
            $item['balance'] = roundFigure($item['totalTax'] - $item['adjustAmount']);
            if ($item['balance'] == 0)
                $item['onePercPenaltyTax'] = 0;
        }
        return $generatedDemand;
    }

    /**
     * | Apply GB Saf
     */
    public function applyGbSaf(ReqGBSaf $req)
    {
        try {
            // Variable Assignments
            $user = authUser($req);
            $userId = $user->id;
            $userType = $user->user_type;
            $ulbId = $req->ulbId ?? $user->ulb_id;
            $propActiveSafs = new PropActiveSaf();
            $safCalculation = new SafCalculation;
            $mPropFloors = new PropActiveSafsFloor();
            $mPropGbOfficer = new PropActiveGbOfficer();
            $safReq = array();
            $reqFloors = $req->floors;
            $applicationDate = $this->_todayDate->format('Y-m-d');
            $assessmentId = $req->assessmentType;
            $calculateSafById = new CalculateSafById;
            $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse;
            $insertTax = new InsertTax;
            $postSafPropTax = new PostSafPropTaxes;

            // Derivative Assignments
            $ulbWfId = $this->readGbAssessUlbWfId($req, $ulbId);
            $roadWidthType = $this->readRoadWidthType($req->roadWidth);                               // Read Road Width Type
            $refInitiatorRoleId = $this->getInitiatorId($ulbWfId->id);                                // Get Current Initiator ID
            $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();

            $refFinisherRoleId = $this->getFinisherId($ulbWfId->id);
            $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
            $req = $req->merge(
                [
                    'road_type_mstr_id' => $roadWidthType,
                    'ward' => $req->wardId,
                    'propertyType' => 1,
                    'roadType' => $req->roadWidth,
                    'floor' => $req->floors,
                    'isGBSaf' => true
                ]
            );

            $safTaxes = $safCalculation->calculateTax($req);
            // Generate Calculation
            $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
            $calculateSafById->_safDetails['assessment_type'] = $assessmentId;

            if (isset($req->holdingNo))
                $calculateSafById->_holdingNo = $req->holdingNo;
            $calculateSafById->_currentQuarter = calculateQtr($applicationDate);
            $calculateSafById->generateSafDemand();
            $generatedDemand = $calculateSafById->_generatedDemand;
            $isResidential = $safTaxes->original['data']['demand']['isResidential'];
            $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);
            $demandToBeSaved = $demandResponse['details']->values()->collapse();
            $lateAssessmentPenalty = $demandResponse['amounts']['lateAssessmentPenalty'];
            $lateAssessmentPenalty = ($lateAssessmentPenalty > 0) ? $lateAssessmentPenalty : null;
            // Send to Workflow
            $currentRole = ($userType == $this->_citizenUserType) ? $initiatorRoleId->role_id : $initiatorRoleId->role_id;
            $isTrust = $this->isPropTrust($req['floor']);

            $safReq = [
                'assessment_type' => $req->assessmentType,
                'ulb_id' => $ulbId,
                'prop_type_mstr_id' => 2,               // Independent Building
                'building_name' => $req->buildingName,
                'gb_office_name' => $req->nameOfOffice,
                'ward_mstr_id' => $req->wardId,
                'prop_address' => $req->buildingAddress,
                'gb_usage_types' => $req->gbUsageTypes,
                'gb_prop_usage_types' => $req->gbPropUsageTypes,
                'zone_mstr_id' => $req->zone,
                'road_width' => $req->roadWidth,
                'road_type_mstr_id' => $roadWidthType,
                'is_mobile_tower' => $req->isMobileTower,
                'tower_area' => $req->mobileTower['area'] ?? null,
                'tower_installation_date' => $req->mobileTower['dateFrom'] ?? null,

                'is_hoarding_board' => $req->isHoardingBoard,
                'hoarding_area' => $req->hoardingBoard['area'] ?? null,
                'hoarding_installation_date' => $req->hoardingBoard['dateFrom'] ?? null,


                'is_petrol_pump' => $req->isPetrolPump,
                'under_ground_area' => $req->petrolPump['area'] ?? null,
                'petrol_pump_completion_date' => $req->petrolPump['dateFrom'] ?? null,

                'is_water_harvesting' => $req->isWaterHarvesting,
                'area_of_plot' => $req->areaOfPlot,
                'is_gb_saf' => true,
                'application_date' => $applicationDate,
                'initiator_role_id' => $currentRole,
                'current_role' => $currentRole,
                'finisher_role_id' => $finisherRoleId->role_id,
                'workflow_id' => $ulbWfId->wf_master_id,
                'is_trust' => $isTrust,
                'trust_type' => $req->trustType ?? null
            ];
            DB::beginTransaction();
            $createSaf = $propActiveSafs->storeGBSaf($safReq);           // Store Saf
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // Store Floors
            foreach ($reqFloors as $floor) {
                $mPropFloors->addfloor($floor, $safId, $userId);
            }


            // Insert Officer Details
            $gbOfficerReq = [
                'saf_id' => $safId,
                'officer_name' => strtoupper($req->officerName),
                'designation' => strtoupper($req->designation),
                'mobile_no' => $req->officerMobile,
                'email' => $req->officerEmail,
                'address' => $req->address,
                'ulb_id' => $ulbId
            ];
            $mPropGbOfficer->store($gbOfficerReq);
            $this->sendToWorkflow($createSaf, $userId);
            // Demand Saved
            $insertTax->insertTax($safId, $ulbId, $demandToBeSaved, $userId);
            $postSafPropTax->postSafTaxes($safId, $generatedDemand['details']->toArray(), $ulbId);                        // Saf Tax Generation

            DB::commit();
            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => Carbon::parse($applicationDate)->format('d-m-Y'),
                "safId" => $safId,
                "demand" => $demandResponse
            ], "010102", "1.0", "1s", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010103", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read GB Assessment Type and Ulb Workflow Id
     */
    public function readGbAssessUlbWfId($request, $ulb_id)
    {
        if ($request->assessmentType == 1) {                                                    // New Assessment 
            $workflow_id = Config::get('workflow-constants.GBSAF_NEW_ASSESSMENT');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
        }

        if ($request->assessmentType == 2) {                                                    // Reassessment
            $workflow_id = Config::get('workflow-constants.GBSAF_REASSESSMENT');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
        }

        return WfWorkflow::where('wf_master_id', $workflow_id)
            ->where('ulb_id', $ulb_id)
            ->first();
    }

    /**
     * | Send to Workflow Level
     */
    public function sendToWorkflow($activeSaf, $userId)
    {
        $mWorkflowTrack = new WorkflowTrack();
        $todayDate = $this->_todayDate;
        $refTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
        $reqWorkflow = [
            'workflow_id' => $activeSaf->original['workflow_id'],
            'ref_table_dot_id' => $refTable,
            'ref_table_id_value' => $activeSaf->original['safId'],
            'track_date' => $todayDate->format('Y-m-d h:i:s'),
            'module_id' => Config::get('module-constants.PROPERTY_MODULE_ID'),
            'user_id' => $userId,
            'receiver_role_id' => $activeSaf->original['current_role'],
            'ulb_id' => $activeSaf->original['ulb_id'],
        ];
        $mWorkflowTrack->store($reqWorkflow);
    }
}
