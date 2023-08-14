<?php

namespace App\Http\Controllers\Markets;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\RenewalRequest;
use App\Http\Requests\Hostel\StoreRequest;
use App\Http\Requests\Hostel\UpdateRequest;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\AdvChequeDtl;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Markets\MarActiveHostel;
use App\Models\Markets\MarHostel;
use App\Models\Markets\MarketPriceMstr;
use App\Models\Markets\MarRejectedHostel;
use App\Models\Param\AdvMarTransaction;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\Workflows\WorkflowTrack;
use App\Repository\Markets\iMarketRepo;
use App\Traits\MarDetailsTraits;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * | Created By- Bikash Kumar 
 * | Created for the Hostel Operations
 * | Status - Closed (24 Apr 2023), Total Function - 35, Total API - 33,  Total no. of lines - 1665
 */
class HostelController extends Controller
{

    use WorkflowTrait;

    use MarDetailsTraits;
    protected $_modelObj;
    protected $_moduleIds;
    protected $_workflowIds;
    protected $_repository;
    protected $_docCode;
    protected $_docCodeRenew;
    protected $_baseUrl;
    protected $_tempParamId;
    protected $_paramId;
    protected $_wfMasterId;
    protected $_fileUrl;

    //Constructor
    public function __construct(iMarketRepo $mar_repo)
    {
        $this->_modelObj = new MarActiveHostel();
        // $this->_workflowIds = Config::get('workflow-constants.HOSTEL_WORKFLOWS');
        $this->_moduleIds = Config::get('workflow-constants.MARKET_MODULE_ID');
        $this->_repository = $mar_repo;
        $this->_docCode = Config::get('workflow-constants.HOSTEL_DOC_CODE');
        $this->_docCodeRenew = Config::get('workflow-constants.HOSTEL_DOC_CODE_RENEW');
        $this->_paramId = Config::get('workflow-constants.HOS_ID');
        $this->_tempParamId = Config::get('workflow-constants.T_HOS_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');

        $this->_wfMasterId = Config::get('workflow-constants.HOSTEL_WF_MASTER_ID');
    }

