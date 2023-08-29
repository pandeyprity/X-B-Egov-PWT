<?php

namespace App\Http\Controllers\Advertisements;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\RenewalHordingRequest;
use App\Http\Requests\Agency\RenewalRequest;
use App\Http\Requests\Agency\StoreRequest;
use App\Http\Requests\Agency\StoreLicenceRequest;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\AdvActiveAgency;
use App\Models\Advertisements\AdvAgency;
use App\Models\Advertisements\AdvAgencyAmount;
use App\Models\Advertisements\AdvRejectedAgency;
use App\Models\Advertisements\AdvRejectedAgencyLicense;
use App\Models\Advertisements\AdvActiveAgencyLicense;
use App\Models\Advertisements\AdvActiveHoarding;
use App\Models\Advertisements\AdvAgencyLicense;
use App\Models\Advertisements\AdvCheckDtl;
use App\Models\Advertisements\AdvChequeDtl;
use App\Models\Advertisements\AdvHoarding;
use App\Models\Advertisements\AdvTypologyMstr;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Param\AdvMarTransaction;
use App\Models\User;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

use App\Traits\AdvDetailsTraits;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\SelfAdvets\iSelfAdvetRepo;
use App\Models\Workflows\WorkflowTrack;
use App\Traits\WorkflowTrait;

use Illuminate\Support\Facades\Validator;


use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-02-01-20222 
 * | Created By- Anshu Kumar
 * | Changes By- Bikash Kumar
 * | Agency Operations
 * | Status - Closed, By - Bikash kumar 24 Apr 2023, Total API - 34, Total Function - 38, Total no. of Lines - 1624
 */
