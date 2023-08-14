<?php

namespace App\Http\Controllers\Advertisements;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;

use App\Http\Requests\Agency\RenewalHordingRequest;
use App\Http\Requests\Agency\StoreLicenceRequest;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Advertisements\AdvActiveHoarding;
use App\Models\Advertisements\AdvChequeDtl;
use App\Models\Advertisements\AdvHoarding;
use App\Models\Advertisements\AdvRejectedHoarding;
use App\Models\Advertisements\AdvTypologyMstr;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Param\AdvMarTransaction;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\Workflows\WorkflowTrack;
use App\Repository\SelfAdvets\iSelfAdvetRepo;
use App\Traits\AdvDetailsTraits;
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
 * | Hoarding Controller
 * | Status - Open
 */
class HoardingController extends Controller
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
        $this->_modelObj = new AdvActivehoarding();
        // $this->_workflowIds = Config::get('workflow-constants.AGENCY_HORDING_WORKFLOWS');
        $this->_moduleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_docCode = Config::get('workflow-constants.AGENCY_HORDING_DOC_CODE');
        $this->_tempParamId = Config::get('workflow-constants.TEMP_HOR_ID');
        $this->_paramId = Config::get('workflow-constants.HOR_ID');
        $this->_baseUrl = Config::get('constants.BASE_URL');
        $this->_fileUrl = Config::get('workflow-constants.FILE_URL');
        $this->Repository = $agency_repo;

        $this->_wfMasterId = Config::get('workflow-constants.HORDING_WF_MASTER_ID');
    }

    /**
     * | Get Typology List
     * | Function - 01
     * | API - 01
     */
    public function getHordingCategory(Request $req)
    {
        try {
            // Variable initialization
            $mAdvTypologyMstr = new AdvTypologyMstr();
            $typologyList = $mAdvTypologyMstr->getHordingCategory();

            return responseMsgs(true, "Typology Data Fetch Successfully!!", remove_null($typologyList), "050601", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050601", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Typology List
     * | Function - 02
     * | API - 02
     */
    public function listTypology(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "ulbId" => "required|integer",
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mAdvTypologyMstr = new AdvTypologyMstr();
            $typologyList = $mAdvTypologyMstr->listTypology1($req->ulbId);
            $typologyList = $typologyList->groupBy('type');
            foreach ($typologyList as $key => $data) {
                $type = [
                    'Type' => "Type " . $key,
                    'data' => $typologyList[$key]
                ];
                $fData[] = $type;
            }
            $fullData['typology'] = $fData;


            return responseMsgs(true, "Typology Data Fetch Successfully!!", remove_null($fullData), "050602", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050602", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Save Application For Hoarding
     * | Function - 03
     * | API - 03
     */
    public function addNew(StoreLicenceRequest $req)
    {
        try {
            $checkPaymentStatus = $this->checkPaymentCompleteOrNot($req->auth['email']);
            if ($checkPaymentStatus == 0)
                throw new Exception("Agency Registration Payment Not Complete !!!");

            // Variable initialization
            $mAdvActiveHoarding = new AdvActiveHoarding();
            if ($req->auth['user_type'] == 'JSK') {
                $userId = ['userId' => $req->auth['id']];
                $req->request->add($userId);
            } else {
                $citizenId = ['citizenId' => $req->auth['id']];
                $req->request->add($citizenId);
            }

            $ulbId = ['ulbId' => $req->auth['ulb_id']];
            $req->request->add($ulbId);

            $idGeneration = new PrefixIdGenerator($this->_tempParamId, $req->ulbId);
            $generatedId = $idGeneration->generate();
            $applicationNo = ['application_no' => $generatedId];
            $req->request->add($applicationNo);

            $WfMasterId = ['WfMasterId' =>  $this->_wfMasterId];
            $req->request->add($WfMasterId);

            DB::beginTransaction();
            $LicenseNo = $mAdvActiveHoarding->addNew($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Submitted the application !!", ['status' => true, 'ApplicationNo' => $LicenseNo], "050603", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050603", "1.0", "", "POST", $req->deviceId ?? "");
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
            $mAdvActiveHoarding = new AdvActiveHoarding();
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $inboxList = $mAdvActiveHoarding->listInbox($roleIds, $ulbId);                      // <----- Get Inbox List
            if (trim($req->key))
                $inboxList =  searchFilter($inboxList, $req);
            $list = paginator($inboxList, $req);

            return responseMsgs(true, "Inbox Applications",  $list, "050604", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050604", "1.0", "", 'POST', $req->deviceId ?? "");
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
            $mAdvActiveHoarding = new AdvActiveHoarding();
            $bearerToken = $req->bearerToken();
            $ulbId = $req->auth['ulb_id'];
            $workflowRoles = collect($this->getRoleByUserId($bearerToken));             // <----- Get Workflow Roles roles 
            $roleIds = collect($workflowRoles)->map(function ($workflowRole) {          // <----- Filteration Role Ids
                return $workflowRole['wf_role_id'];
            });
            $outboxList = $mAdvActiveHoarding->listOutbox($roleIds, $ulbId);                    // <----- Get Inbox List
            if (trim($req->key))
                $outboxList =  searchFilter($outboxList, $req);
            $list = paginator($outboxList, $req);

            return responseMsgs(true, "Outbox List", $list, "050605", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050605", "1.0", "", 'POST', $req->deviceId ?? "");
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
            $mAdvActiveHoarding = new AdvActiveHoarding();
            // $data = array();
            $fullDetailsData = array();
            if (isset($req->type)) {
                $type = $req->type;
            } else {
                $type = NULL;
            }
            if ($req->applicationId) {
                $data = $mAdvActiveHoarding->getDetailsById($req->applicationId, $type);
            } else {
                throw new Exception("Application Id Not Passed");
            }

            if (!$data) {
                throw new Exception("Application Details Not Found");
            }
            // Basic Details
            $basicDetails = $this->generatehordingDetails($data); // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Hoarding Details",
                "data" => $basicDetails
            ];

            $cardDetails = $this->generateHoardingCardDetails($data);
            $cardElement = [
                'headerTitle' => "Hoarding Details",
                'data' => $cardDetails
            ];

            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);

            $metaReqs['customFor'] = 'HOARDING';
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
            $fullDetailsData['timelineData'] = collect($req);
            $fullDetailsData['workflowId'] = $data['workflow_id'];

            return responseMsgs(true, 'Data Fetched', $fullDetailsData, "050606", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), "", "050606", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Applied Applications by Logged In Citizen
     * | Function - 07
     * | API - 07
     */
    public function listAppliedApplications(Request $req)
    {
        try {
            // Variable initialization
            $citizenId = $req->auth['id'];
            $mAdvActiveHoarding = new AdvActiveHoarding();
            $applications = $mAdvActiveHoarding->listAppliedApplications($citizenId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);

            return responseMsgs(true, "Applied Applications", $list, "050607", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050607", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Escalate Application
     * | Function - 08
     * | API - 08
     */
    public function escalateApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "escalateStatus" => "required|int",
            "applicationId" => "required|int",
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $userId = $request->auth['id'];
            $applicationId = $request->applicationId;
            $data = AdvActiveHoarding::find($applicationId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();

            return responseMsgs(true, $request->escalateStatus == 1 ? 'Hording is Escalated' : "Hording is removed from Escalated", '', "050608", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050608", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Special Inbox (Escalated Application)
     * | Function - 09
     * | API - 09
     */
    public function listEscalated(Request $req)
    {
        try {
            // Variable initialization

            $mWfWardUser = new WfWardUser();
            $userId = $req->auth['id'];
            $ulbId = $req->auth['ulb_id'];

            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {          // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });

            $mWfWorkflow = new WfWorkflow();
            $workflowId = $mWfWorkflow->getulbWorkflowId($this->_wfMasterId, $ulbId);      // get workflow Id

            $advData = $this->Repository->specialAgencyLicenseInbox($workflowId)   // Repository function to get Advertiesment Details
                ->where('is_escalate', 1)
                ->where('adv_active_hoardings.ulb_id', $ulbId);
            // ->whereIn('ward_mstr_id', $wardId)
            // ->get();
            if (trim($req->key))
                $advData =  searchFilter($advData, $req);
            $list = paginator($advData, $req);

            return responseMsgs(true, "Data Fetched",  $list, "050609", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050609", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Forward or Backward Application
     * | Function - 10
     * | API - 10
     */
    public function forwardNextLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $adv = AdvActiveHoarding::find($request->applicationId);
            if ($adv->parked == NULL && $adv->doc_upload_status == '0')
                throw new Exception("Document Rejected Please Send Back to Citizen !!!");
            if ($adv->parked == '1' && $adv->doc_upload_status == '0')
                throw new Exception("Document Are Not Re-upload By Citizen !!!");
            if ($adv->doc_verify_status == '0' && $adv->parked == NULL)
                throw new Exception("Please Verify All Documents To Forward The Application !!!");
            $adv->last_role_id = $request->senderRoleId;
            $adv->current_role_id = $request->receiverRoleId;
            $adv->save();

            $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs['workflowId'] = $adv->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_hoardings.id";
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            // Hording  Application Update Current Role Updation
            DB::beginTransaction();
            $track->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "050610", "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050610", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | Application Post Independent Comment
     * | Function - 11
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
            $mAdvActiveHoarding = AdvActiveHoarding::find($request->applicationId);                // Agency License Details
            $mModuleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs = array();
            $metaReqs = [
                'workflowId' => $mAdvActiveHoarding->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "adv_active_hoardings.id",
                'refTableIdValue' => $mAdvActiveHoarding->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment
            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $mAdvActiveHoarding->workflow_id,
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
            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "050611", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050611", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | Get  Hoarding Documents
     * | Function - 12
     * | API - 12
     */
    public function viewHoardingDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), "", "050612", "1.0", "", "POST", $req->deviceId ?? "");
        }
        $mWfActiveDocument = new WfActiveDocument();
        if ($req->type == 'Active')
            $workflowId = AdvActiveHoarding::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Approve')
            $workflowId = AdvHoarding::find($req->applicationId)->workflow_id;
        elseif ($req->type == 'Reject')
            $workflowId = AdvRejectedHoarding::find($req->applicationId)->workflow_id;
        $data = array();
        if ($req->applicationId) {
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
     * | Function - 13
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
        $mWfActiveDocument = new WfActiveDocument();
        $workflowId = AdvActiveHoarding::find($req->applicationId)->workflow_id;
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
     * | API - 14
     */
    public function viewDocumentsOnWorkflow(Request $req)
    {
        // Variable initialization
        $startTime = microtime(true);
        $mWfActiveDocument = new WfActiveDocument();
        if (isset($req->type) && $req->type == 'Approve')
            $workflowId = AdvHoarding::find($req->applicationId)->workflow_id;
        else
            $workflowId = AdvActiveHoarding::find($req->applicationId)->workflow_id;
        $data = array();
        if ($req->applicationId) {
            $data = $mWfActiveDocument->uploadDocumentsViewById($req->applicationId, $workflowId);
        }
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $appUrl = $this->_fileUrl;
        $data1 = collect($data)->map(function ($value) use ($appUrl) {
            $value->doc_path = $appUrl . $value->doc_path;
            return $value;
        });
        return responseMsgs(true, "Data Fetched", remove_null($data1), "050614", "1.0", "$executionTime Sec", "POST", "");
    }

    /**
     * | Final Approval and Rejection of the Application
     * | Function - 15
     * | API - 15
     * | Status- closed
     */
    public function approvalOrRejection(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'roleId' => 'required',
                'applicationId' => 'required|integer',
                'status' => 'required|integer',
                // 'payment_amount' => 'required',
            ]);
            if ($validator->fails()) {
                return ['status' => false, 'message' => $validator->errors()];
            }
            // Variable initialization       
            $mAdvActiveHoarding = AdvActiveHoarding::find($req->applicationId);
            $getFinisherQuery = $this->getFinisherId($mAdvActiveHoarding->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsgs(false, "Access Forbidden", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $mCalculateRate = new CalculateRate();
                $amount = $mCalculateRate->getHordingPrice($mAdvActiveHoarding->typology, $mAdvActiveHoarding->zone_id);
                $payment_amount = ['payment_amount' => $amount];
                $req->request->add($payment_amount);

                $idGeneration = new PrefixIdGenerator($this->_paramId, $mAdvActiveHoarding->ulb_id);
                $generatedId = $idGeneration->generate();
                if ($mAdvActiveHoarding->renew_no == NULL) {
                    // approved Hording Application replication
                    $approvedHoarding = $mAdvActiveHoarding->replicate();
                    $approvedHoarding->setTable('adv_hoardings');
                    $temp_id = $approvedHoarding->id = $mAdvActiveHoarding->id;
                    $approvedHoarding->license_no = $generatedId;
                    $approvedHoarding->payment_amount = round($req->payment_amount);
                    $approvedHoarding->demand_amount = $req->payment_amount;
                    $approvedHoarding->approve_date = Carbon::now();
                    $approvedHoarding->save();

                    // Save in Hording Renewal
                    $approvedHoarding = $mAdvActiveHoarding->replicate();
                    $approvedHoarding->approve_date = Carbon::now();
                    $approvedHoarding->license_no = $generatedId;
                    $approvedHoarding->setTable('adv_hoarding_renewals');
                    $approvedHoarding->id = $temp_id;
                    $approvedHoarding->save();

                    $mAdvActiveHoarding->delete();

                    // Update in adv_hoardings (last_renewal_id)

                    DB::table('adv_hoardings')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedHoarding->id]);

                    $msg = "Application Successfully Approved !!";
                } else {
                    //  Renewal Application Case

                    // Hording Application replication
                    $license_no = $mAdvActiveHoarding->license_no;
                    AdvHoarding::where('license_no', $license_no)->delete();

                    $approvedHoarding = $mAdvActiveHoarding->replicate();
                    $approvedHoarding->setTable('adv_hoardings');
                    $temp_id = $approvedHoarding->id = $mAdvActiveHoarding->id;
                    $approvedHoarding->payment_amount = round($req->payment_amount);
                    $approvedHoarding->demand_amount = $req->payment_amount;
                    $approvedHoarding->payment_status = $req->payment_status;
                    $approvedHoarding->license_no = $license_no;
                    $approvedHoarding->approve_date = Carbon::now();
                    $approvedHoarding->save();

                    // Save in Hording Advertisement Renewal
                    $approvedHoarding = $approvedHoarding->replicate();
                    $approvedHoarding->approve_date = Carbon::now();
                    $approvedHoarding->setTable('adv_hoarding_renewals');
                    $approvedHoarding->id = $temp_id;
                    $approvedHoarding->save();

                    $mAdvActiveHoarding->delete();

                    // Update in adv_hoardings (last_renewal_id)
                    DB::table('adv_hoardings')
                        ->where('id', $temp_id)
                        ->update(['last_renewal_id' => $approvedHoarding->id]);
                    $msg = "Application Successfully Renewal !!";
                }
            }
            // Rejection
            if ($req->status == 0) {

                $payment_amount = ['payment_amount' => 0];
                $req->request->add($payment_amount);

                // Agency advertisement Application replication
                $rejectedHoarding = $mAdvActiveHoarding->replicate();
                $rejectedHoarding->setTable('adv_rejected_hoardings');
                $rejectedHoarding->id = $mAdvActiveHoarding->id;
                $rejectedHoarding->rejected_date = Carbon::now();
                $rejectedHoarding->save();
                $mAdvActiveHoarding->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;

            return responseMsgs(true, $msg, "", '050615', 01, responseTime(), 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050615", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Approve License Application List for Citzen
     * | @param Request $req
     * | Function - 16
     * | API - 16
     */
    public function listApproved(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);

            // $citizenId = authUser()->id;
            $citizenId = $req->auth['id'];
            $userId = $req->auth['user_type'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listApproved($citizenId, $userId);
            // $totalApplication = $applications->count();
            // remove_null($applications);
            // $data1['data'] = $applications;
            // $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $allApproveList = paginator($applications, $req);

            foreach ($allApproveList['data'] as $key => $list) {
                $current_date = Carbon::now()->format('Y-m-d');
                $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
                if ($current_date >= $notify_date) {
                    $allApproveList['data'][$key]['renew_option'] = '1';     // Renew option Show
                }
                if ($current_date < $notify_date) {
                    $allApproveList['data'][$key]['renew_option'] = '0';      // Renew option Not Show
                }
                if ($list['valid_upto'] < $current_date) {
                    $allApproveList['data']['renew_option'] = 'Expired';    // Renew Expired
                }
            }
            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;

            return responseMsgs(true, "Approved Application List", $allApproveList, "050616", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050616", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Reject License Application List for Citizen
     * | @param Request $req
     * | Function - 17
     * | API - 17
     */
    public function listRejected(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);

            // $citizenId = authUser()->id;
            $citizenId = $req->auth['id'];
            $mAdvRejectedHoarding = new AdvRejectedHoarding();
            $applications = $mAdvRejectedHoarding->listRejected($citizenId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);
            // $totalApplication = $applications->count();
            // remove_null($applications);
            // $data1['data'] = $applications;
            // $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }
            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;

            return responseMsgs(true, "Rejected Application List", $list, "050617", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050617", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Unpaid License Application List for Citzen
     * | @param Request $req
     * | Function - 18
     * | API - 18
     */
    public function listUnpaid(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);

            // $citizenId = authUser()->id;
            $citizenId = $req->auth['id'];
            $userId = $req->auth['user_type'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listUnpaid($citizenId, $userId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);
            // $totalApplication = $applications->count();
            // remove_null($applications);
            // $data1['data'] = $applications;
            // $data1['arrayCount'] =  $totalApplication;
            // if ($data1['arrayCount'] == 0) {
            //     $data1 = null;
            // }
            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;
            return responseMsgs(true, "Unpaid Application List", $list, "050618", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050618", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }



    /**
     * | Get Applied License Applications by Logged In JSK
     * | Function - 19
     * | API - 19
     */
    public function getJskApplications(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);
            $userId = $req->auth['id'];
            $mmAdvRejectedHoarding = new AdvActiveHoarding();
            $applications = $mmAdvRejectedHoarding->getJskApplications($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }
            // $endTime = microtime(true);
            // $executionTime = $endTime - $startTime;
            return responseMsgs(true, "Applied Applications", $data1, "050619", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050619", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Approve License Application List for JSK
     * | @param Request $req
     * | Function - 20
     * | API - 20
     */
    public function listJskApprovedApplication(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);
            $userId = $req->auth['id'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listJskApprovedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Approved Application List", $data1, "050620", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050620", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Reject License Application List for JSK
     * | @param Request $req
     * | Function - 21
     * | API - 21
     */
    public function listJskRejectedApplication(Request $req)
    {
        try {
            // Variable initialization
            // $startTime = microtime(true);
            $userId = $req->auth['id'];
            $mAdvRejectedHoarding = new AdvRejectedHoarding();
            $applications = $mAdvRejectedHoarding->listJskRejectedApplication($userId);
            $totalApplication = $applications->count();
            remove_null($applications);
            $data1['data'] = $applications;
            $data1['arrayCount'] =  $totalApplication;
            if ($data1['arrayCount'] == 0) {
                $data1 = null;
            }

            return responseMsgs(true, "Rejected Application List", $data1, "050621", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050621", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Generate Payment Order ID
     * | @param Request $req
     * | Function - 22
     * | API - 22
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
            // $startTime = microtime(true);
            $mAdvHoarding = AdvHoarding::find($req->id);
            $reqData = [
                "id" => $mAdvHoarding->id,
                'amount' => $mAdvHoarding->payment_amount,
                'workflowId' => $mAdvHoarding->workflow_id,
                'ulbId' => $mAdvHoarding->ulb_id,
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

            $data->name = $mAdvHoarding->applicant;
            $data->email = $mAdvHoarding->email;
            $data->contact = $mAdvHoarding->mobile_no;
            $data->type = "Hoarding";


            return responseMsgs(true, "Payment OrderId Generated Successfully !!!", $data, "050622", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050622", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * License (Hording) application Details For Payment
     * @param Request $req
     * @return void
     * | Function - 23
     * | API - 2
     */
    public function getApplicationDetailsForPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvHoarding = new AdvHoarding();
            if ($req->applicationId) {
                $data = $mAdvHoarding->getApplicationDetailsForPayment($req->applicationId);
            }

            if (!$data)
                throw new Exception("Application Not Found");

            $data['type'] = "Hoarding";

            return responseMsgs(true, 'Data Fetched',  $data, "050623", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050623", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Hoarding details for renew
     * | Function - 24
     * | API - 24
     */
    public function getHordingDetailsForRenew(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvHoarding = new AdvHoarding();
            $details = $mAdvHoarding->applicationDetailsForRenew($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found !!!");

            return responseMsgs(true, "Application Fetched !!!", remove_null($details), "050624", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050624", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Save Application For Hoarding Renewal
     * | Function - 25
     * | API - 25
     */
    public function renewalHording(RenewalHordingRequest $req)
    {
        try {
            // Variable initialization

            $mAdvActiveHoarding = new AdvActiveHoarding();
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
            $RenewNo = $mAdvActiveHoarding->renewalHording($req);       //<--------------- Model function to store 
            DB::commit();


            return responseMsgs(true, "Successfully Renewal the application !!", ['status' => true, 'ApplicationNo' => $RenewNo], "050625", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(true, $e->getMessage(), "", "050625", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Payment Via Cash For Hoarding
     * | Function - 26
     * | API - 26
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

            $mAdvHoarding = new AdvHoarding();
            $mAdvMarTransaction = new AdvMarTransaction();
            DB::beginTransaction();
            $data = $mAdvHoarding->paymentByCash($req);
            $appDetails = AdvHoarding::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cash");
            DB::commit();

            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050626", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050626", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050626", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Entry Cheque or DD for Hoarding Payment
     * | Function - 27
     * | API - 27
     */
    public function entryChequeDd(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|string',
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
            $wfId = AdvHoarding::find($req->applicationId)->workflow_id;
            $workflowId = ['workflowId' => $wfId];
            $req->request->add($workflowId);
            $transNo = $mAdvCheckDtl->entryChequeDd($req);

            return responseMsgs(true, "Check Entry Successfully !!", ['status' => true, 'TransactionNo' => $transNo], "050627", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050627", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Clear or Bouns Cheque for Hoarding 
     * | Function - 28
     * | API - 28
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
            $appDetails = AdvHoarding::find($req->applicationId);
            $mAdvMarTransaction->addTransaction($appDetails, $this->_moduleId, "Advertisement", "Cheque/DD");
            DB::commit();


            if ($req->status == '1' && $data['status'] == 1) {
                return responseMsgs(true, "Payment Successfully !!", ['status' => true, 'transactionNo' => $data['payment_id'], 'workflowId' => $appDetails->workflow_id], "050628", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
            } else {
                return responseMsgs(false, "Payment Rejected !!", '', "050628", "1.0", "", 'POST', $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050628", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Verify Single Application Approve or reject
     * | Function - 29
     * | API - 29
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
            $mAdvActiveHoarding = new AdvActiveHoarding();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = $req->auth['id'];
            $applicationId = $req->applicationId;

            $wfLevel = Config::get('constants.SELF-LABEL');
            // Derivative Assigments
            $appDetails = $mAdvActiveHoarding->getHoardingNo($applicationId);

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

            return responseMsgs(true, $req->docStatus . " Successfully", "", "050629", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "050629", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Send Application back to citizen
     * | Function - 30
     * | API - 30
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            // Variable initialization
            $redis = Redis::connection();
            $mAdvActiveHoarding = AdvActiveHoarding::find($req->applicationId);
            if ($mAdvActiveHoarding->doc_verify_status == 1)
                throw new Exception("All Documents Are Approved, So Application is Not BTC !!!");
            if ($mAdvActiveHoarding->doc_upload_status == 1)
                throw new Exception("No Any Document Rejected, So Application is Not BTC !!!");

            $workflowId = $mAdvActiveHoarding->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $mAdvActiveHoarding->current_role_id = $backId->wf_role_id;
            $mAdvActiveHoarding->parked = 1;
            $mAdvActiveHoarding->save();


            $metaReqs['moduleId'] = $this->_moduleId;
            $metaReqs['workflowId'] = $mAdvActiveHoarding->workflow_id;
            $metaReqs['refTableDotId'] = "adv_active_hoardings.id";
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);

            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '050630', '01', responseTime(), 'POST', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050630", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }



    /**
     * | Back To Citizen Inbox
     * | Function - 31
     * | API - 31
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

            $mAdvActiveHoarding = new AdvActiveHoarding();
            $btcList = $mAdvActiveHoarding->getHoardingList($ulbId)
                ->whereIn('adv_active_hoardings.current_role_id', $roleId)
                // ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('adv_active_hoardings.id');
            // ->get();

            if (trim($req->key))
                $btcList =  searchFilter($btcList, $req);
            $list = paginator($btcList, $req);

            return responseMsgs(true, "BTC Inbox List", $list, "050631", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",  "050631", 1.0, "271ms", "POST", "", "");
        }
    }


    /**
     * | Reupload Rejected Documents
     * | Function - 32
     * | API - 32
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
            $mAdvActivehoarding = new AdvActivehoarding();
            DB::beginTransaction();
            $appId = $mAdvActivehoarding->reuploadDocument($req);
            $this->checkFullUpload($appId);
            DB::commit();
            return responseMsgs(true, "Document Uploaded Successfully", "", "050632", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, "Document Not Uploaded", "", "050632", 1.0, "271ms", "POST", "", "");
        }
    }


    /**
     * | Hoarding Application list For Renew
     * | @param Request $req
     * | Function - 33
     * | API - 33
     */
    public function getRenewActiveApplications(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];
            $AdvHoarding = new AdvHoarding();
            $applications = $AdvHoarding->getRenewActiveApplications($citizenId, $userType);

            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);

            return responseMsgs(true, "Approved Application List", $list, "050633", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050633", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Expired Hoarding List
     * | Function - 34
     * | API - 34
     */
    public function listExpiredHording(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userId = $req->auth['user_type'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listExpiredHording($citizenId, $userId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);


            return responseMsgs(true, "Approved Application List", $list, "050634", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050634", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Archived Application By Id 
     * | Function - 35
     * | API - 35
     */
    public function archivedHording(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mAdvHoarding = AdvHoarding::find($req->applicationId);
            $mAdvHoarding->is_archived = 1;
            $mAdvHoarding->save();

            return responseMsgs(true, "Archived Application Successfully", "", "050635", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050635", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Hording Archived List for Citizen
     * | @param Request $req
     * | Function - 36
     * | API - 36
     */
    public function listHordingArchived(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userId = $req->auth['user_type'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listHordingArchived($citizenId, $userId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);

            return responseMsgs(true, "Archived Application List", $list, "050636", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050636", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Blacklist Application By Id 
     * | Function - 37
     * | API - 37
     */
    public function blacklistHording(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mmAdvHoarding = AdvHoarding::find($req->applicationId);
            $mmAdvHoarding->is_blacklist = 1;
            $mmAdvHoarding->save();

            return responseMsgs(true, "Blacklist Application Successfully", "", "050637", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050637", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Hording Archived List for Citizen
     * | @param Request $req
     * | Function - 38
     * | API - 38
     */
    public function listHordingBlacklist(Request $req)
    {
        try {
            // Variable initialization

            $citizenId = $req->auth['id'];
            $userId = $req->auth['user_type'];
            $mAdvHoarding = new AdvHoarding();
            $applications = $mAdvHoarding->listHordingArchived($citizenId, $userId);
            if (trim($req->key))
                $applications =  searchFilter($applications, $req);
            $list = paginator($applications, $req);
            return responseMsgs(true, "Blacklist Application List", $list, "050638", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050638", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }
    /**
     * | Agency Dashboard Graph
     * | Function - 39
     * | API - 39
     */
    public function agencyDashboardGraph(Request $req)
    {
        $mAdvHoarding = new AdvHoarding();
        $licenseYear = getFinancialYear(date('Y-m-d'));
        $licenseYearId = DB::table('ref_adv_paramstrings')->select('id')->where('string_parameter', $licenseYear)->first()->id;
        $agencyDashboard = $mAdvHoarding->agencyDashboardGraph($req->auth['id'], $licenseYearId);
        return responseMsgs(true, "Monthaly Hoarding Applied", $agencyDashboard, "050638", "1.0", responseTime(), "POST", $req->deviceId ?? "");
    }

    /* ============================================= */

    /**
     * | Get Application role details
     * | Function - 40
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
     * | Check if the Document is Fully Verified or Not (4.1)
     * | Function - 41
     */
    public function ifFullDocVerified($applicationId)
    {
        $mAdvActiveHoarding = new AdvActiveHoarding();
        $mWfActiveDocument = new WfActiveDocument();
        $mAdvActiveHoarding = $mAdvActiveHoarding->getHoardingNo($applicationId);                      // Get Application Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $mAdvActiveHoarding->workflow_id,
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
     * | Check full document upload or not
     * | Function - 42
     */
    public function checkFullUpload($applicationId)
    {
        $docCode = $this->_docCode;
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = $this->_moduleId;
        $totalRequireDocs = $mWfActiveDocument->totalNoOfDocs($docCode);
        $appDetails = AdvActiveHoarding::find($applicationId);
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
     * | Get Application Between Two Dates
     * | Function - 43
     * | API - 40
     */
    public function getApplicationBetweenDate(Request $req)
    {
        if (authUser()->ulb_id < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050640", 1.0, "271ms", "POST", "", "");
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
            $mAdvHoarding = new AdvHoarding();
            $approveList = $mAdvHoarding->approveListForReport();

            $approveList = $approveList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvActiveHoarding = new AdvActiveHoarding();
            $pendingList = $mAdvActiveHoarding->pendingListForReport();

            $pendingList = $pendingList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->whereBetween('application_date', [$req->dateFrom, $req->dateUpto]);

            $mAdvRejectedHoarding = new AdvRejectedHoarding();
            $rejectList = $mAdvRejectedHoarding->rejectListForReport();

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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050640", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050640", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | Get Application Financial Year Wise
     * | Function - 44
     * | API - 41
     */
    public function getApplicationFinancialYearWise(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050641", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        $validator = Validator::make($req->all(), [
            'applicationType' => 'required|in:New Apply,Renew',
            'perPage' => 'required|integer',
            'financialYear' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization

            $mAdvHoarding = new AdvHoarding();
            $approveList = $mAdvHoarding->approveListForReport();

            $approveList = $approveList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->where('license_year', $req->financialYear);

            $mAdvActiveHoarding = new AdvActiveHoarding();
            $pendingList = $mAdvActiveHoarding->pendingListForReport();

            $pendingList = $pendingList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)
                ->where('license_year', $req->financialYear);

            $mAdvRejectedHoarding = new AdvRejectedHoarding();
            $rejectList = $mAdvRejectedHoarding->rejectListForReport();

            $rejectList = $rejectList->where('application_type', $req->applicationType)->where('ulb_id', $ulbId)->where('license_year', $req->financialYear);

            $data = collect(array());
            $data = $approveList->union($pendingList)->union($rejectList);
            $data = $data->paginate($req->perPage);

            return responseMsgs(true, "Application Fetched Successfully", $data, "050641", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050641", 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * | COllection From New or Renew Application
     * | Function - 45
     * | API - 42
     */
    public function paymentCollection(Request $req)
    {
        if ($req->auth['ulb_id'] < 1)
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050642", 1.0, "271ms", "POST", "", "");
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

            $approveList = DB::table('adv_hoarding_renewals')
                ->select('id', 'application_no', 'application_date', 'application_type', DB::raw("'Approve' as application_status"), 'payment_amount', 'payment_date', 'payment_mode')->where('application_type', $req->applicationType)->where('payment_status', '1')->where('ulb_id', $ulbId)
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

            return responseMsgs(true, "Application Fetched Successfully", $data, "050642", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050642", 1.0, "271ms", "POST", "", "");
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
            if ($userType != "Advert-Agency")
                throw new Exception("You Are Not Advertisement Agency");

            // Variable initialization

            $citizenId = $req->auth['id'];
            // $citizenId = 68
            $licenseYear = getFinancialYear(date('Y-m-d'));
            $licenseYearId = DB::table('ref_adv_paramstrings')->select('id')->where('string_parameter', $licenseYear)->first()->id;
            $mAdvHoarding = new AdvHoarding();
            $agencyDashboard = $mAdvHoarding->agencyDashboard($citizenId, $licenseYearId);
            if (empty($agencyDashboard)) {
                throw new Exception("You Have Not Agency !!");
            } else {
                return responseMsgs(true, "Data Fetched !!!", $agencyDashboard, "050532", "1.0", responseTime(), "POST", $req->deviceId ?? "");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050532", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Check Agency Payment Complete or Not
     * | Function - 37
     */
    public function checkPaymentCompleteOrNot($email)
    {
        return DB::table('adv_agencies')->select('payment_status')->where('email', $email)->first('payment_status');
    }
}
