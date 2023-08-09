<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\CustomDetail;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveWaiver;
use App\Models\Property\PropProperty;
use App\Models\Waiver;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Property\SafDetailsTrait;
use App\Traits\Property\WaiverTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WaiverController extends Controller
{
    use WaiverTrait;
    use SafDetailsTrait;

    /**
     * | For apply waiver application
     */
    public function apply(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "isBillWaiver" => "required",
            "isOnePercentPenalty" => "required",
            "isRwhPenalty" => "required",
            "isLateAssessmentPenalty" => "required",
            "billAmount" => "nullable",
            "billWaiverAmount" => "nullable",
            "onePercentPenaltyAmount" => "nullable",
            "onePercentPenaltyWaiverAmount" => "nullable",
            "rwhAmount" => "nullable",
            "rwhWaiverAmount" => "nullable",
            "lateAssessmentPenaltyAmount" => "nullable",
            "lateAssessmentPenaltyWaiverAmount" => "nullable",
            "propertyId" => "nullable",
            "safId" => "nullable",
            "waiverDocument" => "required",
            "description" => "required",
        ]);

        if ($validation->fails()) {
            return validationError($validation);
        }

        try {
            $user = authUser($request);
            $mPropActiveWaiver = new PropActiveWaiver();
            $waiverParamId = Config::get('PropertyConstaint.WAIVER_PARAM_ID');
            $wfMasterId = Config::get('workflow-constants.PROPERTY_WAIVER_ID');
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $wfMasterId)
                ->where('ulb_id', $user->ulb_id)
                ->first();
            /**
                Request is hard coded
             */
            $idGeneration = new PrefixIdGenerator($waiverParamId, $user->ulb_id);
            $applicationNo = $idGeneration->generate();

            $request->merge([
                "userId"        => $user->id,
                "workflowId"    => $ulbWorkflowId->id,
                "currentRole"   => 3,
                "ulbId"         => $user->ulb_id,
                "applicationNo" => $applicationNo
            ]);

            $data = $mPropActiveWaiver->addWaiver($request);
            $this->saveDoc($request, $data);

            return responseMsgs(true, "Waiver Application Applied. Your Application No.", $data->application_no);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    public function saveDoc($request, $data)
    {
        $docUpload = new DocUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $relativePath = Config::get('PropertyConstaint.WAIVER_RELATIVE_PATH');
        $user = authUser($request);
        $refImageName = $request->docCode;
        $refImageName = $data->id . '-' . str_replace(' ', '_', $refImageName);
        $document = $request->waiverDocument;

        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $metaReqs['moduleId']     = Config::get('module-constants.PROPERTY_MODULE_ID');
        $metaReqs['activeId']     = $data->id;
        $metaReqs['workflowId']   = $data->workflow_id;
        $metaReqs['ulbId']        = $data->ulb_id;
        $metaReqs['document']     = $imageName;
        $metaReqs['relativePath'] = $relativePath;
        $metaReqs['docCode']      = $request->docCode;

        $metaReqs = new Request($metaReqs);
        $mWfActiveDocument->postDocuments($metaReqs, $user);
    }

    /**
     * | Post Next Level Application
     */
    // public function postNextLevel(Request $req)
    // {
    //     $wfLevels = Config::get('PropertyConstaint.CONCESSION-LABEL');
    //     $req->validate([
    //         'applicationId' => 'required|integer',
    //         'receiverRoleId' => 'nullable|integer',
    //         'action' => 'required|In:forward,backward',
    //     ]);
    //     try {
    //         $userId = authUser($req)->id;
    //         $track = new WorkflowTrack();
    //         $mWfWorkflows = new WfWorkflow();
    //         $mWfRoleMaps = new WfWorkflowrolemap();
    //         $mPropActiveWaiver = PropActiveWaiver::find($req->applicationId);
    //         $senderRoleId = $mPropActiveWaiver->current_role;
    //         $ulbWorkflowId = $mPropActiveWaiver->workflow_id;
    //         $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
    //         $roleMapsReqs = new Request([
    //             'workflowId' => $ulbWorkflowMaps->id,
    //             'roleId' => $senderRoleId
    //         ]);
    //         $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

    //         DB::beginTransaction();
    //         if ($req->action == 'forward') {
    //             $this->checkPostCondition($senderRoleId, $wfLevels, $mPropActiveWaiver);          // Check Post Next level condition
    //             $mPropActiveWaiver->current_role = $forwardBackwardIds->forward_role_id;
    //             // $mPropActiveWaiver->last_role_id =  $forwardBackwardIds->forward_role_id;         // Update Last Role Id
    //             $metaReqs['verificationStatus'] = 1;
    //             $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
    //         }

    //         $mPropActiveWaiver->save();

    //         $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
    //         $metaReqs['workflowId'] = $mPropActiveWaiver->workflow_id;
    //         $metaReqs['refTableDotId'] = 'prop_active_waivers.id';
    //         $metaReqs['refTableIdValue'] = $req->applicationId;
    //         $metaReqs['senderRoleId'] = $senderRoleId;
    //         $metaReqs['user_id'] = $userId;

    //         $req->request->add($metaReqs);
    //         $track->saveTrack($req);

    //         // Updation of Received Date
    //         $preWorkflowReq = [
    //             'workflowId' => $mPropActiveWaiver->workflow_id,
    //             'refTableDotId' => 'prop_active_mPropActiveWaivers.id',
    //             'refTableIdValue' => $req->applicationId,
    //             'receiverRoleId' => $senderRoleId
    //         ];
    //         $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
    //         $previousWorkflowTrack->update([
    //             'forward_date' => Carbon::now()->format('Y-m-d'),
    //             'forward_time' => Carbon::now()->format('H:i:s')
    //         ]);

    //         DB::commit();
    //         return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", '010708', '01', '', 'Post', '');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }

    /**
     * | Final Approval
     */
    public function approvalRejection(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            // Check if the Current User is Finisher or Not
            $mWfRoleUsermap = new WfRoleusermap();
            $mPropActiveWaiver = new PropActiveWaiver();
            $track = new WorkflowTrack();

            $activeWaiver = PropActiveWaiver::findorFail($req->applicationId);
            $userId = authUser($req)->id;
            // $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            // $refGetFinisher = collect(DB::select($getFinisherQuery))->first();

            $workflowId = $activeWaiver->workflow_id;
            $senderRoleId = $activeWaiver->current_role;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            // $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            // $roleId = $readRoleDtls->wf_role_id;

            // if ($refGetFinisher->role_id != $roleId) {
            //     return responseMsg(false, "Forbidden Access", "");
            // }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {

                $activeWaiver->is_approved = true;
                $activeWaiver->save();
                $msg =  "Application Successfully Approved !!";
                $metaReqs['verificationStatus'] = 1;
            }
            // Rejection
            if ($req->status == 0) {
                $activeWaiver->is_approved = false;
                $activeWaiver->save();
                $msg =  "Application Successfully Rejected !!";
                $metaReqs['verificationStatus'] = 0;
            }

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $activeWaiver->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_waivers.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;
            $metaReqs['trackDate'] = Carbon::now()->format('Y-m-d H:i:s');
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            // Updation of Received Date
            $preWorkflowReq = [
                'workflowId' => $activeWaiver->workflow_id,
                'refTableDotId' => 'prop_active_waivers.id',
                'refTableIdValue' => $req->applicationId,
                'receiverRoleId' => $senderRoleId
            ];
            // $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
            // $previousWorkflowTrack->update([
            //     'forward_date' => Carbon::now()->format('Y-m-d'),
            //     'forward_time' => Carbon::now()->format('H:i:s')
            // ]);
            // dd();
            DB::commit();
            return responseMsgs(true, $msg, "", "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Aprroved List
     */
    public function approvedApplication(Request $req)
    {
        try {
            $mPropActiveWaiver   = new PropActiveWaiver();
            $perPage = $req->perPage ?? 10;
            $approvedList = $mPropActiveWaiver->waiverList()
                ->where('is_approved', true)
                ->paginate($perPage);

            return responseMsgs(true, "Approved Application", $approvedList, "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |
     */
    public function applicationDetails(Request $req)
    {
        $validation = Validator::make($req->all(), [
            "applicationId" => "required|integer",
        ]);

        if ($validation->fails()) {
            return validationError($validation);
        }
        try {
            $forwardBackward = new WorkflowMap;
            $mCustomDetails = new CustomDetail();
            $mWorkflowTracks = new WorkflowTrack();
            $applicationDtl = PropActiveWaiver::find($req->applicationId);
            $propertyDetail = PropProperty::find($applicationDtl->property_id);
            $safDetail      = PropActiveSaf::find($applicationDtl->saf_id);

            if (!$applicationDtl)
                throw new Exception("Application Not Found for this id");

            // Data Array
            $propertyDetails = $this->generatePropertyDetails($propertyDetail);   // (Property Details) Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            $fullDetailsData['application_no'] = $applicationDtl->application_no;
            $fullDetailsData['apply_date'] = ($applicationDtl->created_at)->format('d-m-Y');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$propertyElement]);
            $waiverList = collect();

            switch ($applicationDtl) {
                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_one_percent_penalty && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;

                    $waiverDetails = $this->waiverDetail1($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "Is Bill Waiver", "Bill Amount", "Bill Waiver Amount",
                            "One Percent Penalty", "One Percent Penalty Amount", "One Percent Penalty Waiver Amount",
                            "RWH Penalty", "RWH Amount", "RWH Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_one_percent_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;

                    $waiverDetails = $this->waiverDetail2($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "Is Bill Waiver", "Bill Amount", "Bill Waiver Amount",
                            "One Percent Penalty", "One Percent Penalty Amount", "One Percent Penalty Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_one_percent_penalty && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;

                    $waiverDetails = $this->waiverDetail3($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "One Percent Penalty", "One Percent Penalty Amount", "One Percent Penalty Waiver Amount",
                            "RWH Penalty", "RWH Amount", "RWH Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;

                    $waiverDetails = $this->waiverDetail4($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "Is Bill Waiver", "Bill Amount", "Bill Waiver Amount",
                            "RWH Penalty", "RWH Amount", "RWH Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_bill_waiver):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;

                    $waiverDetails = $this->waiverDetail5($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "Is Bill Waiver", "Bill Amount", "Bill Waiver Amount",
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_one_percent_penalty):
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;

                    $waiverDetails = $this->waiverDetail6($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "One Percent Penalty", "One Percent Penalty Amount", "One Percent Penalty Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_rwh_penalty):
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;

                    $waiverDetails = $this->waiverDetail7($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "RWH Penalty", "RWH Amount", "RWH Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;

                case ($applicationDtl->is_lateassessment_penalty):
                    $waiverList['is_lateassessment_penalty'] = true;
                    $waiverList['lateassessment_penalty_amount'] = $applicationDtl->lateassessment_penalty_amount;
                    $waiverList['lateassessment_penalty_waiver_amount'] = $applicationDtl->lateassessment_penalty_waiver_amount;

                    $waiverDetails = $this->waiverDetail8($waiverList);
                    $waiverElement = [
                        'headerTitle' => 'Waiver Details',
                        'tableHead' => [
                            "Lateassessment Penalty", "Lateassessment Penalty Amount", "Lateassessment Penalty Waiver Amount"
                        ],
                        'tableData' => [$waiverDetails]
                    ];
                    break;
            }

            $waiverList = json_decode(json_encode($waiverList), true);       // Convert Std class to array

            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$waiverElement]);

            // Card Details
            $cardElement = $this->generateWaiverCardDtls($applicationDtl, $propertyDetail);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $levelComment = $mWorkflowTracks->getTracksByRefId('prop_active_waivers', $req->applicationId);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks('prop_active_waivers', $req->applicationId, $applicationDtl->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'PROPERTY-WAIVER';
            $metaReqs['wfRoleId'] = $applicationDtl->current_role;
            $metaReqs['workflowId'] = $applicationDtl->workflow_id;
            $metaReqs['lastRoleId'] = 11;
            $req->request->add($metaReqs);

            $forwardBackward = $forwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Application Details", $fullDetailsData, "", '010709', '01', responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | 
     */
    public function inbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mPropActiveWaiver   = new PropActiveWaiver();

            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $perPage = $req->perPage ?? 10;

            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                       // Model () to get Occupied Wards of Current User
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $waiverDtl = $mPropActiveWaiver->waiverList()                                         // Repository function to get SAF Details
                // ->where('prop_active_waivers.ulb_id', $ulbId)
                ->where('prop_active_waivers.is_approved', false)
                // ->where('prop_active_waivers.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('prop_active_waivers.id')
                ->paginate($perPage);

            return responseMsgs(true, "Data Fetched", remove_null($waiverDtl), "010103", "1.0", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     *  | Get uploaded documents
     */
    public function getUploadedDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveWaiver = new PropActiveWaiver();
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $waiverDetails = $mPropActiveWaiver::find($req->applicationId);
            if (!$waiverDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $waiverDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
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
            $mPropActiveWaiver = new PropActiveWaiver();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = authUser($req)->id;
            $applicationId = $req->applicationId;
            // $wfLevel = Config::get('PropertyConstaint.SAF-LABEL');
            // Derivative Assigments
            $waiverDtl = $mPropActiveWaiver::find($applicationId);
            $safReq = new Request([
                'userId' => $userId,
                'workflowId' => $waiverDtl->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($safReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;
            /**
                role id is hard coded
             */
            if ($senderRoleId != 3)                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            if (!$waiverDtl || collect($waiverDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

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
                $waiverDtl->doc_upload_status = 0;
                $waiverDtl->doc_verify_status = 0;
                $waiverDtl->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);

            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $waiverDtl->doc_verify_status = 1;
                $waiverDtl->save();
            }

            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     */
    public function ifFullDocVerified($applicationId)
    {
        $mPropActiveWaiver = new PropActiveWaiver();
        $mWfActiveDocument = new WfActiveDocument();
        $getWaiverDtls = $mPropActiveWaiver::find($applicationId);                      // Get Saf Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $getWaiverDtls->workflow_id,
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
     * | Approved Static Details
     */
    public function staticDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807',
        ]);
        try {
            $waiverDtl = PropActiveWaiver::find($req->applicationId);

            $bill = PropActiveWaiver::select(
                'id',
                'bill_amount as amount',
                'bill_waiver_amount as waiver_amount',
                DB::raw("'bill' as key"),
                DB::raw("'Demand' as col_name"),
            )
                ->where('is_bill_waiver', true)
                ->where('id', $req->applicationId);

            $onePercent = PropActiveWaiver::select(
                'id',
                'one_percent_penalty_amount as amount',
                'one_percent_penalty_waiver_amount as waiver_amount',
                DB::raw("'one_percent' as key"),
                DB::raw("'1% Penalty' as col_name"),
            )
                ->where('is_one_percent_penalty', true)
                ->where('id', $req->applicationId);

            $rwh = PropActiveWaiver::select(
                'id',
                'rwh_amount as amount',
                'rwh_waiver_amount as waiver_amount',
                DB::raw("'rwh' as key"),
                DB::raw("'RWH Penalty' as col_name"),
            )
                ->where('is_rwh_penalty', true)
                ->where('id', $req->applicationId);

            $lateAssessment = PropActiveWaiver::select(
                'id',
                'lateassessment_penalty_amount as amount',
                'lateassessment_penalty_waiver_amount as waiver_amount',
                DB::raw("'late_assessment' as key"),
                DB::raw("'Late Assessment Penalty' as col_name"),
            )
                ->where('is_lateassessment_penalty', true)
                ->where('id', $req->applicationId);

            $approvedList['waiverData'] = $bill
                ->union($onePercent)
                ->union($rwh)
                ->union($lateAssessment)
                ->get();
            $approvedList['applicationNo'] = $waiverDtl->application_no;


            return responseMsgs(true, "Approved Application", $approvedList, "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | 
     */
    public function finalWaivedAmount(Request $req)
    {
        $validation = Validator::make($req->all(), [
            "applicationId" => "required|integer",
        ]);

        if ($validation->fails()) {
            return validationError($validation);
        }
        try {
            $waiverDtls = PropActiveWaiver::find($req->applicationId);
            $waiverDtls->final_bill_waived                   = $req->bill;
            $waiverDtls->final_one_percent_penalty_waived    = $req->one_percent;
            $waiverDtls->final_rwh_penalty_waived            = $req->rwh;
            $waiverDtls->final_lateassessment_penalty_waived = $req->late_assessment;
            $waiverDtls->save();

            return responseMsgs(true, "", $waiverDtls, "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
