<?php

namespace App\Models\Advertisements;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use App\MicroServices\DocumentUpload;
use Illuminate\Support\Facades\DB;
use App\Traits\WorkflowTrait;
use Illuminate\Http\Request;


class AdvActiveAgency extends Model
{
    use HasFactory;

    use WorkflowTrait;
    protected $guarded = [];
    protected $_applicationDate;

    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * | Meta Data Uses to Store data in DB
     */
    public function metaReqs($req)
    {
        $metaReqs = [
            'application_date' => $this->_applicationDate,
            'application_no' => $req->application_no,
            'entity_type' => $req->entityType,
            'entity_name' => $req->entityName,
            'address' => $req->address,
            'mobile_no' => $req->mobileNo,
            'telephone' => $req->officeTelephone,
            'fax' => $req->fax,
            'email' => $req->email,
            'pan_no' => $req->panNo,
            'gst_no' => $req->gstNo,
            'blacklisted' => $req->blacklisted,
            'pending_court_case' => $req->pendingCourtCase,
            'pending_amount' => $req->pendingAmount,
            'citizen_id' => $req->citizenId,
            'user_id' => $req->userId,
            'ulb_id' => $req->ulbId
        ];
        return $metaReqs;
    }

    /**
     * | Renewal Data Uses to Store data in DB
     */
    public function renewalReqs($req)
    {
        $metaReqs = [
            'application_date' => $this->_applicationDate,
            'entity_type' => $req->entityType,
            'entity_name' => $req->entityName,
            'address' => $req->address,
            'mobile_no' => $req->mobileNo,
            'telephone' => $req->officeTelephone,
            'fax' => $req->fax,
            'email' => $req->email,
            'pan_no' => $req->panNo,
            'gst_no' => $req->gstNo,
            'blacklisted' => $req->blacklisted,
            'pending_court_case' => $req->pendingCourtCase,
            'pending_amount' => $req->pendingAmount,
            'citizen_id' => $req->citizenId,
            'user_id' => $req->userId,
            'ulb_id' => $req->ulbId,
            'application_no' => $req->application_no,
        ];
        return $metaReqs;
    }

