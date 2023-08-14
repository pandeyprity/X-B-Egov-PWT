<?php

namespace App\Http\Controllers\Advertisements;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateLand\RenewalRequest;
use App\Http\Requests\PrivateLand\StoreRequest;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\AdvActivePrivateland;
use App\Models\Advertisements\AdvChequeDtl;
use App\Models\Advertisements\AdvPrivateland;
use App\Models\Advertisements\AdvRejectedPrivateland;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Param\AdvMarTransaction;
use App\Models\Workflows\WfRoleusermap;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\AdvDetailsTraits;


use App\Traits\WorkflowTrait;
use App\Models\Workflows\WorkflowTrack;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\SelfAdvets\iSelfAdvetRepo;


use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\Empty_;

/**
 * | Created On-02-01-2022 
 * | Created By- Anshu Kumar
 * | Changes By- Bikash Kumar
 * | Private Land Operations
 * | Status -  Closed, By - Bikash - 24 Apr 2023 total no of lines - 1597, Total Function - 36, Tolal API - 33
 */

class PrivateLandController extends Controller
{

    use WorkflowTrait;
    use AdvDetailsTraits;
    protected $_modelObj;

    protected $_workflowIds;
    protected $_moduleId;

    protected $Repository;
    protected $_docCode;
    protected $_tempParamId;
    protected $_paramId;
    protected $_baseUrl;
    protected $_wfMasterId;
    protected $_fileUrl;
    public function __construct(iSelfAdvetRepo $privateland_repo)
    {
        $this->_modelObj = new AdvActivePrivateland();
        // $this->_workflowIds = Config::get('workflow-constants.PRIVATE_LANDS_WORKFLOWS');
        $this->_moduleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_docCode = Config::get('workflow-constants.PRIVATE_LANDS_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_LAND_ID');
        $this->_paramId = Config::get('workflow-constants.LAND_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->Repository = $privateland_repo;

        $this->_wfMasterId = Config::get('workflow-constants.PRIVATE_LAND_WF_MASTER_ID');
    }

    /**
     * | Apply For Private Land Advertisement
     * | Function - 01
     * | API - 01
     */
    public function addNew(StoreRequest $req)
    {
        try {
            // Variable initialization
            $privateLand = new AdvActivePrivateland();
            if ($req->auth['user_type'] == 'JSK') {
                $userId = ['userId' => $req->auth['id']];                            // Find Jsk Id
                $req->request->add($userId);
            } else {
                $citizenId = ['citizenId' => $req->auth['id']];                       // Find CItizen Id
                $req->request->add($citizenId);
            }
            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];
            $req->request->add($applicationNo);
            // $mWfWorkflow=new WfWorkflow();
            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $applicationNo = $privateLand->addNew($req);                            // Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050401", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050401", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Details for renew
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
            // Variable initialization
            $mAdvPrivateland = new AdvPrivateland();
            $details = $mAdvPrivateland->applicationDetailsForRenew($req->applicationId);

            if (!$details)
                throw new Exception("Application Not Found !!!");

            return responseMsgs(true, "Application Fetched !!!", remove_null($details), "050402", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050402", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Apply For Private Land Advertisement
     * | Function - 03
     * | API - 03
     */
    public function renewalApplication(RenewalRequest $req)
    {
        try {
            // Variable initialization
            $privateLand = new AdvActivePrivateland();
            if ($req->auth['user_type'] == 'JSK') {
                $userId = ['userId' => $req->auth['id']];                            // Find Jsk Id
                $req->request->add($userId);
            } else {
                $citizenId = ['citizenId' => $req->auth['id']];                       // Find CItizen Id
                $req->request->add($citizenId);
            }
            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];

            $req->request->add($applicationNo);
            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);
            DB::beginTransaction();
            $applicationNo = $privateLand->renewalApplication($req);                            // Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050403", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050403", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $mAdvActivePrivateland = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });

            $inboxList = $mAdvActivePrivateland->listInbox($roleIds, $ulbId);                   // <----- Get Inbox List From Model
            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);

            return responseMsgs(true, "Inbox Applications", $list, "050404", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050404", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization
            $mPrivateLand = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });

            $outboxList = $mPrivateLand->listOutbox($roleIds, $ulbId);                            // <----- Get Outbox List From Model
            if (trim($req->key))
                $outboxList =  searchFilter($outboxList, $req);
            $list = paginator($outboxList, $req);


            return responseMsgs(true, "Outbox Lists", $list, "050405", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050405", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization
            $mAdvActivePrivateland = new AdvActivePrivateland();
            $fullDetailsData = array();
            if (isset($req->type)) {
                $type = $req->type;
            } else {
                $type = NULL;
            }

            if ($req->applicationId) {
                $data = $mAdvActivePrivateland->getDetailsById($req->applicationId, $type);     // Get Application Details
            } else {
                throw new Exception("Not Pass Application Id");
            }

            if (!$data) {
                throw new Exception("Not Application Details Found");
            }

            //Basic Details
            $basicDetails = $this->generatePrivateLandBasicDetails($data);              // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            $cardDetails = $this->generatePrivateLandCardDetails($data);                // Trait function to get Card Details
            $cardElement = [
                'headerTitle' => "Private Land Advertisement",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);

            $metaReqs['customFor'] = 'PRIVATE';
            $metaReqs['wfRoleId'] = $data['current_role_id'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];

            $req->request->add($metaReqs);

            $forwardBackward = $this->getRoleDetails($req);                            // Get Role Ids
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData = remove_null($fullDetailsData);

            $fullDetailsData['application_no'] = $data['application_no'];
            $fullDetailsData['apply_date'] = Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y');
            $fullDetailsData['zone'] = $data['zone'];
            $fullDetailsData['doc_verify_status'] = $data['doc_verify_status'];
            if (isset($data['payment_amount'])) {
                $fullDetailsData['payment_amount'] = $data['payment_amount'];
            }
            $fullDetailsData['timelineData'] = collect($req);                           // Get Timeline Data
            $fullDetailsData['workflowId'] = $data['workflow_id'];

            return responseMsgs(true, 'Data Fetched', $fullDetailsData, "050406", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050406", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Role Details
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
            // Variable initialization
            $citizenId = $req->auth['id'];
            $mAdvActivePrivateland = new AdvActivePrivateland();

            $applications = $mAdvActivePrivateland->listAppliedApplications($citizenId);            // Find Applied Application By Citizen

            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            // if ($totalApplication == 0) {
            //     $data1['data'] = NULL;
            // }

            return responseMsgs(true, "Applied Applications", $data1, "050407", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050407", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization

            $userId = $request->auth['id'];
            $applicationId = $request->applicationId;
            $data = AdvActivePrivateland::find($applicationId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();                                                               // Save After escalate or De-Escalate

            return responseMsgs(true, $request->escalateStatus == 1 ? 'Private Lands is Escalated' : "Private Lands is removed from Escalated", '', "050408", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050408", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | List of Escalated Application 
     * | Function - 10
     * | API - 09
     */
    public function listEscalated(Request $req)
    {
        try {
            // Variable initialization

            $mWfWardUser = new WfWardUser();
            $userId = $req->auth['id'];
            $ulbId = $req->auth['ulb_id'];
            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);          // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {             // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });

            $mWfWorkflow = new WfWorkflow();
            $workflowId = $mWfWorkflow->getulbWorkflowId($this->_wfMasterId, $ulbId);      // get workflow Id

            $advData = $this->Repository->specialPrivateLandInbox($workflowId)          // Repository function to get Advertiesment Details
                ->where('is_escalate', 1)
                ->where('adv_active_privatelands.ulb_id', $ulbId);
            // ->whereIn('ward_mstr_id', $wardId)
            // ->get();
            if (trim($req->key))
                $advData =  searchFilter($advData, $req);
            $list = paginator($advData, $req);

            return responseMsgs(true, "Data Fetched", $list, "050409", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050409", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $adv = AdvActivePrivateland::find($request->applicationId);
            if ($adv->parked == NULL && $adv->doc_upload_status == '0')
                throw new Exception("Document Rejected Please Send Back to Citizen !!!");
            if ($adv->parked == '1' && $adv->doc_upload_status == '0')
                throw new Exception("Document Are Not Re-upload By Citizen !!!");
            if ($adv->doc_verify_status == '0' && $adv->parked == NULL)
                throw new Exception("Please Verify All Documents To Forward The Application !!!");
            if ($adv->zone == NULL)
                throw new Exception("Zone Not Selected !!!");
            $adv->last_role_id = $request->current_role_id;
            $adv->current_role_id = $request->receiverRoleId;
            $adv->save();

            $metaReqs['moduleId'] = $this->_moduleId;
            $metaReqs['workflowId'] = $adv->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_privatelands.id";
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            // Advertisment Application Update Current Role Updation
            DB::beginTransaction();
            $track->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "050410", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050410", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Comment on Application
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
            // Variable initialization
            $userId = $request->auth['id'];
            $userType = $request->auth['user_type'];
            $workflowTrack = new WorkflowTrack();
            $mWfRoleUsermap = new WfRoleusermap();
            $mAdvActivePrivateland = AdvActivePrivateland::find($request->applicationId);                // Advertisment Details
            $metaReqs = array();
            $metaReqs = [
                'workflowId' => $mAdvActivePrivateland->workflow_id,
                'moduleId' => $this->_moduleId,
                'refTableDotId' => "adv_active_privatelands.id",
                'refTableIdValue' => $mAdvActivePrivateland->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment

            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $mAdvActivePrivateland->workflow_id,
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
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "050411", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050411", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Get Uploaded Document by application ID
     * | Function - 13
     * | API - 12
     */
    public function viewPvtLandDocuments(Request $req)
    {
        // Variable initialization
        $mWfActiveDocument = new WfActiveDocument();
        if ($req->type == 'Active')
            $workflowId = AdvActivePrivateland::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Approve')
            $workflowId = AdvPrivateland::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Reject')
            $workflowId = AdvRejectedPrivateland::find($req->applicationId)->workflow_id;
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
        // Variable initialization
        // $startTime = microtime(true);
        $workflowId = AdvActivePrivateland::find($req->applicationId)->workflow_id;
        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        $data = $mWfActiveDocument->uploadedActiveDocumentsViewById($req->applicationId, $workflowId);  // Get uploaded Documents
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
        // Variable initialization
        $mWfActiveDocument = new WfActiveDocument();
        if (isset($req->type) && $req->type == 'Approve')
            $workflowId = AdvPrivateland::find($req->applicationId)->workflow_id;
        else
            $workflowId = AdvActivePrivateland::find($req->applicationId)->workflow_id;
        $data = array();
        if ($req->applicationId) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId, $workflowId);
        }
        $appUrl = $this->_fileUrl;
        $data1 = collect($data)->map(function ($value) use ($appUrl) {
            $value->doc_path = $appUrl . $value->doc_path;
            return $value;
        });
        return responseMsgs(true, "Data Fetched", remove_null($data1), "050414", "1.0", responseTime(), "POST", "");
    }



    /**
     * | Approval and Rejection of the Application
     * | Function - 16
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
            // Variable initialization
            // Check if the Current User is Finisher or Not         
            $mAdvActivePrivateland = AdvActivePrivateland::find($req->applicationId);
            $getFinisherQuery = $this->getFinisherId($mAdvActivePrivateland->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsgs(false, " Access Forbidden", "");
            }
            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $typology = $mAdvActivePrivateland->typology;
                $zone = $mAdvActivePrivateland->zone;
                if ($zone == NULL) {
                    throw new Exception("Zone Not Selected !!!");
                }
                $mCalculateRate = new CalculateRate();
                $amount = $mCalculateRate->getPrivateLandPayment($typology, $zone, $mAdvActivePrivateland->license_from, $mAdvActivePrivateland->license_to);
                $payment_amount = ['payment_amount' => $amount];

                // $payment_amount = ['payment_amount' =>1000];
                $req->request->add($payment_amount);

                // $mCalculateRate = new CalculateRate;
                // $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_paramId, $mAdvActivePrivateland->ulb_id); // Generate License No
                $idGeneration = new PrefixIdGenerator($this->_paramId, $mAdvActivePrivateland->ulb_id);
                $generatedId = $idGeneration->generate();
                if ($mAdvActivePrivateland->renew_no == NULL) {
                    // approved Private Land Application replication
                    $approvedPrivateland = $mAdvActivePrivateland->replicate();
                    $approvedPrivateland->setTable('adv_privatelands');
                    $temp_id = $approvedPrivateland->id = $mAdvActivePrivateland->id;
                    $approvedPrivateland->payment_amount = round($req->payment_amount);
                    $approvedPrivateland->demand_amount = $req->payment_amount;
                    $approvedPrivateland->license_no =  $generatedId;
                    $approvedPrivateland->approve_date = Carbon::now();
                    $approvedPrivateland->zone = $zone;
                    $approvedPrivateland->save();

                    // Save in Priate Land Application Advertisement Renewal
                    $approvedPrivateland = $mAdvActivePrivateland->replicate();
                    $approvedPrivateland->approve_date = Carbon::now();
                    $approvedPrivateland->license_no =  $generatedId;
                    $approvedPrivateland->setTable('adv_privateland_renewals');
                    $approvedPrivateland->id = $temp_id;
                    $approvedPrivateland->zone = $zone;
                    $approvedPrivateland->save();

                    $mAdvActivePrivateland->delete();
                    // Update in adv_privatelands (last_renewal_id)
                    DB::table('adv_privatelands')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedPrivateland->id]);

                    $msg = "Application Successfully Approved !!";
                } else {
                    //  Renewal Case
                    // Privateland Advert Application replication
                    $license_no = $mAdvActivePrivateland->license_no;
                    AdvPrivateland::where('license_no', $license_no)->delete();

                    $approvedPrivateland = $mAdvActivePrivateland->replicate();
                    $approvedPrivateland->setTable('adv_privatelands');
                    $temp_id = $approvedPrivateland->id = $mAdvActivePrivateland->id;
                    $approvedPrivateland->payment_amount = round($req->payment_amount);
                    $approvedPrivateland->demand_amount = $req->payment_amount;
                    $approvedPrivateland->payment_status = $req->payment_status;
                    $approvedPrivateland->approve_date = Carbon::now();
                    $approvedPrivateland->save();

                    // Save in Privateland Advertisement Renewal
                    $approvedPrivateland = $mAdvActivePrivateland->replicate();
                    $approvedPrivateland->approve_date = Carbon::now();
                    $approvedPrivateland->setTable('adv_privateland_renewals');
                    $approvedPrivateland->id = $temp_id;
                    $approvedPrivateland->save();

                    $mAdvActivePrivateland->delete();
                    // Update in adv_privatelands (last_renewal_id)
                    DB::table('adv_privatelands')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedPrivateland->id]);
                    $msg = "Application Successfully Renewal !!";
                }
            }
            // Rejection
            if ($req->status == 0) {
                $payment_amount = ['payment_amount' => 0];
                $req->request->add($payment_amount);
                // Privateland advertisement Application replication
                $rejectedPrivateland = $mAdvActivePrivateland->replicate();
                $rejectedPrivateland->setTable('adv_rejected_privatelands');
                $rejectedPrivateland->id = $mAdvActivePrivateland->id;
                $rejectedPrivateland->rejected_date = Carbon::now();
                $rejectedPrivateland->save();
                $mAdvActivePrivateland->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();
            return responseMsgs(true, $msg, "", '050415', 01, responseTime(), 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050415", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $startTime = microtime(true);

            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];
            $mAdvPrivateland = new AdvPrivateland();
            $applications = $mAdvPrivateland->listApproved($citizenId, $userType);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }

            return responseMsgs(true, "Approved Application List", $data1, "050416", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050416", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization
            $citizenId = $req->auth['id'];
            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $applications = $mAdvRejectedPrivateland->listRejected($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Rejected Application List", $data1, "050417", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050417", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization
            $userId = $req->auth['id'];
            $mAdvActivePrivateland = new AdvActivePrivateland();
            $applications = $mAdvActivePrivateland->getJSKApplications($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Applied Applications", $data1, "050418", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050418", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $userId = $req->auth['id'];
            $mAdvPrivateland = new AdvPrivateland();
            $applications = $mAdvPrivateland->listjskApprovedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Approved Application List", $data1, "050419", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050419", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization

            $userId = authUser()->id;
            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $applications = $mAdvRejectedPrivateland->listJskRejectedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }


            return responseMsgs(true, "Rejected Application List", $data1, "050420", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050420", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Generate Payment Order ID
     * | @param Request $req
     * | Function - 22
     * | Api- 21
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
            // Variable initialization

            $mAdvPrivateland = AdvPrivateland::find($req->id);
            $reqData = [
                "id" => $mAdvPrivateland->id,
                'amount' => $mAdvPrivateland->payment_amount,
                'workflowId' => $mAdvPrivateland->workflow_id,
                'ulbId' => $mAdvPrivateland->ulb_id,
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

            $data->name = $mAdvPrivateland->applicant;
            $data->email = $mAdvPrivateland->email;
            $data->contact = $mAdvPrivateland->mobile_no;
            $data->type = "Private Lands";
            // return $data;

            return responseMsgs(true, "Payment OrderId Generated Successfully !!!", $data, "050421", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050421", "1.0", "", 'POST', $req->deviceId ?? "");
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
            // Variable initialization

            $mAdvPrivateland = new AdvPrivateland();
            if ($req->applicationId) {
                $data = $mAdvPrivateland->getApplicationDetailsForPayment($req->applicationId);
            }

            if (!$data)
                throw new Exception("Application Not Found");

            $data['type'] = "Private Lands";

            return responseMsgs(true, 'Data Fetched',  $data, "050422", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050422", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Application payment via cash
     * | Function - 24
     * | API - 23
     */
    public function paymentByCash(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvPrivateland = new AdvPrivateland();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mAdvPrivateland->paymentByCash($req);
            $appDetails = AdvPrivateland::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cash");
            DB::commit();


            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!",  ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050423", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(true, "Payment Rejected !!", '', "050423", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050423", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Entry Cheque or dd for payment
     * | Function - 25
     * | API - 24
     */
    public function entryChequeDd(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string',               //  id of Application
            'bankName' => 'required|string',
            'branchName' => 'required|string',
            'chequeNo' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mAdvCheckDtl = new AdvChequeDtl();
            $wfId = AdvPrivateland::find($req->applicationId)->workflow_id;
            $workflowId = ['workflowId' => $wfId];
            $req->request->add($workflowId);
            $transNo = $mAdvCheckDtl->entryChequeDd($req);                        // Entry Cheque Or DD

            return responseMsgs(true, "Check Entry Successfully !!", ['status' => true, 'TransactionNo' => $transNo], "050424", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050424", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Clear or Bounce cheque or dd
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
            // Variable initialization

            $mAdvCheckDtl = new AdvChequeDtl();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mAdvCheckDtl->clearOrBounceCheque($req);
            $appDetails = AdvPrivateland::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cheque/DD");
            DB::commit();

            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050425", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050425", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050425", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $mAdvActivePrivateland = new AdvActivePrivateland();
            $status = $mAdvActivePrivateland->entryZone($req);             // Entry Zone From Model

            if ($status == '1') {
                return responseMsgs(true, 'Data Fetched', ['status' => true, 'message' => "Zone Added Successfully", 'zone' => $req->zone], "050426", "1.0", responseTime(), "POST", $req->deviceId);
            } else {
                throw new Exception("Zone Already Added !!!");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050426", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            $mWfDocument = new WfActiveDocument();
            $mAdvActivePrivateland = new AdvActivePrivateland();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = $req->auth['id'];
            $applicationId = $req->applicationId;

            $wfLevel = Config::get('constants.SELF-LABEL');
            // Derivative Assigments
            $appDetails = $mAdvActivePrivateland->getPrivateLandNo($applicationId);

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

            return responseMsgs(true, $req->docStatus . " Successfully", "", "050427", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050427", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     * | Function - 29
     */
    public function ifFullDocVerified($applicationId)
    {
        $mAdvActivePrivateland = new AdvActivePrivateland();
        $mWfActiveDocument = new WfActiveDocument();
        $mAdvActivePrivateland = $mAdvActivePrivateland->getPrivateLandNo($applicationId);                      // Get Application Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $mAdvActivePrivateland->workflow_id,
            'moduleId' =>  $this->_moduleId
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
            // Variable initialization

            $redis = Redis::connection();
            $mAdvActivePrivateland = AdvActivePrivateland::find($req->applicationId);
            if ($mAdvActivePrivateland->doc_verify_status == 1)
                throw new Exception("All Documents Are Approved, So Application is Not BTC !!!");
            if ($mAdvActivePrivateland->doc_upload_status == 1)
                throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");

            $workflowId = $mAdvActivePrivateland->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mAdvActivePrivateland->current_role_id = $backId->wf_role_id;
            $mAdvActivePrivateland->parked = 1;
            $mAdvActivePrivateland->save();

            $metaReqs['moduleId'] = $this->_moduleId;
            $metaReqs['workflowId'] = $mAdvActivePrivateland->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_privatelands.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050428', '01', responseTime(), 'POST', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050428", "1.0", "", "POST", $req->deviceId ?? "");
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
            // Variable initialization
            // $startTime = microtime(true);

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

            $mAdvActivePrivateland = new AdvActivePrivateland();
            $btcList = $mAdvActivePrivateland->getPrivateLandList($ulbId)
                ->whereIn('adv_active_privatelands.current_role_id', $roleId)
                // ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('adv_active_privatelands.id');
            // ->get();
            if (trim($req->key))
                $btcList =  searchFilter($btcList, $req);
            $list = paginator($btcList, $req);


            return responseMsgs(true, "BTC Inbox List", $list, "050429", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050429", 1.0, "", "POST", "", "");
        }
    }

    /**
     * | cheque full document upload or not
     * | Function - 32
     */
    public function checkFullUpload($applicationId)
    {
        $docCode = $this->_docCode;
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = $this->_moduleId;
        $totalRequireDocs = $mWfActiveDocument->totalNoOfDocs($docCode);
        $appDetails = AdvActivePrivateland::find($applicationId);
        $totalUploadedDocs = $mWfActiveDocument->totalUploadedDocs($applicationId, $appDetails->workflow_id, $moduleId);
        if ($totalRequireDocs == $totalUploadedDocs) {
            $appDetails->doc_upload_status = '1';
            $appDetails->doc_verify_status = '0';
            $appDetails->parked = NULL;
            $appDetails->save();
        } else {
            $appDetails->doc_upload_status = '0';
            $appDetails->doc_verify_status = '0';
            $appDetails->save();
        }
    }

    /**
     * | Re Upload Rejected DOcuments
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
            // Variable initialization

            $mAdvActivePrivateland = new AdvActivePrivateland();
            DB::beginTransaction();
            $appId = $mAdvActivePrivateland->reuploadDocument($req, $req->auth);
            $this->checkFullUpload($appId);
            DB::commit();

            return responseMsgs(true, "Document Uploaded Successfully", "", "050430", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050430", 1.0, "271ms", "POST", "", "");
        }
    }


    /**
     * | Get Application Between Two Dates
     * | Function - 34
     * | API - 31
     */
    public function getApplicationBetweenDate(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050431", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'applicationStatus' => 'required|in:All,Approve,Reject',
            'entityWard' => 'required|integer',
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
            $mAdvPrivateland = new AdvPrivateland();
            $approveList = $mAdvPrivateland->approveListForReport();

            $approveList = $approveList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvActivePrivateland = new AdvActivePrivateland();
            $pendingList = $mAdvActivePrivateland->pendingListForReport();

            $pendingList = $pendingList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $rejectList = $mAdvRejectedPrivateland->rejectListForReport();

            $rejectList = $rejectList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050431", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050431", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Get Application Display Wise
     * | Function - 35
     * | API - 32
     */
    public function getApplicationDisplayWise(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050432", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'applicationStatus' => 'required|in:All,Approve,Reject',
            'entityWard' => 'required|integer',
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateUpto' => 'required|date_format:Y-m-d',
            'displayType' => 'required|integer',
            'perPage' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvPrivateland = new AdvPrivateland();
            $approveList = $mAdvPrivateland->approveListForReport();

            $approveList = $approveList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('display_type', $req->displayType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvActivePrivateland = new AdvActivePrivateland();
            $pendingList = $mAdvActivePrivateland->pendingListForReport();

            $pendingList = $pendingList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('display_type', $req->displayType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $rejectList = $mAdvRejectedPrivateland->rejectListForReport();

            $rejectList = $rejectList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('display_type', $req->displayType)->where('ulb_id', $ulbId)
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050432", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050432", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | COllection From New or Renew Application
     * | Function - 36
     * | API - 33
     */
    public function paymentCollection(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050433", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'entityWard' => 'required|integer',
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

            $approveList = DB::table('adv_privateland_renewals')
                ->select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', DB::raw("'Approve' as application_status"), 'payment_amount', 'payment_date', 'payment_mode')->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('payment_status', '1')->where('ulb_id', $ulbId)
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050433", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050433", 1.0, "271ms", "POST", "", "");
        }
    }
}
