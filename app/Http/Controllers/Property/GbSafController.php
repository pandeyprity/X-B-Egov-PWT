<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqGbSiteVerification;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\HoldingNoGenerator;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\MicroServices\IdGenerator\PropIdGenerator;
use App\Models\CustomDetail;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Property\PropActiveGbOfficer;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropGbofficer;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Pipelines\GbSafInbox\GbSafByApplicationNo;
use App\Pipelines\GbSafInbox\GbSafByMobileNo;
use App\Pipelines\GbSafInbox\GbSafByName;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDetailsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Pipeline\Pipeline;

/**
 * | Created On-13-03-2023
 * | Created by-Mrinal Kumar
 * | GB SAF Workflow
 */

class GbSafController extends Controller
{
    use SAF;
    use SafDetailsTrait;

    /**
     * | Inbox for GB Saf
     */
    public function inbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                       // Model () to get Occupied Wards of Current User
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safDtl = $mpropActiveSafs->getGbSaf($workflowIds)                                          // Repository function to get SAF Details
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id');

            $safInbox = app(Pipeline::class)
                ->send(
                    $safDtl
                )
                ->through([
                    GbSafByApplicationNo::class,
                    GbSafByMobileNo::class,
                    GbSafByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($safInbox), "010103", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Outbox for GB Saf
     */
    public function outbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wardId = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safData = $mpropActiveSafs->getGbSaf($workflowIds)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id');

            $safOutbox = app(Pipeline::class)
                ->send(
                    $safData
                )
                ->through([
                    GbSafByApplicationNo::class,
                    GbSafByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($safOutbox), "010104", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Fields Verified Inbox
     */
    public function fieldVerifiedInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $mUserId = authUser($req)->id;
            $mUlbId = authUser($req)->ulb_id;
            $mDeviceId = $req->deviceId ?? "";
            $perPage = $req->perPage ?? 10;

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list
            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $mpropActiveSafs->getGbSaf($workflowIds)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->perPage($perPage);

            return responseMsgs(true, "field Verified Inbox!", remove_null($safInbox), 010125, 1.0, responseTime(), "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $mDeviceId);
        }
    }

    /**
     * | Post next level
     */
    public function postNextLevel(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
            'receiverRoleId' => 'nullable|integer',
            'action' => 'required|In:forward,backward'
        ]);

        try {
            // Variable Assigments
            $userId = authUser($request)->id;
            $wfLevels = Config::get('PropertyConstaint.GBSAF-LABEL');
            $saf = PropActiveSaf::findOrFail($request->applicationId);
            $mWfMstr = new WfWorkflow();
            $track = new WorkflowTrack();
            $mWfWorkflows = new WfWorkflow();
            $mWfRoleMaps = new WfWorkflowrolemap();
            $samHoldingDtls = array();

            // Derivative Assignments
            $senderRoleId = $saf->current_role;
            $request->validate([
                'comment' => $senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',

            ]);
            $ulbWorkflowId = $saf->workflow_id;
            $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);
            DB::beginTransaction();
            if ($request->action == 'forward') {
                $wfMstrId = $mWfMstr->getWfMstrByWorkflowId($saf->workflow_id);
                $samHoldingDtls = $this->checkPostCondition($senderRoleId, $wfLevels, $saf, $userId);          // Check Post Next level condition
                $saf->current_role = $forwardBackwardIds->forward_role_id;
                $saf->last_role_id =  $forwardBackwardIds->forward_role_id;                     // Update Last Role Id
                $metaReqs['verificationStatus'] = 1;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
            }
            // SAF Application Update Current Role Updation
            if ($request->action == 'backward') {
                $saf->current_role = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->backward_role_id;
            }

            $saf->save();
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = Config::get('PropertyConstaint.SAF_REF_TABLE');
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;

            $request->request->add($metaReqs);

            $track->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", $samHoldingDtls, "010109", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", "010109", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | check Post Condition for backward forward(9.1)
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $saf, $userId)
    {
        // Variable Assigments
        $mPropSafDemand = new PropSafsDemand();
        $mPropMemoDtl = new PropSafMemoDtl();
        $todayDate = Carbon::now()->format('Y-m-d');
        $fYear = calculateFYear($todayDate);
        $ptParamId = Config::get('PropertyConstaint.PT_PARAM_ID');
        $propIdGenerator = new PropIdGenerator;
        $holdingNoGenerator = new HoldingNoGenerator;

        // Derivative Assignments

        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($saf->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;

            case $wfLevels['DA']:                       // DA Condition
                if ($saf->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                $demand = $mPropSafDemand->getFirstDemandByFyearSafId($saf->id, $fYear);
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for the Current Year to Generate SAM");
                $idGeneration = new PrefixIdGenerator($ptParamId, $saf->ulb_id);
                // Holding No Generation
                $holdingNo = $holdingNoGenerator->generateHoldingNo($saf);
                $ptNo = $idGeneration->generate();
                $saf->pt_no = $ptNo;                        // Generate New Property Tax No for All Conditions
                $saf->holding_no = $holdingNo;
                $saf->save();

                $samNo = $propIdGenerator->generateMemoNo("SAM", $saf->ward_mstr_id, $demand->fyear);           // Sam No Generation

                $mergedDemand = array_merge($demand->toArray(), [
                    'holding_no' => $saf->holding_no,
                    'memo_type' => 'SAM',
                    'memo_no' => $samNo,
                    'pt_no' => $ptNo,
                    'ward_id' => $saf->ward_mstr_id,
                    'userId'  => $userId
                ]);
                $memoReqs = new Request($mergedDemand);
                $mPropMemoDtl->postSafMemoDtls($memoReqs);
                $this->replicateSaf($saf->id);
                break;

            case $wfLevels['TC']:
                if ($saf->is_geo_tagged == false)
                    throw new Exception("Geo Tagging Not Done");
                break;
            case $wfLevels['UTC']:
                if ($saf->is_field_verified == false)
                    throw new Exception("Field Verification Not Done");
                break;
        }
        return [
            'holdingNo' =>  $saf->holding_no ?? "",
            'samNo' => $samNo ?? ""
        ];
    }

    /**
     * | Replicate Tables of saf to property
     */
    public function replicateSaf($safId)
    {
        $activeSaf = PropActiveSaf::query()
            ->where('id', $safId)
            ->first();
        $officerDetails = PropActiveGbOfficer::query()
            ->where('saf_id', $safId)
            ->first();
        $floorDetails = PropActiveSafsFloor::query()
            ->where('saf_id', $safId)
            ->get();

        $toBeProperties = PropActiveSaf::query()
            ->where('id', $safId)
            ->select(
                'saf_no',
                'ulb_id',
                'cluster_id',
                'holding_no',
                'applicant_name',
                'ward_mstr_id',
                'ownership_type_mstr_id',
                'prop_type_mstr_id',
                'appartment_name',
                'no_electric_connection',
                'elect_consumer_no',
                'elect_acc_no',
                'elect_bind_book_no',
                'elect_cons_category',
                'building_plan_approval_no',
                'building_plan_approval_date',
                'water_conn_no',
                'water_conn_date',
                'khata_no',
                'plot_no',
                'village_mauja_name',
                'road_type_mstr_id',
                'road_width',
                'area_of_plot',
                'prop_address',
                'prop_city',
                'prop_dist',
                'prop_pin_code',
                'prop_state',
                'corr_address',
                'corr_city',
                'corr_dist',
                'corr_pin_code',
                'corr_state',
                'is_mobile_tower',
                'tower_area',
                'tower_installation_date',
                'is_hoarding_board',
                'hoarding_area',
                'hoarding_installation_date',
                'is_petrol_pump',
                'under_ground_area',
                'petrol_pump_completion_date',
                'is_water_harvesting',
                'land_occupation_date',
                'new_ward_mstr_id',
                'zone_mstr_id',
                'flat_registry_date',
                'assessment_type',
                'holding_type',
                'apartment_details_id',
                'ip_address',
                'status',
                'user_id',
                'citizen_id',
                'pt_no',
                'building_name',
                'street_name',
                'location',
                'landmark',
                'is_gb_saf',
                'gb_office_name',
                'gb_usage_types',
                'gb_prop_usage_types',
                'is_trust',
                'trust_type',
                'is_trust_verified',
                'rwh_date_from'
            )->first();

        $assessmentType = $activeSaf->assessment_type;

        if (in_array($assessmentType, ['New Assessment'])) { // Make New Property For New Assessment
            $propProperties = $toBeProperties->replicate();
            $propProperties->setTable('prop_properties');
            $propProperties->saf_id = $activeSaf->id;
            $propProperties->new_holding_no = $activeSaf->holding_no;
            $propProperties->save();

            // SAF Officer replication
            $approvedOfficers = $officerDetails->replicate();
            $approvedOfficers->setTable('prop_gbofficers');
            $approvedOfficers->id = $officerDetails->id;
            $approvedOfficers->property_id = $propProperties->id;
            $approvedOfficers->save();

            // SAF Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $propFloor = $floorDetail->replicate();
                $propFloor->setTable('prop_floors');
                $propFloor->property_id = $propProperties->id;
                $propFloor->save();
            }
        }

        // Edit In Case of Reassessment,Mutation
        if (in_array($assessmentType, ['Reassessment'])) {         // Edit Property In case of Reassessment
            $propId = $activeSaf->previous_holding_id;
            $mProperty = new PropProperty();
            $mPropOfficer = new PropGbofficer();
            $mPropFloors = new PropFloor();
            // Edit Property
            $mProperty->editPropBySaf($propId, $activeSaf);

            // Edit Owners 
            $ifOwnerExist = $mPropOfficer->getPropOfficerByOfficerId($officerDetails->id);
            $officerDetail = array_merge($officerDetails->toArray(), ['property_id' => $propId]);
            $officerDetail = new Request($officerDetail);
            if ($ifOwnerExist)
                $mPropOfficer->editOfficer($officerDetail);
            else
                $mPropOfficer->postOfficer($officerDetail);

            // Edit Floors
            foreach ($floorDetails as $floorDetail) {
                $ifFloorExist = $mPropFloors->getFloorByFloorId($floorDetail->prop_floor_details_id);
                $floorReqs = new Request([
                    'floor_mstr_id' => $floorDetail->floor_mstr_id,
                    'usage_type_mstr_id' => $floorDetail->usage_type_id,
                    'const_type_mstr_id' => $floorDetail->construction_type_id,
                    'occupancy_type_mstr_id' => $floorDetail->occupancy_type_id,
                    'builtup_area' => $floorDetail->builtup_area,
                    'date_from' => $floorDetail->date_from,
                    'date_upto' => $floorDetail->date_to,
                    'carpet_area' => $floorDetail->carpet_area,
                    'property_id' => $propId,
                    'saf_id' => $safId

                ]);
                if ($ifFloorExist) {
                    $mPropFloors->editFloor($ifFloorExist, $floorReqs);
                } else
                    $mPropFloors->postFloor($floorReqs);
            }
        }
    }

    /**
     * | Site Verification
     */
    public function siteVerification(ReqGbSiteVerification $req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.GBSAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.GBSAF-LABEL.UTC');
            $propActiveSaf = new PropActiveSaf();
            $verification = new PropSafVerification();
            $mWfRoleUsermap = new WfRoleusermap();
            $verificationDtl = new PropSafVerificationDtl();
            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;

            $safDtls = $propActiveSaf->getSafNo($req->safId);
            $workflowId = $safDtls->workflow_id;
            $roadWidthType = $this->readRoadWidthType($req->roadWidth);                                 // Read Road Width Type by Trait
            $getRoleReq = new Request([                                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);

            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            DB::beginTransaction();
            switch ($roleId) {
                case $taxCollectorRole:                                                                  // In Case of Agency TAX Collector
                    $req->agencyVerification = true;
                    $req->ulbVerification = false;
                    $msg = "Site Successfully Verified";
                    break;
                case $ulbTaxCollectorRole:                                                                // In Case of Ulb Tax Collector
                    $req->agencyVerification = false;
                    $req->ulbVerification = true;
                    $msg = "Site Successfully Verified";
                    $propActiveSaf->verifyFieldStatus($req->safId);                                         // Enable Fields Verify Status
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }
            $req->merge(['roadType' => $roadWidthType, 'userId' => $userId, 'ulbId' => $ulbId]);
            // Verification Store
            $verificationId = $verification->store($req);                            // Model function to store verification and get the id
            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floor as $floorDetail) {
                if ($floorDetail['useType'] == 1)
                    $carpetArea =  $floorDetail['buildupArea'] * 0.70;
                else
                    $carpetArea =  $floorDetail['buildupArea'] * 0.80;

                $floorReq = [
                    'verification_id' => $verificationId,
                    'saf_id' => $req->safId,
                    'saf_floor_id' => $floorDetail['floorId'] ?? null,
                    'floor_mstr_id' => $floorDetail['floorNo'],
                    'usage_type_id' => $floorDetail['useType'],
                    'construction_type_id' => $floorDetail['constructionType'],
                    'occupancy_type_id' => $floorDetail['occupancyType'],
                    'builtup_area' => $floorDetail['buildupArea'],
                    'date_from' => $floorDetail['dateFrom'],
                    'date_to' => $floorDetail['dateUpto'],
                    'carpet_area' => $carpetArea,
                    'user_id' => $userId,
                    'ulb_id' => $ulbId
                ];
                $verificationDtl->store($floorReq);
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Geo tagging
     */
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|numeric",
            "imagePath" => "required|array|min:3|max:3",
            "imagePath.*" => "required|image|mimes:jpeg,jpg,png,gif",
            "directionType" => "required|array|min:3|max:3",
            "directionType.*" => "required|In:Left,Right,Front",
            "longitude" => "required|array|min:3|max:3",
            "longitude.*" => "required|numeric",
            "latitude" => "required|array|min:3|max:3",
            "latitude.*" => "required|numeric"
        ]);
        try {
            $docUpload = new DocUpload;
            $geoTagging = new PropSafGeotagUpload();
            $relativePath = Config::get('PropertyConstaint.GEOTAGGING_RELATIVE_PATH');
            $safDtls = PropActiveSaf::findOrFail($req->safId);
            $images = $req->imagePath;
            $directionTypes = $req->directionType;
            $longitude = $req->longitude;
            $latitude = $req->latitude;

            DB::beginTransaction();
            collect($images)->map(function ($image, $key) use ($directionTypes, $relativePath, $req, $docUpload, $longitude, $latitude, $geoTagging) {
                $refImageName = 'saf-geotagging-' . $directionTypes[$key] . '-' . $req->safId;
                $docExistReqs = new Request([
                    'safId' => $req->safId,
                    'directionType' => $directionTypes[$key]
                ]);
                $imageName = $docUpload->upload($refImageName, $image, $relativePath);         // <------- Get uploaded image name and move the image in folder
                $isDocExist = $geoTagging->getGeoTagBySafIdDirectionType($docExistReqs);

                $docReqs = [
                    'saf_id' => $req->safId,
                    'image_path' => $imageName,
                    'direction_type' => $directionTypes[$key],
                    'longitude' => $longitude[$key],
                    'latitude' => $latitude[$key],
                    'relative_path' => $relativePath,
                    'user_id' => authUser($req)->id
                ];
                if ($isDocExist)
                    $geoTagging->edit($isDocExist, $docReqs);
                else
                    $geoTagging->store($docReqs);
            });

            $safDtls->is_geo_tagged = true;
            $safDtls->save();

            DB::commit();
            return responseMsgs(true, "Geo Tagging Done Successfully", "", "010119", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get The Verification done by Agency Tc
     */
    public function getTcVerifications(Request $req)
    {
        $req->validate([
            'safId' => 'required|numeric'
        ]);
        try {
            $data = array();
            $safVerifications = new PropSafVerification();
            $safVerificationDtls = new PropSafVerificationDtl();
            $mSafGeoTag = new PropSafGeotagUpload();

            $data = $safVerifications->getVerificationsData($req->safId);                       // <--------- Prop Saf Verification Model Function to Get Prop Saf Verifications Data 
            if (collect($data)->isEmpty())
                throw new Exception("Tc Verification Not Done");

            $data = json_decode(json_encode($data), true);

            $verificationDtls = $safVerificationDtls->getFullVerificationDtls($data['id']);     // <----- Prop Saf Verification Model Function to Get Verification Floor Dtls
            $existingFloors = $verificationDtls->where('saf_floor_id', '!=', NULL);
            $newFloors = $verificationDtls->where('saf_floor_id', NULL);
            $data['newFloors'] = $newFloors->values();
            $data['existingFloors'] = $existingFloors->values();
            $geoTags = $mSafGeoTag->getGeoTags($req->safId);
            $data['geoTagging'] = $geoTags;
            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back to citizen
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $saf = PropActiveSaf::find($req->applicationId);
            $track = new WorkflowTrack();
            DB::beginTransaction();
            $senderRoleId = $saf->current_role;
            $initiatorRoleId = $saf->initiator_role_id;
            $saf->current_role = $initiatorRoleId;
            $saf->parked = true;                        //<------ SAF Pending Status true
            $saf->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_safs.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['user_id'] = authUser($req)->id;
            $metaReqs['verificationStatus'] = 2;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "010111", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | BTC Inbox
     */
    public function btcInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();

            $mUserId = authUser($req)->id;
            $mUlbId = authUser($req)->ulb_id;
            $mDeviceId = $req->deviceId ?? "";
            $perPage = $req->perPage ?? 10;

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list
            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safData = $mpropActiveSafs->getGbSaf($workflowIds)
                ->selectRaw(DB::raw(
                    "case when prop_active_safs.citizen_id is not null then 'true'
                      else false end
                      as btc_for_citizen"
                ))
                ->where('parked', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id');

            $btcList = app(Pipeline::class)
                ->send(
                    $safData
                )
                ->through([
                    GbSafByApplicationNo::class,
                    GbSafByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "BTC Inbox List", remove_null($btcList), 010123, 1.0, responseTime(), "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, responseTime(), "POST", $mDeviceId);
        }
    }

    /**
     * | Post Escalate
     */
    public function postEscalate(Request $request)
    {
        $request->validate([
            "escalateStatus" => "required|int",
            "applicationId" => "required|int",
        ]);
        try {
            $userId = authUser($request)->id;
            $saf_id = $request->applicationId;
            $data = PropActiveSaf::find($saf_id);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();
            return responseMsgs(true, $request->escalateStatus == 1 ? 'Saf is Escalated' : "Saf is removed from Escalated", '', "010106", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * | Escalatee Inbox
     */
    public function specialInbox(Request $req)
    {
        try {
            $mWfWardUser = new WfWardUser();
            $mWfRoleUserMaps = new WfRoleusermap();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mpropActiveSafs = new PropActiveSaf();
            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $wardIds = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                        // Get All Occupied Ward By user id using trait
            $roleIds = $mWfRoleUserMaps->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safData = $mpropActiveSafs->getGbSaf($workflowIds)                      // Repository function to get SAF Details
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardIds)
                ->orderByDesc('id');

            $specialList = app(Pipeline::class)
                ->send(
                    $safData
                )
                ->through([
                    GbSafByApplicationNo::class,
                    GbSafByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($specialList), "010107", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Static Saf Details
     */
    public function getStaticSafDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            // Variable Assignments
            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveGbOfficer = new PropActiveGbOfficer();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mPropSafMemoDtls = new PropSafMemoDtl();
            $memoDtls = array();
            $data = array();

            // Derivative Assignments
            $data = $mPropActiveSaf->getActiveSafDtls()                         // <------- Model function Active SAF Details
                ->where('prop_active_safs.id', $req->applicationId)
                ->first();
            if (!$data)
                throw new Exception("Data Not Found");
            $data = json_decode(json_encode($data), true);

            $officerDtls = $mPropActiveGbOfficer->getOfficerBySafId($data['id']);
            $data['officer'] = $officerDtls;
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data['id']);      // Model Function to Get Floor Details
            $data['floors'] = $getFloorDtls;

            $memoDtls = $mPropSafMemoDtls->memoLists($data['id']);
            $data['memoDtls'] = $memoDtls;
            return responseMsgs(true, "Saf Dtls", remove_null($data), "010127", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * | Final Approval Rejection
     */
    public function approvalRejectionGbSaf(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        try {
            // Check if the Current User is Finisher or Not (Variable Assignments)
            $safDetails = PropActiveSaf::findOrFail($req->applicationId);
            $mWfRoleUsermap = new WfRoleusermap();
            $propSafVerification = new PropSafVerification();
            $propSafVerificationDtl = new PropSafVerificationDtl();
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropSafDemand = new PropSafsDemand();
            $mPropProperties = new PropProperty();
            $mPropFloors = new PropFloor();
            $mPropDemand = new PropDemand();
            $todayDate = Carbon::now()->format('Y-m-d');
            $currentFinYear = calculateFYear($todayDate);
            $famParamId = Config::get('PropertyConstaint.FAM_PARAM_ID');
            $propIdGenerator = new PropIdGenerator;

            $userId = authUser($req)->id;
            $safId = $req->applicationId;
            // Derivative Assignments
            $workflowId = $safDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            if ($safDetails->finisher_role_id != $roleId)
                throw new Exception("Forbidden Access");
            $activeSaf = PropActiveSaf::query()
                ->where('id', $req->applicationId)
                ->first();
            $officerDetails = PropActiveGbOfficer::query()
                ->where('saf_id', $safId)
                ->first();
            $floorDetails = PropActiveSafsFloor::query()
                ->where('saf_id', $req->applicationId)
                ->get();

            $propDtls = $mPropProperties->getPropIdBySafId($req->applicationId);
            $propId = $propDtls->id;
            $fieldVerifiedSaf = $propSafVerification->getVerificationsBySafId($safId);
            if (collect($fieldVerifiedSaf)->isEmpty())
                throw new Exception("Site Verification not Exist");

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $safDetails->saf_pending_status = 0;
                $safDetails->save();

                $demand = $mPropDemand->getFirstDemandByFyearPropId($propId, $currentFinYear);
                if (collect($demand)->isEmpty())
                    $demand = $mPropSafDemand->getFirstDemandByFyearSafId($safId, $currentFinYear);
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for the Current Year to Generate FAM");

                // SAF Application replication
                $famNo = $propIdGenerator->generateMemoNo("FAM", $safDetails->ward_mstr_id, $demand->fyear);
                $mergedDemand = array_merge($demand->toArray(), [
                    'memo_type' => 'FAM',
                    'memo_no' => $famNo,
                    'holding_no' => $activeSaf->new_holding_no ?? $activeSaf->holding_no,
                    'pt_no' => $activeSaf->pt_no,
                    'ward_id' => $activeSaf->ward_mstr_id,
                    'prop_id' => $propId,
                    'saf_id' => $safId,
                    'userId'  => $userId
                ]);
                $memoReqs = new Request($mergedDemand);
                $mPropSafMemoDtl->postSafMemoDtls($memoReqs);
                $this->finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $officerDetails, $floorDetails, $mPropFloors, $safId);
                $msg = "Application Approved Successfully";
            }
            // Rejection
            if ($req->status == 0) {
                $this->finalRejectionSafReplica($activeSaf, $officerDetails, $floorDetails);
                $msg = "Application Rejected Successfully";
            }

            $propSafVerification->deactivateVerifications($req->applicationId);                 // Deactivate Verification From Table
            $propSafVerificationDtl->deactivateVerifications($req->applicationId);              // Deactivate Verification from Saf floor Dtls
            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->holding_no], "010110", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Replication of Final Approval SAf(10.1)
     */
    public function finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $officerDetails, $floorDetails, $mPropFloors, $safId)
    {
        $mPropProperties->replicateVerifiedSaf($propId, collect($fieldVerifiedSaf)->first());             // Replicate to Saf Table
        $approvedSaf = $activeSaf->replicate();
        $approvedSaf->setTable('prop_safs');
        $approvedSaf->id = $activeSaf->id;
        $approvedSaf->property_id = $propId;
        $approvedSaf->save();
        $activeSaf->delete();

        // Saf Officer Replication
        $approvedOfficers = $officerDetails->replicate();
        $approvedOfficers->setTable('prop_safgbofficers');
        $approvedOfficers->id = $officerDetails->id;
        $approvedOfficers->save();
        $officerDetails->delete();


        // Saf Floors Replication
        foreach ($floorDetails as $floorDetail) {
            $approvedFloor = $floorDetail->replicate();
            $approvedFloor->setTable('prop_safs_floors');
            $approvedFloor->id = $floorDetail->id;
            $approvedFloor->save();
            $floorDetail->delete();
        }

        foreach ($fieldVerifiedSaf as $key) {
            $ifFloorExist = $mPropFloors->getFloorBySafFloorIdSafId($safId, $key->saf_floor_id);

            $floorReqs = new Request([
                'floor_mstr_id' => $key->floor_mstr_id,
                'usage_type_mstr_id' => $key->usage_type_id,
                'const_type_mstr_id' => $key->construction_type_id,
                'occupancy_type_mstr_id' => $key->occupancy_type_id,
                'builtup_area' => $key->builtup_area,
                'date_from' => $key->date_from,
                'date_upto' => $key->date_to,
                'carpet_area' => $key->carpet_area,
                'property_id' => $propId,
                'saf_id' => $safId

            ]);
            if ($ifFloorExist) {
                $mPropFloors->editFloor($ifFloorExist, $floorReqs);
            } else
                $mPropFloors->postFloor($floorReqs);
        }
    }

    /**
     * | Replication of Final Rejection Saf(10.2)
     */
    public function finalRejectionSafReplica($activeSaf, $officerDetails, $floorDetails)
    {
        // Rejected SAF Application replication
        $rejectedSaf = $activeSaf->replicate();
        $rejectedSaf->setTable('prop_rejected_safs');
        $rejectedSaf->id = $activeSaf->id;
        $rejectedSaf->push();
        $activeSaf->delete();

        // Saf Officer Replication
        $approvedOfficers = $officerDetails->replicate();
        $approvedOfficers->setTable('prop_rejected_safgbofficers');
        $approvedOfficers->id = $officerDetails->id;
        $approvedOfficers->save();
        $officerDetails->delete();

        // SAF Floors Replication
        foreach ($floorDetails as $floorDetail) {
            $approvedFloor = $floorDetail->replicate();
            $approvedFloor->setTable('prop_rejected_safs_floors');
            $approvedFloor->id = $floorDetail->id;
            $approvedFloor->save();
            $floorDetail->delete();
        }
    }

    /**
     * | Get uploaded documents
     */
    public function getUploadedDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveSaf = new PropActiveSaf();
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $safDetails = $mPropActiveSaf->getSafNo($req->applicationId);
            if (!$safDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $safDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | To upload document
     */
    public function uploadDocument(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg",
            "docCode" => "required",
        ]);
        $extention = $req->document->getClientOriginalExtension();
        $req->validate([
            'document' => $extention == 'pdf' ? 'max:10240' : 'max:1024',
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $relativePath = Config::get('PropertyConstaint.SAF_RELATIVE_PATH');
            $propModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $getSafDtls = $mActiveSafs->getSafNo($req->applicationId);
            $refImageName = $req->docCode;
            $refImageName = $getSafDtls->id . '-' . $refImageName;
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = $propModuleId;
            $metaReqs['activeId'] = $getSafDtls->id;
            $metaReqs['workflowId'] = $getSafDtls->workflow_id;
            $metaReqs['ulbId'] = $getSafDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docCode;

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);

            $getSafDtls->doc_upload_status = 1;
            $getSafDtls->save();

            return responseMsgs(true, "Document Uploadation Successful", "", "010201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Doc List
     */
    public function getDocList(Request $req)
    {
        try {
            $mActiveSafs = new PropActiveSaf();
            $refSafs = $mActiveSafs->getSafNo($req->applicationId);                      // Get Saf Details
            if (!$refSafs)
                throw new Exception("Application Not Found for this id");

            $gbSafDocs['listDocs'] = $this->getGbSafDocLists($refSafs);

            return responseMsgs(true, "Doc List", remove_null($gbSafDocs), 010717, 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", responseTime(), 'POST', "");
        }
    }

    /**
     * 
     */
    public function getGbSafDocLists($refSafs)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $mWfActiveDocument = new WfActiveDocument();
        $applicationId = $refSafs->id;
        $workflowId = $refSafs->workflow_id;
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_GB_SAF")->requirements;

        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList));

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $refSafs) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $label = array_shift($document);
            $documents = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $refSafs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)->first();
                if ($uploadedDoc) {
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? "",
                        "verifyStatus" => $refSafs->payment_status == 1 ? ($uploadedDoc->verify_status ?? "") : 0,
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['docName'] = substr($label, 1, -1);

            // Check back to citizen status
            $uploadedDocument = $documents->last();
            if (collect($uploadedDocument)->isNotEmpty() && $uploadedDocument['verifyStatus'] == 2) {
                $reqDoc['btcStatus'] = true;
            } else
                $reqDoc['btcStatus'] = false;

            $reqDoc['uploadedDoc'] = $documents->last();
            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks" => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }

    /**
     * | Document Verify Reject
     */
    public function docVerifyReject(Request $req)
    {
        $req->validate([
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);

        try {
            // Variable Assignments
            $mWfDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = authUser($req)->id;
            $applicationId = $req->applicationId;
            $wfLevel = Config::get('PropertyConstaint.SAF-LABEL');
            // Derivative Assigments
            $safDtls = $mActiveSafs->getSafNo($applicationId);
            $safReq = new Request([
                'userId' => $userId,
                'workflowId' => $safDtls->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($safReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;

            if ($senderRoleId != $wfLevel['DA'])                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            if (!$safDtls || collect($safDtls)->isEmpty())
                throw new Exception("Saf Details Not Found");

            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);       // (Current Object Derivative Function 4.1)

            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                $status = 2;
                // For Rejection Doc Upload Status and Verify Status will disabled
                $safDtls->doc_upload_status = 0;
                $safDtls->doc_verify_status = 0;
                $safDtls->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);

            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $safDtls->doc_verify_status = 1;
                $safDtls->save();
            }

            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     */
    public function ifFullDocVerified($applicationId)
    {
        $mActiveSafs = new PropActiveSaf();
        $mWfActiveDocument = new WfActiveDocument();
        $refSafs = $mActiveSafs->getSafNo($applicationId);                      // Get Saf Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $refSafs->workflow_id,
            'moduleId' => Config::get('module-constants.PROPERTY_MODULE_ID')
        ];
        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        // Property List Documents
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == 1)
            return 0;
        else
            return 1;
    }

    /**
     * | Independent Comment
     */
    public function commentIndependent(Request $request)
    {
        $request->validate([
            'comment' => 'required',
            'applicationId' => 'required|integer',
        ]);

        try {
            $userId = authUser($request)->id;
            $userType = authUser($request)->user_type;
            $workflowTrack = new WorkflowTrack();
            $mWfRoleUsermap = new WfRoleusermap();
            $saf = PropActiveSaf::findOrFail($request->applicationId);                // SAF Details
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $saf->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "prop_active_safs.id",
                'refTableIdValue' => $saf->id,
                'message' => $request->comment
            ];
            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $saf->workflow_id,
                    'userId' => $userId,
                ]);
                $wfRoleId = $mWfRoleUsermap->getRoleByUserWfId($roleReqs);
                $metaReqs = array_merge($metaReqs, ['senderRoleId' => $wfRoleId->wf_role_id]);
                $metaReqs = array_merge($metaReqs, ['user_id' => $userId]);
            }
            DB::beginTransaction();
            // For Citizen Independent Comment
            if ($userType == 'Citizen') {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $userId]);
                $metaReqs = array_merge($metaReqs, ['ulb_id' => $saf->ulb_id]);
                $metaReqs = array_merge($metaReqs, ['user_id' => NULL]);
            }

            $request->request->add($metaReqs);
            $workflowTrack->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | GBSaf Details
     */
    public function gbSafDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropActiveSaf = new PropActiveSaf();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mPropActiveGbOfficer = new PropActiveGbOfficer();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $forwardBackward = new WorkflowMap;
            $mRefTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
            // Saf Details
            $data = array();
            $fullDetailsData = array();
            if ($req->applicationId) {                                       //<------- Search By SAF ID
                $data = $mPropActiveSaf->getActiveSafDtls()      // <------- Model function Active SAF Details
                    ->where('prop_active_safs.id', $req->applicationId)
                    ->first();
            }
            if ($req->safNo) {                                  // <-------- Search By SAF No
                $data = $mPropActiveSaf->getActiveSafDtls()    // <------- Model Function Active SAF Details
                    ->where('prop_active_safs.saf_no', $req->safNo)
                    ->first();
            }
            // return $data;
            if (!$data)
                throw new Exception("Application Not Found for this id");

            // Basic Details
            $basicDetails = $this->generateGbBasicDetails($data);      // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            // Property Details
            $propertyDetails = $this->generateGbPropertyDetails($data);   // Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            $fullDetailsData['application_no'] = $data->saf_no;
            $fullDetailsData['apply_date'] = $data->application_date;
            $fullDetailsData['doc_verify_status'] = $data->doc_verify_status;
            $fullDetailsData['doc_upload_status'] = $data->doc_upload_status;
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement]);
            // Table Array
            // Owner Details
            $getOfficerDetails = $mPropActiveGbOfficer->getOfficerBySafId($data->id);    // Model function to get Owner Details
            $officerDetails = $this->generateOfficerDetails($getOfficerDetails);
            $officerElement = [
                'headerTitle' => 'Officer Details',
                'tableHead' => ["#", "Officer Name", "Designation", "Mobile No", "Email", "Adddress"],
                'tableData' => [$officerDetails]
            ];
            // Floor Details
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data->id);      // Model Function to Get Floor Details
            $floorDetails = $this->generateFloorDetails($getFloorDtls);
            $floorElement = [
                'headerTitle' => 'Floor Details',
                'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
                'tableData' => $floorDetails
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$officerElement, $floorElement]);
            // Card Detail Format
            $cardDetails = $this->generateGbCardDetails($data, $getOfficerDetails);
            $cardElement = [
                'headerTitle' => "About Property",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);
            $data = json_decode(json_encode($data), true);
            $metaReqs['customFor'] = 'GBSAF';
            $metaReqs['wfRoleId'] = $data['current_role'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $data['id']);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $data['id'], $data['user_id']);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $req->request->add($metaReqs);
            $forwardBackward = $forwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, 'Data Fetched', remove_null($fullDetailsData), "010104", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