    /**
     * | Store function to apply(1)
     * | @param request 
     */
    public function addNew($req)
    {
        $directors = $req->directors;
        $bearerToken = $req->bearerToken();
        $metaReqs = $this->metaReqs($req);
        // $workflowId = Config::get('workflow-constants.AGENCY');
        // $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);        // Workflow Trait Function
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        $ulbWorkflows = $ulbWorkflows['data'];
        // $ipAddress = getClientIpAddress();
        // $mApplicationNo = ['application_no' => 'AGENCY-' . random_int(100000, 999999)];                  // Generate Application No
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'application_type' => "New Apply"
            ],
            $this->metaReqs($req),
            // $mApplicationNo,
            $ulbWorkflowReqs
        );

        $agencyDirector = new AdvActiveAgencydirector();
        $agencyId = AdvActiveAgency::create($metaReqs)->id;

        $mDocuments = $req->documents;
        $this->uploadDocument($agencyId, $mDocuments,$req->auth);

        // Store Director Details
        $mDocService = new DocumentUpload;
        $mRelativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');
        collect($directors)->map(function ($director) use ($agencyId, $agencyDirector, $mDocService, $mRelativePath) {
            // $mDocRelativeName = "AADHAR";
            // $mImage = $director['aadhar'];
            // $mDocName = $mDocService->upload($mDocRelativeName, $mImage, $mRelativePath);
            $agencyDirector->store($director, $agencyId);       // Model function to store
        });

        return $req->application_no;
    }

    /**
     * | Upload document after application is submit
     */
    public function uploadDocument($tempId, $documents,$auth)
    {
        collect($documents)->map(function ($doc) use ($tempId,$auth) {
            $metaReqs = array();
            $docUpload = new DocumentUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mAdvActiveAgency = new AdvActiveAgency();
            $relativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');
            $getApplicationDtls = $mAdvActiveAgency->getAgencyDetails($tempId);
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

            $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
            $metaReqs['activeId'] = $getApplicationDtls->id;
            $metaReqs['workflowId'] = $getApplicationDtls->workflow_id;
            $metaReqs['ulbId'] = $getApplicationDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $doc['docCode'];
            $metaReqs['ownerDtlId'] = $doc['ownerDtlId'];
            $a = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($a,$auth);
        });
    }

    /**
     * | Get Agency Details by application id
     */
    public function getAgencyDetails($appId)
    {
        return AdvActiveAgency::select('*')
            ->where('id', $appId)
            ->first();
    }


    /**
     * | Get Application Details by id
     * | @param Agencies id
     */
    public function getDetailsById($id, $type)
    {
        $details = array();
        if ($type == "Active" || $type == NULL) {
            $details = DB::table('adv_active_agencies')
                ->select(
                    'adv_active_agencies.*',
                    'u.ulb_name',
                    'et.string_parameter as entityType',
                )
                ->where('adv_active_agencies.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_active_agencies.ulb_id')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'adv_active_agencies.entity_type')
                ->first();
        } elseif ($type == "Reject") {
            $details = DB::table('adv_rejected_agencies')
                ->select(
                    'adv_rejected_agencies.*',
                    'u.ulb_name',
                    'et.string_parameter as entityType',
                )
                ->where('adv_rejected_agencies.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_rejected_agencies.ulb_id')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'adv_rejected_agencies.entity_type')
                ->first();
        } elseif ($type == "Approve") {
            $details = DB::table('adv_agencies')
                ->select(
                    'adv_agencies.*',
                    'u.ulb_name',
                    'et.string_parameter as entityType',
                )
                ->where('adv_agencies.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_agencies.ulb_id')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'adv_agencies.entity_type')
                ->first();
        }

        $details = json_decode(json_encode($details), true);            // Convert Std Class to Array
        //    return $details['temp_id'];
        $directors = DB::table('adv_active_agencydirectors')
            ->select(
                'adv_active_agencydirectors.*',
                DB::raw("CONCAT(adv_active_agencydirectors.relative_path,'/',adv_active_agencydirectors.doc_name) as document_path")
            );
        // if($type=='Active'){
        //     $directors = $directors->where('agency_id', $id);
        // }
        // elseif($type=='Reject'){
        //     $directors = $directors->where('agency_id', $details['id']);
        // }
        // elseif($type=='Approve'){
        //     $directors = $directors->where('agency_id', $details['id']);
        // }
        $directors = $directors->where('agency_id', $id);
        $directors = $directors->get();
        $details['directors'] = remove_null($directors->toArray());
        return $details;
    }


    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds,$ulbId)
    {
        $inbox = DB::table('adv_active_agencies')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'entity_name',
                'address',
                'doc_upload_status',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked',NULL)
            ->where('ulb_id',$ulbId)
            ->whereIn('current_role_id', $roleIds);
            // ->get();
        return $inbox;
    }


    /**
     * | Get Citizen Applied applications
     * | @param citizenId
     */
    public function listAppliedApplications($citizenId)
    {
        return AdvActiveAgency::where('citizen_id', $citizenId)
            ->select(
                'adv_active_agencies.id',
                'adv_active_agencies.application_no',
                'adv_active_agencies.application_date',
                'adv_active_agencies.entity_name',
                'adv_active_agencies.address',
                'adv_active_agencies.doc_upload_status',
                'adv_active_agencies.application_type',
                'adv_active_agencies.parked',
                DB::raw("TO_CHAR(adv_active_agencies.application_date, 'DD-MM-YYYY') as application_date"),
                'wr.role_name',
                'um.ulb_name',
            )
            ->join('wf_roles as wr','wr.id','=','adv_active_agencies.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'adv_active_agencies.ulb_id')
            ->orderByDesc('adv_active_agencies.id')
            ->get();
    }


    /**
     * | Get Application Outbox List by Role Ids
     */
    public function listOutbox($roleIds,$ulbId)
    {
        $outbox = DB::table('adv_active_agencies')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'entity_name',
                'address',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked',NULL)
            ->where('ulb_id',$ulbId)
            ->whereNotIn('current_role_id', $roleIds);
            // ->get();
        return $outbox;
    }

    /**
     * | Get uploaded documents
     */
    public function viewUploadedDocuments($id, $workflowId)
    {
        $documents = DB::table('adv_active_selfadvetdocuments')
            ->select(
                'adv_active_selfadvetdocuments.*',
                'd.document_name as doc_type',
                DB::raw("CONCAT(adv_active_selfadvetdocuments.relative_path,'/',adv_active_selfadvetdocuments.doc_name) as doc_path")
            )
            ->leftJoin('ref_adv_document_mstrs as d', 'd.id', '=', 'adv_active_selfadvetdocuments.document_id')
            ->where(array('adv_active_selfadvetdocuments.temp_id' => $id, 'adv_active_selfadvetdocuments.workflow_id' => $workflowId))
            ->get();
        $details['documents'] = remove_null($documents->toArray());
        return $details;
    }


    /**
     * | Get Jsk Applied applications
     * | @param userId
     */
    public function getJSKApplications($userId)
    {
        return AdvActiveAgency::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Agency Renewals
     * | @param request 
     */
    public function renewalAgency($req)
    {
        $directors = $req->directors;
        $bearerToken = $req->bearerToken();
        $metaReqs = $this->renewalReqs($req);

        // $workflowId = Config::get('workflow-constants.AGENCY');
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        $ulbWorkflows = $ulbWorkflows['data'];
         // $ipAddress = getClientIpAddress();
        $mRenewNo = ['renew_no' => 'AGENCY/REN-' . random_int(100000, 999999)];                  // Generate Application No
        $details = AdvAgency::find($req->applicationId);                              // Find Previous Application No
        $licenseNo = ['license_no' => $details->license_no];
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'renewal' => 1,
                'application_type' => "Renew"
            ],
            $this->renewalReqs($req),
            $mRenewNo,
            $licenseNo,
            $ulbWorkflowReqs
        );

        $agencyDirector = new AdvActiveAgencydirector();
        $agencyId = AdvActiveAgency::create($metaReqs)->id;

        $mDocuments = $req->documents;
        $this->uploadDocument($agencyId, $mDocuments,$req->auth);

        // Store Director Details
        $mDocService = new DocumentUpload;
        $mRelativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');
        collect($directors)->map(function ($director) use ($agencyId, $agencyDirector, $mDocService, $mRelativePath) {
            // $mDocRelativeName = "AADHAR";
            // $mImage = $director['aadhar'];
            // $mDocName = $mDocService->upload($mDocRelativeName, $mImage, $mRelativePath);
            $agencyDirector->store($director, $agencyId);       // Model function to store
        });

        return $mRenewNo['renew_no'];
        // return $req->applicationNo;
    }


    /**
     * | Get Agency Details By application Id
     */
    public function getAgencyNo($appId)
    {
        return AdvActiveAgency::select('*')
            ->where('id', $appId)
            ->first();
    }


    /**
     * | Get Agency list ULB Wise
     */
    public function getAgencyList($ulbId)
    {
        return AdvActiveAgency::select('*')
            ->where('adv_active_agencies.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');

        $refImageName = $docDetails['doc_code'];
        $refImageName = $docDetails['active_id'] . '-' . $refImageName;
        $documentImg = $req->image;
        $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

        $metaReqs['moduleId'] = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $metaReqs['activeId'] = $docDetails['active_id'];
        $metaReqs['workflowId'] = $docDetails['workflow_id'];
        $metaReqs['ulbId'] = $docDetails['ulb_id'];
        $metaReqs['relativePath'] = $relativePath;
        $metaReqs['document'] = $imageName;
        $metaReqs['docCode'] = $docDetails['doc_code'];
        $metaReqs['ownerDtlId'] = $docDetails['ownerDtlId'];
        $a = new Request($metaReqs);
        $mWfActiveDocument = new WfActiveDocument();
        $mWfActiveDocument->postDocuments($a,$req->auth);
        $docDetails->current_status = '0';
        $docDetails->save();
        return $docDetails['active_id'];
    }

    /**
     * | Get Pending applications
     * | @param citizenId
     */
    public function allPendingList()
    {
        return AdvActiveAgency::all();
    }

    /**
     * | Pending List For Report
     */
    public function pendingListForReport()
    {
        return AdvActiveAgency::select('id', 'application_no', 'entity_name', 'application_date', 'application_type', 'ulb_id', DB::raw("'Active' as application_status"));
    }
}
