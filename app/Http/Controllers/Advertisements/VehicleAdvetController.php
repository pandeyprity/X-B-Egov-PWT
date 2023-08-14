<?php

namespace App\Http\Controllers\Advertisements;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicles\RenewalRequest;
use App\Http\Requests\Vehicles\StoreRequest;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\AdvActiveVehicle;
use App\Models\Advertisements\AdvChequeDtl;
use App\Models\Advertisements\AdvVehicle;
use App\Models\Advertisements\AdvRejectedVehicle;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Param\AdvMarTransaction;
use App\Models\Param\AdvMarTransactions;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\AdvDetailsTraits;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WorkflowTrack;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\SelfAdvets\iSelfAdvetRepo;

use Carbon\Carbon;


use App\Traits\WorkflowTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class VehicleAdvetController extends Controller
{
    /**
     * | Created On-31-12-2022 
     * | Created By- Anshu Kumar 
     * | Changes By- Bikash Kumar 
     * | Created for the Movable Vehicles Operations
     * | Status - Closed, By Bikash on 24 Apr 2023,  Total no. of lines - 1512, Total Function - 35, Total API - 32
     */


    use WorkflowTrait;
    use AdvDetailsTraits;

    protected $_modelObj;
    protected $Repository;
    protected $_workflowIds;
    protected $_moduleIds;
    protected $_docCode;
    protected $_tempParamId;
    protected $_paramId;
    protected $_baseUrl;
    protected $_wfMasterId;
    protected $_fileUrl;
    public function __construct(iSelfAdvetRepo $self_repo)
    {
        $this->_modelObj = new AdvActiveVehicle();
        // $this->_workflowIds = Config::get('workflow-constants.MOVABLE_VEHICLE_WORKFLOWS');
        $this->_moduleIds = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_docCode = Config::get('workflow-constants.MOVABLE_VEHICLE_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_VCL_ID');
        $this->_paramId = Config::get('workflow-constants.VCL_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->Repository = $self_repo;

        $this->_wfMasterId = Config::get('workflow-constants.VEHICLE_WF_MASTER_ID');
    }

    /**
     * | Apply for new document
     * | Function - 01
     * | API - 01
     */
    public function addNew(StoreRequest $req)
    {
        try {
            // Variable Initialization
            $advVehicle = new AdvActiveVehicle();
            if ($req->auth['user_type'] == 'JSK') {
                $userId = ['userId' => $req->auth['id']];
                $req->request->add($userId);
            } else {
                $citizenId = ['citizenId' => $req->auth['id']];
                $req->request->add($citizenId);
            }

            // $mCalculateRate = new CalculateRate;
            // $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_tempParamId, $req->ulbId); // Generate Application No
            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];
            $req->request->add($applicationNo);

            // $mWfWorkflow=new WfWorkflow();
            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $applicationNo = $advVehicle->addNew($req);                             // Apply Vehicle Application 
            DB::commit();

            return responseMsgs(true, "Successfully Applied the Application !!", ["status" => true, "ApplicationNo" => $applicationNo], "050301", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050301", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Details For Renew
     * | Function - 02
     * | API - 02
     */
    public function applicationDetailsForRenew(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mAdvVehicle = new AdvVehicle();
            $details = $mAdvVehicle->applicationDetailsForRenew($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found !!!");

            return responseMsgs(true, "Application Fetched !!!", remove_null($details), "050302", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050302", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Vehicle Application Renewal
     * | Function - 03
     * | API - 03
     */
    public function renewalApplication(RenewalRequest $req)
    {
        try {
            // Variable Initialization
            $advVehicle = new AdvActiveVehicle();
            if ($req->auth['user_type'] == 'JSK') {
                $userId = ['userId' => $req->auth['id']];
                $req->request->add($userId);
            } else {
                $citizenId = ['citizenId' => $req->auth['id']];
                $req->request->add($citizenId);
            }
            // $mCalculateRate = new CalculateRate;
            // $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_tempParamId, $req->ulbId); // Generate Application No
            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];
            $req->request->add($applicationNo);

            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $applicationNo = $advVehicle->renewalApplication($req);               // Renewal Vehicle Application
            DB::commit();


            return responseMsgs(true, "Successfully Applied the Application !!", ["status" => true, "ApplicationNo" => $applicationNo], "050303", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050303", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Inbox List
     * | @param Request $req
     * | Function - 04
     * | API - 04
     */
    public function listInbox(Request $req)
    {
        try {
            // Variable Initialization
            $mvehicleAdvets = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $inboxList = $mvehicleAdvets->listInbox($roleIds, $ulbId);                          // <----- get Inbox list
            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);

            return responseMsgs(true, "Inbox Applications", $list, "050304", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050304", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Outbox List
     * | Function - 05
     * | API - 05
     */
    public function listOutbox(Request $req)
    {
        try {
            // Variable Initialization
            $mvehicleAdvets = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $outboxList = $mvehicleAdvets->listOutbox($roleIds, $ulbId);                       // <----- Get Outbox list
            if (trim($req->key))
                $outboxList =  searchFilter($outboxList, $req);
            $list = paginator($outboxList, $req);

            return responseMsgs(true, "Outbox Lists", $list, "050305", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050305", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Application Details
     * | Function - 06
     * | API - 06
     */
    public function getDetailsById(Request $req)
    {
        try {
            // Variable Initialization
            $mvehicleAdvets = new AdvActiveVehicle();
            // $data = array();
            $type = NULL;
            $fullDetailsData = array();
            if (isset($req->type)) {
                $type = $req->type;
            }
            if ($req->applicationId) {
                $data = $mvehicleAdvets->getDetailsById($req->applicationId, $type);
            } else {
                throw new Exception("Not Pass Application Id");
            }
            if (!$data) {
                throw new Exception("Not Application Details Found");
            }

            // Basic Details
            $basicDetails = $this->generateVehicleBasicDetails($data); // Trait function to get Vehicle Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            $cardDetails = $this->generateVehicleCardDetails($data);
            $cardElement = [
                'headerTitle' => "Movables Vehicle Advertisment",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);

            $metaReqs['customFor'] = 'MOVABLE';
            $metaReqs['wfRoleId'] = $data['current_roles'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];
            $req->request->add($metaReqs);
            $forwardBackward = $this->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData = remove_null($fullDetailsData);

            $fullDetailsData['application_no'] = $data['application_no'];
            $fullDetailsData['apply_date'] = Carbon::createFromFormat('Y-m-d H:i:s',  $data['created_at'])->format('d-m-Y');
            $fullDetailsData['zone'] = $data['zone'];
            $fullDetailsData['doc_verify_status'] = $data['doc_verify_status'];
            if (isset($data['payment_amount'])) {
                $fullDetailsData['payment_amount'] = $data['payment_amount'];
            }
            $fullDetailsData['timelineData'] = collect($req);
            $fullDetailsData['workflowId'] = $data['workflow_id'];

            return responseMsgs(true, 'Data Fetched', $fullDetailsData, "050306", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050306", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Role Details
     * | Function - 07
     */
    public function getRoleDetails(Request $request)
    {
        // $ulbId = $request->auth['ulb_id'];
        $request->validate([
            'workflowId' => 'required|int'
        ]);
        $roleDetails = DB::table('wf_workflowrolemaps')
            ->select(
                'wf_workflowrolemaps.id',
                'wf_workflowrolemaps.workflow_id',
                'wf_workflowrolemaps.wf_role_id',
                'wf_workflowrolemaps.forward_role_id',
                'wf_workflowrolemaps.backward_role_id',
                'wf_workflowrolemaps.is_initiator',
                'wf_workflowrolemaps.is_finisher',
                'r.role_name as forward_role_name',
                'rr.role_name as backward_role_name'
            )
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->where('workflow_id', $request->workflowId)
            ->where('wf_role_id', $request->wfRoleId)
            ->first();
        return responseMsgs(true, "Data Retrived", remove_null($roleDetails));
    }


    /**
     * | Get Applied Applications by Logged In Citizen
     * | Function - 08
     * | API - 07
     */
    public function listAppliedApplications(Request $req)
    {
        try {
            // Variable Initialization
            $citizenId = $req->auth['id'];
            $mvehicleAdvets = new AdvActiveVehicle();
            $applications = $mvehicleAdvets->listAppliedApplications($citizenId);

            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Applied Applications", $data1, "050307", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050307", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /** 
     * | Escalate Application
     * | Function - 09
     * | API - 08
     */
    public function escalateApplication(Request $request)
    {
        $request->validate([
            "escalateStatus" => "required|int",
            "applicationId" => "required|int",
        ]);
        try {
            // Variable Initialization

            $userId = $request->auth['id'];
            $applicationId = $request->applicationId;
            $data = AdvActiveVehicle::find($applicationId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();

            return responseMsgs(true, $request->escalateStatus == 1 ? 'Movable Vechicle is Escalated' : "Movable Vechicle is removed from Escalated", '', "050308", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050308", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Escalated Application List
     * | Function - 10
     * | API - 09
     */
    public function listEscalated(Request $req)
    {
        try {
            // Variable Initialization

            $mWfWardUser = new WfWardUser();
            $userId = $req->auth['id'];
            $ulbId = $req->auth['ulb_id'];

            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                           // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });

            $mWfWorkflow = new WfWorkflow();
            $workflowId = $mWfWorkflow->getulbWorkflowId($this->_wfMasterId, $ulbId);      // get workflow Id

            $advData = $this->Repository->specialVehicleInbox($workflowId)                      // Repository function to get Advertiesment Details
                ->where('is_escalate', 1)
                ->where('adv_active_vehicles.ulb_id', $ulbId);
            // ->whereIn('ward_mstr_id', $wardId)
            // ->get();
            if (trim($req->key))
                $advData =  searchFilter($advData, $req);
            $list = paginator($advData, $req);

            return responseMsgs(true, "Data Fetched", $list, "050309", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050309", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Forward or Backward Application
     * | Function - 11
     * | API - 10
     */
    public function forwardNextLevel(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // Variable Initialization
            $mAdvActiveVehicle = AdvActiveVehicle::find($request->applicationId);
            if ($mAdvActiveVehicle->parked == NULL && $mAdvActiveVehicle->doc_upload_status == '0')
                throw new Exception("Document Rejected Please Send Back to Citizen !!!");
            if ($mAdvActiveVehicle->parked == '1' && $mAdvActiveVehicle->doc_upload_status == '0')
                throw new Exception("Document Are Not Re-upload By Citizen !!!");
            if ($mAdvActiveVehicle->doc_verify_status == '0' && $mAdvActiveVehicle->parked == NULL)
                throw new Exception("Please Verify All Documents To Forward The Application !!!");
            if ($mAdvActiveVehicle->zone == NULL)
                throw new Exception("Zone Not Selected !!!");
            $mAdvActiveVehicle->last_role_id = $request->current_roles;
            $mAdvActiveVehicle->current_roles = $request->receiverRoleId;
            $mAdvActiveVehicle->save();

            $metaReqs['moduleId'] = $this->_moduleIds;
            $metaReqs['workflowId'] = $mAdvActiveVehicle->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_vehicles.id";
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            // Vehicle Application Update Current Role Updation
            DB::beginTransaction();
            $track->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "050310", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050310", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Post Independent Comment
     * | Function - 12
     * | API - 11
     */
    public function commentApplication(Request $request)
    {
        $request->validate([
            'comment' => 'required',
            'applicationId' => 'required|integer',
            'senderRoleId' => 'nullable|integer'
        ]);
        try {
            // Variable Initialization
            $userId = $request->auth['id'];
            $userType = $request->auth['user_type'];
            $workflowTrack = new WorkflowTrack();
            $mWfRoleUsermap = new WfRoleusermap();
            $mAdvActiveVehicle = AdvActiveVehicle::find($request->applicationId);                // Advertisment Details
            $mModuleId = $this->_moduleIds;
            $metaReqs = array();
            $metaReqs = [
                'workflowId' => $mAdvActiveVehicle->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "adv_active_vehicles.id",
                'refTableIdValue' => $mAdvActiveVehicle->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment
            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $mAdvActiveVehicle->workflow_id,
                    'userId' => $userId,
                ]);
                $wfRoleId = $mWfRoleUsermap->getRoleByUserWfId($roleReqs);
                $metaReqs = array_merge($metaReqs, ['senderRoleId' => $wfRoleId->wf_role_id]);
                $metaReqs = array_merge($metaReqs, ['user_id' => $userId]);
            }
            $request->request->add($metaReqs);
            DB::beginTransaction();
            // Save On Workflow Track For Level Independent
            $workflowTrack->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "050311", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050311", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | View Vehicle upload document
     * | Function - 13
     * | API - 12
     */
    public function viewVehicleDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), "", "050311", "1.0", "", "POST", $req->deviceId ?? "");
        }
        if ($req->type == 'Active')
            $workflowId = AdvActiveVehicle::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Approve')
            $workflowId = AdvVehicle::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Reject')
            $workflowId = AdvRejectedVehicle::find($req->applicationId)->workflow_id;
        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        if ($req->applicationId && $req->type) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId, $workflowId);
        } else {
            throw new Exception("Required Application Id And Application Type ");
        }
        $appUrl = $this->_fileUrl;
        $data1['data'] = collect($data)->map(function ($value) use ($appUrl) {
            $value->doc_path = $appUrl . $value->doc_path;
            return $value;
        });
        return $data1;
    }

    /**
     * | Get Uploaded Active Document by application ID
     * | Function - 14
     * | API - 13
     */
    public function viewActiveDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        $workflowId = AdvActiveVehicle::find($req->applicationId)->workflow_id;

        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        $data = $mWfActiveDocument->uploadedActiveDocumentsViewById($req->applicationId, $workflowId);
        $appUrl = $this->_fileUrl;
        $data1['data'] = collect($data)->map(function ($value) use ($appUrl) {
            $value->doc_path = $appUrl . $value->doc_path;
            return $value;
        });
        return $data1;
    }

    /**
     * | Workflow View Uploaded Document by application ID
     * | Function - 15
     * | API - 14
     */
    public function viewDocumentsOnWorkflow(Request $req)
    {
        // Variable Initialization

        if (isset($req->type) && $req->type == 'Approve')
            $workflowId = AdvVehicle::find($req->applicationId)->workflow_id;
        else
            $workflowId = AdvActiveVehicle::find($req->applicationId)->workflow_id;
        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        if ($req->applicationId) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId, $workflowId);
        }
        $appUrl = $this->_fileUrl;
        $data1 = collect($data)->map(function ($value) use ($appUrl) {
            $value->doc_path = $appUrl . $value->doc_path;
            return $value;
        });

        return responseMsgs(true, "Data Fetched", remove_null($data1), "050314", "1.0", responseTime(), "POST", "");
    }

    /**
     * | Final Approval and Rejection of the Application 
     * | Function - 16
     * | Status- closed
     * | API - 15
     */
    public function approvedOrReject(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'roleId' => 'required',
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            // Check if the Current User is Finisher or Not         
            $mAdvActiveVehicle = AdvActiveVehicle::find($req->applicationId);
            $getFinisherQuery = $this->getFinisherId($mAdvActiveVehicle->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsgs(false, " Access Forbidden", "");
            }
            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $typology = $mAdvActiveVehicle->typology;
                $zone = $mAdvActiveVehicle->zone;
                if ($zone == NULL) {
                    throw new Exception("Zone Not Selected !!!");
                }
                $mCalculateRate = new CalculateRate();
                $amount = $mCalculateRate->getMovableVehiclePayment($typology, $zone, $mAdvActiveVehicle->license_from, $mAdvActiveVehicle->license_to);
                $payment_amount = ['payment_amount' => $amount];
                $req->request->add($payment_amount);

                // $mCalculateRate = new CalculateRate;
                // $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_paramId, $mAdvActiveVehicle->ulb_id); // Generate Application No
                $idGeneration = new PrefixIdGenerator($this->_paramId, $mAdvActiveVehicle->ulb_id);
                $generatedId = $idGeneration->generate();
                // approved Vehicle Application replication
                if ($mAdvActiveVehicle->renew_no == NULL) {
                    $approvedVehicle = $mAdvActiveVehicle->replicate();
                    $approvedVehicle->setTable('adv_vehicles');
                    $temp_id = $approvedVehicle->id = $mAdvActiveVehicle->id;
                    $approvedVehicle->payment_amount = round($req->payment_amount);
                    $approvedVehicle->demand_amount = $req->payment_amount;
                    $approvedVehicle->license_no = $generatedId;
                    $approvedVehicle->approve_date = Carbon::now();
                    $approvedVehicle->zone = $zone;
                    $approvedVehicle->save();

                    // Save in vehicle Advertisement Renewal
                    $approvedVehicle = $mAdvActiveVehicle->replicate();
                    $approvedVehicle->approve_date = Carbon::now();
                    $approvedVehicle->setTable('adv_vehicle_renewals');
                    $approvedVehicle->license_no = $generatedId;
                    $approvedVehicle->id = $temp_id;
                    $approvedVehicle->zone = $zone;
                    $approvedVehicle->save();


                    $mAdvActiveVehicle->delete();

                    // Update in adv_vehicles (last_renewal_id)

                    DB::table('adv_vehicles')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedVehicle->id]);

                    $msg = "Application Successfully Approved !!";
                } else {
                    //  Renewal Case
                    // Vehicle Advert Application replication
                    $license_no = $mAdvActiveVehicle->license_no;
                    AdvVehicle::where('license_no', $license_no)->delete();

                    $approvedVehicle = $mAdvActiveVehicle->replicate();
                    $approvedVehicle->setTable('adv_vehicles');
                    $temp_id = $approvedVehicle->id = $mAdvActiveVehicle->id;
                    $approvedVehicle->payment_amount = round($req->payment_amount);
                    $approvedVehicle->demand_amount = $req->payment_amount;
                    $approvedVehicle->approve_date = Carbon::now();
                    $approvedVehicle->save();

                    // Save in Vehicle Advertisement Renewal
                    $approvedVehicle = $mAdvActiveVehicle->replicate();
                    $approvedVehicle->approve_date = Carbon::now();
                    $approvedVehicle->setTable('adv_vehicle_renewals');
                    $approvedVehicle->id = $temp_id;
                    $approvedVehicle->save();

                    $mAdvActiveVehicle->delete();

                    // Update in adv_vehicles (last_renewal_id)
                    DB::table('adv_vehicles')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedVehicle->id]);
                    $msg = "Application Successfully Renewal !!";
                }
            }
            // Rejection
            if ($req->status == 0) {
                $payment_amount = ['payment_amount' => 0];
                $req->request->add($payment_amount);

                // Vehicles advertisement Application replication
                $rejectedVehicle = $mAdvActiveVehicle->replicate();
                $rejectedVehicle->setTable('adv_rejected_vehicles');
                $rejectedVehicle->id = $mAdvActiveVehicle->id;
                $rejectedVehicle->rejected_date = Carbon::now();
                $rejectedVehicle->save();
                $mAdvActiveVehicle->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            return responseMsgs(true, $msg, "", '011111', 01, responseTime(), 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), '', "050315", "1.0", "", "POST", "");
        }
    }


    /**
     * | Approve Application List for Citzen
     * | @param Request $req
     * | Function - 17
     * | API - 16
     */
    public function listApproved(Request $req)
    {
        try {
            // Variable Initialization
            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];
            $mAdvVehicle = new AdvVehicle();
            $applications = $mAdvVehicle->listApproved($citizenId, $userType);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Approved Application List", $data1, "050316", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050316", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Reject Application List for Citizen
     * | @param Request $req
     * | Function - 18
     * | API - 17
     */
    public function listRejected(Request $req)
    {
        try {
            // Variable Initialization

            $citizenId = $req->auth['id'];
            $mAdvRejectedVehicle = new AdvRejectedVehicle();
            $applications = $mAdvRejectedVehicle->listRejected($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Approved Application List", $data1, "050317", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050317", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }



    /**
     * | Get Applied Applications by Logged In JSK
     * | Function - 19
     * | API - 18
     */
    public function getJSKApplications(Request $req)
    {
        try {
            // Variable Initialization

            $userId = $req->auth['id'];
            $mAdvActiveVehicle = new AdvActiveVehicle();
            $applications = $mAdvActiveVehicle->getJSKApplications($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Applied Applications", $data1, "050318", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050318", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Approve Application List for JSK
     * | @param Request $req
     * | Function - 20
     * | API - 19
     */
    public function listjskApprovedApplication(Request $req)
    {
        try {
            // Variable Initialization
            $userId = $req->auth['id'];
            $mAdvVehicle = new AdvVehicle();
            $applications = $mAdvVehicle->listjskApprovedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Approved Application List", $data1, "050319", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050319", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Reject Application List for JSK
     * | @param Request $req
     * | Function - 21
     * | API - 20
     */
    public function listJskRejectedApplication(Request $req)
    {
        try {
            // Variable Initialization

            $userId = $req->auth['id'];
            $mAdvRejectedVehicle = new AdvRejectedVehicle();
            $applications = $mAdvRejectedVehicle->listJskRejectedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Rejected Application List", $data1, "050320", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050320", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }



    /**
     * | Generate Payment Order ID
     * | @param Request $req
     * | Function - 22
     * | API - 21
     */

    public function generatePaymentOrderId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        try {
            // Variable Initialization
            $mAdvVehicle = AdvVehicle::find($req->id);
            $reqData = [
                "id" => $mAdvVehicle->id,
                'amount' => $mAdvVehicle->payment_amount,
                'workflowId' => $mAdvVehicle->workflow_id,
                'ulbId' => $mAdvVehicle->ulb_id,
                'departmentId' => Config::get('workflow-constants.ADVERTISMENT_MODULE_ID'),
                'auth' => $req->auth,
            ];
            $paymentUrl = Config::get('constants.PAYMENT_URL');
            $refResponse = Http::withHeaders([
                "api-key" => "eff41ef6-d430-4887-aa55-9fcf46c72c99"
            ])
                ->withToken($req->bearerToken())
                ->post($paymentUrl . 'api/payment/generate-orderid', $reqData);

            $data = json_decode($refResponse);
            $data = $data->data;
            if (!$data)
                throw new Exception("Payment Order Id Not Generate");

            $data->name = $mAdvVehicle->applicant;
            $data->email = $mAdvVehicle->email;
            $data->contact = $mAdvVehicle->mobile_no;
            $data->type = "Movable Vehicles";

            return responseMsgs(true, "Payment OrderId Generated Successfully !!!", $data, "050321", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050321", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * Summary of application Details For Payment
     * @param Request $req
     * @return void
     * | Function - 23
     * | API - 22
     */
    public function getApplicationDetailsForPayment(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            // Variable Initialization
            $mAdvVehicle = new AdvVehicle();

            if ($req->applicationId) {
                $data = $mAdvVehicle->detailsForPayments($req->applicationId);
            }

            if (!$data)
                throw new Exception("Application Not Found");

            $data['type'] = "Movable Vehicles";

            return responseMsgs(true, 'Data Fetched',  $data, "050322", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050322", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Payment Via Cash
     * | Function - 24
     * | API - 23
     */
    public function paymentByCash(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string',
            'status' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mAdvVehicle = new AdvVehicle();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mAdvVehicle->paymentByCash($req);
            $appDetails = AdvVehicle::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleIds, "Advertisement", "Cash");
            DB::commit();
            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' =>  $appDetails->workflow_id], "050323", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050323", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050323", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Entry Cheque or DD
     * | Function - 25
     * | API - 24
     */
    public function entryChequeDd(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string',               //  temp_id of Application
            'bankName' => 'required|string',
            'branchName' => 'required|string',
            'chequeNo' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $wfId = AdvVehicle::find($req->applicationId)->workflow_id;
            $mAdvCheckDtl = new AdvChequeDtl();
            $workflowId = ['workflowId' => $wfId];
            $req->request->add($workflowId);
            $transNo = $mAdvCheckDtl->entryChequeDd($req);                     // Entry Cheque And DD in Model

            return responseMsgs(true, "Check Entry Successfully !!", ['status' => true, 'TransactionNo' => $transNo], "050324", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050324", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Clear or bounce Cheque or DD
     * | Function - 26
     * | API - 25
     */
    public function clearOrBounceCheque(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'paymentId' => 'required|string',
            'status' => 'required|string',
            'remarks' => $req->status == 1 ? 'nullable|string' : 'required|string',
            'bounceAmount' => $req->status == 1 ? 'nullable|numeric' : 'required|numeric',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mAdvCheckDtl = new AdvChequeDtl();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mAdvCheckDtl->clearOrBounceCheque($req);
            $appDetails = AdvVehicle::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleIds, "Advertisement", "Cheque/DD");
            DB::commit();
            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050325", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050325", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050325", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Entry Zone of the Application 
     * | Function - 27
     * | API - 26
     */
    public function entryZone(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer',
            'zone' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mAdvActiveVehicle = new AdvActiveVehicle();
            $status = $mAdvActiveVehicle->entryZone($req);                   // Entry Zone In Model

            if ($status == '1') {
                return responseMsgs(true, 'Data Fetched',  "Zone Added Successfully", "050326", "1.0", responseTime(), "POST", $req->deviceId);
            } else {
                throw new Exception("Zone Not Added !!!");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050326", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Verify Single Application Approve or reject
     * | Function - 28
     * | API - 27
     */
    public function verifyOrRejectDoc(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mWfDocument = new WfActiveDocument();
            $mAdvActiveVehicle = new AdvActiveVehicle();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = $req->auth['id'];
            $applicationId = $req->applicationId;

            $wfLevel = Config::get('constants.SELF-LABEL');
            // Derivative Assigments
            $appDetails = $mAdvActiveVehicle->getVehicleNo($applicationId);

            if (!$appDetails || collect($appDetails)->isEmpty())
                throw new Exception("Application Details Not Found");

            $appReq = new Request([
                'userId' => $userId,
                'workflowId' => $appDetails->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($appReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;

            if ($senderRoleId != $wfLevel['DA'])                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");


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
                $appDetails->doc_upload_status = 0;
                $appDetails->doc_verify_status = 0;
                $appDetails->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);

            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $appDetails->doc_verify_status = 1;
                $appDetails->save();
            }

            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "050327", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050327", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     * | Function - 29
     */
    public function ifFullDocVerified($applicationId)
    {
        $mAdvActiveVehicle = new AdvActiveVehicle();
        $mWfActiveDocument = new WfActiveDocument();
        $mAdvActiveVehicle = $mAdvActiveVehicle->getVehicleNo($applicationId);                      // Get Application Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $mAdvActiveVehicle->workflow_id,
            'moduleId' =>  $this->_moduleIds
        ];
        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $totalApproveDoc = $refDocList->count();
        $ifAdvDocUnverified = $refDocList->contains('verify_status', 0);

        $totalNoOfDoc = $mWfActiveDocument->totalNoOfDocs($this->_docCode);
        // $totalNoOfDoc=$mWfActiveDocument->totalNoOfDocs($this->_docCodeRenew);
        // if($mMarActiveBanquteHall->renew_no==NULL){
        //     $totalNoOfDoc=$mWfActiveDocument->totalNoOfDocs($this->_docCode);
        // }
        if ($totalApproveDoc == $totalNoOfDoc) {
            if ($ifAdvDocUnverified == 1)
                return 0;
            else
                return 1;
        } else {
            return 0;
        }
    }


    /**
     * | send back to citizen
     * | Function - 30
     * | API - 28
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            // Variable Initialization
            $redis = Redis::connection();
            $mAdvActiveVehicle = AdvActiveVehicle::find($req->applicationId);
            if ($mAdvActiveVehicle->doc_verify_status == 1)
                throw new Exception("All Documents Are Approved, So Application is Not BTC !!!");
            if ($mAdvActiveVehicle->doc_upload_status == 1)
                throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");

            $workflowId = $mAdvActiveVehicle->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mAdvActiveVehicle->current_roles = $backId->wf_role_id;
            $mAdvActiveVehicle->parked = 1;
            $mAdvActiveVehicle->save();

            $metaReqs['moduleId'] = $this->_moduleIds;
            $metaReqs['workflowId'] = $mAdvActiveVehicle->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_vehicles.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050328', '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050328", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Back To Citizen Inbox
     * | Function - 31
     * | API - 29
     */
    public function listBtcInbox(Request $req)
    {
        try {
            // Variable Initialization
            // $auth = auth()->user();
            $userId = $req->auth['id'];
            $ulbId = $req->auth['ulb_id'];
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);

            $roleId = collect($roles)->map(function ($role) {                                       // get Roles of the user
                return $role->wf_role_id;
            });

            $mAdvActiveVehicle = new AdvActiveVehicle();
            $btcList = $mAdvActiveVehicle->getVehicleList($ulbId)
                ->whereIn('adv_active_vehicles.current_roles', $roleId)
                // ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('adv_active_vehicles.id');
            // ->get();

            if (trim($req->key))
                $btcList =  searchFilter($btcList, $req);
            $list = paginator($btcList, $req);

            return responseMsgs(true, "BTC Inbox List", $list, "050329", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050329", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Check all documents of apllication uploaded or not
     * | Function - 32
     */
    public function checkFullUpload($applicationId)
    {
        $docCode = $this->_docCode;
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = $this->_moduleIds;
        $totalRequireDocs = $mWfActiveDocument->totalNoOfDocs($docCode);
        $appDetails = AdvActiveVehicle::find($applicationId);
        $totalUploadedDocs = $mWfActiveDocument->totalUploadedDocs($applicationId, $appDetails->workflow_id, $moduleId);
        if ($totalRequireDocs == $totalUploadedDocs) {
            $appDetails->doc_upload_status = '1';
            // $appDetails->doc_verify_status = '1';
            $appDetails->parked = NULL;
            $appDetails->save();
        } else {
            $appDetails->doc_upload_status = '0';
            $appDetails->doc_verify_status = '0';
            $appDetails->save();
        }
    }

    /**
     * | Reupload Rejected Documents
     * | Function - 33
     * | API - 30
     */
    public function reuploadDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|digits_between:1,9223372036854775807',
            'image' => 'required|mimes:png,jpeg,pdf,jpg'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable Initialization
            $mAdvActiveVehicle = new AdvActiveVehicle();
            DB::beginTransaction();
            $appId = $mAdvActiveVehicle->reuploadDocument($req);
            $this->checkFullUpload($appId);
            DB::commit();
            return responseMsgs(true, "Document Uploaded Successfully", "", "050330", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050330", 1.0, "271ms", "POST", "", "");
        }
    }
    /**
     * | Get Application Between Two Dates
     * | Function - 34
     * | API - 31
     */
    public function getApplicationBetweenDate(Request $req)
    {
        if (authUser()->ulb_id < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050331", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = authUser()->ulb_id;
        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'applicationStatus' => 'required|in:All,Approve,Reject',
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateUpto' => 'required|date_format:Y-m-d',
            'perPage' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            #=============================================================
            $mAdvVehicle = new AdvVehicle();
            $approveList = $mAdvVehicle->approveListForReport();

            $approveList = $approveList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvActiveVehicle = new AdvActiveVehicle();
            $pendingList = $mAdvActiveVehicle->pendingListForReport();

            $pendingList = $pendingList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvRejectedVehicle = new AdvRejectedVehicle();
            $rejectList = $mAdvRejectedVehicle->rejectListForReport();

            $rejectList = $rejectList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $data = collect(array());
            if ($req->applicationStatus == 'All') {
                $data = $approveList->union($pendingList)->union($rejectList);
            }
            if ($req->applicationStatus == 'Reject') {
                $data = $rejectList;
            }
            if ($req->applicationStatus == 'Approve') {
                $data = $approveList;
            }
            $data = $data->paginate($req->perPage);
            #=============================================================
            return responseMsgs(true, "Application Fetched Successfully", $data, "050331", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050331", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | COllection From New or Renew Application
     * | Function - 35
     * | API - 32
     */
    public function paymentCollection(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050332", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateUpto' => 'required|date_format:Y-m-d',
            'perPage' => 'required|integer',
            'payMode' => 'required|in:All,Online,Cash,Cheque/DD',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $approveList = DB::table('adv_vehicle_renewals')
                ->select('id', 'application_no', 'applicant', 'application_date', 'application_type', DB::raw("'Approve' as application_status"), 'payment_amount', 'payment_date', 'payment_mode')->where('application_type', $req->applicationType)->where('payment_status', '1')->where('ulb_id', $ulbId)
                ->whereBetween('payment_date', [$req->dateFrom, $req->dateUpto]);

            $data = collect(array());
            if ($req->payMode == 'All') {
                $data = $approveList;
            }
            if ($req->payMode == 'Online') {
                $data = $approveList->where('payment_mode', $req->payMode);
            }
            if ($req->payMode == 'Cash') {
                $data = $approveList->where('payment_mode', $req->payMode);
            }
            if ($req->payMode == 'Cheque/DD') {
                $data = $approveList->where('payment_mode', $req->payMode);
            }
            $data = $data->paginate($req->perPage);

            $ap = $data->toArray();

            $amounts = collect();
            $data1 = collect($ap['data'])->map(function ($item, $key) use ($amounts) {
                $amounts->push($item->payment_amount);
            });

            return responseMsgs(true, "Application Fetched Successfully", $data, "050332", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050332", 1.0, "271ms", "POST", "", "");
        }
    }
}
