<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grievance\saveGrievanceReq;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\Models\Grievance\GrievanceActiveApplicantion;
use App\Models\Grievance\GrievanceApprovedApplicantion;
use App\Models\Grievance\GrievanceClosedApplicantion;
use App\Models\Grievance\GrievanceRejectedApplicantion;
use App\Models\Grievance\GrievanceReopenApplicantionDetail;
use App\Models\Grievance\GrievanceSolvedApplicantion;
use App\Models\ThirdParty\OtpRequest;
use App\Models\ThirdParty\RefRequiredDocument;
use App\Models\ThirdParty\WfActiveDocument;
use App\Models\Workflow\WfRoleusermap;
use App\Models\Workflow\WfWorkflow;
use App\Models\Workflow\WfWorkflowrolemap;
use App\Models\Workflow\WorkflowMap;
use App\Models\Workflow\WorkflowTrack;
use App\Traits\GrievanceTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Empty_;

/**
 * | Created by :
 * | Created at :
 * | Modified By : Sam Kerketta
 * | Modefied At : 19-07-2023
 * | Status : Open
 * | 
 * | Grievance Module Opreration and workflow 
 */

class GrievanceController extends Controller
{
    use GrievanceTrait;
    use Workflow;

    private $_moduleId;
    private $_workflowMstId;
    private $_imageName;
    private $_relativePath;
    private $_grievanceDocCode;
    private $_grievanceRoleLevel;
    private $_databaseName;
    private $_wfDatabase;
    private $_idGenParamIds;
    private $_userType;
    private $_departmentType;
    private $_applythrough;
    private $_wfRejectedDatabase;
    private $_condition;
    private $_solvedStatus;

    public function __construct()
    {
        $this->_moduleId            = Config::get('workflow-constants.GRIEVANCE_MODULE_ID');
        $this->_workflowMstId       = Config::get('workflow-constants.GRIEVANCE_WF_MASTER_ID');
        $this->_imageName           = Config::get('grievance-constants.REF_IMAGE_NAME');
        $this->_relativePath        = Config::get('grievance-constants.RELATIVE_PATH');
        $this->_grievanceDocCode    = Config::get('grievance-constants.DOC_CODE');
        $this->_grievanceRoleLevel  = Config::get('workflow-constants.GRIVANCE_ROLE_LEVEL');
        $this->_databaseName        = Config::get('grievance-constants.DB_NAME');
        $this->_wfDatabase          = Config::get('grievance-constants.WF_DATABASE');
        $this->_idGenParamIds       = Config::get('grievance-constants.ID_GEN_PARAM');
        $this->_userType            = Config::get('grievance-constants.REF_USER_TYPE');
        $this->_departmentType      = Config::get('grievance-constants.DEPARTMENT_LISTING');
        $this->_applythrough        = Config::get('grievance-constants.APPLY_THROUGH');
        $this->_wfRejectedDatabase  = Config::get('grievance-constants.WF_REJECTED_DATABASE');
        $this->_condition           = Config::get('grievance-constants.CONDITION');
        $this->_solvedStatus        = Config::get('grievance-constants.SOLVED_STATUS');
    }