    /**
     * | Apply for Hostel 
     * | @param StoreRequest 
     * | Function - 01
     * | API - 01
     */
    public function addNew(StoreRequest $req)
    {
        try {
            // Variable initialization

            $mMarActiveHostel = $this->_modelObj;
            $citizenId = ['citizenId' => $req->auth['id']];
            $req->request->add($citizenId);

            // Generate Application No
            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];
            $req->request->add($applicationNo);

            // $mWfWorkflow=new WfWorkflow();
            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $applicationNo = $mMarActiveHostel->addNew($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050901", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050901", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Inbox List
     * | @param Request $req
     * | Function - 02
     * | API - 02
     */
    public function listInbox(Request $req)
    {
        try {
            // Variable initialization

            $mMarActiveHostel = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $inboxList = $mMarActiveHostel->listInbox($roleIds, $ulbId);                       // <----- Get Inbox List
            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);

            return responseMsgs(true, "Inbox Applications", $list, "050902", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050902", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Outbox List
     * | Function - 03
     * | API - 03
     */
    public function listOutbox(Request $req)
    {
        try {
            // Variable initialization

            $mMarActiveHostel = $this->_modelObj;
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $outboxList = $mMarActiveHostel->listOutbox($roleIds, $ulbId);                      // <----- Get Outbox List
            if (trim($req->key))
                $outboxList =  searchFilter($outboxList, $req);
            $list = paginator($outboxList, $req);

            return responseMsgs(true, "Outbox Lists",  $list, "050903", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050903", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Application Details
     * | Function - 04
     * | API - 04
     */

    public function getDetailsById(Request $req)
    {
        try {
            // Variable initialization
            $mMarActiveHostel = $this->_modelObj;
            $fullDetailsData = array();
            $type = NULL;
            if (isset($req->type)) {
                $type = $req->type;
            }
            if ($req->applicationId) {
                $data = $mMarActiveHostel->getDetailsById($req->applicationId, $type);
            } else {
                throw new Exception("Not Pass Application Id");
            }

            if (!$data)
                throw new Exception("Application Not Found");
            // Basic Details
            $basicDetails = $this->generateBasicDetailsForHostel($data); // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            $cardDetails = $this->generateCardDetails($data);
            $cardElement = [
                'headerTitle' => "Hostel",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);

            $metaReqs['customFor'] = 'HOSTEL';
            $metaReqs['wfRoleId'] = $data['current_role_id'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];

            $req->request->add($metaReqs);
            $forwardBackward = $this->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData = remove_null($fullDetailsData);

            $fullDetailsData['application_no'] = $data['application_no'];
            $fullDetailsData['apply_date'] = Carbon::createFromFormat('Y-m-d',  $data['application_date'])->format('d-m-Y');
            $fullDetailsData['doc_verify_status'] = $data['doc_verify_status'];
            $fullDetailsData['timelineData'] = collect($req);
            $fullDetailsData['workflowId'] = $data['workflow_id'];

            return responseMsgs(true, 'Data Fetched', $fullDetailsData, "050904", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050904", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }
    /**
     * | Get Application role details
     * | Function - 05
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
     * Summary of getCitizenApplications
     * @param Request $req
     * @return void
     * | Function - 06
     * | API - 05
     */
    public function listAppliedApplications(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $mMarActiveHostel = $this->_modelObj;
            $applications = $mMarActiveHostel->listAppliedApplications($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Applied Applications", $data1, "050905", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050905", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     *  | Escalate
     * @param Request $request
     * @return void
     * | Function - 07
     * | API - 06
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
            $data = MarActiveHostel::find($applicationId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();

            return responseMsgs(true, $request->escalateStatus == 1 ? 'Hostel is Escalated' : "Hostel is removed from Escalated", '', "050906", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050906", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     *  Special Inbox List
     * @param Request $req
     * @return void
     * | Function - 08
     * | API - 07
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

            $advData = $this->_repository->specialInboxHostel($workflowId)                      // Repository function to get Markets Details
                ->where('is_escalate', 1)
                ->where('mar_active_hostels.ulb_id', $ulbId);
            // ->whereIn('ward_mstr_id', $wardId)
            // ->get();
            if (trim($req->key))
                $advData =  searchFilter($advData, $req);
            $list = paginator($advData, $req);

            return responseMsgs(true, "Data Fetched", $list, "050907", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050907", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * Forward or Backward Application
     * @param Request $request
     * @return void
     * | Function - 09
     * | API - 08
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
            // Marriage Banqute Hall Application Update Current Role Updation
            $mMarActiveHostel = MarActiveHostel::find($request->applicationId);
            if ($mMarActiveHostel->parked == NULL && $mMarActiveHostel->doc_upload_status == '0')
                throw new Exception("Document Rejected Please Send Back to Citizen !!!");
            if ($mMarActiveHostel->parked == '1' && $mMarActiveHostel->doc_upload_status == '0')
                throw new Exception("Document Are Not Re-upload By Citizen !!!");
            if ($mMarActiveHostel->doc_verify_status == '0' && $mMarActiveHostel->parked == NULL)
                throw new Exception("Please Verify All Documents To Forward The Application !!!");

            $mMarActiveHostel->last_role_id = $mMarActiveHostel->current_role_id;
            $mMarActiveHostel->current_role_id = $request->receiverRoleId;
            $mMarActiveHostel->save();

            $metaReqs['moduleId'] = $this->_moduleIds;
            $metaReqs['workflowId'] = $mMarActiveHostel->workflow_id;
            $metaReqs['refTableDotId'] = "mar_active_hostels.id";
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            DB::beginTransaction();
            $track->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "050908", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050908", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * Post Independent Comment
     * @param Request $request
     * @return void
     * | Function - 10
     * | API - 09
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

            $workflowTrack = new WorkflowTrack();
            $mMarActiveHostel = MarActiveHostel::find($request->applicationId);                // Advertisment Details
            $mModuleId = $this->_moduleIds;
            $metaReqs = array();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $mMarActiveHostel->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "mar_active_hostels.id",
                'refTableIdValue' => $mMarActiveHostel->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment
            if (!$request->senderRoleId) {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $mMarActiveHostel->user_id]);
            }

            $request->request->add($metaReqs);

            DB::beginTransaction();
            $workflowTrack->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "050909", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050909", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * Get Uploaded Document by application ID
     * @param Request $req
     * @return void
     * | Function - 11
     * | API - 10
     */
    public function viewHostelDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), "", "050910", "1.0", "", "POST", $req->deviceId ?? "");
        }
        if ($req->type == 'Active')
            $workflowId = MarActiveHostel::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Approve')
            $workflowId = MarHostel::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Reject')
            $workflowId = MarRejectedHostel::find($req->applicationId)->workflow_id;
        $mWfActiveDocument = new WfActiveDocument();
        $data = array();
        if ($req->applicationId && $req->type) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId,  $workflowId);
        } else {
            throw new Exception("Required Application Id And Application Type");
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
     * | Function - 12
     * | API - 11
     */
    public function viewActiveDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        $workflowId = MarActiveHostel::find($req->applicationId)->workflow_id;
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
     * | Function - 13
     * | API - 12
     */
    public function viewDocumentsOnWorkflow(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        // Variable initialization
        $startTime = microtime(true);
        if (isset($req->type) && $req->type == 'Approve')
            $workflowId = MarHostel::find($req->applicationId)->workflow_id;
        else
            $workflowId = MarActiveHostel::find($req->applicationId)->workflow_id;
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
        return responseMsgs(true, "Data Fetched", remove_null($data1), "050912", "1.0", responseTime(), "POST", "");
    }


    /**
     * Final Approval and Rejection of the Application
     * @param Request $req
     * @return void
     * | Function - 14
     * | API - 13
     */
    public function approvedOrReject(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'roleId' => 'required',
            'applicationId' => 'required|integer',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            // Check if the Current User is Finisher or Not         
            $mMarActiveHostel = MarActiveHostel::find($req->applicationId);
            $getFinisherQuery = $this->getFinisherId($mMarActiveHostel->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsgs(false, " Access Forbidden", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {

                $mMarketPriceMstr = new MarketPriceMstr();
                $amount = $mMarketPriceMstr->getMarketTaxPrice($this->_wfMasterId, $mMarActiveHostel->no_of_beds, $mMarActiveHostel->ulb_id);

                if ($mMarActiveHostel->is_approve_by_govt == true) {
                    $amount = $mMarketPriceMstr->getMarketTaxPriceGovtHostel($this->_wfMasterId, $mMarActiveHostel->ulb_id);
                }
                $payment_amount = ['payment_amount' => $amount];
                $req->request->add($payment_amount);

                $idGeneration = new PrefixIdGenerator($this->_paramId, $mMarActiveHostel->ulb_id);
                $generatedId = $idGeneration->generate();

                if ($mMarActiveHostel->renew_no == NULL) {
                    // Hostel Application replication
                    $approvedhostel = $mMarActiveHostel->replicate();
                    $approvedhostel->setTable('mar_hostels');
                    $temp_id = $approvedhostel->id = $mMarActiveHostel->id;
                    $approvedhostel->payment_amount = round($req->payment_amount);
                    $approvedhostel->demand_amount = $req->payment_amount;
                    $approvedhostel->license_no = $generatedId;
                    $approvedhostel->approve_date = Carbon::now();
                    $approvedhostel->save();

                    // Save in Hostel Renewal
                    $approvedhostel = $mMarActiveHostel->replicate();
                    $approvedhostel->approve_date = Carbon::now();
                    $approvedhostel->setTable('mar_hostel_renewals');
                    $approvedhostel->app_id = $mMarActiveHostel->id;
                    $approvedhostel->license_no = $generatedId;
                    $approvedhostel->save();

                    $mMarActiveHostel->delete();

                    // Update in mar_hostels (last_renewal_id)
                    DB::table('mar_hostels')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedhostel->id]);

                    $msg = "Application Successfully Approved !!";
                } else {
                    //  Renewal Case
                    // Hostel Application replication
                    $application_no = $mMarActiveHostel->application_no;
                    MarHostel::where('application_no', $application_no)->delete();

                    $approvedHostel = $mMarActiveHostel->replicate();
                    $approvedHostel->setTable('mar_hostels');
                    $temp_id = $approvedHostel->id = $mMarActiveHostel->id;
                    $approvedHostel->payment_amount = round($req->payment_amount);
                    $approvedHostel->demand_amount = $req->payment_amount;
                    $approvedHostel->payment_status = $req->payment_status;
                    $approvedHostel->approve_date = Carbon::now();
                    $approvedHostel->save();

                    // Save in Hostel Renewal
                    $approvedHostel = $mMarActiveHostel->replicate();
                    $approvedHostel->approve_date = Carbon::now();
                    $approvedHostel->setTable('mar_hostel_renewals');
                    $approvedHostel->app_id = $temp_id;
                    $approvedHostel->save();

                    $mMarActiveHostel->delete();

                    // Update in mar_hostels (last_renewal_id)
                    DB::table('mar_hostels')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedHostel->id]);
                    $msg = "Application Successfully Renewal !!";
                }
            }
            // Rejection
            if ($req->status == 0) {
                //Hostel Application replication
                $rejectedhostel = $mMarActiveHostel->replicate();
                $rejectedhostel->setTable('mar_rejected_hostels');
                $rejectedhostel->id = $mMarActiveHostel->id;
                $rejectedhostel->rejected_date = Carbon::now();
                $rejectedhostel->save();
                $mMarActiveHostel->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            return responseMsgs(true, $msg, "", '050913', 01, responseTime(), 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false,  $e->getMessage(), "", '050913', 01, "", 'POST', $req->deviceId);
        }
    }

    /**
     * Approved Application List for Citizen
     * @param Request $req
     * @return void
     * | Function - 15
     * | API - 14
     */
    public function listApproved(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];
            $mMarHostel = new MarHostel();
            $applications = $mMarHostel->listApproved($citizenId, $userType);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;

            return responseMsgs(true, "Approved Application List", $data1, "050914", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050914", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * Rejected Application List
     * @param Request $req
     * @return void
     * | Function - 16
     * | API - 15
     */
    public function listRejected(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $mMarRejectedHostel = new MarRejectedHostel();
            $applications = $mMarRejectedHostel->listRejected($citizenId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Rejected Application List", $data1, "050915", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050915", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * generate Payment OrderId for Payment
     * @param Request $req
     * @return void
     * | Function - 17
     * | API - 16
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

            $mMarHostel = MarHostel::find($req->id);
            $reqData = [
                "id" => $mMarHostel->id,
                'amount' => $mMarHostel->payment_amount,
                'workflowId' => $mMarHostel->workflow_id,
                'ulbId' => $mMarHostel->ulb_id,
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

            $data->name = $mMarHostel->applicant;
            $data->email = $mMarHostel->email;
            $data->contact = $mMarHostel->mobile_no;
            $data->type = "Hostel";

            return responseMsgs(true, "Payment OrderId Generated Successfully !!!", $data, "050916", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050916", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * Get application Details For Payment
     * @return void
     * | Function - 18
     * | API - 17
     */
    public function getApplicationDetailsForPayment(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            // Variable initialization
            $mMarHostel = new MarHostel();
            if ($req->applicationId) {
                $data = $mMarHostel->getApplicationDetailsForPayment($req->applicationId);
            }

            if (!$data)
                throw new Exception("Application Not Found");

            $data['type'] = "Hostel";

            return responseMsgs(true, 'Data Fetched',  $data, "050917", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050917", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Verify Single Application Approve or reject
     * | Function - 19
     * | API - 18
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
            $mMarActiveHostel = new MarActiveHostel();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = $req->auth['id'];
            $applicationId = $req->applicationId;

            $wfLevel = Config::get('constants.MARKET-LABEL');
            // Derivative Assigments
            $appDetails = $mMarActiveHostel->getHostelDetails($applicationId);

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

            return responseMsgs(true, $req->docStatus . " Successfully", "", "050918", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050918", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     * | Function - 20
     */
    public function ifFullDocVerified($applicationId)
    {
        $mMarActiveHostel = new MarActiveHostel();
        $mWfActiveDocument = new WfActiveDocument();
        $mMarActiveHostel = $mMarActiveHostel->getHostelDetails($applicationId);                      // Get Application Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $mMarActiveHostel->workflow_id,
            'moduleId' =>  $this->_moduleIds
        ];
        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $totalApproveDoc = $refDocList->count();
        // self Advertiesement List Documents
        $ifAdvDocUnverified = $refDocList->contains('verify_status', 0);
        $totalNoOfDoc = $mWfActiveDocument->totalNoOfDocs($this->_docCode);
        // $totalNoOfDoc=$mWfActiveDocument->totalNoOfDocs($this->_docCodeRenew);
        // if($mMarActiveHostel->renew_no==NULL){
        //     $totalNoOfDoc=$mWfActiveDocument->totalNoOfDocs($this->_docCode);
        // }
        if ($totalApproveDoc >= $totalNoOfDoc) {
            if ($ifAdvDocUnverified == 1)
                return 0;
            else
                return 1;
        } else {
            return 0;
        }
    }

    /**
     * | Send back to citizen
     * | Function - 21
     * | API - 19
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            // Variable initialization

            $redis = Redis::connection();
            $mMarActiveHostel = MarActiveHostel::find($req->applicationId);
            if($mMarActiveHostel -> doc_verify_status == 1)
                throw new Exception("All Documents Are Approved, So Application is Not BTC !!!");
                
            if ($mMarActiveHostel->doc_upload_status == 1)
            throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");

            $workflowId = $mMarActiveHostel->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mMarActiveHostel->current_role_id = $backId->wf_role_id;
            $mMarActiveHostel->parked = 1;
            $mMarActiveHostel->save();


            $metaReqs['moduleId'] = $this->_moduleIds;
            $metaReqs['workflowId'] = $mMarActiveHostel->workflow_id;
            $metaReqs['refTableDotId'] = "mar_active_hostels.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050919', '01', responseTime(), 'POST', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050919", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Back To Citizen Inbox
     * | Function - 22
     * | API - 20
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

            $mMarActiveHostel = new MarActiveHostel();
            $btcList = $mMarActiveHostel->getHostelList($ulbId)
                ->whereIn('mar_active_hostels.current_role_id', $roleId)
                // ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('mar_active_hostels.id');
            // ->get();
            if (trim($req->key))
                $btcList =  searchFilter($btcList, $req);
            $list = paginator($btcList, $req);

            return responseMsgs(true, "BTC Inbox List", $list, "050920", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050920", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Check full document uploaded or not
     * | Function - 23
     */
    public function checkFullUpload($applicationId)
    {
        $appDetails = MarActiveHostel::find($applicationId);
        $docCode = $this->_docCode;
        // $docCode = $this->_docCodeRenew;
        // if($appDetails->renew_no==NULL){
        //     $docCode = $this->_docCode;
        // }
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = $this->_moduleIds;
        $totalRequireDocs = $mWfActiveDocument->totalNoOfDocs($docCode);
        $totalUploadedDocs = $mWfActiveDocument->totalUploadedDocs($applicationId, $appDetails->workflow_id, $moduleId);
        if ($totalUploadedDocs >= $totalRequireDocs) {
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
     * | Re-upload rejected document by citizen
     * | Function - 24
     * | API - 21
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

            $mMarActiveHostel = new MarActiveHostel();
            DB::beginTransaction();
            $appId = $mMarActiveHostel->reuploadDocument($req);
            $this->checkFullUpload($appId);
            DB::commit();

            return responseMsgs(true, "Document Uploaded Successfully", "", "050921", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050921", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Payment Application Via Cash
     * | Function - 25
     * | API - 22
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
            // Variable initialization
            $mMarHostel = new MarHostel();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mMarHostel->paymentByCash($req);
            $appDetails = MarHostel::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleIds, "Market", "Cash");
            DB::commit();

            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050922", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050922", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050922", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Entry Cheque or DD for payment
     * | Function - 26
     * | API - 23
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
            // Variable initialization
            $wfId = MarHostel::find($req->applicationId)->workflow_id;
            $mAdvCheckDtl = new AdvChequeDtl();
            $workflowId = ['workflowId' => $wfId];
            $req->request->add($workflowId);
            $transNo = $mAdvCheckDtl->entryChequeDd($req);

            return responseMsgs(true, "Check Entry Successfully !!", ['status' => true, 'TransactionNo' => $transNo], "050923", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050923", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Clear or Bounce cheque for payment
     * | Function - 27
     * | API - 24
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
            $status = $mAdvCheckDtl->clearOrBounceCheque($req);
            $appDetails = MarHostel::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleIds, "Market", "Cheque/DD");
            DB::commit();

            if ($req->status == '1' && $status == 1) {
                return responseMsgs(true, "Payment Successfully !!", '', "050924", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050924", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050924", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Details For Renew
     * | Function - 28
     * | API - 25
     */
    public function getApplicationDetailsForRenew(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mMarHostel = new MarHostel();
            $details = $mMarHostel->applicationDetailsForRenew($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found !!!");

            return responseMsgs(true, "Application Fetched !!!", remove_null($details), "050925", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050925", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Apply for Lodge
     * | @param StoreRequest 
     * | Function - 29
     * | API - 26
     */
    public function renewApplication(RenewalRequest $req)
    {
        try {
            // Variable initialization

            $mMarActiveLodge = $this->_modelObj;
            $citizenId = ['citizenId' => $req->auth['id']];
            $req->request->add($citizenId);

            $mCalculateRate = new CalculateRate;
            $generatedId = $mCalculateRate->generateId($req->bearerToken(), $this->_tempParamId, $req->ulbId); // Generate Application No
            $applicationNo = ['application_no' => $generatedId];

            // $mWfWorkflow=new WfWorkflow();
            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $applicationNo = $mMarActiveLodge->renewApplication($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Renewal the application !!", ['status' => true, 'ApplicationNo' => $applicationNo], "050926", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050926", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Application Details For Update Application
     * | Function - 30
     * | API - 27
     */
    public function getApplicationDetailsForEdit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mMarActiveHostel = new MarActiveHostel();
            $details = $mMarActiveHostel->getApplicationDetailsForEdit($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found !!!");
            return responseMsgs(true, "Application Featch Successfully !!!", $details, "050927", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Featched !!!", "", "050927", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Application Updation
     * | Function - 31
     * | API - 28
     */
    public function editApplication(UpdateRequest $req)
    {
        try {
            // Variable initialization

            $mMarActiveHostel = $this->_modelObj;
            DB::beginTransaction();
            $res = $mMarActiveHostel->updateApplication($req);       //<--------------- Update Banquet Hall Application
            DB::commit();

            if ($res)
                return responseMsgs(true, "Application Update Successfully !!!", "", "050928", 1.0, responseTime(), "POST", "", "");
            else
                return responseMsgs(false, "Application Not Updated !!!", "", "050928", 1.0, "271ms", "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Application Not Updated !!!", $e->getMessage(), "050928", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Get Application Between Two Dates
     * | Function - 32
     * | API - 29
     */
    public function getApplicationBetweenDate(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050929", 1.0, "271ms", "POST", "", "");
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
            $mMarHostel = new MarHostel();
            $approveList = $mMarHostel->approveListForReport();

            $approveList = $approveList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mMarActiveHostel = new MarActiveHostel();
            $pendingList = $mMarActiveHostel->pendingListForReport();

            $pendingList = $pendingList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mMarRejectedHostel = new MarRejectedHostel();
            $rejectList = $mMarRejectedHostel->rejectListForReport();

            $rejectList = $rejectList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

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
            return responseMsgs(true, "Application Fetched Successfully", $data, "050929", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050929", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Get Application Financial Year Wise
     * | Function - 33
     * | API - 30
     */
    public function getApplicationFinancialYearWise(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050930", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = authUser()->ulb_id;

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'entityWard' => 'required|integer',
            'perPage' => 'required|integer',
            'financialYear' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            #=============================================================
            $mMarHostel = new MarHostel();
            $approveList = $mMarHostel->approveListForReport();

            $approveList = $approveList->where('application_type', $req->applicationType)->where('entity_ward_id', $req->entityWard)->where('ulb_id', $ulbId)->where('license_year', $req->financialYear);

            $mMarActiveHostel = new MarActiveHostel();
            $pendingList = $mMarActiveHostel->pendingListForReport();

            $pendingList = $pendingList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->where('entity_ward_id', $req->entityWard)->where('license_year', $req->financialYear);

            $mMarRejectedHostel = new MarRejectedHostel();
            $rejectList = $mMarRejectedHostel->rejectListForReport();

            $rejectList = $rejectList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->where('entity_ward_id', $req->entityWard)->where('license_year', $req->financialYear);

            $data = collect(array());
            $data = $approveList->union($pendingList)->union($rejectList);
            $data = $data->paginate($req->perPage);

            return responseMsgs(true, "Application Fetched Successfully", $data, "050930", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050930", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | COllection From New or Renew Application
     * | Function - 34
     * | API - 31
     */
    public function paymentCollection(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050931", 1.0, "271ms", "POST", "", "");
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

            $approveList = DB::table('mar_hostel_renewals')
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050931", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050931", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Rule Wise Applications
     * | Function - 34
     * | API - 32
     */
    public function ruleWiseApplications(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050932", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'applicationStatus' => 'required|in:All,Approve,Reject',
            'ruleType' => 'required|in:All,New Rule,Old Rule',
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
            $mMarHostel = new MarHostel();
            $approveList = $mMarHostel->approveListForReport();

            $approveList = $approveList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->where('rule', $req->ruleType)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mMarActiveHostel = new MarActiveHostel();
            $pendingList = $mMarActiveHostel->pendingListForReport();

            $pendingList = $pendingList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->where('rule', $req->ruleType)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);


            $mMarRejectedHostel = new MarRejectedHostel();
            $rejectList = $mMarRejectedHostel->rejectListForReport();

            $rejectList = $rejectList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->where('rule', $req->ruleType)
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
            return responseMsgs(true, "Application Fetched Successfully", $data, "050932", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050932", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Get Application Hosteml Type Wise
     * | Function - 35
     * | API - 33
     */
    public function getApplicationByHostelType(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050933", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'applicationStatus' => 'required|in:All,Approve,Reject',
            'entityWard' => 'required|integer',
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateUpto' => 'required|date_format:Y-m-d',
            'hostelType' => 'required|integer',
            'perPage' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mMarHostel = new MarHostel();
            $approveList = $mMarHostel->approveListForReport();

            $approveList = $approveList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('hostel_type', $req->hostelType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);


            $mMarActiveHostel = new MarActiveHostel();
            $pendingList = $mMarActiveHostel->pendingListForReport();

            $pendingList = $pendingList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('hostel_type', $req->hostelType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mMarRejectedHostel = new MarRejectedHostel();
            $rejectList = $mMarRejectedHostel->rejectListForReport();

            $rejectList = $rejectList->where('entity_ward_id', $req->entityWard)->where('application_type', $req->applicationType)->where('hostel_type', $req->hostelType)->where('ulb_id', $ulbId)
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050933", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050933", 1.0, "271ms", "POST", "", "");
        }
    }
}
