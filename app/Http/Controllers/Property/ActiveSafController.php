<?php

namespace App\Http\Controllers\Property;

use App\BLL\Property\CalculateSafById;
use App\BLL\Property\PaymentReceiptHelper;
use App\BLL\Property\PostRazorPayPenaltyRebate;
use App\BLL\Property\PostSafPropTaxes;
use App\BLL\Property\PreviousHoldingDeactivation;
use App\BLL\Property\TcVerificationDemandAdjust;
use App\BLL\Property\UpdateSafDemand;
use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqPayment;
use App\Http\Requests\Property\ReqSiteVerification;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\MicroServices\IdGenerator\PropIdGenerator;
use App\Models\CustomDetail;
use App\Models\Payment\TempTransaction;
use App\Models\Property\Logs\LogPropFloor;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropRazorpayPenalrebate;
use App\Models\Property\PropRazorpayRequest;
use App\Models\Property\PropRazorpayResponse;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafsFloor;
use App\Models\Property\PropSafsOwner;
use App\Models\Property\PropSafTax;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Property\PropTax;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropGbbuildingusagetype;
use App\Models\Property\RefPropGbpropusagetype;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropRoadType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\Property\ZoneMaster;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Pipelines\SafInbox\SearchByApplicationNo;
use App\Pipelines\SafInbox\SearchByMobileNo;
use App\Pipelines\SafInbox\SearchByName;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDetailsTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\MicroServices\IdGenerator\HoldingNoGenerator;
use Illuminate\Support\Facades\Http;

class ActiveSafController extends Controller
{
    use Workflow;
    use SAF;
    use Razorpay;
    use SafDetailsTrait;
    /**
     * | Created On-10-08-2022
     * | Created By-Anshu Kumar
     * | Status - Open
     * -----------------------------------------------------------------------------------------
     * | SAF Module all operations 
     * | --------------------------- Workflow Parameters ---------------------------------------
     * |                                 # SAF New Assessment
     * | wf_master id=1 
     * | wf_workflow_id=4
     * |                                 # SAF Reassessment 
     * | wf_mstr_id=2
     * | wf_workflow_id=3
     * |                                 # SAF Mutation
     * | wf_mstr_id=3
     * | wf_workflow_id=5
     * |                                 # SAF Bifurcation
     * | wf_mstr_id=4
     * | wf_workflow_id=182 
     * |                                 # SAF Amalgamation
     * | wf_mstr_id=5
     * | wf_workflow_id=381
     */

    protected $user_id;
    protected $_todayDate;
    protected $Repository;
    protected $_moduleId;
    // Initializing function for Repository
    protected $saf_repository;
    public $_replicatedPropId;
    public function __construct(iSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
        $this->_todayDate = Carbon::now();
        $this->_moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
    }

    /**
     * | Master data in Saf Apply
     * | @var ulbId Logged In User Ulb 
     * | Status-Closed
     * | Query Costing-369ms 
     * | Rating-3
     */
    public function masterSaf(Request $req)
    {
        try {
            $method = $req->getMethod();
            $redisConn = Redis::connection();
            $data = [];
            if ($method == 'GET')
                $ulbId = authUser($req)->ulb_id;
            else
                $ulbId = $req->ulbId;
            if (!$ulbId)
                throw new Exception('ulbId field is required');
            $ulbWardMaster = new UlbWardMaster();
            $refPropOwnershipType = new RefPropOwnershipType();
            $refPropType = new RefPropType();
            $refPropFloor = new RefPropFloor();
            $refPropUsageType = new RefPropUsageType();
            $refPropOccupancyType = new RefPropOccupancyType();
            $refPropConstructionType = new RefPropConstructionType();
            $refPropTransferMode = new RefPropTransferMode();
            $refPropRoadType = new RefPropRoadType();
            $refPropGbbuildingusagetype = new RefPropGbbuildingusagetype();
            $refPropGbpropusagetype = new RefPropGbpropusagetype();
            $mZoneMstrs = new ZoneMaster();

            // Getting Masters from Redis Cache
            $wards = json_decode(Redis::get('wards-ulb-' . $ulbId));
            $ownershipTypes = json_decode(Redis::get('prop-ownership-types'));
            $propertyType = json_decode(Redis::get('property-types'));
            $floorType = json_decode(Redis::get('property-floors'));
            $usageType = json_decode(Redis::get('property-usage-types'));
            $occupancyType = json_decode(Redis::get('property-occupancy-types'));
            $constructionType = json_decode(Redis::get('property-construction-types'));
            $transferModuleType = json_decode(Redis::get('property-transfer-modes'));
            $roadType = json_decode(Redis::get('property-road-type'));
            $gbbuildingusagetypes = json_decode(Redis::get('property-gb-building-usage-types'));
            $gbpropusagetypes = json_decode(Redis::get('property-gb-prop-usage-types'));
            $zoneMstrs = json_decode(Redis::get('zone-ulb-' . $ulbId));

            // Ward Masters
            if (!$wards) {
                $wards = collect();
                $wardMaster = $ulbWardMaster->getWardByUlbId($ulbId);   // <----- Get Ward by Ulb ID By Model Function
                $groupByWards = $wardMaster->groupBy('ward_name');
                foreach ($groupByWards as $ward) {
                    $wards->push(collect($ward)->first());
                }
                $wards->sortBy('ward_name')->values();
                $redisConn->set('wards-ulb-' . $ulbId, json_encode($wards));            // Caching
            }

            $data['ward_master'] = $wards;

            // Ownership Types
            if (!$ownershipTypes) {
                $ownershipTypes = $refPropOwnershipType->getPropOwnerTypes();   // <--- Get Property OwnerShip Types
                $redisConn->set('prop-ownership-types', json_encode($ownershipTypes));
            }

            $data['ownership_types'] = $ownershipTypes;

            // Property Types
            if (!$propertyType) {
                $propertyType = $refPropType->propPropertyType();
                $redisConn->set('property-types', json_encode($propertyType));
            }

            $data['property_type'] = $propertyType;

            // Property Floors
            if (!$floorType) {
                $floorType = $refPropFloor->getPropTypes();
                $redisConn->set('propery-floors', json_encode($floorType));
            }

            $data['floor_type'] = $floorType;

            // Property Usage Types
            if (!$usageType) {
                $usageType = $refPropUsageType->propUsageType();
                $redisConn->set('property-usage-types', json_encode($usageType));
            }

            $data['usage_type'] = $usageType;

            // Property Occupancy Types
            if (!$occupancyType) {
                $occupancyType = $refPropOccupancyType->propOccupancyType();
                $redisConn->set('property-occupancy-types', json_encode($occupancyType));
            }

            $data['occupancy_type'] = $occupancyType;

            // property construction types
            if (!$constructionType) {
                $constructionType = $refPropConstructionType->propConstructionType();
                $redisConn->set('property-construction-types', json_encode($constructionType));
            }

            $data['construction_type'] = $constructionType;

            // property transfer modes
            if (!$transferModuleType) {
                $transferModuleType = $refPropTransferMode->getTransferModes();
                $redisConn->set('property-transfer-modes', json_encode($transferModuleType));
            }

            $data['transfer_mode'] = $transferModuleType;

            // road type master
            if (!$roadType) {
                $roadType = $refPropRoadType->propRoadType();
                $redisConn->set('property-road-type', json_encode($roadType));
            }

            $data['road_type'] = $roadType;

            // GB Building Usage Types
            if (!$gbbuildingusagetypes) {
                $gbbuildingusagetypes = $refPropGbbuildingusagetype->getGbbuildingusagetypes();   // <--- Get GB Building Usage Types
                $redisConn->set('property-gb-building-usage-types', json_encode($gbbuildingusagetypes));
            }

            $data['gbbuildingusage_type'] = $gbbuildingusagetypes;

            // GB Prop Usage Types
            if (!$gbpropusagetypes) {
                $gbpropusagetypes = $refPropGbpropusagetype->getGbpropusagetypes();   // <--- Get GB Prop Usage Types
                $redisConn->set('property-gb-prop-usage-types', json_encode($gbpropusagetypes));
            }

            $data['gbpropusage_type'] = $gbpropusagetypes;

            // Zone Masters by Ulb
            if (!$zoneMstrs) {
                $zoneMstrs = $mZoneMstrs->getZone($ulbId);
                $redisConn->set('zone-ulb-' . $ulbId, json_encode($zoneMstrs));
            }
            $data['zone_mstrs'] = $zoneMstrs;

            return responseMsgs(true, 'Property Masters', $data, "010101", "1.0", responseTime(), "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     */
    public function editSaf(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric',
            'owner' => 'array',
            'owner.*.ownerId' => 'required|numeric',
            'owner.*.ownerName' => 'required',
            'owner.*.guardianName' => 'required',
            'owner.*.relation' => 'required',
            'owner.*.mobileNo' => 'numeric|string|digits:10',
            'owner.*.aadhar' => 'numeric|string|digits:12|nullable',
            'owner.*.email' => 'email|nullable',
        ]);

        try {
            $mPropSaf = new PropActiveSaf();
            $mPropSafOwners = new PropActiveSafsOwner();
            $mOwners = $req->owner;

            DB::beginTransaction();
            $mPropSaf->edit($req);                                                      // Updation SAF Basic Details

            collect($mOwners)->map(function ($owner) use ($mPropSafOwners) {            // Updation of Owner Basic Details
                $mPropSafOwners->edit($owner);
            });

            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * ---------------------- Saf Workflow Inbox --------------------
     * | Initialization
     * -----------------
     * | @var userId > logged in user id
     * | @var ulbId > Logged In user ulb Id
     * | @var refWorkflowId > Workflow ID 
     * | @var workflowId > SAF Wf Workflow ID 
     * | @var query > Contains the Pg Sql query
     * | @var workflow > get the Data in laravel Collection
     * | @var checkDataExisting > check the fetched data collection in array
     * | @var roleId > Fetch all the Roles for the Logged In user
     * | @var data > all the Saf data of current logged roleid 
     * | @var occupiedWard > get all Permitted Ward Of current logged in user id
     * | @var wardId > filtered Ward Id from the data collection
     * | @var safInbox > Final returned Data
     * | @return response #safInbox
     * | Status-Closed
     * | Query Cost-327ms 
     * | Rating-3
     * ---------------------------------------------------------------
     */
    #Inbox
    public function inbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                       // Model () to get Occupied Wards of Current User
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safDtl = $this->Repository->getSaf($workflowIds)                                          // Repository function to get SAF Details
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name');

            $safInbox = app(Pipeline::class)
                ->send(
                    $safDtl
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobileNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($safInbox), "010103", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Inbox for the Back To Citizen parked true
     * | @var mUserId authenticated user id
     * | @var mUlbId authenticated user ulb id
     * | @var readWards get all the wards of the user id
     * | @var occupiedWardsId get all the wards id of the user id
     * | @var readRoles get all the roles of the user id
     * | @var roleIds get all the logged in user role ids
     */
    public function btcInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $mUserId = authUser($req)->id;
            $mUlbId = authUser($req)->ulb_id;
            $mDeviceId = $req->deviceId ?? "";
            $perPage = $req->perPage ?? 10;

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list

            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safDtl = $this->Repository->getSaf($workflowIds)                 // Repository function getSAF
                ->selectRaw(DB::raw(
                    "case when prop_active_safs.citizen_id is not null then 'true'
                          else false end
                          as btc_for_citizen"
                ))
                ->where('parked', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name');

            $safInbox = app(Pipeline::class)
                ->send(
                    $safDtl
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "BTC Inbox List", remove_null($safInbox), 010123, 1.0, responseTime(), "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, responseTime(), "POST", $mDeviceId);
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

            $mUserId = authUser($req)->id;
            $mUlbId = authUser($req)->ulb_id;
            $mDeviceId = $req->deviceId ?? "";
            $perPage = $req->perPage ?? 10;

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list
            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $this->Repository->getSaf($workflowIds)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->paginate($perPage);

            return responseMsgs(true, "field Verified Inbox!", remove_null($safInbox), 010125, 1.0, "", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $mDeviceId);
        }
    }

    /**
     * | Saf Outbox
     * | @var userId authenticated user id
     * | @var ulbId authenticated user Ulb Id
     * | @var workflowRoles get All Roles of the user id
     * | @var roles filteration of roleid from collections
     * | Status-Closed
     * | Query Cost-369ms 
     * | Rating-4
     */

    public function outbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wardId = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safDtl = $this->Repository->getSaf($workflowIds)   // Repository function to get SAF
                ->where('prop_active_safs.parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name');

            $safData = app(Pipeline::class)
                ->send(
                    $safDtl
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($safData), "010104", "1.0", "274ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | @var ulbId authenticated user id
     * | @var ulbId authenticated ulb Id
     * | @var occupiedWard get ward by user id using trait
     * | @var wardId Filtered Ward ID from the collections
     * | @var safData SAF Data List
     * | @return
     * | @var \Illuminate\Support\Collection $safData
     * | Status-Closed
     * | Query Costing-336ms 
     * | Rating-2 
     */
    public function specialInbox(Request $req)
    {
        try {
            $mWfWardUser = new WfWardUser();
            $mWfRoleUserMaps = new WfRoleusermap();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $perPage = $req->perPage ?? 10;

            $wardIds = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                        // Get All Occupied Ward By user id using trait
            $roleIds = $mWfRoleUserMaps->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safDtl = $this->Repository->getSaf($workflowIds)                      // Repository function to get SAF Details
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'prop_active_safs.saf_no', 'ward.ward_name', 'p.property_type');

            $safData = app(Pipeline::class)
                ->send(
                    $safDtl
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByName::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($safData), "010107", "1.0", "251ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\JsonResponse
     * desc This function get the application brief details 
     * request : saf_id (requirde)
     * ---------------Tables-----------------
     * active_saf_details            |
     * ward_mastrs                   | Saf details
     * property_type                 |
     * active_saf_owner_details      -> Saf Owner details
     * active_saf_floore_details     -> Saf Floore Details
     * workflow_tracks               |  
     * users                         | Comments and  date rolles
     * role_masters                  |
     * =======================================
     * helpers : Helpers/utility_helper.php   ->remove_null() -> for remove  null values
     * | Status-Closed
     * | Query Cost-378ms 
     * | Rating-4 
     */
    #Saf Details
    public function safDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropActiveSaf = new PropActiveSaf();
            $mPropSaf = new PropSaf();
            $mPropActiveSafOwner = new PropActiveSafsOwner();
            $mActiveSafsFloors = new PropActiveSafsFloor();
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

                if (collect($data)->isEmpty()) {
                    $data = $mPropSaf->getSafDtls()
                        ->where('prop_safs.id', $req->applicationId)
                        ->first();
                    $data->current_role_name = 'Approved By ' . $data->current_role_name;
                }
            }
            if ($req->safNo) {                                  // <-------- Search By SAF No
                $data = $mPropActiveSaf->getActiveSafDtls()    // <------- Model Function Active SAF Details
                    ->where('prop_active_safs.saf_no', $req->safNo)
                    ->first();

                if (collect($data)->isEmpty()) {
                    $data = $mPropSaf->getSafDtls()
                        ->where('prop_safs.saf_no', $req->applicationId)
                        ->first();
                    $data->current_role_name = 'Approved By ' . $data->current_role_name;
                }
            }

            if (!$data)
                throw new Exception("Application Not Found for this id");

            if ($data->payment_status == 0) {
                $data->current_role_name = null;
                $data->current_role_name2 = "Payment is Pending";
            } else
                $data->current_role_name2 = $data->current_role_name;

            // Basic Details
            $basicDetails = $this->generateBasicDetails($data);      // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            // Property Details
            $propertyDetails = $this->generatePropertyDetails($data);   // Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            // Corresponding Address Details
            $corrDetails = $this->generateCorrDtls($data);              // Trait function to generate corresponding address details
            $corrElement = [
                'headerTitle' => 'Corresponding Address',
                'data' => $corrDetails,
            ];

            // Electricity & Water Details
            $electDetails = $this->generateElectDtls($data);            // Trait function to generate Electricity Details
            $electElement = [
                'headerTitle' => 'Electricity & Water Details',
                'data' => $electDetails
            ];
            $fullDetailsData['application_no'] = $data->saf_no;
            $fullDetailsData['apply_date'] = $data->application_date;

            $fullDetailsData['doc_verify_status'] = $data->doc_verify_status;
            $fullDetailsData['doc_upload_status'] = $data->doc_upload_status;
            $fullDetailsData['payment_status'] = $data->payment_status;
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement, $corrElement, $electElement]);
            // Table Array
            // Owner Details
            $getOwnerDetails = $mPropActiveSafOwner->getOwnersBySafId($data->id);    // Model function to get Owner Details
            $ownerDetails = $this->generateOwnerDetails($getOwnerDetails);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email", "IsArmedForce", "isSpeciallyAbled"],
                'tableData' => $ownerDetails
            ];
            // Floor Details
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data->id);      // Model Function to Get Floor Details
            $floorDetails = $this->generateFloorDetails($getFloorDtls);
            $floorElement = [
                'headerTitle' => 'Floor Details',
                'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
                'tableData' => $floorDetails
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement, $floorElement]);
            // Card Detail Format
            $cardDetails = $this->generateCardDetails($data, $getOwnerDetails);
            $cardElement = [
                'headerTitle' => "About Property",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);
            $data = json_decode(json_encode($data), true);
            $metaReqs['customFor'] = 'SAF';
            $metaReqs['wfRoleId'] = $data['current_role'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $data['id']);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $data['id'], $data['citizen_id']);
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
            $mPropSafOwner = new PropSafsOwner();
            $mPropSaf = new PropSaf();
            $mPropSafsFloors = new PropSafsFloor();
            $mPropActiveSafOwner = new PropActiveSafsOwner();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mPropSafMemoDtls = new PropSafMemoDtl();
            $memoDtls = array();
            $data = array();