class AgencyController extends Controller
{
    use AdvDetailsTraits;
    use WorkflowTrait;
    protected $_modelObj;
    protected $Repository;
    protected $_workflowIds;
    protected $_moduleId;
    protected $_docCode;
    protected $_tempParamId;
    protected $_paramId;
    protected $_baseUrl;
    protected $_wfMasterId;
    protected $_fileUrl;
    public function __construct(iSelfAdvetRepo $agency_repo)
    {
        $this->_modelObj = new AdvActiveAgency();
        // $this->_workflowIds = Config::get('workflow-constants.AGENCY_WORKFLOWS');
        $this->_moduleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_docCode = Config::get('workflow-constants.AGENCY_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_AGY_ID');
        $this->_paramId = Config::get('workflow-constants.AGY_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->Repository = $agency_repo;

        $this->_wfMasterId = Config::get('workflow-constants.AGENCY_WF_MASTER_ID');
    }

    /**
     * | Store 
     * | @param StoreRequest Request
     * | Function - 01
     * | API - 01
     */
    public function addNew(StoreRequest $req)
    {
        try {
            // Variable initialization

            $agency = new AdvActiveAgency();
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
            $applicationNo = $agency->addNew($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050501", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050501", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Agency Details
     * | Function - 02
     * | API - 02
     */
    public function getAgencyDetails(Request $req)
    {
        // return $req;
        // $validator = Validator::make($req->all(), [
        //     'applicationId' => 'required|integer',
        // ]);
        // if ($validator->fails()) {
        //     return ['status' => false, 'message' => $validator->errors()];
        // }
        try {
            // Variable initialization

            $mAdvAgency = new AdvAgency();
            $agencydetails = $mAdvAgency->getagencyDetails($req->auth['email']);
            if (!$agencydetails) {
                throw new Exception('You Have No Any Agency !!!');
            }
            remove_null($agencydetails);
            $data1['data'] = $agencydetails;

            return responseMsgs(true, "Agency Details", $data1, "050502", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Inbox List
     * | @param Request $req
     * | Function - 03
     * | API - 03
     */
    public function listInbox(Request $req)
    {
        try {
            // Variable initialization
            $ulbId = $req->auth['ulb_id'];
            $mAdvActiveAgency = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $inboxList = $mAdvActiveAgency->listInbox($roleIds, $ulbId);                        // <----- Get Inbox List
            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);


            return responseMsgs(true, "Inbox Applications", $list, "050503", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050503", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    public function listInbox_new(Request $req)
    {
        try {
            // Variable initialization

            $mAdvActiveAgency = $this->_modelObj;
            $bearerToken = $req->token;
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $inboxList = $mAdvActiveAgency->listInbox($roleIds, $ulbId);                        // <----- Get Inbox List

            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);

            return responseMsgs(true, "Inbox Applications",  $list, "050503", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050503", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Outbox List
     * | Function - 04
     * | API - 04
     */
    public function listOutbox(Request $req)
    {
        try {
            // Variable initialization
            $mAdvActiveAgency = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $outboxList = $mAdvActiveAgency->listOutbox($roleIds, $ulbId);                      // <----- Get Outbox List
            if (trim($req->key))
                $outboxList =  searchFilter($outboxList, $req);
            $list = paginator($outboxList, $req);

            return responseMsgs(true, "Outbox Lists", $list, "050504", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050504", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }



    /**
     * | Application Details
     * | Function - 05
     * | API - 05
     */

    public function getDetailsById(Request $req)
    {
        try {
            // Variable initialization
            $mAdvActiveAgency = new AdvActiveAgency();
            // $data = array();
            $fullDetailsData = array();
            if (isset($req->type)) {
                $type = $req->type;
            } else {
                $type = NULL;
            }
            if ($req->applicationId) {
                $data = $mAdvActiveAgency->getDetailsById($req->applicationId, $type);
            } else {
                throw new Exception("Not Pass Application Id");
            }

            // Basic Details
            $basicDetails = $this->generateAgencyBasicDetails($data); // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Agency Details",
                "data" => $basicDetails
            ];

            $cardDetails = $this->generateAgencyCardDetails($data);
            $cardElement = [
                'headerTitle' => "Agency Application",
                'data' => $cardDetails
            ];

            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);


            $metaReqs['customFor'] = 'AGENCY';
            $metaReqs['wfRoleId'] = $data['current_role_id'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];
            // return $metaReqs;
            $req->request->add($metaReqs);

            $forwardBackward = $this->getRoleDetails($req);
            // return $forwardBackward;
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData = remove_null($fullDetailsData);

            $fullDetailsData['application_no'] = $data['application_no'];
            $fullDetailsData['apply_date'] = Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y');
            $fullDetailsData['doc_verify_status'] = $data['doc_verify_status'];
            if (isset($data['payment_amount'])) {
                $fullDetailsData['payment_amount'] = $data['payment_amount'];
            }
            $fullDetailsData['directors'] = $data['directors'];
            $fullDetailsData['timelineData'] = collect($req);
            $fullDetailsData['workflowId'] = $data['workflow_id'];

            return responseMsgs(true, 'Data Fetched', $fullDetailsData, "050505", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050505", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Role Details
     * | Function - 06
     */
    public function getRoleDetails(Request $request)
    {
        // $ulbId = auth()->user()->ulb_id;
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
     * | Function - 07
     * | API - 06
     */
    public function listAppliedApplications(Request $req)
    {
        try {
            // Variable initialization
            $citizenId = $req->auth['id'];
            $mAdvActiveAgency = new AdvActiveAgency();
            $applications = $mAdvActiveAgency->listAppliedApplications($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }

            return responseMsgs(true, "Applied Applications", $data1, "050506", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050506", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Escalate Application
     * | Function - 08
     * | API - 07
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
            $data = AdvActiveAgency::find($applicationId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();

            return responseMsgs(true, $request->escalateStatus == 1 ? 'Agency is Escalated' : "Agency is removed from Escalated", '', "050507", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050507", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Special Inbox
     * | Function - 09
     * | API - 08
     */
    public function listEscalated(Request $req)
    {
        try {
            // Variable initialization
            $mWfWardUser = new WfWardUser();
            $userId = $req->auth['id'];
            $ulbId = $req->auth['ulb_id'];

            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                           // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });

            $mWfWorkflow = new WfWorkflow();
            $workflowId = $mWfWorkflow->getulbWorkflowId($this->_wfMasterId, $ulbId);      // get workflow Id

            $advData = $this->Repository->specialAgencyInbox($workflowId)                      // Repository function to get Advertiesment Details
                ->where('is_escalate', 1)
                ->where('adv_active_agencies.ulb_id', $ulbId);
            // ->whereIn('ward_mstr_id', $wardId)
            // ->get();
            if (trim($req->key))
                $advData =  searchFilter($advData, $req);
            $list = paginator($advData, $req);

            return responseMsgs(true, "Data Fetched", $list, "050508", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050508", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Forward or Backward Application
     * | Function - 10
     * | API - 09
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
            // Advertisment Application Update Current Role Updation
            $adv = AdvActiveAgency::find($request->applicationId);
            if ($adv->parked == NULL && $adv->doc_upload_status == '0')
                throw new Exception("Document Rejected Please Send Back to Citizen !!!");
            if ($adv->parked == '1' && $adv->doc_upload_status == '0')
                throw new Exception("Document Are Not Re-upload By Citizen !!!");
            if ($adv->doc_verify_status == '0' && $adv->parked == NULL)
                throw new Exception("Please Verify All Documents To Forward The Application !!!");
            $adv->last_role_id = $request->current_role_id;
            $adv->current_role_id = $request->receiverRoleId;
            $adv->save();

            $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs['workflowId'] = $adv->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_agencies.id";
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            DB::beginTransaction();
            $track->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "050509", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050509", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | Post Independent Comment
     * | Function - 11
     * | API - 10
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
            $mAdvActiveAgency = AdvActiveAgency::find($request->applicationId);                // Agency Details
            $mModuleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs = array();
            $metaReqs = [
                'workflowId' => $mAdvActiveAgency->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "adv_active_agencies.id",
                'refTableIdValue' => $mAdvActiveAgency->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment
            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $mAdvActiveAgency->workflow_id,
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

            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "050510", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050510", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | View Ageny uploaded documents
     * | Function - 12
     * | API - 11
     */
    public function viewAgencyDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), "", "050511", "1.0", "", "POST", $req->deviceId ?? "");
        }
        // $mWfWorkflow=new WfWorkflow();
        // $workflowId = $mWfWorkflow->getulbWorkflowId($this->_wfMasterId,$ulbId);      // get workflow Id
        if ($req->type == 'Active')
            $workflowId = AdvActiveAgency::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Approve')
            $workflowId = AdvAgency::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Reject')
            $workflowId = AdvRejectedAgency::find($req->applicationId)->workflow_id;
        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        if ($req->applicationId) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId,  $workflowId);
        } else {
            throw new Exception("Required Application Id ");
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
     * | Function - 13
     * | API - 12
     */
    public function viewActiveDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        $workflowId = AdvActiveAgency::find($req->applicationId)->workflow_id;
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
     * | Function - 14
     * | API - 13
     */
    public function viewDocumentsOnWorkflow(Request $req)
    {
        if (isset($req->type) && $req->type == 'Approve')
            $workflowId = AdvAgency::find($req->applicationId)->workflow_id;
        else
            $workflowId = AdvActiveAgency::find($req->applicationId)->workflow_id;
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
        return responseMsgs(true, "Data Fetched", remove_null($data1), "050513", "1.0", responseTime(), "POST", "");
    }

    /**
     * | Final Approval and Rejection of the Application
     * | Function - 15
     * | API - 14
     * | Status- Closed
     */
    public function approvedOrReject(Request $req)
    {
        try {
            $req->validate([
                'roleId' => 'required',
                'applicationId' => 'required|integer',
                'status' => 'required|integer',
            ]);
            // Variable initialization

            // Check if the Current User is Finisher or Not         
            $mAdvActiveAgency = AdvActiveAgency::find($req->applicationId);
            $getFinisherQuery = $this->getFinisherId($mAdvActiveAgency->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsgs(false, " Access Forbidden", "");
            }
            $price = $this->getAgencyPrice($mAdvActiveAgency->ulb_id, $mAdvActiveAgency->application_type);
            $payment_amount = ['payment_amount' => $price->amount];
            $req->request->add($payment_amount);

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                // License No Generate
                $reqData = [
                    "paramId" => $this->_paramId,
                    'ulbId' => $mAdvActiveAgency->ulb_id
                ];
                // $mCalculateRate = new CalculateRate;
                // $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_paramId, $mAdvActiveAgency->ulb_id); // Generate Application No
                $idGeneration = new PrefixIdGenerator($this->_paramId, $mAdvActiveAgency->ulb_id);
                $generatedId = $idGeneration->generate();
                // approved Vehicle Application replication
                $mAdvActiveAgency = AdvActiveAgency::find($req->applicationId);
                if ($mAdvActiveAgency->renew_no == NULL) {
                    $approvedAgency = $mAdvActiveAgency->replicate();
                    $approvedAgency->setTable('adv_agencies');
                    $temp_id = $approvedAgency->id = $mAdvActiveAgency->id;
                    $approvedAgency->license_no =  $generatedId;
                    $approvedAgency->payment_amount = round($req->payment_amount);
                    $approvedAgency->demand_amount = $req->payment_amount;
                    $approvedAgency->approve_date = Carbon::now();
                    $approvedAgency->save();

                    // Save in Agency Advertisement Renewal
                    $approvedAgency = $mAdvActiveAgency->replicate();
                    $approvedAgency->approve_date = Carbon::now();
                    $approvedAgency->license_no =  $generatedId;
                    $approvedAgency->setTable('adv_agency_renewals');
                    $approvedAgency->agencyadvet_id = $temp_id;
                    $approvedAgency->save();

                    $mAdvActiveAgency->delete();
                    // Update in adv_agencies (last_renewal_id)
                    DB::table('adv_agencies')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedAgency->id]);

                    $msg = "Application Successfully Approved !!";
                } else {
                    //  Renewal Case
                    // Agency Advert Application replication
                    $license_no = $mAdvActiveAgency->license_no;
                    AdvAgency::where('license_no', $license_no)->delete();

                    $approvedAgency = $mAdvActiveAgency->replicate();
                    $approvedAgency->setTable('adv_agencies');
                    $temp_id = $approvedAgency->id = $mAdvActiveAgency->id;
                    $approvedAgency->payment_amount = $req->payment_amount;
                    $approvedAgency->demand_amount = round($req->payment_amount);
                    $approvedAgency->payment_status = $req->payment_status;
                    $approvedAgency->approve_date = Carbon::now();
                    $approvedAgency->save();

                    // Save in Agency Advertisement Renewal
                    $approvedAgency = $mAdvActiveAgency->replicate();
                    $approvedAgency->approve_date = Carbon::now();
                    $approvedAgency->setTable('adv_agency_renewals');
                    $approvedAgency->agencyadvet_id = $temp_id;
                    $approvedAgency->save();

                    $mAdvActiveAgency->delete();
                    // Update in adv_agencies (last_renewal_id)
                    DB::table('adv_agencies')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedAgency->id]);
                    $msg = "Application Successfully Renewal !!";
                }
                $userId = $this->store($req, $mAdvActiveAgency);

                // dd($userId);
            }
            // Rejection
            if ($req->status == 0) {
                $payment_amount = ['payment_amount' => 0];
                $req->request->add($payment_amount);

                // Agency advertisement Application replication
                $rejectedAgency = $mAdvActiveAgency->replicate();
                $rejectedAgency->setTable('adv_rejected_agencies');
                $rejectedAgency->id = $mAdvActiveAgency->id;
                $rejectedAgency->rejected_date = Carbon::now();
                $rejectedAgency->save();
                $mAdvActiveAgency->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();
            return responseMsgs(true, $msg, "", '050514', 01, responseTime(), 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050514", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Agency price
     * | Function - 16
     */
    public function getAgencyPrice($ulb_id, $application_type)
    {
        $mAdvAgencyAmount = new AdvAgencyAmount();
        return $mAdvAgencyAmount->getAgencyPrice($ulb_id, $application_type);
    }


    /**
     * | Approve Application List for Citzen
     * | @param Request $req
     * | Function - 17
     * | API - 15
     */
    public function listApproved(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];
            $mAdvAgency = new AdvAgency();
            $applications = $mAdvAgency->listApproved($citizenId, $userType);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }
            return responseMsgs(true, "Approved Application List", $data1, "050515", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050515", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Reject Application List for Citizen
     * | @param Request $req
     * | Function - 18
     * | API - 16
     */
    public function listRejected(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $mAdvRejectedAgency = new AdvRejectedAgency();
            $applications = $mAdvRejectedAgency->listRejected($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }


            return responseMsgs(true, "Rejected Application List", $data1, "050516", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050516", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Applied Applications by Logged In JSK
     * | Function - 19
     * | API - 17
     */
    public function getJSKApplications(Request $req)
    {
        try {
            // Variable initialization
            $userId = $req->auth['id'];
            $mAdvActiveAgency = new AdvActiveAgency();
            $applications = $mAdvActiveAgency->getJSKApplications($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Applied Applications", $data1, "050517", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050517", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Approve Application List for JSK
     * | @param Request $req
     * | Function - 20
     * | API - 18
     */
    public function listjskApprovedApplication(Request $req)
    {
        try {
            // Variable initialization
            $userId = $req->auth['id'];
            $mAdvAgency = new AdvAgency();
            $applications = $mAdvAgency->listjskApprovedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Approved Application List", $data1, "050518", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050518", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Reject Application List for JSK
     * | @param Request $req
     * | Function - 21
     * | API - 19
     */
    public function listJskRejectedApplication(Request $req)
    {
        try {
            // Variable initialization
            $userId = $req->auth['id'];
            $mAdvRejectedAgency = new AdvRejectedAgency();
            $applications = $mAdvRejectedAgency->listJskRejectedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }
            return responseMsgs(true, "Rejected Application List", $data1, "050519", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050519", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Generate Payment Order ID
     * | @param Request $req
     * | Function - 22
     * | API - 20
     */
    public function generatePaymentOrderId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mAdvAgency = AdvAgency::find($req->id);
            $reqData = [
                "id" => $mAdvAgency->id,
                'amount' => $mAdvAgency->payment_amount,
                'workflowId' => $mAdvAgency->workflow_id,
                'ulbId' => $mAdvAgency->ulb_id,
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

            $data->name = $mAdvAgency->applicant;
            $data->email = $mAdvAgency->email;
            $data->contact = $mAdvAgency->mobile_no;
            $data->type = "Agency";
            // return $data;

            return responseMsgs(true, "Payment OrderId Generated Successfully !!!", $data, "050520", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050520", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * Summary of application Details For Payment
     * @param Request $req
     * @return void
     * | Function - 23
     * | API - 21
     */
    public function getApplicationDetailsForPayment(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            // Variable initialization

            $mAdvAgency = new AdvAgency();
            if ($req->applicationId) {
                $data = $mAdvAgency->getApplicationDetailsForPayment($req->applicationId);
            }

            if (!$data)
                throw new Exception("Application Not Found");

            $data['type'] = "Agency";
            return responseMsgs(true, 'Data Fetched',  $data, "050521", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050521", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Renewal Agency
     * | Function - 24
     * | API - 22
     */
    public function renewalAgency(RenewalRequest $req)
    {
        try {
            // Variable initialization
            $agency = new AdvActiveAgency();
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

            DB::beginTransaction();
            $applicationNo = $agency->renewalAgency($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted Application For Renewals !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050522", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050522", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Agency Payment by cash
     * | Function - 25
     * | API - 23
     */
    public function agencyPaymentByCash(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string',
            'status' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvAgency = new AdvAgency();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $d = $mAdvAgency->paymentByCash($req);
            $appDetails = AdvAgency::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cash");
            DB::commit();

            if ($req->status == '1' && $d['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $d['paymentId'], 'workflowId' => $appDetails->workflow_id], "050523", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050523", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050523", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Entry Cheque or DD for payment
     * | Function - 26
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
            $wfId = AdvAgency::find($req->applicationId)->workflow_id;
            $mAdvCheckDtl = new AdvChequeDtl();
            $workflowId = ['workflowId' => $wfId];
            $req->request->add($workflowId);
            $transNo = $mAdvCheckDtl->entryChequeDd($req);

            return responseMsgs(true, "Check Entry Successfully !!", ['status' => true, 'TransactionNo' => $transNo], "050524", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050524", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Clear or bounce cheque or dd
     * | Function - 27
     * | API - 25
     */
    public function clearOrBounceCheque(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'paymentId' => 'required|string',
            'status' => 'required|integer',
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
            $appDetails = AdvAgency::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cheque/DD");
            DB::commit();

            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050525", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050525", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050525", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Verify Single Application Approve or reject
     * | Function - 28
     * | API - 26
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
            $mAdvActiveAgency = new AdvActiveAgency();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = $req->auth['id'];
            $applicationId = $req->applicationId;

            $wfLevel = Config::get('constants.SELF-LABEL');
            // Derivative Assigments
            $appDetails = $mAdvActiveAgency->getAgencyNo($applicationId);

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


            $ifFullDocVerified1 = $this->ifFullDocVerified($applicationId);       // (Current Object Derivative Function 4.1)

            if ($ifFullDocVerified1 == 1)
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

            return responseMsgs(true, $req->docStatus . " Successfully", "", "050526", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050526", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     * | Function - 29
     */
    public function ifFullDocVerified($applicationId)
    {
        $mAdvActiveVehicle = new AdvActiveAgency();
        $mWfActiveDocument = new WfActiveDocument();
        $mAdvActiveVehicle = $mAdvActiveVehicle->getAgencyNo($applicationId);                      // Get Application Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $mAdvActiveVehicle->workflow_id,
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
     * | API - 27
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            // Variable initialization

            $redis = Redis::connection();
            $mAdvActiveAgency = AdvActiveAgency::find($req->applicationId);
            if ($mAdvActiveAgency->doc_verify_status == 1)
                throw new Exception("All Documents Are Approved, So Application is Not BTC !!!");
            if ($mAdvActiveAgency->doc_upload_status == 1)
                throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");

            $workflowId = $mAdvActiveAgency->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mAdvActiveAgency->current_role_id = $backId->wf_role_id;
            $mAdvActiveAgency->parked = 1;
            $mAdvActiveAgency->save();

            $metaReqs['moduleId'] = $this->_moduleId;
            $metaReqs['workflowId'] = $mAdvActiveAgency->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_agencies.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050527', '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050527", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Back To Citizen Inbox
     * | Function - 31
     * | API - 28
     */
    public function listBtcInbox(Request $req)
    {
        try {
            // Variable initialization

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

            $mAdvActiveAgency = new AdvActiveAgency();
            $btcList = $mAdvActiveAgency->getAgencyList($ulbId)
                ->whereIn('adv_active_agencies.current_role_id', $roleId)
                // ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('adv_active_agencies.id');
            // ->get();
            if (trim($req->key))
                $btcList =  searchFilter($btcList, $req);
            $list = paginator($btcList, $req);

            return responseMsgs(true, "BTC Inbox List", $list, "050528", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050528", 1.0, "", "POST", "", "");
        }
    }

    /**
     * | Check full document upload or not
     * | Function - 32
     */
    public function checkFullUpload($applicationId)
    {
        $docCode = $this->_docCode;
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = $this->_moduleId;
        $totalRequireDocs = $mWfActiveDocument->totalNoOfDocs($docCode);
        $appDetails = AdvActiveAgency::find($applicationId);
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
     * | Re-upload rejetced documents
     * | Function - 33
     * | API - 29
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

            $mAdvActiveAgency = new AdvActiveAgency();
            DB::beginTransaction();
            $appId = $mAdvActiveAgency->reuploadDocument($req);
            $this->checkFullUpload($appId);
            DB::commit();

            return responseMsgs(true, "Document Uploaded Successfully", "", "050529", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050529", 1.0, "", "POST", "", "");
        }
    }
    /**
     * | Search application by mobile no., entity name, and owner name
     * | Function - 34
     * | API - 30
     */
    public function searchByNameorMobile(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'filterBy' => 'required|in:mobileNo,entityName',
            'parameter' => $req->filterBy == 'mobileNo' ? 'required|digits:10' : 'required|string',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvAgency = new AdvAgency();
            $listApplications = $mAdvAgency->searchByNameorMobile($req);
            if (!$listApplications)
                throw new Exception("Application Not Found !!!");

            return responseMsgs(true, "Application Fetched Successfully", $listApplications, "050530", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050530", 1.0, "", "POST", "", "");
        }
    }

    /**
     * Check isAgency or Not
     * @return void
     * | Function - 35
     * | API - 31
     */
    public function isAgency(Request $req)
    {
        try {
            $userType = $req->auth['user_type'];
            if ($userType == "Citizen") {
                // Variable initialization
                $startTime = microtime(true);

                $citizenId = $req->auth['id'];
                $mAdvAgency = new AdvAgency();
                $isAgency = $mAdvAgency->checkAgency($citizenId);

                if (empty($isAgency)) {
                    throw new Exception("You Have Not Agency !!");
                } else {
                    return responseMsgs(true, "Data Fetched !!!", $isAgency, "050531", "1.0", responseTime(), "POST", $req->deviceId ?? "");
                }
            } else {
                throw new Exception("You Are Not Citizen");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050531", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Agency Dashboard
     * | Function - 36
     * | API - 32
     */
    public function getAgencyDashboard(Request $req)
    {
        try {
            $userType = $req->auth['user_type'];
            if ($userType == "Citizen") {
                // Variable initialization
                $citizenId = authUser()->id;
                $mAdvHoarding = new AdvHoarding();
                $agencyDashboard = $mAdvHoarding->agencyDashboard($citizenId, 119);
                if (empty($agencyDashboard)) {
                    throw new Exception("You Have Not Agency !!");
                } else {
                    return responseMsgs(true, "Data Fetched !!!", $agencyDashboard, "050532", "1.0", responseTime(), "POST", $req->deviceId ?? "");
                }
            } else {
                throw new Exception("You Are Not Citizen");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050532", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Between Two Dates
     * | Function - 37
     * | API - 33
     */
    public function getApplicationBetweenDate(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050533", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
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
            $mAdvAgency = new AdvAgency();
            $approveList = $mAdvAgency->approveListForReport();

            $approveList = $approveList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvActiveAgency = new AdvActiveAgency();
            $pendingList = $mAdvActiveAgency->pendingListForReport();

            $pendingList = $pendingList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvRejectedAgency = new AdvRejectedAgency();
            $rejectList = $mAdvRejectedAgency->rejectListForReport();

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
            return responseMsgs(true, "Application Fetched Successfully", $data, "050533", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050533", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | COllection From New or Renew Application
     * | Function - 38
     * | API - 34
     */
    public function paymentCollection(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050534", 1.0, "271ms", "POST", "", "");
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

            $approveList = DB::table('adv_agency_renewals')
                ->select('id', 'application_no', 'entity_name', 'application_date', 'application_type', DB::raw("'Approve' as application_status"), 'payment_amount', 'payment_date', 'payment_mode')->where('application_type', $req->applicationType)->where('payment_status', '1')->where('ulb_id', $ulbId)
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
            return responseMsgs(true, "Application Fetched Successfully", $data, "050534", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050534", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Create Agency User For Hoardings
     */
    public function store($req, $data)
    {
        // try {
        // Validation---@source-App\Http\Requests\AuthUserRequest
        $user = new User();
        $this->saving($user, $req, $data);                     // Storing data using Auth trait
        $user->password = Hash::make($data->mobile_no);
        $user->save();
        return $id = $user->id;
        // } catch (Exception $e) {
        //     return responseMsgs(false, $e->getMessage(), "");
        // }
    }

    /**
     * Saving User Credentials 
     */
    public function saving($user, $request, $data)
    {
        $user->name = $data->entity_name;
        $user->mobile = $data->mobile_no;
        $user->email = $data->email;
        // if ($request['ulb']) {
        $user->ulb_id = $data->ulb_id;
        // }
        // if ($request['userType']) {
        $user->user_type = "Advert-Agency";
        // }

        $token = Str::random(80);                       //Generating Random Token for Initial
        $user->remember_token = $token;
    }

    /**
     * | Image Document Upload
     * | @param refImageName format Image Name like SAF-geotagging-id (Pass Your Ref Image Name Here)
     * | @param requested image (pass your request image here)
     * | @param relativePath Image Relative Path (pass your relative path of the image to be save here)
     * | @return imageName imagename to save (Final Image Name with time and extension)
     */
    public function upload($refImageName, $image, $relativePath)
    {
        $extention = $image->getClientOriginalExtension();
        $imageName = time() . '-' . $refImageName . '.' . $extention;
        $image->move($relativePath, $imageName);

        return $imageName;
    }

    /**
     * | Check Email is Available or not For Agency user
     * | Function - 39
     * | API - 35
     */
    public function isEmailAvailable(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            $count = (DB::table('users')->where('email', $req->email))->count();
            if ($count > 0)
                return ['status' => true, 'data' => 0];                                      // Email is Taken 
            else
                return ['status' => true, 'data' => 1];                                      // Email is Free For Taken
        } catch (Exception $e) {
            return responseMsgs(false, "", $e->getMessage(), "050535", 1.0, "271ms", "POST", "", "");
        }
    }
}