    /**
     * | Request Otp 
        | Serial No : 01
        | Working
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobileNo' => 'required|numeric|digits:10',
        ]);

        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => $validator->errors(),
                'data' => [],
            ];
            return response()->json($response, 422);
        }

        try {
            $mOtpRequest        = new OtpRequest();
            $refIdGeneration    = new IdGeneration;
            $mobile             = $request->mobileNo;
            $otp                = $refIdGeneration->generateOtp();

            $mOtpRequest->saveOtp($mobile, $otp);
            $returnData = [
                "otp"       => $otp,
                "mobileNo"  => $mobile
            ];
            return responseMsgs(true, "OTP generated !", $returnData, "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Validate Otp
        | Serial No : 02
        | Working
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobileNo'  => 'required|numeric|digits:10',
            'otp'       => 'required|numeric|digits:6'
        ]);
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => $validator->errors(),
                "data" => []
            ];
            return response()->json($response, 422);
        }

        try {
            $mOtpRequest = new OtpRequest();
            $isValidOtp = $mOtpRequest->getOtpDetails($request->mobileNo, $request->otp);
            if ($isValidOtp) {
                $isValidOtp->delete();
                return responseMsgs(true, "OTP Verified!", [], "", "01", responseTime(), "POST", $request->deviceId);
            }
            return responseMsgs(false, "OTP not matched!", [], "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Save the grievance details in db
        | Serial No : 03
        | Working / But the validation is creating problem
        | Check the agency and the citizen apply difference
        | Return the whastapp message alse
     */
    public function registerGrievance(saveGrievanceReq $request)
    {
        try {
            $user                           = authUser($request) ?? null;
            $ulbId                          = $request->ulbId;
            $docUpload                      = new DocUpload;
            $mWorkflowTrack                 = new WorkflowTrack();
            $mWfWorkflow                    = new WfWorkflow();
            $mWfActiveDocument              = new WfActiveDocument();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $refModuleId                    = $this->_moduleId;
            $refWorkflow                    = $this->_workflowMstId;
            $refImageName                   = $this->_imageName;
            $refRelativePath                = $this->_relativePath;
            $confUserType                   = $this->_userType;
            $applyThrough                   = $this->_applythrough;

            # Get initiater and finisher
            $error = $this->checkParamForRegister($request, $user);
            if ($error) {
                return $error;
            }
            $ulbWorkflowId = $mWfWorkflow->getulbWorkflowId($refWorkflow, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to Water Workflow!");
            }
            $refInitiatorRoleId = $mWfWorkflow->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId  = $mWfWorkflow->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId     = DB::select($refFinisherRoleId);
            $initiatorRoleId    = DB::select($refInitiatorRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception("initiatorRoleId or finisherRoleId not found for respective Workflow!");
            }

            DB::beginTransaction();
            $applicationNo  = "GRE" . Str::random(10) . Str::random(2);          // Use the id generation service
            $document       = $request->document;
            if ($document) {
                $imageName  = $docUpload->upload($refImageName['GRIEVANCE_APPLY'], $document, $refRelativePath['1']);
            }
            $refRequest = [
                "workflowId"        => $ulbWorkflowId->id,
                "applicationNo"     => $applicationNo,
                "userId"            => $user->id ?? null,
                "userType"          => $user->user_type ?? null,
                "initiatorRoleId"   => collect($initiatorRoleId)->first()->role_id,
                "finisherRoleId"    => collect($finisherRoleId)->first()->role_id,
                "applyThrough"      => (collect($applyThrough)->flip())['ONLINE']
            ];

            # Save the grievance to the active table
            $applicationDetails = $mGrievanceActiveApplicantion->saveGrievanceDetails($request, $refRequest);   // Incomplete
            if ($document) {
                $docStatus = true;
                $metaReqs = [
                    'moduleId'      => $refModuleId,
                    'activeId'      => $applicationDetails['id'],
                    'workflowId'    => $ulbWorkflowId->id,
                    'ulbId'         => $ulbId,
                    'relativePath'  => $refRelativePath['1'],
                    'document'      => $imageName,
                    'docCode'       => $request->docCode,
                    'docCategory'   => $request->docCategory,
                ];
                # Document saving
                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);
                $mGrievanceActiveApplicantion->updateDocStatus($applicationDetails['id'], $docStatus);
            }

            # Save the current role for ajency case
            if (isset($user->user_type)) {
                if ($user->user_type != $confUserType['1']) {
                    $mGrievanceActiveApplicantion->updateCurrentRole($applicationDetails['id'], collect($initiatorRoleId)->first()->role_id);
                }
            }

            # Save data in track
            $metaReqs = new Request(
                [
                    'citizenId'         => null,
                    'moduleId'          => $this->_moduleId,
                    'workflowId'        => $ulbWorkflowId->id,
                    'refTableDotId'     => 'grievance_active_applicantions.id',                             // Static                              // Static
                    'refTableIdValue'   => $applicationDetails['id'],
                    'user_id'           => $user->id ?? null,
                    'ulb_id'            => $ulbId,
                    'senderRoleId'      => null,
                    'receiverRoleId'    => collect($initiatorRoleId)->first()->role_id,
                ]
            );
            $mWorkflowTrack->saveTrack($metaReqs);
            DB::commit();

            # Send Message behalf of registration
            $watsAppMessge = (Whatsapp_Send(
                "$request->mobileNo",
                "register_message",                     // Set at env or database and 
                [
                    "conten_type" => "text",
                    [
                        $request->applicantName,
                        "Grievance",                    // Static
                        $applicationNo,
                    ]
                ]
            ));
            $returnData = [
                "applicationNo" => $applicationNo,
                "applicationId" => $applicationDetails['id']
            ];
            return responseMsgs(true, "You'r Grievance is submited successfully!", $returnData, "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Check param for regestring grievance
        | Serial No : 0
        | Working
        | Add the comparision btw the citizen and Agency
     */
    public function checkParamForRegister($request, $user)
    {
        $confUserType   = $this->_userType;
        $confDocCode    = $this->_grievanceDocCode;

        # Check the document type
        if ($request->docCode != $confDocCode) {
            throw new Exception("Please provide proper Doc Code!");
        }

        # Check diff btw user and agency
        # Check if the user is appliying through proper login 
        if (isset($user->user_type)) {
            if ($user->user_type != $confUserType['1']) {
                $validated = Validator::make(
                    $request->all(),
                    ['applyThrough' => "required|integer",]
                );
                if ($validated->fails()) {
                    return validationError($validated);
                }

                if (!in_array($user->user_type, [$confUserType['4'], $confUserType['3']])) {
                    throw new Exception("You are not allowed to register Grievance!");
                }
            }
        }
    }


    /**
     * | Get appled applications
        | Serial No : 0
        | Working
        | Modification req
     */
    public function getAppliedGrievance(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationNo' => "required|",
                'mobileNo'      => "required|numeric|digits:10"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $listedGrievance = $mGrievanceActiveApplicantion->getActiveGrievance($request->applicationNo, $request->mobileNo)->first();
            if (!$listedGrievance) {
                return responseMsgs(false, "Data not found!", [], "", "01", responseTime(), "POST", $request->deviceId);
            }
            return responseMsgs(true, "List of grievance!", remove_null($listedGrievance), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return  responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get doc list to be upload
        | Serial No : 0
        | Working
        | Not used
     */
    public function getDocToUpload(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'required|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $applicationId = $request->applicationId;
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $refGrievance = $mGrievanceActiveApplicantion->getActiveGrievanceById($applicationId)->first();                      // Get Saf Details
            if (!$refGrievance) {
                throw new Exception("Application Not Found for this id");
            }
            $documentList = $this->getGrievanceDocLists($refGrievance);
            $grievanceTypeDocs['listDocs'] = collect($documentList)->map(function ($value, $key) use ($refGrievance) {
                return $this->filterDocument($value, $refGrievance)->first();
            });

            $totalDocLists = collect($grievanceTypeDocs);
            $totalDocLists['docUploadStatus'] = $refGrievance->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refGrievance->doc_verify_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get list of document
        | Serial No : 0
        | Working
        | Not used
     */
    public function getGrievanceDocLists($application)
    {
        $mRefReqDocs    = new RefRequiredDocument();
        $moduleId       = $this->_moduleId;
        $confDocCode    = $this->_grievanceDocCode;

        $type = [$confDocCode];
        return $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
    }

    /**
     * | Filter document for displaying 
        | Serial No : 0
        | Working
        | Not used
     */
    public function filterDocument($documentList, $refWaterApplication, $ownerId = null)
    {
        $mWfActiveDocument  = new WfActiveDocument();
        $applicationId      = $refWaterApplication->id;
        $workflowId         = $refWaterApplication->workflow_id;
        $moduleId           = $this->_moduleId;
        $uploadedDocs       = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);

        $explodeDocs = collect(explode('#', $documentList->requirements));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId, $documentList) {

            # var defining
            $document   = explode(',', $explodeDoc);
            $key        = array_shift($document);
            $label      = array_shift($document);
            $documents  = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId, $documentList) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    $path = $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode"  => $item,
                        "ownerId"       => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath"       => $fullDocPath ?? "",
                        "verifyStatus"  => $uploadedDoc->verify_status ?? "",
                        "remarks"       => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType']      = $key;
            $reqDoc['uploadedDoc']  = $documents->last();
            $reqDoc['docName']      = substr($label, 1, -1);

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                if (isset($uploadedDoc)) {
                    $path =  $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                }
                $arr = [
                    "documentCode"  => $doc,
                    "docVal"        => ucwords($strReplace),
                    "uploadedDoc"   => $fullDocPath ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks"       => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }


    /**
     * | Read Document Path
        | Serial No : 0
        | Working
     */
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }


    /**
     * | Get uploaded Docs for respective application
        | Serial No : 0
        | Working
     */
    public function listUploadedDocs(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWfActiveDocument              = new WfActiveDocument();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $moduleId                       = $this->_moduleId;

            $grievanceDetails = $mGrievanceActiveApplicantion->getActiveGrievanceById($request->applicationId)->first();
            if (!$grievanceDetails)
                throw new Exception("Application Not Found for respective application Id");

            $documents = $mWfActiveDocument->getDocsByAppNo($request->applicationId, $grievanceDetails->workflow_id, $moduleId);
            if (!$documents) {
                throw new Exception("Document not found!");
            }
            $returnData = collect($documents)->map(function ($value) {                          // Static
                $path = $this->readDocumentPath($value->ref_doc_path);
                $value->doc_path = !empty(trim($value->ref_doc_path)) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "Uploaded Documents", remove_null($returnData), "010102", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | Inbox details 
     * | Also display the concept of inner workflow
        | Serial No : 0
        | Working
     */
    public function inbox(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'perPage'       => 'nullable|integer',
                'workflowId'    => 'nullable|integer'  // Workflow master id 
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $pages                  = $request->perPage ?? 10;
            $user                   = authUser($request);
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $dataBase       = $this->getLevelsOfWf($request);
            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $inboxDetails = $this->getActiveApplicatioList($workflowIds, $ulbId, $dataBase)
                ->whereIn($dataBase . '.current_role', $roleId)
                ->whereIn($dataBase . '.ward_id', $occupiedWards)
                ->where($dataBase . '.is_escalate', false)
                ->where($dataBase . '.parked', false)
                ->orderByDesc($dataBase . '.id')
                ->paginate($pages);

            $isDataExist = collect($inboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Inbox List Details!", remove_null($inboxDetails), '', '02', '', 'Post', '');
        } catch (Exception $e) {
            responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Outbox details 
     * | Also display the concept of inner workflow
        | Serial No : 0
        | Working
     */
    public function outbox(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'perPage'       => 'nullable|integer',
                'workflowId'    => 'nullable|integer'  // Workflow master id
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user                   = authUser($request);
            $pages                  = $request->perPage ?? 10;
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $dataBase       = $this->getLevelsOfWf($request);
            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $outBoxDetails = $this->getActiveApplicatioList($workflowIds, $ulbId, $dataBase)
                ->whereNotIn($dataBase . '.current_role', $roleId)
                ->whereIn($dataBase . '.ward_id', $occupiedWards)
                ->orderByDesc($dataBase . '.id')
                ->paginate($pages);

            $isDataExist = collect($outBoxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Successfully listed consumer req inbox details!", remove_null($outBoxDetails), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the associated database according to 
        | Serial No : 0
        | Working
        | Check for multiple wf and its database
     */
    public function getLevelsOfWf($request)
    {
        $workflowId     = $request->workflowId ?? $request->header()->workflowId;
        $wfDatabase     = $this->_wfDatabase;
        $refDatabase    = collect($wfDatabase)->flip();
        if (!$workflowId) {
            throw new Exception("Please provide workflowId!");
        }

        # Get the database name according to wfId
        switch ($workflowId) {
            case ($wfDatabase['grievance_active_applicantions']):
                $dataBase = $refDatabase['34'];
                break;
            case ($wfDatabase['associated_grievance_active_applicantions']):
                $dataBase = $refDatabase['36'];
                break;
        }
        return $dataBase;
    }


    /**
     * | Verify the document in the workflow for the parent grievance workflow
        | Serial No : 0
        | Working
        | Not Used
     */
    public function verifyRejectDocs(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'id'            => 'required|digits_between:1,9223372036854775807',
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'docRemarks'    =>  $request->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
                'docStatus'     => 'required|in:Verified,Rejected'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            # Variable Assignments
            $mWfDocument                    = new WfActiveDocument();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $mWfRoleusermap                 = new WfRoleusermap();
            $wfDocId                        = $request->id;
            $applicationId                  = $request->applicationId;
            $user                           = authUser($request);
            $wfLevel                        = $this->_grievanceRoleLevel;

            # validating application existence
            $grievanceApplicationDtl = $mGrievanceActiveApplicantion->getActiveGrievanceById($applicationId)
                ->first();
            if (!$grievanceApplicationDtl || is_null($grievanceApplicationDtl)) {
                throw new Exception("Application Details Not Found");
            }
            if ($grievanceApplicationDtl->is_doc != true) {
                throw new Exception("Document for the respective Grievance is not uploaded!");
            }

            # validating roles
            $waterReq = new Request([
                'userId'        => $user->id,
                'workflowId'    => $grievanceApplicationDtl->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($waterReq);
            if (is_null($senderRoleDtls) || !$senderRoleDtls)
                throw new Exception("Role Not Available");

            # validating role for DA
            $senderRoleId = $senderRoleDtls->wf_role_id;
            if ($senderRoleId != $wfLevel['DA'])                                    // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            # validating if full documet is uploaded
            $ifFullDocVerified = $this->ifFullDocVerified($applicationId, $grievanceApplicationDtl->workflow_id);          // (Current Object Derivative Function 0.1)
            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($request->docStatus == "Verified") {
                $status = 1;
            }
            if ($request->docStatus == "Rejected") {
                # For Rejection Doc Upload Status and Verify Status will disabled 
                $status = 2;
                // $grievanceApplicationDtl->doc_upload_status = 0;
                $grievanceApplicationDtl->doc_verify_status = 0;
                $grievanceApplicationDtl->save();
            }
            $reqs = [
                'remarks'           => $request->docRemarks,
                'verify_status'     => $status,
                'action_taken_by'   => $user->id
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            if ($request->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId, $grievanceApplicationDtl->workflow_id);
            else {
                $ifFullDocVerifiedV1 = 0;                                           // Static                                      
            }
            if ($ifFullDocVerifiedV1 == 1) {                                        // If The Document Fully Verified Update Verify Status
                $mGrievanceActiveApplicantion->updateAppliVerifyStatus($applicationId, true);
            }
            DB::commit();
            return responseMsgs(true, $request->docStatus . " Successfully", [], "", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Validate if full documet is uploaded and verified
        | Serial No : 0
        | Working
        | Not Used
     */
    public function ifFullDocVerified($applicationId, $workflow_id)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $refReq = [
            'activeId'      => $applicationId,
            'workflowId'    => $workflow_id,
            'moduleId'      => $this->_moduleId
        ];

        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == true)
            return 0;
        else
            return 1;
    }

    /**
     * | Post next level to the parent workFlow
        | Serial No : 0
        | Under Con
        | Check the user role id is in the current role id 
        | If receiver role id is provided then check that the following role id is in the workflow
     */
    public function postNextLevel(Request $request)
    {
        $wfLevels = $this->_grievanceRoleLevel;
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId'     => 'required',
                // 'receiverRoleId'    => 'nullable',
                'action'            => 'required|In:forward,backward',
                'comment'           => $request->senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user                           = authUser($request);
            $mWfRoleMaps                    = new WfWorkflowrolemap();
            $current                        = Carbon::now();
            $wfLevels                       = $this->_grievanceRoleLevel;
            $databaseName                   = $this->_databaseName;
            $GrievanceActiveApplicantion    = GrievanceActiveApplicantion::find($request->applicationId);
            if (!$GrievanceActiveApplicantion) {
                throw new Exception("application details not found!");
            }

            # Derivative Assignments
            $senderRoleId = $GrievanceActiveApplicantion->current_role;
            $ulbWorkflowId = $GrievanceActiveApplicantion->workflow_id;
            $ulbWorkflowMaps = WfWorkflow::find($ulbWorkflowId);
            if (!$ulbWorkflowMaps) {
                throw new Exception("Workflow details not found!");
            }
            $roleMapsReqs = new Request([
                'workflowId'    => $ulbWorkflowMaps->id,
                'roleId'        => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);
            if (!$forwardBackwardIds) {
                throw new Exception("forward role or Backward role not found!");
            }

            DB::beginTransaction();
            if ($request->action == 'forward') {                                                            // Static
                $forwardBackwardIds = $this->checkPostCondition($senderRoleId, $wfLevels, $GrievanceActiveApplicantion);            // Check Post Next level condition
                // if ($GrievanceActiveApplicantion->current_role == $wfLevels['JE']) {
                //     $GrievanceActiveApplicantion->is_field_verified = true;
                // }
                $metaReqs['verificationStatus']             = 1;                                            // Static
                $metaReqs['receiverRoleId']                 = $forwardBackwardIds->forward_role_id;
                $GrievanceActiveApplicantion->current_role  = $forwardBackwardIds->forward_role_id;
                $GrievanceActiveApplicantion->last_role_id  = $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id

            }
            if ($request->action == 'backward') {
                $metaReqs['receiverRoleId']                 = $forwardBackwardIds->backward_role_id;                                                         // Static
                $GrievanceActiveApplicantion->current_role  = $forwardBackwardIds->backward_role_id;
            }
            $GrievanceActiveApplicantion->save();

            $metaReqs['moduleId']           = $this->_moduleId;
            $metaReqs['workflowId']         = $GrievanceActiveApplicantion->workflow_id;
            $metaReqs['refTableDotId']      = $databaseName['P_GRIEVANCE'] . ".id";
            $metaReqs['refTableIdValue']    = $request->applicationId;
            $metaReqs['senderRoleId']       = $senderRoleId;
            $metaReqs['user_id']            = $user->id;
            $metaReqs['ulb_id']             = $user->ulb_id ?? null;
            $metaReqs['trackDate']          = $current->format('Y-m-d H:i:s');
            $request->request->add($metaReqs);

            $waterTrack = new WorkflowTrack();
            $waterTrack->saveTrack($request);

            # Check in all the cases the data if entered in the track table 
            $preWorkflowReq = [
                'workflowId'        => $GrievanceActiveApplicantion->workflow_id,
                'refTableDotId'     => $databaseName['P_GRIEVANCE'] . ".id",
                'refTableIdValue'   => $request->applicationId,
                'receiverRoleId'    => $senderRoleId
            ];

            $previousWorkflowTrack = $waterTrack->getWfTrackByRefId($preWorkflowReq);
            if (!$previousWorkflowTrack) {
                throw new Exception("some error in the workflow track previous data not found in track!");
            }
            $previousWorkflowTrack->update([
                'forward_date' => $current->format('Y-m-d'),
                'forward_time' => $current->format('H:i:s')
            ]);
            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", [], "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Check the param for the case of forward to any level
        | Serial No : 0
        | Under con 
     */
    public function checkPostCondition()
    {
        
        $person = [
            "forward_role_id" => 4
        ];

        return (object)$person;
    }


    /**
     * | Get application details by applicton id 
     * | List details for the workflow display
        | Serial No : 0
        | Working 
        | But make the changes for the master data maping with key ids
        | And add the function for other workflow applications
     */
    public function getDetailsById(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'required',
                'workflowId'    => 'nullable|integer'  // workflow master id
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            # object assigning
            $wfDatabase                     = $this->_wfDatabase;
            $refDatabase                    = collect($wfDatabase)->flip();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();

            # application details
            $database = $this->getLevelsOfWf($request);
            $applicationDetails = $mGrievanceActiveApplicantion->getGrievanceFullDetails($request->applicationId, $database)
                ->leftJoin('wf_roles', 'wf_roles.id', '=', $database . '.current_role')
                ->first();
            if (!$applicationDetails) {
                throw new Exception("application detials Not found!");
            }

            # Devide the process of formating the applicton data into seprate parts according to workflow
            switch ($database) {
                case ($refDatabase['34']):
                    $returnValues = $this->parentGrievance($applicationDetails, $request);
                    break;

                    # Under Construction
                case ($refDatabase['36']):
                    $returnValues = $this->associatedGrievance($applicationDetails, $request);
                    break;
            }
            return responseMsgs(true, "listed Data!", $returnValues, "", "02", responseTime(), "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "02", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the data format for the parent Grievance Application details
        | Serial No : 0
        | Working
        | Recheck / Changes req
     */
    public function parentGrievance($applicationDetails, $request)
    {
        $mWorkflowMap                   = new WorkflowMap();
        $mWorkflowTracks                = new WorkflowTrack();

        # Basic details
        $aplictionList = [
            'application_no'    => collect($applicationDetails)['application_no'],
            'apply_date'        => collect($applicationDetails)['apply_date']
        ];

        # DataArray
        $basicDetails       = $this->getBasicDetails($applicationDetails);
        $grievanceDetails   = $this->getGrievanceDetails($applicationDetails);
        $firstView = [
            'headerTitle'   => 'Basic Details',
            'data'          => $basicDetails
        ];
        $secondView = [
            'headerTitle'   => 'Grievance Details',
            'data'          => $grievanceDetails
        ];
        $fullDetailsData['fullDetailsData']['dataArray'] = new collection([$firstView, $secondView]);

        # CardArray
        $cardDetails = $this->getCardDetails($applicationDetails);
        $cardData = [
            'headerTitle' => 'Water Connection',
            'data' => $cardDetails
        ];
        $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardData);

        # TableArray
        $ownerList[] = $this->getComplainantDetails($applicationDetails);
        $ownerView = [
            'headerTitle'   => 'Complainant Details',
            'tableHead'     => ["#", "Owner Name", "Aadhar", "Mobile No", "Gender", "Address", "Email"],
            'tableData'     => $ownerList
        ];
        $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerView]);

        # Level comment
        $mtableId                       = $applicationDetails->id;
        $mRefTable                      = $this->_databaseName;
        $levelComment['levelComment']   = $mWorkflowTracks->getTracksByRefId($mRefTable['P_GRIEVANCE'], $mtableId);

        # Role Details
        $data = json_decode(json_encode($applicationDetails), true);
        $metaReqs = [
            'customFor'     => 'Grievance',
            'wfRoleId'      => $data['current_role'],
            'workflowId'    => $data['workflow_id'],
            'lastRoleId'    => $data['last_role_id']
        ];
        $request->request->add($metaReqs);
        $forwardBackward = $mWorkflowMap->getRoleDetails($request);
        $roleDetails['roleDetails'] = collect($forwardBackward);
        # Timeline Data
        $timelineData['timelineData'] = collect($request);

        return array_merge($aplictionList, $fullDetailsData, $levelComment, $roleDetails, $timelineData);
    }


    /**
     * | Get the format for the basic details 
        | Serial No : 0
        | Working 
     */
    public function getBasicDetails($details)
    {
        $dateString     = $details->apply_date;
        $date           = Carbon::createFromFormat('Y-m-d', $dateString);
        $formattedDate  = $date->format('d-m-Y');
        return new Collection([
            ['displayString' => 'Ward No',              'key' => 'WardNo',              'value' => $details->ward_name ?? null],
            ['displayString' => 'Department',           'key' => 'Department',          'value' => $details->department ?? null],           // Change
            ['displayString' => 'Ulb Name',             'key' => 'UlbName',             'value' => $details->ulb_name ?? null],
            ['displayString' => 'Apply Date',           'key' => 'ApplyDate',           'value' => $formattedDate ?? null]
        ]);
    }

    /**
     * | Get Grievance details and formating
        | Serial No : 0
        | Working
     */
    public function getGrievanceDetails($details)
    {
        switch ($details->disability) {
            case ("true"):
                $disablity = "Yes";
                break;
            case ("false"):
                $disablity = "No";
                break;
        }
        return new Collection([
            ['displayString' => 'Disability',           'key' => 'Disability',          'value' => $disablity ?? null],
            ['displayString' => 'Grievance Head',       'key' => 'GrievanceHead',       'value' => $details->grievance_head ?? null],       // Change
            ['displayString' => 'Description',          'key' => 'Description',         'value' => $details->description ?? null],
            ['displayString' => 'District Id',          'key' => 'DistrictId',          'value' => $details->district_id ?? null],          // Change
            ['displayString' => 'Other Info',           'key' => 'OtherInfo',           'value' => $details->other_info ?? null]
        ]);
    }


    /**
     * | Get card details 
        | Serial No : 0
        | Working
     */
    public function getCardDetails($details)
    {
        $dateString     = $details->apply_date;
        $date           = Carbon::createFromFormat('Y-m-d', $dateString);
        $formattedDate  = $date->format('d-m-Y');
        return new Collection([
            ['displayString' => 'Ward No.',             'key' => 'WardNo.',             'value' => $details->ward_name],
            ['displayString' => 'Application No.',      'key' => 'ApplicationNo.',      'value' => $details->application_no],
            ['displayString' => 'Owner Name',           'key' => 'OwnerName',           'value' => $details->applicant_name],
            ['displayString' => 'Mobile No',            'key' => 'MobileNo',            'value' => $details->mobile_no],
            ['displayString' => 'Gender',               'key' => 'Gender',              'value' => $details->gender],
            ['displayString' => 'Apply-Date',           'key' => 'ApplyDate',           'value' => $formattedDate],
        ]);
    }

    /**
     * | Get owner detials and formating
        | Serial No : 0
        | Working
     */
    public function getComplainantDetails($details)
    {
        return [
            1,
            $details->applicant_name,
            $details->uid,
            $details->mobile_no,
            $details->gender,
            $details->address,
            $details->email
        ];
    }


    /**
     * | Get details for the special inbox
        | Serial No : 0
        | Working
     */
    public function specialInbox(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'perPage'       => 'nullable|integer',
                'workflowId'    => 'required' // Workflow master id
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $pages                  = $request->perPage ?? 10;
            $user                   = authUser($request);
            $userId                 = $user->id;
            $ulbId                  = $user->ulb_id;
            $mWfWorkflowRoleMaps    = new WfWorkflowrolemap();

            $dataBase       = $this->getLevelsOfWf($request);
            $occupiedWards  = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId         = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds    = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $specialInboxDetails = $this->getActiveApplicatioList($workflowIds, $ulbId, $dataBase)
                ->where($dataBase . '.is_escalate', 1)
                ->whereIn($dataBase . '.ward_id', $occupiedWards)
                ->where($dataBase . '.parked', false)
                ->orderByDesc($dataBase . '.id')
                ->paginate($pages);

            $isDataExist = collect($specialInboxDetails)->last();
            if (!$isDataExist || $isDataExist == 0) {
                throw new Exception('Data not Found!');
            }
            return responseMsgs(true, "Inbox List Details!", remove_null($specialInboxDetails), '', '02', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Final Approval of the application in parent workflow
        | Serial No : 0
        | Working
        | Recheck
     */
    public function finalApprovalRejection(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "applicationId" => "required",
                "status"        => "required|in:1,0",
                "comment"       => "required"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $ApprovedId                     = null;
            $user                           = authUser($request);
            $mWfRoleUsermap                 = new WfRoleusermap();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $grievanceDetials               = $mGrievanceActiveApplicantion->getActiveGrievanceById($request->applicationId)->first();
            if (!$grievanceDetials) {
                throw new Exception("Application detial not found!");
            }
            # Check the login user is finisher or not
            $workflowId = $grievanceDetials->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId'        => $user->id,
                'workflowId'    => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $grievanceDetials->finisher_id) {
                throw new Exception("You are not the Finisher!");
            }
            # Condition while the final Check
            $refRoles               = $this->_grievanceRoleLevel;
            $wfGrievanceParamId     = $this->_idGenParamIds;
            $refGrievanceDetails    = $this->preApprovalConditionCheck($grievanceDetials, $roleId, $request);

            DB::beginTransaction();
            # Approval of grievance application 
            if ($request->status == 1) {
                # Consumer no generation
                $grievanceApproveNo = "GRE-APR-" . Str::random(15);
                $ApprovedId = $this->finalApproval($request, $grievanceApproveNo, $grievanceDetials);
                $msg = "Application Successfully Approved !!";
            }
            # Rejection of grievance application
            if ($request->status == 0) {
                $this->finalRejectionOfAppication($request, $grievanceDetials);
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();
            return responseMsgs(true, $msg, $ApprovedId, "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Check if the grievance fullfill the criteria for final approval
        | Serial No : 0
        | Working
     */
    public function preApprovalConditionCheck($grievanceDetials, $roleId, $request)
    {
        $mWfWorkflow    = new WfWorkflow();
        $workflowId     = $this->_workflowMstId;
        $ulbWorkflowId  = $mWfWorkflow->getulbWorkflowId($workflowId, $grievanceDetials->ulb_id);
        switch ($grievanceDetials) {
            case ($grievanceDetials->in_inner_workflow == true):
                throw new Exception("Application is currently in Inner Workflow!");
                break;
            case ($grievanceDetials->current_role != $grievanceDetials->finisher_id):
                throw new Exception("Application is not under finisher!");
                break;
            case ($grievanceDetials->workflow_id != $ulbWorkflowId->id):
                throw new Exception("Application is not under respective workflow!");
                break;
        }
    }


    /**
     * |------------------- Final Approval of the Grievance application -------------------|
     * | @param request
     * | @param approveNo
     * | @param activeGrievanceDetials
        | Serial No : 0
        | Working
        | Uncomment the delete process
     */
    public function finalApproval($request, $approveNo, $activeGrievanceDetials)
    {
        $applicationId                  = $request->applicationId;
        $waterTrack                     = new WorkflowTrack();
        $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
        $mGrievanceSolvedApplicantion   = new GrievanceSolvedApplicantion();

        # Checking if grievance already exist 
        $checkSolvedGrie = $mGrievanceSolvedApplicantion->getSolvedApplication($applicationId)->first();
        if ($checkSolvedGrie) {
            throw new Exception("Access Denied ! Grievance Already Exist!");
        }

        # data formating for save the consumer details 
        $refDetails = [
            "reopenCount"   => $activeGrievanceDetials->reopen_count + 1 - 1,
            "approvalNo"    => $approveNo
        ];
        $grievanceId = $mGrievanceSolvedApplicantion->saveGrievanceDetials($activeGrievanceDetials, $refDetails);

        # dend record in the track table 
        $metaReqs = [
            'moduleId'          => $this->_moduleId,
            'workflowId'        => $activeGrievanceDetials->workflow_id,
            'refTableDotId'     => 'grievance_active_applicantions.id',                     // Static
            'refTableIdValue'   => $activeGrievanceDetials->id,
            'user_id'           => authUser($request)->id,
        ];
        $request->request->add($metaReqs);
        $waterTrack->saveTrack($request);

        # final delete
        // $mGrievanceActiveApplicantion->deleteRecord($applicationId);
        return $grievanceId;
    }

    /**
     * | Final rejection of the Grievance Application 
     * | Transfer the data to rejected table
        | Serial No : 0
        | Working
        | Uncomment the delete process
     */
    public function finalRejectionOfAppication($request, $activeGrievanceDetials)
    {
        $applicationId                  = $request->applicationId;
        $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
        $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();
        $idDataExist = $mGrievanceRejectedApplicantion->getGrievanceById($applicationId)->first();
        if ($idDataExist) {
            throw new Exception("Application Data already exist!");
        }

        # replication in the rejected application table 
        $grievanceRep = $activeGrievanceDetials->replicate();
        $grievanceRep->setTable('grievance_rejected_applicantions');
        $grievanceRep->id = $activeGrievanceDetials->id;
        $grievanceRep->remarks = $request->comment;
        $grievanceRep->save();

        # save record in track table 
        $waterTrack = new WorkflowTrack();
        $metaReqs = [
            'moduleId'          => $this->_moduleId,
            'workflowId'        => $activeGrievanceDetials->workflow_id,
            'refTableDotId'     => 'grievance_active_applicantions.id',
            'refTableIdValue'   => $activeGrievanceDetials->id,
            'user_id'           => authUser($request)->id
        ];
        $request->request->add($metaReqs);
        $waterTrack->saveTrack($request);

        # final delete 
        // $mGrievanceActiveApplicantion->deleteRecord($applicationId);
    }


    /**
     * | List Grievance application for the agency dashbord
        | Serial No : 
        | Under Con
        | Add pagination 
     */
    public function getGrievanceForAgency(Request $request)
    {
        try {
            $msg = "listed Grievances!";
            $workflowMstId = $this->_workflowMstId;
            $moduleId = $this->_moduleId;
            $user = authUser($request);
            if (!$user) {
                throw new Exception("User details not found!");
            }
            $perPage = $request->perPage ?? 10;
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $listedetails = $mGrievanceActiveApplicantion->getGriavanceDetails($moduleId)
                ->selectRaw(
                    DB::raw("'$workflowMstId' as ref_workflow_id")
                )
                ->whereNull('current_role')
                ->get();
            if (!collect($listedetails)->first()) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($listedetails), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Verify the Application to send it in workflow
        | Serial No : 0
        | Under Con
     */
    public function sendApplicationToWf(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "applicationId" => "required",
                "status" => "required|in:1,0"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user           = authUser($request);
            $userId         = $user->id;
            $ulbId          = $user->ulb_id;
            $now            = Carbon::now();
            $confWorkflowId = $this->_workflowMstId;
            $mWfWorkflow    = new WfWorkflow();
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();

            $workflowDetails = $mWfWorkflow->getulbWorkflowId($confWorkflowId, $ulbId);
            if (!$workflowDetails) {
                throw new Exception("Respective Ulb is not maped to Water Workflow!");
            }
            $request->merge([
                'workflowId' => $workflowDetails->id
            ]);
            $roleDetails = $this->getRole($request);
            if (!collect($roleDetails)->first()) {
                throw new Exception("user Role details not found!");
            }
            $grievanceDetails = $this->checkParamToSendToWf($roleDetails, $request);

            DB::beginTransaction();
            switch ($request->status) {
                case ("1"):
                    # Send application to the workflow
                    $refInitiatorRoleId = $mWfWorkflow->getInitiatorId($workflowDetails->id);
                    $initiatorRoleId    = DB::select($refInitiatorRoleId);
                    if (!$initiatorRoleId) {
                        throw new Exception("initiatorRoleId not found for respective Workflow!");
                    }
                    $grievanceDetails->update([
                        "current_role"          => collect($initiatorRoleId)->first()->role_id,
                        "agency_approved_by"    => $userId,
                        "agency_approve_date"   => $now
                    ]);
                    $msg = "Application approved for workflow!";
                    break;

                case ("0"):
                    # Send data to the rejected table
                    $validated = Validator::make(
                        $request->all(),
                        [
                            "remarks" => "required",
                        ]
                    );
                    if ($validated->fails())
                        return validationError($validated);

                    $grievanceRep = $grievanceDetails->replicate();
                    $grievanceRep->setTable('grievance_rejected_applicantions');
                    $grievanceRep->id = $grievanceDetails->id;
                    $grievanceRep->agency_rejected_by = $userId;
                    $grievanceRep->agency_rejected_date = $now;
                    $grievanceRep->remarks = $request->remarks;
                    $grievanceRep->save();

                    # Final delete 
                    $mGrievanceActiveApplicantion->deleteRecord($request->applicationId);
                    $msg = "application rejected!";
                    break;
            }
            DB::commit();
            return responseMsgs(true, $msg, [], "", "02", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Check param to send the application to the parent workflow
        | Serial No :
        | Under Con
     */
    public function checkParamToSendToWf($roleDetails, $request)
    {
        $userRole = $roleDetails['wf_role_id'];
        $applicationId = $request->applicationId;
        $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
        $refRole = $this->_grievanceRoleLevel;
        if (!in_array($userRole, [$refRole['JSK'], $refRole['TC']])) {
            throw new Exception("You are not allowed to operate!");
        }

        $grievanceDetails = $mGrievanceActiveApplicantion->getActiveGrievanceById($applicationId)
            ->whereNull('current_role')
            ->first();
        if (!$grievanceDetails) {
            throw new Exception("Grievance details not found for $applicationId!");
        }
        return $grievanceDetails;
    }


    /**
     * | Get Active grievance details for view according to id
        | Serial No : 0  
        | Under Con
     */
    public function getGrievanceById(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "applicationId" => "required",
                "workflowId" => "required|integer"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $moduleId = $this->_moduleId;
            $dataBase = $this->getLevelsOfWf($request);
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $applicationDetails = $mGrievanceActiveApplicantion->getGrievanceFullDetails($request->applicationId, $dataBase)
                ->leftjoin('wf_active_documents', 'wf_active_documents.active_id', $dataBase . '.id')
                ->whereColumn('wf_active_documents.ulb_id', $dataBase . '.ulb_id')
                ->where('wf_active_documents.module_id', $moduleId)
                ->whereColumn('wf_active_documents.workflow_id', $dataBase . '.workflow_id')
                ->where('wf_active_documents.status', 1)
                ->selectRaw(DB::raw("CONCAT('" . config('app.url') . "', '/', wf_active_documents.relative_path, '/', wf_active_documents.document) as full_url"))
                ->first();
            if (!$applicationDetails) {
                throw new Exception("application detials Not found!");
            }
            return responseMsgs(true, "Application deails!", remove_null($applicationDetails), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the rejected Grievance list for the agency
        | Serial No : 
        | Under Con
     */
    public function rejectedGrievanceByAgency(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "perPage" => "nullable|integer",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user = authUser($request);
            if (!$user) {
                throw new Exception("User details not found!");
            }
            $workflowMstId  = $this->_workflowMstId;
            $moduleId       = $this->_moduleId;
            $perPage        = $request->perPage ?? 10;
            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();
            $applicationDetails = $mGrievanceRejectedApplicantion->rejectedGrievanceFullDetails($moduleId)
                ->selectRaw(
                    DB::raw("'$workflowMstId' as ref_workflow_id")
                )
                ->whereNull('current_role')
                ->where('grievance_rejected_applicantions.agency_rejected_by', $user->id)
                ->get();
            if (!$applicationDetails) {
                throw new Exception("Application detials Not found!");
            }
            return responseMsgs(true, "Application deails!", remove_null($applicationDetails), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the rejected table of wf 
        | Serial No : 
        | Not used
     */
    public function getRejectedLevelsOfWf($request)
    {
        $workflowId     = $request->workflowId ?? $request->header()->workflowId;
        $wfRejectedDatabase = $this->_wfRejectedDatabase;
        $refDatabase    = collect($wfRejectedDatabase)->flip();
        if (!$workflowId) {
            throw new Exception("Please provide workflowId!");
        }

        # Get the database name according to wfId
        switch ($workflowId) {
            case ($wfRejectedDatabase['grievance_rejected_applicantions']):
                $dataBase = $refDatabase['34'];
                break;
            case ($wfRejectedDatabase['associated_grievance_rejected_applicantions']):
                $dataBase = $refDatabase['36'];
                break;
        }
        return $dataBase;
    }


    /**
     * | Search the Grievance details for Agency
        | Serila No :
        | Working
        | Add the pagination
     */
    public function searchGrievanceForAgency(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required|in:mobileNo,applicationNo',
                'parameter' => 'required',
                'condition' => 'required|in:0,1',
                'perPage'   => 'nullable|integer'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $isActive = $request->condition;
            $msg = "List of Grievance!";
            $perPage = $request->perPage ?? 10;
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();

            switch ($request->filterBy) {
                case ("mobileNo"):
                    if ($request->condition == 1) {
                        $returnData = $mGrievanceActiveApplicantion->searchActiveGrievance()
                            ->selectRaw(DB::raw("'$isActive' as active_status"))
                            ->where('grievance_active_applicantions.mobile_no', 'LIKE', '%' . $request->parameter . '%')
                            ->get();
                    } else {
                        $returnData = $mGrievanceRejectedApplicantion->searchRejectedGrievance()
                            ->selectRaw(DB::raw("'$isActive' as active_status"))
                            ->where('grievance_rejected_applicantions.mobile_no', 'LIKE', '%' . $request->parameter . '%')
                            ->get();
                    }
                    break;

                case ("applicationNo"):
                    if ($request->condition == 1) {
                        $returnData = $mGrievanceActiveApplicantion->searchActiveGrievance()
                            ->selectRaw(DB::raw("'$isActive' as active_status"))
                            ->where('grievance_active_applicantions.application_no', 'LIKE', '%' . $request->parameter . '%')
                            ->get();
                    } else {
                        $returnData = $mGrievanceRejectedApplicantion->searchRejectedGrievance()
                            ->selectRaw(DB::raw("'$isActive' as active_status"))
                            ->where('grievance_rejected_applicantions.application_no', 'LIKE', '%' . $request->parameter . '%')
                            ->get();
                    }
                    break;
            }
            if (empty($returnData->toArray())) {
                $msg = "Data Not Found!";
            }
            return responseMsgs(true, $msg, remove_null($returnData), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Search the Grievance using the mobile no hence for whatsapp 
        | Serial No :
        | Under Con
     */
    public function getGrievanceByMobileNo(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'mobileNo'  => 'required|numeric|digits:10',
                'perPage'   => 'nullable|integer'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {

            # Check the param for Secret key
            // $salt = env('SALT_VALUE');
            // $combinedData = $request->mobileNo . $request->id . $request->condition . $salt;
            // $hashedFile = hash('sha256', $combinedData);

            // if ($request->secretKey != $hashedFile) {
            //     throw new Exception("You are not Autherised to proceed!");
            // }

            $msg = "Listed grievance!";
            $confCondition = $this->_condition;
            $activeCondition = $confCondition['ACTIVE'];
            $rejecetdCondition = $confCondition['REJECTED'];
            $perPage = $request->perPage ?? 10;
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();

            $approvedData = $mGrievanceActiveApplicantion->searchActiveGrievance()
                ->selectRaw(DB::raw("'$activeCondition' as active_status"))
                ->where('grievance_active_applicantions.mobile_no', $request->mobileNo)
                ->limit($perPage)
                ->get();
            # Listing the rejected grievance
            // $rejectedData = $mGrievanceRejectedApplicantion->searchRejectedGrievance()
            //     ->selectRaw(DB::raw("'$rejecetdCondition' as active_status"))
            //     ->where('grievance_rejected_applicantions.mobile_no', $request->mobileNo)
            //     ->limit($perPage)
            //     ->get();
            $returnData = $approvedData; // ->merge($rejectedData);
            return responseMsgs(true, $msg, remove_null($returnData), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the full details of the Grievance
        | Serial No : 
        | Under Con
     */
    public function viewGrievanceDetails(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'id'        => 'required|',
                // 'mobileNo'  => 'required|',
                'condition' => 'required|in:1,0',
                // 'secretKey' => 'required|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {

            # Check the param for Secret key
            // $salt = env('SALT_VALUE');
            // $combinedData = $request->mobileNo . $request->id . $request->condition . $salt;
            // $hashedFile = hash('sha256', $combinedData);

            // if ($request->secretKey != $hashedFile) {
            //     throw new Exception("You are not Autherised to proceed!");
            // }

            $msg = "Data of grievance!";
            $moduleId = $this->_moduleId;
            $condition = $request->condition;
            $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();
            switch ($request->condition) {
                case (1):
                    $returnData = $mGrievanceActiveApplicantion->getGriavanceDetails($moduleId)
                        ->selectRaw(
                            DB::raw("'$condition' as active_status"),
                            // DB::raw("(SELECT wf_masters.id FROM wf_masters 
                            //             JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                            //             WHERE wf_workflows.id = grievance_active_applicantions.workflow_id) as workflow_mstr_id"),
                        )
                        ->where('grievance_active_applicantions.id', $request->id)
                        ->first();
                    break;

                case (0):
                    $returnData = $mGrievanceRejectedApplicantion->rejectedGrievanceFullDetails($moduleId)
                        ->selectRaw(DB::raw("'$condition' as active_status"))
                        ->where('grievance_rejected_applicantions.id', $request->id)
                        ->first();
                    break;
            }
            if (!$returnData) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($returnData), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }



    /**
     * | Send the application form parent workflow to the asociated workflow
     * | According to the respective role ie. wf associated to the role
        | Serial No : 
        | Under Con
     */
    public function postAssociatedWf(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId'     => 'required',
                'ulbWorkflowId'     => 'required|integer', // ulb WorkflowId
                'comment'           => 'required',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user                           = authUser($request);
            $confDatabase                   = $this->_wfDatabase;
            $current                        = Carbon::now();
            $mWorkflowTrack                 = new WorkflowTrack();
            $mWfWorkflow                    = new WfWorkflow();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $wfDatabaseDetial               = $this->checkRoleWorkflow($request);
            $applicationDetails             = $mGrievanceActiveApplicantion->getGrievanceFullDetails($request->applicationId, $wfDatabaseDetial['databaseType'])->first();
            $wfApplicationDetails           = $this->checkApplication($request, $applicationDetails);

            # Get initater and finisher
            $refUlbWorkflowId       = $wfApplicationDetails['roleDetails']['associated_workflow_id'];
            $refInitiatorRoleId     = $mWfWorkflow->getInitiatorId($refUlbWorkflowId);
            $refFinisherRoleId      = $mWfWorkflow->getFinisherId($refUlbWorkflowId);
            $finisherRoleId         = DB::select($refFinisherRoleId);
            $initiatorRoleId        = DB::select($refInitiatorRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception("initiatorRoleId or finisherRoleId not found for respective Workflow!");
            }

            $refDbName              = collect($confDatabase)->flip();
            $workflowMasterId       = $wfDatabaseDetial['workflowMasterId'];
            $associatedWfmasteId    = $this->getWorkflowMstId($refUlbWorkflowId)->first();
            $associatedDatabase     = $refDbName[$associatedWfmasteId->id];

            $refMetaReq = [
                "initiatorRoleId"   => collect($initiatorRoleId)->first()->role_id,
                "finisherRoleId"    => collect($finisherRoleId)->first()->role_id,
                "workflowId"        => $refUlbWorkflowId,
                "userId"            => $user->id,
                "senderRoleId"      => $wfApplicationDetails['roleDetails']['wf_role_id']
            ];

            DB::beginTransaction();

            # Data base replicate
            $mGrievanceActiveApplicantion->saveGrievanceInAssociatedWf($applicationDetails, $associatedDatabase, $refMetaReq);
            $mGrievanceActiveApplicantion->updateParentAppForInnerWf($request, $wfDatabaseDetial, $refUlbWorkflowId, $refMetaReq);
            # Save data in track
            $metaReqs = [
                'moduleId'          => $this->_moduleId,
                'workflowId'        => $applicationDetails->workflow_id,
                'refTableDotId'     => $wfDatabaseDetial['databaseType'] . '.id',
                'refTableIdValue'   => $applicationDetails->id,
                'user_id'           => $user->id,
                'senderRoleId'      => $wfApplicationDetails['roleDetails']['wf_role_id'],
                'ulb_id'            => $user->ulb_id ?? null,
                'trackDate'         => $current->format('Y-m-d H:i:s')
            ];
            $request->request->add($metaReqs);
            $mWorkflowTrack->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "Applied Grievance is Poted To inner Wf!", [], "", "02", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Check the role, workfow and application details details
        | Serial No : 
        | Under Con
     */
    public function checkRoleWorkflow($request)
    {
        $moduleId = $this->_moduleId;
        # Check the role details for the workflow
        $workflowMasterId = $this->getWorkflowMstId($request->ulbWorkflowId)->first();
        if (!$workflowMasterId) {
            throw new Exception("Workflow master data not found!");
        }
        $workflowExist = $this->getWorkflowByModule($workflowMasterId->id, $moduleId);
        if (!$workflowExist) {
            throw new Exception("Workflow according to module not found!");
        }
        $refRequest = new Request([
            "workflowId" => $workflowMasterId->id
        ]);
        $databaseType = $this->getLevelsOfWf($refRequest);
        return [
            "databaseType"      => $databaseType,
            "workflowMasterId"  => $workflowMasterId->id
        ];
    }


    /**
     * | Check the Application detial in diff workflow
        | Serial No :
        | Under Con
     */
    public function checkApplication($request, $applicationDetails)
    {
        if (!$applicationDetails) {
            throw new Exception("Application details not found!");
        }
        if ($applicationDetails->workflow_id != $request->ulbWorkflowId) {
            throw new Exception("application workflow don't match with provided workflow id!");
        }
        if ($applicationDetails->parked == true || $applicationDetails->in_inner_workflow == true) {
            throw new Exception("application is under inner workflow or is parked!");
        }
        # Get Role details 
        $request->merge([
            "workflowId" => $applicationDetails->workflow_id
        ]);
        $roleDetails = $this->getRole($request);
        if (!collect($roleDetails)->first()) {
            throw new Exception("Respective user dont have any role in the workflow!");
        }
        if ($roleDetails['wf_role_id'] != $applicationDetails->current_role) {
            throw new Exception("Application is not under logedIn user!");
        }
        if ($roleDetails['post_inner_workflow'] != true) {
            throw new Exception("You are not allowed to post in inner workflow!");
        }
        return [
            "roleDetails" => $roleDetails
        ];
    }


    /**
     * | List the application those are approved in the workflow
        | Serial No :
        | Working
     */
    public function getWfApprovedGrievances(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'perPage'     => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $condition  = 1;
            $msg = "List of wf approved Grievance!";
            $perPage = $request->perPage ?? 10;
            $mGrievanceSolvedApplicantion = new GrievanceSolvedApplicantion();
            $solvedGrievance = $mGrievanceSolvedApplicantion->getWfSolvedGrievance()
                ->selectRaw(DB::raw("'$condition' as active_status"),)
                ->limit($perPage)
                ->get();
            if (!collect($solvedGrievance)->first()) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($solvedGrievance), '', "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Get the list of rejected Grievance 
        | Serial No :
        | Under Con
     */
    public function getWfRejectedGrievances(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'perPage'     => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $condition  = 0;
            $msg        = "List of wf rejected Grievance!";
            $perPage    = $request->perPage ?? 10;

            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();
            $rejectedGrievance = $mGrievanceRejectedApplicantion->searchRejectedGrievance()
                ->selectRaw(DB::raw("'$condition' as active_status"),)
                ->limit($perPage)
                ->get();
            if (!collect($rejectedGrievance)->first()) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($rejectedGrievance), '', "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Get the grievance detials according to application id and condition
        | Serial No : 
        | Under con
        | Not tested
     */
    public function viewGrievanceFullDetails(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'id'        => 'required|',
                'condition' => 'required|in:1,0',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $msg        = "Data of grievance!";
            $moduleId   = $this->_moduleId;
            $condition  = $request->condition;
            $mGrievanceSolvedApplicantion   = new GrievanceSolvedApplicantion();
            $mGrievanceRejectedApplicantion = new GrievanceRejectedApplicantion();
            switch ($request->condition) {
                case (1):
                    $returnData = $mGrievanceSolvedApplicantion->getSolvedGriavanceDetails($moduleId)
                        ->selectRaw(
                            DB::raw("'$condition' as active_status"),
                        )
                        ->where('grievance_solved_applicantions.id', $request->id)
                        ->first();
                    break;

                case (0):
                    $returnData = $mGrievanceRejectedApplicantion->rejectedGrievanceFullDetails($moduleId)
                        ->selectRaw(
                            DB::raw("'$condition' as active_status")
                        )
                        ->where('grievance_rejected_applicantions.id', $request->id)
                        ->first();
                    break;
            }
            if (!$returnData) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($returnData), "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Agency final Close and reopen the process
        | Serial No :
        | Working
        | Check condition for Closer of Grievance
     */
    public function agencyFinalCloser(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'id'        => 'required|',
                'remarks'   => 'required',
                'rank'      => "nullable|integer|in:1,2,3,4,5,6,7,8,9,10"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $status                         = $this->_solvedStatus;
            $solvedApplicationId            = $request->id;
            $user                           = authUser($request);
            $mGrievanceSolvedApplicantion   = new GrievanceSolvedApplicantion();
            $mGrievanceClosedApplicantion   = new GrievanceClosedApplicantion();
            $solvedGrievanceDetails         = $mGrievanceSolvedApplicantion->getSolvedGrievance($solvedApplicationId)->where('status', 1)->first();
            if (!$solvedGrievanceDetails) {
                throw new Exception("Application detial not found!");
            }
            $request->merge([
                'workflowId'    => $solvedGrievanceDetails->workflow_id,
                'userId'        => $user->id
            ]);
            $roleDetails = $this->getRole($request);
            if (!$roleDetails) {
                throw new Exception("Role details not found!");
            }
            $this->checkParamForAgncyCloser($request, $solvedGrievanceDetails, $roleDetails);
            DB::beginTransaction();
            # Save the Solved application detial in the Closer database and update the solved application status '2' to make it closed
            $mGrievanceClosedApplicantion->saveClosedGrievance($solvedGrievanceDetails, $request);
            $mGrievanceSolvedApplicantion->updateStatus($solvedApplicationId, $status['CLOSED']);
            DB::commit();
            return responseMsgs(true, "Grievance Closed successfully!", [], "", "02", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Check param for final closer of grievance from agrncy
        | Serial No :
        | Under Con
     */
    public function checkParamForAgncyCloser($request, $solvedGrievanceDetails, $roleDetails)
    {
        $mGrievanceClosedApplicantion = new GrievanceClosedApplicantion();
        if ($solvedGrievanceDetails->current_role != $solvedGrievanceDetails->finisher_id) {
            throw new Exception("Process was not completed by the finisher!");
        };
        if ($solvedGrievanceDetails->in_inner_workflow == true) {
            throw new Exception("Error.. application is under inner workflow!");
        }

        $isClosedData = $mGrievanceClosedApplicantion->getClosedGrievnaceByRefId($solvedGrievanceDetails->id)->where('status', 1)->first();
        if ($isClosedData) {
            throw new Exception("Grievance Already Closed!");
        }
    }

    /**
     * | Grievance reopen process from and the agency
        | Serial No : 0 
        | Under Con
        | check the param for reopen
     */
    public function grievanceReopen(Request $request)
    {
        $validated  = Validator::make(
            $request->all(),
            [
                'id'        => 'required|',
                'reason'    => 'nullable',
                'remarks'   => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $status     = $this->_solvedStatus;
            $id         = $request->id;
            $user       = authUser($request);
            $moduleId   = $this->_moduleId;

            $mGrievanceSolvedApplicantion       = new GrievanceSolvedApplicantion();
            $mGrievanceActiveApplicantion       = new GrievanceActiveApplicantion();
            $mGrievanceReopenApplicantionDetail = new GrievanceReopenApplicantionDetail();
            $mWfActiveDocument                  = new WfActiveDocument();

            $applicationDetails = $mGrievanceSolvedApplicantion->getSolvedGrievance($id)->where('status', 1)->first();
            if (!$applicationDetails) {
                throw new Exception("Application data not found!");
            }

            DB::beginTransaction();
            $applicationNo  = "GRE" . Str::random(10) . "RE";
            $refRequest = [
                "applicationNo"     => $applicationNo,
                "initiatorRoleId"   => $applicationDetails->initiator_id,
                "finisherRoleId"    => $applicationDetails->finisher_id,
                "workflowId"        => $applicationDetails->workflow_id,
                "userId"            => $user->id,
                "userType"          => $user->user_type,
                "reopenCount"       => $applicationDetails->reopen_count + 1

            ];
            $refDetails = new Request([
                "mobileNo"      => $applicationDetails->mobile_no,
                "email"         => $applicationDetails->email,
                "applicantName" => $applicationDetails->applicant_name,
                "aadhar"        => $applicationDetails->uid,
                "description"   => $applicationDetails->description,
                "grievanceHead" => $applicationDetails->grievance_head,
                "department"    => $applicationDetails->department,
                "gender"        => $applicationDetails->gender,
                "disability"    => $applicationDetails->disability,
                "address"       => $applicationDetails->address,
                "districtId"    => $applicationDetails->district_id,
                "ulbId"         => $applicationDetails->ulb_id,
                "wardId"        => $applicationDetails->ward_id,
                "otherInfo"     => $applicationDetails->other_info,
                "applyThrough"  => $applicationDetails->user_apply_through,
                "isDoc"         => $applicationDetails->is_doc

            ]);
            $docRequest = new Request([
                "oldActiveId"   => $applicationDetails->application_id,
                "workflowId"    => $applicationDetails->workflow_id,
                "ulbId"         => $applicationDetails->ulb_id,
                "moduleId"      => $moduleId
            ]);
            $newGrievanceDetails = $mGrievanceActiveApplicantion->saveGrievanceDetails($refDetails, $refRequest);
            $mGrievanceReopenApplicantionDetail->saveReopenDetails($request, $applicationDetails, $applicationNo);
            $mGrievanceSolvedApplicantion->updateStatus($applicationDetails->id, $status['REOPEN']);
            $mWfActiveDocument->updateActiveIdOfDoc($docRequest, $newGrievanceDetails['id']);
            DB::commit();
            $returnDetails = [
                "applicationNo" => $applicationNo
            ];
            # Send Whatsapp message
            return responseMsgs(true, "Grievacne successfully reopened!", $returnDetails, "", "02", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Edit the application detials by the agency before sending it in workflow
        | Serial No : 0
        | Under Con
        | Check params for updating the grievance applications
     */
    public function updateCitizenGrievance(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'id'            => 'required|integer',
                'mobileNo'      => 'nullable|',
                'email'         => 'nullable|email',
                'applicantName' => 'nullable|',
                'uid'           => 'nullable|integer|digits:12',
                'description'   => 'nullable|',
                'grievanceHead' => 'nullable|integer',
                'department'    => 'nullable|integer',
                'gender'        => 'nullable|in:male,female',
                'disability'    => 'nullable|in:true,false',
                'address'       => 'nullable|',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $now            = Carbon::now();
            $user           = authUser($request);
            $applicationId  = $request->id;

            $mGrievanceActiveApplication = new GrievanceActiveApplicantion();
            $applicationDtls = $mGrievanceActiveApplication->getActiveGrievanceById($applicationId)->whereNull('current_role')->first();
            if (!$applicationDtls) {
                throw new Exception("Application details not found!");
            }

            DB::beginTransaction();
            $logDtls = $applicationDtls->replicate();
            $logDtls->setTable('log_grievance_active_applications');
            $logDtls->edited_by = $user->id;
            $logDtls->edited_date = $now;
            $logDtls->application_id = $applicationDtls->id;
            $logDtls->save();
            $mGrievanceActiveApplication->editCitizenGrievance($request);
            DB::commit();
            return responseMsgs(true, "Data Updated", [], "", "02", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "02", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Post the grievance from inner workflow to parent workflow
        | Serial No :
        | Under Con
     */
    public function sendApplicationToParentWf(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId'     => 'required|integer',
                'ulbWorkflowId'     => 'required|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $status                         = $this->_solvedStatus;
            $user                           = authUser($request);
            $confDatabase                   = $this->_wfDatabase;
            $current                        = Carbon::now();
            $mWorkflowTrack                 = new WorkflowTrack();
            $mWfWorkflow                    = new WfWorkflow();
            $mGrievanceActiveApplicantion   = new GrievanceActiveApplicantion();
            $wfDatabaseDetial               = $this->checkRoleWorkflow($request);
            $applicationDetails             = $mGrievanceActiveApplicantion->getGrievanceFullDetails($request->applicationId, $wfDatabaseDetial['databaseType'])->first();
            $wfApplicationDetails           = $this->checkApplication($request, $applicationDetails);

            $refParentUlbWorkflowId = $applicationDetails->parent_wf_id;
            $refDbName              = collect($confDatabase)->flip();
            $workflowMasterId       = $wfDatabaseDetial['workflowMasterId'];
            $parentWfmasteId        = $this->getWorkflowMstId($refParentUlbWorkflowId)->first();
            $parentDatabase         = $refDbName[$parentWfmasteId->id];

            $request->merge([
                "status" => $status['CLOSED']
            ]);
            DB::beginTransaction();
            # Save and update in associated wf and the parent wf 
            $mGrievanceActiveApplicantion->updateWfParent($applicationDetails['application_no']);
            $mGrievanceActiveApplicantion->updateAssociatedDbStatus($wfDatabaseDetial['databaseType'], $request);

            # Save the details in track
            $metaReqs['moduleId']           = $this->_moduleId;
            $metaReqs['workflowId']         = $applicationDetails->workflow_id;
            $metaReqs['refTableDotId']      = $wfDatabaseDetial['databaseType'] . ".id";
            $metaReqs['refTableIdValue']    = $request->applicationId;
            $metaReqs['senderRoleId']       = $wfApplicationDetails['roleDetails']['wf_role_id'];
            $metaReqs['user_id']            = $user->id;
            $metaReqs['ulb_id']             = $user->ulb_id ?? null;
            $metaReqs['trackDate']          = $current->format('Y-m-d H:i:s');
            $request->request->add($metaReqs);
            $mWorkflowTrack->saveTrack($request);
            DB::commit();
            return responseMsgs(true, "Application reverted back to its parent workflow!", [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "02", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