            // Derivative Assignments
            $data = $mPropActiveSaf->getActiveSafDtls()                         // <------- Model function Active SAF Details
                ->where('prop_active_safs.id', $req->applicationId)
                ->first();
            // if (!$data)
            // throw new Exception("Application Not Found");

            if (collect($data)->isEmpty()) {
                $data = $mPropSaf->getSafDtls()
                    ->where('prop_safs.id', $req->applicationId)
                    ->first();
                $data->current_role_name = 'Approved By ' . $data->current_role_name;
            }

            if (collect($data)->isEmpty())
                throw new Exception("Application Not Found");

            if ($data->payment_status == 0) {
                $data->current_role_name = null;
                $data->current_role_name2 = "Payment is Pending";
            } elseif ($data->payment_status == 2) {
                $data->current_role_name = null;
                $data->current_role_name2 = "Cheque Payment Verification Pending";
            } else
                $data->current_role_name2 = $data->current_role_name;

            $data = json_decode(json_encode($data), true);

            $ownerDtls = $mPropActiveSafOwner->getOwnersBySafId($data['id']);
            if (collect($ownerDtls)->isEmpty())
                $ownerDtls = $mPropSafOwner->getOwnersBySafId($data['id']);

            $data['owners'] = $ownerDtls;
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data['id']);      // Model Function to Get Floor Details
            if (collect($getFloorDtls)->isEmpty())
                $getFloorDtls = $mPropSafsFloors->getFloorsBySafId($data['id']);
            $data['floors'] = $getFloorDtls;

            $memoDtls = $mPropSafMemoDtls->memoLists($data['id']);
            $data['memoDtls'] = $memoDtls;
            return responseMsgs(true, "Saf Dtls", remove_null($data), "010127", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), [], "010127", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * @var userId Logged In User Id
     * desc This function set OR remove application on special category
     * request : escalateStatus (required, int type), safId(required)
     * -----------------Tables---------------------
     *  active_saf_details
     * ============================================
     * active_saf_details.is_escalate <- request->escalateStatus 
     * active_saf_details.escalate_by <- request->escalateStatus 
     * ============================================
     * #message -> return response 
     * Status-Closed
     * | Query Cost-353ms 
     * | Rating-1
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
            return responseMsgs(true, $request->escalateStatus == 1 ? 'Saf is Escalated' : "Saf is removed from Escalated", '', "010106", "1.0", "353ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    // Post Independent Comment
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
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", "", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Function for Post Next Level(9)
     * | @param mixed $request
     * | @var preLevelPending Get the Previous level pending data for the saf id
     * | @var levelPending new Level Pending to be add
     * | Status-Closed
     * | Rating-3 
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
            $wfLevels = Config::get('PropertyConstaint.SAF-LABEL');
            $saf = PropActiveSaf::findOrFail($request->applicationId);
            $mWfMstr = new WfWorkflow();
            $track = new WorkflowTrack();
            $mWfWorkflows = new WfWorkflow();
            $mWfRoleMaps = new WfWorkflowrolemap();
            $mPropSafGeotagUpload = new PropSafGeotagUpload();
            $samHoldingDtls = array();
            $safId = $saf->id;

            // Derivative Assignments
            $senderRoleId = $saf->current_role;
            if (!$senderRoleId)
                throw new Exception("Current Role Not Available");

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
                $samHoldingDtls = $this->checkPostCondition($senderRoleId, $wfLevels, $saf, $wfMstrId, $userId);          // Check Post Next level condition

                $geotagExist = $saf->is_field_verified == true;

                if ($geotagExist && $saf->current_role == $wfLevels['DA'])
                    $forwardBackwardIds->forward_role_id = $wfLevels['UTC'];

                if ($saf->is_bt_da == true) {
                    $forwardBackwardIds->forward_role_id = $wfLevels['SI'];
                    $saf->is_bt_da = false;
                }

                $saf->current_role = $forwardBackwardIds->forward_role_id;
                $saf->last_role_id =  $forwardBackwardIds->forward_role_id;                     // Update Last Role Id
                $saf->parked = false;
                $metaReqs['verificationStatus'] = 1;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
            }
            // SAF Application Update Current Role Updation
            if ($request->action == 'backward') {
                $samHoldingDtls = $this->checkBackwardCondition($senderRoleId, $wfLevels, $saf);          // Check Backward condition

                #_Back to Dealing Assistant by Section Incharge
                if ($request->isBtd == true) {
                    $saf->is_bt_da = true;
                    $forwardBackwardIds->backward_role_id = $wfLevels['DA'];
                }

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
            $metaReqs['trackDate'] = $this->_todayDate->format('Y-m-d H:i:s');
            $request->request->add($metaReqs);
            $track->saveTrack($request);

            // Updation of Received Date
            $preWorkflowReq = [
                'workflowId' => $saf->workflow_id,
                'refTableDotId' => Config::get('PropertyConstaint.SAF_REF_TABLE'),
                'refTableIdValue' => $request->applicationId,
                'receiverRoleId' => $senderRoleId
            ];
            $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
            $previousWorkflowTrack->update([
                'forward_date' => $this->_todayDate->format('Y-m-d'),
                'forward_time' => $this->_todayDate->format('H:i:s')
            ]);
            // dd();
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", $samHoldingDtls, "010109", "1.0", "", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", "010109", "1.0", "", "POST", $request->deviceId);
        }
    }

    /**
     * | check Post Condition for backward forward(9.1)
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $saf, $wfMstrId, $userId)
    {
        // Variable Assigments
        $mPropSafDemand = new PropSafsDemand();
        $mPropMemoDtl = new PropSafMemoDtl();
        $mPropSafTax = new PropSafTax();
        $mPropTax = new PropTax();
        $mPropProperty = new PropProperty();
        $propIdGenerator = new PropIdGenerator;
        $ptParamId = Config::get('PropertyConstaint.PT_PARAM_ID');
        $holdingNoGenerator = new HoldingNoGenerator;

        // Derivative Assignments
        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($saf->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;

            case $wfLevels['DA']:                       // DA Condition
                $demand = $mPropSafDemand->getDemandsBySafId($saf->id)->groupBy('fyear')->first();
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available");
                $demand = $demand->last();
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for the to Generate SAM");
                if ($saf->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");

                $propertyExist = $mPropProperty->where('saf_id', $saf->id)
                    ->first();

                if (!$propertyExist) {
                    $idGeneration = new PrefixIdGenerator($ptParamId, $saf->ulb_id);

                    if (in_array($saf->assessment_type, ['New Assessment', 'Bifurcation', 'Amalgamation', 'Mutation'])) { // Make New Property For New Assessment,Bifurcation and Amalgamation & Mutation
                        // Holding No Generation
                        $holdingNo = $holdingNoGenerator->generateHoldingNo($saf);
                        $ptNo = $idGeneration->generate();
                        $saf->pt_no = $ptNo;                        // Generate New Property Tax No for All Conditions
                        $saf->holding_no = $holdingNo;
                        $saf->save();
                    }
                    $ptNo = $saf->pt_no;
                    // Sam No Generator
                    $samNo = $propIdGenerator->generateMemoNo("SAM", $saf->ward_mstr_id, $demand->fyear);
                    $this->replicateSaf($saf->id);
                    $propId = $this->_replicatedPropId;

                    $mergedDemand = array_merge($demand->toArray(), [       // SAM Memo Generation
                        'holding_no' => $saf->holding_no,
                        'memo_type' => 'SAM',
                        'memo_no' => $samNo,
                        'pt_no' => $ptNo,
                        'ward_id' => $saf->ward_mstr_id,
                        'prop_id' => $propId,
                        'userId'  => $userId
                    ]);
                    $memoReqs = new Request($mergedDemand);
                    $mPropMemoDtl->postSafMemoDtls($memoReqs);

                    $ifPropTaxExists = $mPropTax->getPropTaxesByPropId($propId);
                    if ($ifPropTaxExists)
                        $mPropTax->deactivatePropTax($propId);
                    $safTaxes = $mPropSafTax->getSafTaxesBySafId($saf->id)->toArray();
                    $mPropTax->replicateSafTaxes($propId, $safTaxes);
                }
                break;

            case $wfLevels['TC']:
                if ($saf->is_agency_verified == false)
                    throw new Exception("Agency Verification Not Done");
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
            'samNo' => $samNo ?? "",
            'ptNo' => $ptNo ?? "",
        ];
    }

    /**
     * |
     */
    public function checkBackwardCondition($senderRoleId, $wfLevels, $saf)
    {
        $mPropSafGeotagUpload = new PropSafGeotagUpload();

        switch ($senderRoleId) {
            case $wfLevels['TC']:
                $saf->is_agency_verified = false;
                $saf->save();
                break;
            case $wfLevels['UTC']:
                $saf->is_geo_tagged = false;
                $saf->save();

                $mPropSafGeotagUpload->where('saf_id', $saf->id)
                    ->update(['status' => 0]);
                break;
        }
    }

    /**
     * | Replicate Tables of saf to property
     */
    public function replicateSaf($safId)
    {
        $activeSaf = PropActiveSaf::query()
            ->where('id', $safId)
            ->first();
        $ownerDetails = PropActiveSafsOwner::query()
            ->where('saf_id', $safId)
            ->get();
        $floorDetails = PropActiveSafsFloor::query()
            ->where('saf_id', $safId)
            ->get();

        $toBeProperties = PropActiveSaf::where('id', $safId)
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

        if (in_array($assessmentType, ['New Assessment', 'Bifurcation', 'Amalgamation', 'Mutation'])) { // Make New Property For New Assessment,Bifurcation and Amalgamation
            $propProperties = $toBeProperties->replicate();
            $propProperties->setTable('prop_properties');
            $propProperties->saf_id = $activeSaf->id;
            $propProperties->new_holding_no = $activeSaf->holding_no;
            $propProperties->save();

            $this->_replicatedPropId = $propProperties->id;
            // SAF Owners replication
            foreach ($ownerDetails as $ownerDetail) {
                $approvedOwners = $ownerDetail->replicate();
                $approvedOwners->setTable('prop_owners');
                $approvedOwners->property_id = $propProperties->id;
                $approvedOwners->save();
            }

            // SAF Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $propFloor = $floorDetail->replicate();
                $propFloor->setTable('prop_floors');
                $propFloor->property_id = $propProperties->id;
                $propFloor->save();
            }
        }

        // Edit In Case of Reassessment,Mutation
        if (in_array($assessmentType, ['Reassessment'])) {         // Edit Property In case of Reassessment, Mutation
            $propId = $activeSaf->previous_holding_id;
            $this->_replicatedPropId = $propId;
            $mProperty = new PropProperty();
            $mPropOwners = new PropOwner();
            $mPropFloors = new PropFloor();
            $mLogPropFloors = new LogPropFloor();
            // Edit Property
            $mProperty->editPropBySaf($propId, $activeSaf);
            // Edit Owners 
            foreach ($ownerDetails as $ownerDetail) {
                if ($assessmentType == 'Reassessment') {            // In Case of Reassessment Edit Owners

                    if (!is_null($ownerDetail->prop_owner_id))
                        $ifOwnerExist = $mPropOwners->getOwnerByPropOwnerId($ownerDetail->prop_owner_id);

                    if (isset($ifOwnerExist)) {
                        $ownerDetail = array_merge($ownerDetail->toArray(), ['property_id' => $propId]);
                        $propOwner = $mPropOwners::find($ownerDetail['prop_owner_id']);
                        if (collect($propOwner)->isEmpty())
                            throw new Exception("Owner Not Exists");
                        unset($ownerDetail['id']);
                        $propOwner->update($ownerDetail);
                    }
                }
                if ($assessmentType == 'Mutation') {            // In Case of Mutation Add Owners
                    $ownerDetail = array_merge($ownerDetail->toArray(), ['property_id' => $propId]);
                    $ownerDetail = new Request($ownerDetail);
                    $mPropOwners->postOwner($ownerDetail);
                }
            }
            // Edit Floors
            foreach ($floorDetails as $floorDetail) {
                if (!is_null($floorDetail->prop_floor_details_id))
                    $ifFloorExist = $mPropFloors->getFloorByFloorId($floorDetail->prop_floor_details_id);
                $floorReqs = new Request([
                    'floor_mstr_id' => $floorDetail->floor_mstr_id,
                    'usage_type_mstr_id' => $floorDetail->usage_type_mstr_id,
                    'const_type_mstr_id' => $floorDetail->const_type_mstr_id,
                    'occupancy_type_mstr_id' => $floorDetail->occupancy_type_mstr_id,
                    'builtup_area' => $floorDetail->builtup_area,
                    'date_from' => $floorDetail->date_from,
                    'date_upto' => $floorDetail->date_upto,
                    'carpet_area' => $floorDetail->carpet_area,
                    'property_id' => $propId,
                    'saf_id' => $safId,
                    'saf_floor_id' => $floorDetail->id,
                    'prop_floor_details_id' => $floorDetail->prop_floor_details_id

                ]);
                if (isset($ifFloorExist))
                    $mPropFloors->editFloor($ifFloorExist, $floorReqs);
                else                      // If floor Not Exist by Prop Saf Id
                {
                    $isFloorBySafFloorId = $mPropFloors->getFloorBySafFloorId($safId, $floorDetail->id);        // Check the Floor Existance by Saf Floor Id
                    if ($isFloorBySafFloorId)       // If Floor Exist By Saf Floor Id
                        $mPropFloors->editFloor($isFloorBySafFloorId, $floorReqs);
                    else
                        $mPropFloors->postFloor($floorReqs);
                }
            }
        }
    }

    /**
     * | Approve or Reject The SAF Application
     * --------------------------------------------------
     * | ----------------- Initialization ---------------
     * | @param mixed $req
     * | @var activeSaf The Saf Record by Saf Id
     * | @var approvedSaf replication of the saf record to be approved
     * | @var rejectedSaf replication of the saf record to be rejected
     * ------------------- Alogrithm ---------------------
     * | $req->status (if 1 Application to be approved && if 0 application to be rejected)
     * ------------------- Dump --------------------------
     * | @return msg
     * | Status-Closed
     * | Query Cost-430ms 
     * | Rating-3
     */
    public function approvalRejectionSaf(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        try {
            // Check if the Current User is Finisher or Not (Variable Assignments)
            $mWfRoleUsermap = new WfRoleusermap();
            $propSafVerification = new PropSafVerification();
            $propSafVerificationDtl = new PropSafVerificationDtl();
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropSafDemand = new PropSafsDemand();
            $mPropProperties = new PropProperty();
            $mPropDemand = new PropDemand();
            $track = new WorkflowTrack();
            $handleTcVerification = new TcVerificationDemandAdjust;
            $todayDate = Carbon::now()->format('Y-m-d');
            $currentFinYear = calculateFYear($todayDate);
            $famParamId = Config::get('PropertyConstaint.FAM_PARAM_ID');
            $previousHoldingDeactivation = new PreviousHoldingDeactivation;
            $propIdGenerator = new PropIdGenerator;

            $userId = authUser($req)->id;
            $safId = $req->applicationId;
            // Derivative Assignments
            $safDetails = PropActiveSaf::findOrFail($req->applicationId);
            $senderRoleId = $safDetails->current_role;
            $workflowId = $safDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            if (collect($readRoleDtls)->isEmpty())
                throw new Exception("You Are Not Authorized for this workflow");

            $roleId = $readRoleDtls->wf_role_id;

            if ($safDetails->finisher_role_id != $roleId)
                throw new Exception("Forbidden Access");
            $activeSaf = PropActiveSaf::query()
                ->where('id', $req->applicationId)
                ->first();
            $ownerDetails = PropActiveSafsOwner::query()
                ->where('saf_id', $req->applicationId)
                ->get();
            $floorDetails = PropActiveSafsFloor::query()
                ->where('saf_id', $req->applicationId)
                ->get();

            $propDtls = $mPropProperties->getPropIdBySafId($req->applicationId);
            $propId = $propDtls->id;
            if ($safDetails->prop_type_mstr_id != 4)
                $fieldVerifiedSaf = $propSafVerification->getVerificationsBySafId($safId);          // Get fields Verified Saf with all Floor Details
            else
                $fieldVerifiedSaf = $propSafVerification->getVerifications($safId);
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
                $this->finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $ownerDetails, $floorDetails, $safId);
                $tcVerifyParams = [
                    'safId' => $safId,
                    'fieldVerificationDtls' => $fieldVerifiedSaf,
                    'assessmentType' => $safDetails->assessment_type,
                    'ulbId' => $activeSaf->ulb_id,
                    'activeSafDtls' => $activeSaf,
                    'propId' => $propId
                ];
                $handleTcVerification->generateTcVerifiedDemand($tcVerifyParams);                // current object function (10.3)
                $msg = "Application Approved Successfully";
                $metaReqs['verificationStatus'] = 1;

                $previousHoldingDeactivation->deactivatePreviousHoldings($safDetails);  // Previous holding deactivation in case of Mutation, Amalgamation, Bifurcation
            }

            // Rejection
            if ($req->status == 0) {
                $this->finalRejectionSafReplica($activeSaf, $ownerDetails, $floorDetails);
                $msg = "Application Rejected Successfully";
                $metaReqs['verificationStatus'] = 0;
            }
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $safDetails->workflow_id;
            $metaReqs['refTableDotId'] = Config::get('PropertyConstaint.SAF_REF_TABLE');
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['verificationStatus'] = 1;
            $metaReqs['user_id'] = $userId;
            $metaReqs['trackDate'] = $this->_todayDate->format('Y-m-d H:i:s');
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            // Updation of Received Date
            $preWorkflowReq = [
                'workflowId' => $safDetails->workflow_id,
                'refTableDotId' => Config::get('PropertyConstaint.SAF_REF_TABLE'),
                'refTableIdValue' => $req->applicationId,
                'receiverRoleId' => $senderRoleId
            ];
            $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
            $previousWorkflowTrack->update([
                'forward_date' => $this->_todayDate->format('Y-m-d'),
                'forward_time' => $this->_todayDate->format('H:i:s')
            ]);

            $propSafVerification->deactivateVerifications($req->applicationId);                 // Deactivate Verification From Table
            $propSafVerificationDtl->deactivateVerifications($req->applicationId);              // Deactivate Verification from Saf floor Dtls
            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->holding_no, 'ptNo' => $safDetails->pt_no], "010110", "1.0", "410ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Replication of Final Approval SAf(10.1)
     */
    public function finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $ownerDetails, $floorDetails, $safId)
    {
        $mPropFloors = new PropFloor();
        $mPropProperties->replicateVerifiedSaf($propId, collect($fieldVerifiedSaf)->first());             // Replicate to Prop Property Table
        $approvedSaf = $activeSaf->replicate();
        $approvedSaf->setTable('prop_safs');
        $approvedSaf->id = $activeSaf->id;
        $approvedSaf->property_id = $propId;
        $approvedSaf->save();
        $activeSaf->delete();

        // Saf Owners Replication
        foreach ($ownerDetails as $ownerDetail) {
            $approvedOwner = $ownerDetail->replicate();
            $approvedOwner->setTable('prop_safs_owners');
            $approvedOwner->id = $ownerDetail->id;
            $approvedOwner->save();
            $ownerDetail->delete();
        }
        if ($activeSaf->prop_type_mstr_id != 4) {               // Applicable Not for Vacant Land
            // Saf Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $approvedFloor = $floorDetail->replicate();
                $approvedFloor->setTable('prop_safs_floors');
                $approvedFloor->id = $floorDetail->id;
                $approvedFloor->save();
                $floorDetail->delete();
            }

            // Deactivate Existing Prop Floors by Saf Id
            $existingFloors = $mPropFloors->getFloorsByPropId($propId);
            if ($existingFloors)
                $mPropFloors->deactivateFloorsByPropId($propId);
            foreach ($fieldVerifiedSaf as $key) {
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
                $mPropFloors->postFloor($floorReqs);
            }
        }
    }

    /**
     * | Replication of Final Rejection Saf(10.2)
     */
    public function finalRejectionSafReplica($activeSaf, $ownerDetails, $floorDetails)
    {
        // Rejected SAF Application replication
        $rejectedSaf = $activeSaf->replicate();
        $rejectedSaf->setTable('prop_rejected_safs');
        $rejectedSaf->id = $activeSaf->id;
        $rejectedSaf->push();
        $activeSaf->delete();

        // SAF Owners replication
        foreach ($ownerDetails as $ownerDetail) {
            $approvedOwner = $ownerDetail->replicate();
            $approvedOwner->setTable('prop_rejected_safs_owners');
            $approvedOwner->id = $ownerDetail->id;
            $approvedOwner->save();
            $ownerDetail->delete();
        }

        if ($activeSaf->prop_type_mstr_id != 4) {           // Not Applicable for Vacant Land
            // SAF Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $approvedFloor = $floorDetail->replicate();
                $approvedFloor->setTable('prop_rejected_safs_floors');
                $approvedFloor->id = $floorDetail->id;
                $approvedFloor->save();
                $floorDetail->delete();
            }
        }
    }

    /**
     * | Back to Citizen
     * | @param Request $req
     * | @var redis Establishing Redis Connection
     * | @var workflowId Workflow id of the SAF 
     * | Status-Closed
     * | Query Costing-401ms
     * | Rating-1 
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $safRefTableName = Config::get('PropertyConstaint.SAF_REF_TABLE');
            $saf = PropActiveSaf::findOrFail($req->applicationId);
            $track = new WorkflowTrack();
            $mWfActiveDocument = new WfActiveDocument();
            $senderRoleId = $saf->current_role;

            if ($saf->doc_verify_status == true)
                throw new Exception("Verification Done You Cannot Back to Citizen");

            // Check capability for back to citizen
            $getDocReqs = [
                'activeId' => $saf->id,
                'workflowId' => $saf->workflow_id,
                'moduleId' => $moduleId
            ];
            $getRejectedDocument = $mWfActiveDocument->readRejectedDocuments($getDocReqs);

            if (collect($getRejectedDocument)->isEmpty())
                throw new Exception("Document Not Rejected You Can't back to citizen this application");

            if (is_null($saf->citizen_id)) {                // If the Application has been applied from Jsk or Ulb Employees
                $initiatorRoleId = $saf->initiator_role_id;
                $saf->current_role = $initiatorRoleId;
                $saf->parked = true;                        //<------ SAF Pending Status true
            } else
                $saf->parked = true;                        // If the Application has been applied from Citizen

            DB::beginTransaction();
            $saf->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = $safRefTableName;
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
     * | Calculate SAF by Saf ID
     * | @param req request saf id
     * | @var array contains all the details for the saf id
     * | @var data contains the details of the saf id by the current object function
     * | @return safTaxes returns all the calculated demand
     * | Status-Closed
     * | Query Costing-417ms
     * | Rating-3 
     */
    public function calculateSafBySafId(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id' => 'required|digits_between:1,9223372036854775807',
                'fYear' => 'nullable|max:9|min:9',
                'qtr' => 'nullable|regex:/^[1-4]+/'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $safDtls = PropActiveSaf::find($req->id);
            if (!$safDtls)
                $safDtls = PropSaf::find($req->id);

            if (collect($safDtls)->isEmpty())
                throw new Exception("Saf Not Available");

            if (in_array($safDtls->assessment_type, ['New Assessment', 'Reassessment', 'Re Assessment', 'Mutation']))
                $req = $req->merge(['holdingNo' => $safDtls->holding_no]);
            $calculateSafById = new CalculateSafById;
            $demand = $calculateSafById->calculateTax($req);
            return responseMsgs(true, "Demand Details", remove_null($demand));
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | One Percent Penalty Calculation(13.1)
     */
    public function calcOnePercPenalty($item)
    {
        $penaltyRebateCalc = new PenaltyRebateCalculation;
        $onePercPenalty = $penaltyRebateCalc->calcOnePercPenalty($item->due_date);                  // Calculation One Percent Penalty
        $item['onePercPenalty'] = $onePercPenalty;
        $onePercPenaltyTax = ($item['balance'] * $onePercPenalty) / 100;
        $item['onePercPenaltyTax'] = roundFigure($onePercPenaltyTax);
        return $item;
    }

    /**
     * | Generate Order ID (14)
     * | @param req requested Data
     * | @var auth authenticated users credentials
     * | @var calculateSafById calculated SAF amounts and details by request SAF ID
     * | @var totalAmount filtered total amount from the collection
     * | Status-closed
     * | Query Costing-1.41s
     * | Rating - 5
     * */
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer',
        ]);

        try {
            $ipAddress = getClientIpAddress();
            $mPropRazorPayRequest = new PropRazorpayRequest();
            $postRazorPayPenaltyRebate = new PostRazorPayPenaltyRebate;
            $url            = Config::get('razorpay.PAYMENT_GATEWAY_URL');
            $endPoint       = Config::get('razorpay.PAYMENT_GATEWAY_END_POINT');
            $authUser      = authUser($req);
            $req->merge(['departmentId' => 1]);
            $safDetails = PropActiveSaf::findOrFail($req->id);
            if ($safDetails->payment_status == 1)
                throw new Exception("Payment already done");
            $calculateSafById = $this->calculateSafBySafId($req);
            $demands = $calculateSafById->original['data']['demand'];
            $details = $calculateSafById->original['data']['details'];
            $totalAmount = $demands['payableAmount'];
            $req->request->add(['workflowId' => $safDetails->workflow_id, 'ghostUserId' => 0, 'amount' => $totalAmount, 'auth' => $authUser]);
            DB::beginTransaction();

            $orderDetails = $this->saveGenerateOrderid($req);
            // $orderDetails = Http::withHeaders([])
            //     ->post($url . $endPoint, $req->toArray());

            // $orderDetails = collect(json_decode($orderDetails));

            // $status = isset($orderDetails['status']) ? $orderDetails['status'] : true;                                      //<---------- Generate Order ID Trait

            // if ($status == false)
            //     return $orderDetails;
            $demands = array_merge($demands->toArray(), [
                'orderId' => $orderDetails['orderId']
            ]);
            // Store Razor pay Request
            $razorPayRequest = [
                'order_id' => $demands['orderId'],
                'saf_id' => $req->id,
                'from_fyear' => $demands['dueFromFyear'],
                'from_qtr' => $demands['dueFromQtr'],
                'to_fyear' => $demands['dueToFyear'],
                'to_qtr' => $demands['dueToQtr'],
                'demand_amt' => $demands['totalTax'],
                'ulb_id' => $safDetails->ulb_id,
                'ip_address' => $ipAddress,
                'demand_list' => json_encode($details, true),
                'amount' => $totalAmount,
            ];
            $storedRazorPayReqs = $mPropRazorPayRequest->store($razorPayRequest);
            // Store Razor pay penalty Rebates
            $postRazorPayPenaltyRebate->_safId = $req->id;
            $postRazorPayPenaltyRebate->_razorPayRequestId = $storedRazorPayReqs['razorPayReqId'];
            $postRazorPayPenaltyRebate->postRazorPayPenaltyRebates($demands);
            DB::commit();
            return responseMsgs(true, "Order ID Generated", remove_null($orderDetails), "010114", "1.0", "1s", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Penalty Rebates (14.2)
     */
    public function postPenaltyRebates($calculateSafById, $safId, $tranId, $clusterId = null)
    {
        $mPaymentRebatePanelties = new PropPenaltyrebate();
        $calculatedRebates = collect($calculateSafById->original['data']['demand']['rebates']);
        $rebateList = array();
        $rebatePenalList = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));

        foreach ($calculatedRebates as $item) {
            $rebate = [
                'keyString' => $item['keyString'],
                'value' => $item['rebateAmount'],
                'isRebate' => true
            ];
            array_push($rebateList, $rebate);
        }
        $headNames = [
            [
                'keyString' => $rebatePenalList->where('id', 1)->first()['value'],
                'value' => $calculateSafById->original['data']['demand']['totalOnePercPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => $rebatePenalList->where('id', 5)->first()['value'],
                'value' => $calculateSafById->original['data']['demand']['lateAssessmentPenalty'],
                'isRebate' => false
            ]
        ];
        $headNames = array_merge($headNames, $rebateList);
        collect($headNames)->map(function ($headName) use ($mPaymentRebatePanelties, $safId, $tranId, $clusterId) {
            if ($headName['value'] > 0) {
                $reqs = [
                    'tran_id' => $tranId,
                    'saf_id' => $safId,
                    'cluster_id' => $clusterId,
                    'head_name' => $headName['keyString'],
                    'amount' => $headName['value'],
                    'is_rebate' => $headName['isRebate'],
                    'tran_date' => Carbon::now()->format('Y-m-d')
                ];

                $mPaymentRebatePanelties->postRebatePenalty($reqs);
            }
        });
    }

    /**
     * | SAF Payment
     * | @param req  
     * | @var workflowId SAF workflow ID
     * | Status-Closed
     * | Query Consting-374ms
     * | Rating-3
     */
    public function paymentSaf(ReqPayment $req)
    {
        try {
            $req->validate([
                'paymentId' => "required",
                "transactionNo" => "required"
            ]);
            // Variable Assignments
            $mPropTransactions = new PropTransaction();
            $mPropSafsDemands = new PropSafsDemand();
            $mPropRazorPayRequest = new PropRazorpayRequest();
            $mPropRazorpayPenalRebates = new PropRazorpayPenalrebate();
            $mPropPenaltyRebates = new PropPenaltyrebate();
            $mPropRazorpayResponse = new PropRazorpayResponse();
            $mPropTranDtl = new PropTranDtl();
            $previousHoldingDeactivation = new PreviousHoldingDeactivation;
            $postSafPropTaxes = new PostSafPropTaxes;

            $activeSaf = PropActiveSaf::findOrFail($req['id']);
            if ($activeSaf->payment_status == 1)
                throw new Exception("Payment Already Done");
            $userId = $req['userId'];
            $safId = $req['id'];
            $orderId = $req['orderId'];
            $paymentId = $req['paymentId'];

            if ($activeSaf->payment_status == 1)
                throw new Exception("Payment Already Done");
            $req['ulbId'] = $activeSaf->ulb_id;
            $razorPayReqs = new Request([
                'orderId' => $orderId,
                'key' => 'saf_id',
                'keyId' => $req['id']
            ]);
            $propRazorPayRequest = $mPropRazorPayRequest->getRazorPayRequests($razorPayReqs);
            if (collect($propRazorPayRequest)->isEmpty())
                throw new Exception("No Order Request Found");

            if (!$userId)
                $userId = 0;                                                        // For Ghost User in case of online payment

            $tranNo = $req['transactionNo'];
            // Derivative Assignments
            $demands = json_decode($propRazorPayRequest['demand_list']);
            $amount = $propRazorPayRequest['amount'];

            if (!$demands || collect($demands)->isEmpty())
                throw new Exception("Demand Not Available for Payment");
            // Property Transactions
            $activeSaf->payment_status = 1;             // Paid for Online
            DB::beginTransaction();
            $activeSaf->save();
            // Replication of Prop Transactions
            $tranReqs = [
                'saf_id' => $req['id'],
                'tran_date' => $this->_todayDate->format('Y-m-d'),
                'tran_no' => $tranNo,
                'payment_mode' => 'ONLINE',
                'amount' => $amount,
                'tran_date' => $this->_todayDate->format('Y-m-d'),
                'verify_date' => $this->_todayDate->format('Y-m-d'),
                'citizen_id' => $userId,
                'is_citizen' => true,
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $propRazorPayRequest->ulb_id,
            ];

            $storedTransaction = $mPropTransactions->storeTrans($tranReqs);
            $tranId = $storedTransaction['id'];
            $razorpayPenalRebates = $mPropRazorpayPenalRebates->getPenalRebatesByReqId($propRazorPayRequest->id);
            // Replication of Razorpay Penalty Rebates to Prop Penal Rebates
            foreach ($razorpayPenalRebates as $item) {
                $propPenaltyRebateReqs = [
                    'tran_id' => $tranId,
                    'head_name' => $item['head_name'],
                    'amount' => $item['amount'],
                    'is_rebate' => $item['is_rebate'],
                    'tran_date' => $this->_todayDate->format('Y-m-d'),
                    'saf_id' => $safId,
                ];
                $mPropPenaltyRebates->postRebatePenalty($propPenaltyRebateReqs);
            }

            // Updation of Prop Razor pay Request
            $propRazorPayRequest->status = 1;
            $propRazorPayRequest->payment_id = $paymentId;
            $propRazorPayRequest->save();

            // Update Prop Razorpay Response
            $razorpayResponseReq = [
                'razorpay_request_id' => $propRazorPayRequest->id,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'saf_id' => $req['id'],
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $activeSaf->ulb_id,
                'ip_address' => getClientIpAddress(),
            ];
            $mPropRazorpayResponse->store($razorpayResponseReq);

            foreach ($demands as $demand) {
                $demand = (array)$demand;
                unset($demand['ruleSet'], $demand['rwhPenalty'], $demand['onePercPenalty'], $demand['onePercPenaltyTax']);
                if (isset($demand['status']))
                    unset($demand['status']);
                $demand['paid_status'] = 1;
                $demand['saf_id'] = $safId;
                $demand['balance'] = 0;
                $storedSafDemand = $mPropSafsDemands->postDemands($demand);

                $tranReq = [
                    'tran_id' => $tranId,
                    'saf_demand_id' => $storedSafDemand['demandId'],
                    'total_demand' => $demand['amount'],
                    'ulb_id' => $req['ulbId'],
                ];
                $mPropTranDtl->store($tranReq);
            }
            $previousHoldingDeactivation->deactivateHoldingDemands($activeSaf);  // Deactivate Property Holding
            $this->sendToWorkflow($activeSaf);                                   // Send to Workflow(15.2)
            $demands = collect($demands)->toArray();
            $postSafPropTaxes->postSafTaxes($safId, $demands, $activeSaf->ulb_id);                  // Save Taxes
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "010115", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Offline Saf Payment
     */
    public function offlinePaymentSaf(ReqPayment $req)
    {
        try {
            // Variable Assignments
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $idGeneration = new IdGeneration;
            $propTrans = new PropTransaction();
            $mPropSafsDemands = new PropSafsDemand();
            $verifyPaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODES');
            $mPropTranDtl = new PropTranDtl();
            $previousHoldingDeactivation = new PreviousHoldingDeactivation;
            $postSafPropTaxes = new PostSafPropTaxes;
            $updateSafDemand = new UpdateSafDemand;
            $safId = $req['id'];

            $activeSaf = PropActiveSaf::findOrFail($req['id']);

            if ($activeSaf->payment_status == 1)
                throw new Exception("Payment Already Done");

            $userId = authUser($req)->id;                                      // Authenticated user or Ghost User
            $tranBy = authUser($req)->user_type;

            $tranNo = $req['transactionNo'];
            // Derivative Assignments
            if (!$tranNo)
                $tranNo = $idGeneration->generateTransactionNo($activeSaf->ulb_id);

            $safCalculation = $this->calculateSafBySafId($req);
            if ($safCalculation->original['status'] == false)
                throw new Exception($safCalculation->original['message']);
            $demands = $safCalculation->original['data']['details'];
            $amount = $safCalculation->original['data']['demand']['payableAmount'];

            if (!$demands || collect($demands)->isEmpty())
                throw new Exception("Demand Not Available for Payment");

            // Property Transactions
            $req->merge([
                'userId' => $userId,
                'is_citizen' => false,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo,
                'workflowId' => $activeSaf->workflow_id,
                'amount' => $amount,
                'tranBy' => $tranBy,
                'ulbId' => $activeSaf->ulb_id
            ]);
            $activeSaf->payment_status = 1; // Paid for Online or Cash
            if (in_array($req['paymentMode'], $verifyPaymentModes)) {
                $req->merge([
                    'verifyStatus' => 2
                ]);
                $activeSaf->payment_status = 2;         // Under Verification for Cheque, Cash, DD
            }
            DB::beginTransaction();
            $propTrans = $propTrans->postSafTransaction($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id'],
                    "applicationNo" => $activeSaf->saf_no,

                ]);
                $this->postOtherPaymentModes($req);
            }
            // Reflect on Prop Tran Details For Normal Saf
            if ($activeSaf->is_gb_saf == false) {
                foreach ($demands as $demand) {
                    $demand = $demand->toArray();
                    unset($demand['ruleSet'], $demand['rwhPenalty'], $demand['onePercPenalty'], $demand['onePercPenaltyTax']);
                    if (isset($demand['status']))
                        unset($demand['status']);
                    $demand['paid_status'] = 1;
                    $demand['saf_id'] = $safId;
                    $demand['balance'] = 0;
                    $storedSafDemand = $mPropSafsDemands->postDemands($demand);

                    $tranReq = [
                        'tran_id' => $propTrans['id'],
                        'saf_demand_id' => $storedSafDemand['demandId'],
                        'total_demand' => $demand['amount'],
                        'ulb_id' => $req['ulbId'],
                    ];
                    $mPropTranDtl->store($tranReq);
                }
            }

            // GB Saf Demand Adjustments
            if ($activeSaf->is_gb_saf == true)
                $updateSafDemand->updateDemand($demands->toArray(), $propTrans['id']);

            // Replication Prop Rebates Penalties
            $this->postPenaltyRebates($safCalculation, $safId, $propTrans['id']);

            // Deactivate Property Holdings
            $previousHoldingDeactivation->deactivateHoldingDemands($activeSaf);

            // Update SAF Payment Status
            $activeSaf->save();
            $this->sendToWorkflow($activeSaf);        // Send to Workflow(15.2)
            $demands = collect($demands)->toArray();
            $postSafPropTaxes->postSafTaxes($safId, $demands, $activeSaf->ulb_id);                  // Save Taxes
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "010115", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     */
    public function postOtherPaymentModes($req, $clusterId = null)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $mTempTransaction = new TempTransaction();
        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new PropChequeDtl();
            $chequeReqs = [
                'user_id' => $req['userId'],
                'prop_id' => $req['id'],
                'transaction_id' => $req['tranId'],
                'cheque_date' => $req['chequeDate'],
                'bank_name' => $req['bankName'],
                'branch_name' => $req['branchName'],
                'cheque_no' => $req['chequeNo'],
                'cluster_id' => $clusterId
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id' => $req['tranId'],
            'application_id' => $req['id'],
            'module_id' => $moduleId,
            'workflow_id' => $req['workflowId'],
            'transaction_no' => $req['tranNo'],
            'application_no' => $req['applicationNo'],
            'amount' => $req['amount'],
            'payment_mode' => $req['paymentMode'],
            'cheque_dd_no' => $req['chequeNo'],
            'bank_name' => $req['bankName'],
            'tran_date' => $req['todayDate'],
            'user_id' => $req['userId'],
            'ulb_id' => $req['ulbId'],
            'cluster_id' => $clusterId
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Send to Workflow Level after payment(15.2)
     */
    public function sendToWorkflow($activeSaf)
    {
        $mWorkflowTrack = new WorkflowTrack();
        $todayDate = $this->_todayDate;
        $refTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
        $reqWorkflow = [
            'workflow_id' => $activeSaf->workflow_id,
            'ref_table_dot_id' => $refTable,
            'ref_table_id_value' => $activeSaf->id,
            'track_date' => $todayDate->format('Y-m-d h:i:s'),
            'module_id' => $this->_moduleId,
            'user_id' => null,
            'receiver_role_id' => $activeSaf->current_role,
            'ulb_id' => $activeSaf->ulb_id,
        ];
        $mWorkflowTrack->store($reqWorkflow);
    }

    /**
     * | Generate Payment Receipt(1)
     * | @param request req
     * | Status-Closed
     * | Query Cost-3
     */
    public function generatePaymentReceipt(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['tranNo' => 'required|string']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $propSafsDemand = new PropSafsDemand();
            $transaction = new PropTransaction();
            $propPenalties = new PropPenaltyrebate();
            $paymentReceiptHelper = new PaymentReceiptHelper;
            $mUlbMasters = new UlbMaster();

            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');
            $rebatePenalMstrs = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));

            $onePercKey = $rebatePenalMstrs->where('id', 1)->first()['value'];
            $specialRebateKey = $rebatePenalMstrs->where('id', 6)->first()['value'];
            $firstQtrKey = $rebatePenalMstrs->where('id', 2)->first()['value'];
            $lateAssessKey = $rebatePenalMstrs->where('id', 5)->first()['value'];
            $onlineRebate = $rebatePenalMstrs->where('id', 3)->first()['value'];

            $safTrans = $transaction->getPropByTranPropId($req->tranNo);
            // Saf Payment
            $safId = $safTrans->saf_id;
            $reqSafId = new Request(['id' => $safId]);
            $activeSafDetails = $this->details($reqSafId);
            $calDemandAmt = $safTrans->demand_amt;
            $checkOtherTaxes =  $propSafsDemand->getFirstDemandBySafId($safId);

            $mDescriptions = $paymentReceiptHelper->readDescriptions($checkOtherTaxes);      // Check the Taxes are Only Holding or Not

            $fromFinYear = $safTrans->from_fyear;
            $fromFinQtr = $safTrans->from_qtr;
            $upToFinYear = $safTrans->to_fyear;
            $upToFinQtr = $safTrans->to_qtr;

            // Get Property Penalties against property transaction
            $penalRebates = $propPenalties->getPropPenalRebateByTranId($safTrans->id);
            $onePercPanalAmt = $penalRebates->where('head_name', $onePercKey)->first()['amount'] ?? "";
            $rebateAmt = $penalRebates->where('head_name', 'Rebate')->first()['amount'] ?? "";
            $specialRebateAmt = $penalRebates->where('head_name', $specialRebateKey)->first()['amount'] ?? "";
            $firstQtrRebate = $penalRebates->where('head_name', $firstQtrKey)->first()['amount'] ?? "";
            $lateAssessPenalty = $penalRebates->where('head_name', $lateAssessKey)->first()['amount'] ?? "";
            $jskOrOnlineRebate = collect($penalRebates)->where('head_name', $onlineRebate)->first()->amount ?? 0;

            $taxDetails = $paymentReceiptHelper->readPenalyPmtAmts($lateAssessPenalty, $onePercPanalAmt, $rebateAmt,  $specialRebateAmt, $firstQtrRebate, $safTrans->amount, $jskOrOnlineRebate);   // Get Holding Tax Dtls
            $totalRebatePenals = $paymentReceiptHelper->calculateTotalRebatePenals($taxDetails);
            // Get Ulb Details
            $ulbDetails = $mUlbMasters->getUlbDetails($activeSafDetails['ulb_id']);
            // Response Return Data
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => Carbon::parse($safTrans->tran_date)->format('d-m-Y'),
                "transactionNo" => $safTrans->tran_no,
                "transactionTime" => $safTrans->created_at->format('H:i:s'),
                "applicationNo" => $activeSafDetails['saf_no'],
                "customerName" => $activeSafDetails['applicant_name'],
                "receiptWard" => $activeSafDetails['new_ward_no'],
                "address" => $activeSafDetails['prop_address'],
                "paidFrom" => $fromFinYear,
                "paidFromQtr" => $fromFinQtr,
                "paidUpto" => $upToFinYear,
                "paidUptoQtr" => $upToFinQtr,
                "paymentMode" => $safTrans->payment_mode,
                "bankName" => $safTrans->bank_name,
                "branchName" => $safTrans->branch_name,
                "chequeNo" => $safTrans->cheque_no,
                "chequeDate" => ymdToDmyDate($safTrans->cheque_date),
                "demandAmount" => roundFigure((float)$calDemandAmt),
                "taxDetails" => $taxDetails,
                "totalRebate" => $totalRebatePenals['totalRebate'],
                "totalPenalty" => $totalRebatePenals['totalPenalty'],
                "ulbId" => $activeSafDetails['ulb_id'],
                "oldWardNo" => $activeSafDetails['old_ward_no'],
                "newWardNo" => $activeSafDetails['new_ward_no'],
                "towards" => $mTowards,
                "description" => $mDescriptions,
                "totalPaidAmount" => $safTrans->amount,
                "paidAmtInWords" => getIndianCurrency($safTrans->amount),
                "tcName" => $safTrans->tc_name,
                "tcMobile" => $safTrans->tc_mobile,
                "ulbDetails" => $ulbDetails
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "010116", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "", "010116", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Property Transactions
     * | @param req requested parameters
     * | @var userId authenticated user id
     * | @var propTrans Property Transaction details of the Logged In User
     * | @return responseMsg
     * | Status-Closed
     * | Run time Complexity-346ms
     * | Rating - 3
     */
    public function getPropTransactions(Request $req)
    {
        try {
            $auth = authUser($req);
            $userId = $auth->id;
            if ($auth->user_type == 'Citizen')
                $propTrans = $this->Repository->getPropTransByCitizenUserId($userId, 'citizen_id');
            else
                $propTrans = $this->Repository->getPropTransByCitizenUserId($userId, 'user_id');

            return responseMsgs(true, "Transactions History", remove_null($propTrans), "010117", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010117", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Transactions by Property id or SAF id
     * | @param Request $req
     */
    public function getTransactionBySafPropId(Request $req)
    {
        try {
            $propTransaction = new PropTransaction();
            if ($req->safId)                                                // Get By SAF Id
                $propTrans = $propTransaction->getPropTransBySafId($req->safId);
            if ($req->propertyId)                                           // Get by Property Id
                $propTrans = $propTransaction->getPropTransByPropId($req->propertyId);

            return responseMsg(true, "Property Transactions", remove_null($propTrans));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Property Details by Property Holding No
     * | Rating - 2
     * | Run Time Complexity-500 ms
     */
    public function getPropByHoldingNo(Request $req)
    {
        $req->validate(
            isset($req->holdingNo) ? ['holdingNo' => 'required'] : ['propertyId' => 'required|numeric']
        );
        try {
            $mProperties = new PropProperty();
            $mPropFloors = new PropFloor();
            $mPropOwners = new PropOwner();
            $propertyDtl = [];
            if ($req->holdingNo) {
                $properties = $mProperties->getPropDtls()
                    ->where('prop_properties.holding_no', $req->holdingNo)
                    ->first();
            }

            if ($req->propertyId) {
                $properties = $mProperties->getPropDtls()
                    ->where('prop_properties.id', $req->propertyId)
                    ->first();
            }
            if (!$properties) {
                throw new Exception("Property Not Found");
            }

            $floors = $mPropFloors->getPropFloors($properties->id);        // Model function to get Property Floors
            $owners = $mPropOwners->getOwnersByPropId($properties->id);    // Model function to get Property Owners
            $propertyDtl = collect($properties);
            $propertyDtl['floors'] = $floors;
            $propertyDtl['owners'] = $owners;

            return responseMsgs(true, "Property Details", remove_null($propertyDtl), "010112", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Site Verification
     * | @param req requested parameter
     * | Status-Closed
     */
    public function siteVerification(ReqSiteVerification $req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $propertyType = collect(Config::get('PropertyConstaint.PROPERTY-TYPE'))->flip();
            $propActiveSaf = new PropActiveSaf();
            $verification = new PropSafVerification();
            $mWfRoleUsermap = new WfRoleusermap();
            $verificationDtl = new PropSafVerificationDtl();
            $userId = authUser($req)->id;
            $ulbId = authUser($req)->ulb_id;
            $vacantLand = $propertyType['VACANT LAND'];

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
                    $propActiveSaf->verifyAgencyFieldStatus($req->safId);                                         // Enable Fields Verify Status
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
            if ($req->propertyType != $vacantLand) {
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
                    $status = $verificationDtl->store($floorReq);
                }
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Geo Tagging Photo Uploads
     * | @param request req
     * | @var relativePath Geo Tagging Document Ralative path
     * | @var array images- request image path
     * | @var array directionTypes- request direction types
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
            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", "258ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Demandable Amount By SAF ID
     * | @param $req
     * | Query Run time -272ms 
     * | Rating-2
     */
    public function getDemandBySafId(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric'
        ]);
        try {
            $mWfRoleusermap = new WfRoleusermap();
            $mPropTransactions = new PropTransaction();
            $jskRole = Config::get('PropertyConstaint.JSK_ROLE');
            $tcRole = 5;
            $user = authUser($req);
            $userId = $user->id;
            $safDetails = $this->details($req);
            if ($safDetails['payment_status'] == 1) {       // Get Transaction no if the payment is done
                $transaction = $mPropTransactions->getLastTranByKeyId('saf_id', $req->id);
                $demand['tran_no'] = $transaction->tran_no;
            }
            $workflowId = $safDetails['workflow_id'];
            $mreqs = new Request([
                "workflowId" => $workflowId,
                "userId" => $userId
            ]);
            // $role = $mWfRoleusermap->getRoleByUserWfId($mreqs);
            $role = $mWfRoleusermap->getRoleByUserId($mreqs);

            if (isset($role) && in_array($role->wf_role_id, [$jskRole, $tcRole]))
                $demand['can_pay'] = true;
            else
                $demand['can_pay'] = false;

            $safTaxes = $this->calculateSafBySafId($req);
            if ($safTaxes->original['status'] == false)
                throw new Exception($safTaxes->original['message']);
            $req = $safDetails;
            $demand['basicDetails'] = [
                "ulb_id" => $req['ulb_id'],
                "saf_no" => $req['saf_no'],
                "prop_address" => $req['prop_address'],
                "is_mobile_tower" => $req['is_mobile_tower'],
                "is_hoarding_board" => $req['is_hoarding_board'],
                "is_petrol_pump" => $req['is_petrol_pump'],
                "is_water_harvesting" => $req['is_water_harvesting'],
                "zone_mstr_id" => $req['zone_mstr_id'],
                "holding_no" => $req['new_holding_no'] ?? $req['holding_no'],
                "old_ward_no" => $req['old_ward_no'],
                "new_ward_no" => $req['new_ward_no'],
                "property_type" => $req['property_type'],
                "holding_type" => $req['holding_type'],
                "doc_upload_status" => $req['doc_upload_status'],
                "ownership_type" => $req['ownership_type']
            ];
            $demand['amounts'] = $safTaxes->original['data']['demand'] ?? [];
            $demand['details'] = collect($safTaxes->original['data']['details'])->values();
            $demand['taxDetails'] = collect($safTaxes->original['data']['taxDetails']) ?? [];
            $demand['paymentStatus'] = $safDetails['payment_status'];
            $demand['applicationNo'] = $safDetails['saf_no'];
            return responseMsgs(true, "Demand Details", remove_null($demand), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), []);
        }
    }

    # code by sandeep bara 
    # date 31-01-2023
    // ----------start------------
    public function getVerifications(Request $request)
    {
        $request->validate([
            'verificationId' => 'required|digits_between:1,9223372036854775807',
        ]);

        try {
            $data = array();
            $verifications = PropSafVerification::select(
                'prop_saf_verifications.*',
                'p.property_type',
                'r.road_type',
                'u.ward_name as ward_no',
                'u1.ward_name as new_ward_no',
                "users.name as user_name"
            )
                ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_saf_verifications.prop_type_id')
                ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_saf_verifications.road_type_id')
                ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_saf_verifications.ward_id')
                ->leftJoin('ulb_ward_masters as u1', 'u1.id', '=', 'prop_saf_verifications.new_ward_id')
                ->leftjoin('users', 'users.id', '=', 'prop_saf_verifications.user_id')
                ->where("prop_saf_verifications.id", $request->verificationId)
                ->first();
            if (!$verifications) {
                throw new Exception("verification Data NOt Found");
            }
            $saf = PropActiveSaf::select(
                'prop_active_safs.*',
                'p.property_type',
                'r.road_type',
                'u.ward_name as ward_no',
                'u1.ward_name as new_ward_no',
                "ownership_types.ownership_type"
            )
                ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.prop_type_mstr_id')
                ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_active_safs.road_type_mstr_id')
                ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_active_safs.ward_mstr_id')
                ->leftjoin('ulb_ward_masters as u1', 'u.id', '=', 'prop_active_safs.new_ward_mstr_id')
                ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                ->where("prop_active_safs.id", $verifications->saf_id)
                ->first();
            $tbl = "prop_active_safs";
            if (!$saf) {
                $saf = DB::table("prop_rejected_safs")
                    ->select(
                        'prop_rejected_safs.*',
                        'p.property_type',
                        'r.road_type',
                        'u.ward_name as ward_no',
                        'u1.ward_name as new_ward_no',
                        "ownership_types.ownership_type"
                    )
                    ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_rejected_safs.prop_type_mstr_id')
                    ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_rejected_safs.road_type_mstr_id')
                    ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_rejected_safs.ward_mstr_id')
                    ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_rejected_safs.ownership_type_mstr_id')
                    ->leftJoin('ulb_ward_masters as u1', 'u1.id', '=', 'prop_rejected_safs.new_ward_mstr_id')
                    ->where("prop_rejected_safs.id", $verifications->saf_id)
                    ->first();
                $tbl = "prop_rejected_safs";
            }
            if (!$saf) {
                $saf = DB::table("prop_safs")
                    ->select(
                        'prop_safs.*',
                        'p.property_type',
                        'r.road_type',
                        'u.ward_name as ward_no',
                        'u1.ward_name as new_ward_no',
                        "ownership_types.ownership_type"
                    )
                    ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_safs.prop_type_mstr_id')
                    ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_safs.road_type_mstr_id')
                    ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_safs.ward_mstr_id')
                    ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_safs.ownership_type_mstr_id')
                    ->leftJoin('ulb_ward_masters as u1', 'u1.id', '=', 'prop_safs.new_ward_mstr_id')
                    ->where("prop_safs.id", $verifications->saf_id)
                    ->first();
                $tbl = "prop_safs";
            }
            if (!$saf) {
                throw new Exception("Saf Data Not Found");
            }
            $floars = DB::table($tbl . "_floors")
                ->select($tbl . "_floors.*", 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->leftjoin('ref_prop_floors as f', 'f.id', '=', $tbl . "_floors.floor_mstr_id")
                ->leftjoin('ref_prop_usage_types as u', 'u.id', '=', $tbl . "_floors.usage_type_mstr_id")
                ->leftjoin('ref_prop_occupancy_types as o', 'o.id', '=', $tbl . "_floors.occupancy_type_mstr_id")
                ->leftjoin('ref_prop_construction_types as c', 'c.id', '=', $tbl . "_floors.const_type_mstr_id")
                ->where($tbl . "_floors.saf_id", $saf->id)
                ->get();
            $verifications_detals = PropSafVerificationDtl::select('prop_saf_verification_dtls.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->leftjoin('ref_prop_floors as f', 'f.id', '=', 'prop_saf_verification_dtls.floor_mstr_id')
                ->leftjoin('ref_prop_usage_types as u', 'u.id', '=', 'prop_saf_verification_dtls.usage_type_id')
                ->leftjoin('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_saf_verification_dtls.occupancy_type_id')
                ->leftjoin('ref_prop_construction_types as c', 'c.id', '=', 'prop_saf_verification_dtls.construction_type_id')
                ->where("verification_id", $verifications->id)
                ->get();

            $prop_compairs = [
                [
                    "key" => "Ward No",
                    "values" => $saf->ward_mstr_id == $verifications->ward_id,
                    "according_application" => $saf->ward_no,
                    "according_verification" => $verifications->ward_no,
                ],
                [
                    "key" => "New Ward No",
                    "values" => $saf->new_ward_mstr_id == $verifications->new_ward_id,
                    "according_application" => $saf->new_ward_no,
                    "according_verification" => $verifications->new_ward_no,
                ],
                [
                    "key" => "Property Type",
                    "values" => $saf->prop_type_mstr_id == $verifications->prop_type_id,
                    "according_application" => $saf->property_type,
                    "according_verification" => $verifications->property_type,
                ],
                [
                    "key" => "Plot Area",
                    "values" => $saf->area_of_plot == $verifications->area_of_plot,
                    "according_application" => $saf->area_of_plot,
                    "according_verification" => $verifications->area_of_plot,
                ],
                [
                    "key" => "Road Type",
                    "values" => $saf->road_type_mstr_id == $verifications->road_type_id,
                    "according_application" => $saf->road_type,
                    "according_verification" => $verifications->road_type,
                ],
                [
                    "key" => "Mobile Tower",
                    "values" => $saf->is_mobile_tower == $verifications->has_mobile_tower,
                    "according_application" => $saf->is_mobile_tower ? "Yes" : "No",
                    "according_verification" => $verifications->has_mobile_tower ? "Yes" : "No",
                ],
                [
                    "key" => "Hoarding Board",
                    "values" => $saf->is_hoarding_board == $verifications->has_hoarding,
                    "according_application" => $saf->is_hoarding_board ? "Yes" : "No",
                    "according_verification" => $verifications->has_hoarding ? "Yes" : "No",
                ],
                [
                    "key" => "Petrol Pump",
                    "values" => $saf->is_petrol_pump == $verifications->is_petrol_pump,
                    "according_application" => $saf->is_petrol_pump ? "Yes" : "No",
                    "according_verification" => $verifications->is_petrol_pump ? "Yes" : "No",
                ],
                [
                    "key" => "Water Harvesting",
                    "values" => $saf->is_water_harvesting == $verifications->has_water_harvesting,
                    "according_application" => $saf->is_water_harvesting ? "Yes" : "No",
                    "according_verification" => $verifications->has_water_harvesting ? "Yes" : "No",
                ],
            ];
            $size = sizeOf($floars) >= sizeOf($verifications_detals) ? $floars : $verifications_detals;
            $keys = sizeOf($floars) >= sizeOf($verifications_detals) ? "floars" : "detals";
            $floors_compais = array();
            $floors_compais = $size->map(function ($val, $key) use ($floars, $verifications_detals, $keys) {
                if ($keys == "floars") {
                    // $saf_data=($floars->where("id",$val->id))->values();
                    // $verification=($verifications_detals->where("saf_floor_id",$val->id))->values();
                    $saf_data = collect(array_values(objToArray(($floars->where("id", $val->id))->values())))->all();
                    $verification = collect(array_values(objToArray(($verifications_detals->where("saf_floor_id", $val->id))->values())))->all();
                } else {
                    // $saf_data=($floars->where("id",$val->saf_floor_id))->values();
                    // $verification=($verifications_detals->where("id",$val->id))->values();
                    $saf_data = collect(array_values(objToArray(($floars->where("id", $val->saf_floor_id))->values())))->all();
                    $verification = collect(array_values(objToArray(($verifications_detals->where("id", $val->id))->values())))->all();
                }
                return [
                    "floar_name" => $val->floor_name,
                    "values" => [
                        [
                            "key" => "Usage Type",
                            "values" => ($saf_data[0]->usage_type_mstr_id ?? "") == ($verification[0]['usage_type_id'] ?? ""),
                            "according_application" => $saf_data[0]->usage_type ?? "",
                            "according_verification" => $verification[0]['usage_type_id'] ?? "",
                        ],
                        [
                            "key" => "Occupancy Type",
                            "values" => ($saf_data[0]->occupancy_type_mstr_id ?? "") == ($verification[0]['occupancy_type_id'] ?? ""),
                            "according_application" => $saf_data[0]->occupancy_type ?? "",
                            "according_verification" => $verification[0]['occupancy_type'] ?? "",
                        ],
                        [
                            "key" => "Construction Type",
                            "values" => ($saf_data[0]->const_type_mstr_id ?? "") == ($verification[0]['construction_type_id'] ?? ""),
                            "according_application" => $saf_data[0]->construction_type ?? "",
                            "according_verification" => $verification[0]['construction_type'] ?? "",
                        ],
                        [
                            "key" => "Built Up Area (in Sq. Ft.)",
                            "values" => ($saf_data[0]->builtup_area ?? "") == ($verification[0]['builtup_area'] ?? ""),
                            "according_application" => $saf_data[0]->builtup_area ?? "",
                            "according_verification" => $verification[0]['builtup_area'] ?? "",
                        ],
                        [
                            "key" => "Date of Completion",
                            "values" => ($saf_data[0]->date_from ?? "") == ($verification[0]['date_from'] ?? ""),
                            "according_application" => $saf_data[0]->date_from ?? "",
                            "according_verification" => $verification[0]['date_from'] ?? "",
                        ]
                    ]
                ];
            });
            $message = "ULB TC Verification Details";
            if ($verifications->agency_verification) {
                $PropertyDeactivate = new \App\Repository\Property\Concrete\PropertyDeactivate();
                $geoTagging = PropSafGeotagUpload::where("saf_id", $saf->id)->get()->map(function ($val) use ($PropertyDeactivate) {
                    $val->paths = $PropertyDeactivate->readDocumentPath($val->relative_path . "/" . $val->image_path);
                    return $val;
                });
                $message = "TC Verification Details";
                $data["geoTagging"] = $geoTagging;
            } else {
                $owners = DB::table($tbl . "_owners")
                    ->select($tbl . "_owners.*")
                    ->where($tbl . "_owners.saf_id", $saf->id)
                    ->get();

                $safDetails = $saf;
                $safDetails = json_decode(json_encode($safDetails), true);
                $safDetails['floors'] = $floars;
                $safDetails['owners'] = $owners;
                $req = $safDetails;
                $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
                $safCalculation = new SafCalculation();
                $request = new Request($array);
                $safTaxes = $safCalculation->calculateTax($request);
                // dd($array);
                // $safTaxes = json_decode(json_encode($safTaxes), true);

                $safDetails2 = json_decode(json_encode($verifications), true);

                $safDetails2["ward_mstr_id"] = $safDetails2["ward_id"];
                $safDetails2["prop_type_mstr_id"] = $safDetails2["prop_type_id"];
                $safDetails2["land_occupation_date"] = $saf->land_occupation_date;
                $safDetails2["ownership_type_mstr_id"] = $saf->ownership_type_mstr_id;
                $safDetails2["zone_mstr_id"] = $saf->zone_mstr_id;
                $safDetails2["road_type_mstr_id"] = $saf->road_type_mstr_id;
                $safDetails2["road_width"] = $saf->road_width;
                $safDetails2["is_gb_saf"] = $saf->is_gb_saf;
                $safDetails2["is_trust"] = $saf->is_trust;
                $safDetails2["trust_type"] = $saf->trust_type;


                $safDetails2["is_mobile_tower"] = $safDetails2["has_mobile_tower"];
                $safDetails2["tower_area"] = $safDetails2["tower_area"];
                $safDetails2["tower_installation_date"] = $safDetails2["tower_installation_date"];

                $safDetails2["is_hoarding_board"] = $safDetails2["has_hoarding"];
                $safDetails2["hoarding_area"] = $safDetails2["hoarding_area"];
                $safDetails2["hoarding_installation_date"] = $safDetails2["hoarding_installation_date"];

                $safDetails2["is_petrol_pump"] = $safDetails2["is_petrol_pump"];
                $safDetails2["under_ground_area"] = $safDetails2["underground_area"];
                $safDetails2["petrol_pump_completion_date"] = $safDetails2["petrol_pump_completion_date"];

                $safDetails2["is_water_harvesting"] = $safDetails2["has_water_harvesting"];
                $safDetails2["rwh_date_from"] = $safDetails2["rwh_date_from"];

                $safDetails2['floors'] = $verifications_detals;
                $safDetails2['floors'] = $safDetails2['floors']->map(function ($val) {
                    $val->usage_type_mstr_id    = $val->usage_type_id;
                    $val->const_type_mstr_id    = $val->construction_type_id;
                    $val->occupancy_type_mstr_id = $val->occupancy_type_id;
                    $val->builtup_area          = $val->builtup_area;
                    $val->date_from             = $val->date_from;
                    $val->date_upto             = $val->date_to;
                    return $val;
                });


                $safDetails2['owners'] = $owners;
                $array2 = $this->generateSafRequest($safDetails2);
                // dd($array);
                $request2 = new Request($array2);
                $safTaxes2 = $safCalculation->calculateTax($request2);
                // $safTaxes2 = json_decode(json_encode($safTaxes2), true);
                // dd($safTaxes,$array);
                if (!$safTaxes->original["status"]) {
                    throw new Exception($safTaxes->original["message"]);
                }
                if (!$safTaxes2->original["status"]) {
                    throw new Exception($safTaxes2->original["message"]);
                }
                $safTaxes3 = $this->reviewTaxCalculation($safTaxes);
                $safTaxes4 = $this->reviewTaxCalculation($safTaxes2);
                // dd(json_decode(json_encode($safTaxes), true));
                $compairTax = $this->reviewTaxCalculationCom($safTaxes, $safTaxes2);

                $safTaxes2 = json_decode(json_encode($safTaxes4), true);
                $safTaxes = json_decode(json_encode($safTaxes3), true);
                $compairTax = json_decode(json_encode($compairTax), true);

                $data["Tax"]["according_application"] = $safTaxes["original"]["data"];
                $data["Tax"]["according_verification"] = $safTaxes2["original"]["data"];
                $data["Tax"]["compairTax"] = $compairTax["original"]["data"];
            }
            $data["saf_details"] = $saf;
            $data["employee_details"] = ["user_name" => $verifications->user_name, "date" => ymdToDmyDate($verifications->created_at)];
            $data["property_comparison"] = $prop_compairs;
            $data["floor_comparison"] = $floors_compais;
            return responseMsgs(true, $message, remove_null($data), "010121", "1.0", "258ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getSafVerificationList(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807',
        ]);
        try {
            $data = array();
            $verifications = PropSafVerification::select(
                'id',
                DB::raw("TO_CHAR(created_at,'dd-mm-YYYY') as created_at"),
                'agency_verification',
                "ulb_verification"
            )
                // ->where("prop_saf_verifications.status", 1)     #_removed beacuse not showing data after approval
                ->where("prop_saf_verifications.saf_id", $request->applicationId)
                ->get();

            $data = $verifications->map(function ($val) {
                $val->veryfied_by = $val->agency_verification ? "AGENCY TC" : "ULB TC";
                return $val;
            });
            return responseMsgs(true, "Data Fetched", remove_null($data), "010122", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    private function reviewTaxCalculation(object $response)
    {
        try {
            $finalResponse['demand'] = $response->original['data']['demand'];
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);
            $finalTaxReview = collect();
            $review = collect($reviewDetails)->map(function ($reviewDetail) use ($finalTaxReview) {
                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview) {
                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview) {
                        $first = $floor->first();
                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });
            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) {
                return collect($collection)->pipe(function ($collect) {
                    $quaters['floors'] = $collect;
                    $groupByFloors = $collect->groupBy(['quarterYear', 'qtr']);
                    $quaterlyTaxes = collect();
                    collect($groupByFloors)->map(function ($qtrYear) use ($quaterlyTaxes) {
                        return collect($qtrYear)->map(function ($qtr, $key) use ($quaterlyTaxes) {
                            return collect($qtr)->pipe(function ($floors) use ($quaterlyTaxes, $key) {
                                $taxes = [
                                    'key' => $key,
                                    'effectingFrom' => $floors->first()['dateFrom'],
                                    'qtr' => $floors->first()['qtr'],
                                    'arv' => roundFigure($floors->sum('arv')),
                                    'holdingTax' => roundFigure($floors->sum('holdingTax')),
                                    'waterTax' => roundFigure($floors->sum('waterTax')),
                                    'latrineTax' => roundFigure($floors->sum('latrineTax')),
                                    'educationTax' => roundFigure($floors->sum('educationTax')),
                                    'healthTax' => roundFigure($floors->sum('healthTax')),
                                    'rwhPenalty' => roundFigure($floors->sum('rwhPenalty')),
                                    'quaterlyTax' => roundFigure($floors->sum('totalTax')),
                                ];
                                $quaterlyTaxes->push($taxes);
                            });
                        });
                    });
                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
                    return $quaters;
                });
            });
            $finalResponse['details'] = $reviewCalculation;
            return responseMsg(true, "", $finalResponse);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    private function reviewTaxCalculationCom(object $response, object $response2)
    {

        try {
            $finalResponse['demand'] = $response->original['data']['demand'];
            $finalResponse2['demand'] = $response2->original['data']['demand'];
            // dd( $response->original['data'],  $response2->original['data']);
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);
            $reviewDetails2 = collect($response2->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);

            $finalTaxReview = collect();
            $finalTaxReview2 = collect();

            $review = collect($reviewDetails)->map(function ($reviewDetail) use ($finalTaxReview) {

                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview) {

                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview) {

                        $first = $floor->first();

                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });

            $review2 = collect($reviewDetails2)->map(function ($reviewDetail) use ($finalTaxReview2) {

                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview2) {

                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview2) {

                        $first = $floor->first();

                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview2->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });

            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $ruleSetCollections2 = collect($finalTaxReview2)->groupBy(['ruleSet']);

            $reviewCalculation = collect($ruleSetCollections2)->map(function ($collection, $key) use ($ruleSetCollections) {
                $collection2 = collect($ruleSetCollections[$key] ?? []);
                // dd($key);
                return collect($collection)->pipe(function ($collect) use ($collection2) {

                    $quaters['floors'] = $collect;
                    $quaters2['floors'] = $collection2;

                    $groupByFloors = $collect->groupBy(['quarterYear', 'qtr']);
                    $groupByFloors2 = $collection2->groupBy(['quarterYear', 'qtr']) ?? [];

                    $quaterlyTaxes = collect();

                    collect($groupByFloors)->map(function ($qtrYear, $key1) use ($quaterlyTaxes, $groupByFloors2) {

                        $qtrYear2 = collect($groupByFloors2[$key1] ?? []);

                        return collect($qtrYear)->map(function ($qtr, $key) use ($quaterlyTaxes, $qtrYear2) {

                            $qtr2 = $qtrYear2[$key] ?? collect([]);

                            return collect($qtr)->pipe(function ($floors) use ($quaterlyTaxes, $key, $qtr2) {

                                $taxes = [
                                    'key' => $key,
                                    'effectingFrom' => $floors->first()['dateFrom'],
                                    'qtr' => $floors->first()['qtr'],
                                    'arv' => roundFigure(($floors->sum('arv')) - ($qtr2->sum('arv'))),
                                    'holdingTax' => roundFigure(($floors->sum('holdingTax')) - ($qtr2->sum('holdingTax'))),
                                    'waterTax' => roundFigure(($floors->sum('waterTax')) - ($qtr2->sum('waterTax'))),
                                    'latrineTax' => roundFigure(($floors->sum('latrineTax')) - ($qtr2->sum('latrineTax'))),
                                    'educationTax' => roundFigure(($floors->sum('educationTax')) - ($qtr2->sum('educationTax'))),
                                    'healthTax' => roundFigure(($floors->sum('healthTax')) - ($qtr2->sum('healthTax'))),
                                    'rwhPenalty' => roundFigure(($floors->sum('rwhPenalty')) - ($qtr2->sum('rwhPenalty'))),
                                    'quaterlyTax' => roundFigure(($floors->sum('totalTax')) - ($qtr2->sum('totalTax'))),
                                ];
                                $quaterlyTaxes->push($taxes);
                            });
                        });
                    });

                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
                    return $quaters;
                });
            });
            $finalResponse2['details'] = $reviewCalculation;
            return responseMsg(true, "", $finalResponse2);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    // ---------end----------------
}
