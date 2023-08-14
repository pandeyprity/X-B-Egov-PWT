<?php

namespace App\Models\Advertisements;

use App\MicroServices\DocumentUpload;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class AdvActiveSelfadvertisement extends Model
{
    use WorkflowTrait;
    protected $guarded = [];
    protected $_applicationDate;

    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }

    // helper meta reqs
    public function metaReqs($req)
    {
        return [
            'applicant' => $req->applicantName,
            'application_no' => $req->application_no,
            'license_year' => $req->licenseYear,
            'father' => $req->fatherName,
            'email' => $req->email,
            'residence_address' => $req->residenceAddress,
            'ward_id' => $req->wardId,
            'permanent_address' => $req->permanentAddress,
            'permanent_ward_id' => $req->permanentWardId,
            'entity_name' => $req->entityName,
            'entity_address' => $req->entityAddress,
            'entity_ward_id' => $req->entityWardId,
            'mobile_no' => $req->mobileNo,
            'aadhar_no' => $req->aadharNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'holding_no' => $req->holdingNo,
            'gst_no' => $req->gstNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'display_area' => $req->displayArea,
            'display_type' => $req->displayType,
            'installation_location' => $req->installationLocation,
            'brand_display_name' => $req->brandDisplayName,
            'user_id' => $req->userId,
            'advt_category' => $req->advtCategory
        ];
    }

    // helper meta reqs for renewal
    public function metaRenewalReqs($req)
    {
        return [
            'applicant' => $req->applicantName,
            'application_no' => $req->application_no,
            'license_year' => $req->licenseYear,
            'father' => $req->fatherName,
            'email' => $req->email,
            'residence_address' => $req->residenceAddress,
            'ward_id' => $req->wardId,
            'permanent_address' => $req->permanentAddress,
            'permanent_ward_id' => $req->permanentWardId,
            'entity_name' => $req->entityName,
            'entity_address' => $req->entityAddress,
            'entity_ward_id' => $req->entityWardId,
            'mobile_no' => $req->mobileNo,
            'aadhar_no' => $req->aadharNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'holding_no' => $req->holdingNo,
            'gst_no' => $req->gstNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'display_area' => $req->displayArea,
            'display_type' => $req->displayType,
            'installation_location' => $req->installationLocation,
            'brand_display_name' => $req->brandDisplayName,
            'user_id' => $req->userId,
            'advt_category' => $req->advtCategory
        ];
    }

    // Store Self Advertisements(1)
    public function addNew($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.SELF_ADVERTISENTS');
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        //    echo "<pre>"; print_r($ulbWorkflows); die;
        // if (!$ulbWorkflows)
        //     $ulbWorkflows = $ulbWorkflows->data;

        $ulbWorkflows = $ulbWorkflows['data'];
        // $ipAddress = getClientIpAddress();
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];
        $mDocuments = $req->documents;

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'application_type' => "New Apply",
            ],
            $this->metaReqs($req),
            $ulbWorkflowReqs
        );
        // return $metaReqs;
        // Add Relative Path as Request and Client Ip Address etc.
        $tempId = AdvActiveSelfadvertisement::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments,$req->auth);

        return $req->application_no;
    }

    // Renewal Self Advertisements(1)
    public function renewalSelfAdvt($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.SELF_ADVERTISENTS');
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);        // Workflow Trait Function
        // $ipAddress = getClientIpAddress();
        $mRenewNo = ['renew_no' => 'SELF/REN-' . random_int(100000, 999999)];                  // Generate Renewal No
        $details = AdvSelfadvertisement::find($req->applicationId);                              // Find Previous Application No
        $licenseNo = ['license_no' => $details->license_no];
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];
        $mDocuments = $req->documents;

        $metaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'application_type' => "Renew"
            ],
            $this->metaRenewalReqs($req),
            $mRenewNo,
            $licenseNo,
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = AdvActiveSelfadvertisement::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments,$req->auth);
        return $mRenewNo;
    }


    /**
     * upload Document By Admin
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents, $auth)
    {
        collect($documents)->map(function ($doc) use ($tempId,$auth) {
            $metaReqs = array();
            $docUpload = new DocumentUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mAdvActiveSelfadvertisement = new AdvActiveSelfadvertisement();
            $relativePath = Config::get('constants.SELF_ADVET_RELATIVE_PATH');
            $getApplicationDtls = $mAdvActiveSelfadvertisement->getSelfAdvertNo($tempId);
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
     * | Get Citizen Applied applications
     * | @param citizenId
     */
    public function listAppliedApplications($citizenId)
    { 
        return AdvActiveSelfadvertisement::where('citizen_id', $citizenId)
            ->select(
                'adv_active_selfadvertisements.id',
                'adv_active_selfadvertisements.application_no',
                'adv_active_selfadvertisements.applicant',
                'adv_active_selfadvertisements.entity_name',
                'adv_active_selfadvertisements.entity_address',
                'adv_active_selfadvertisements.payment_status',
                'adv_active_selfadvertisements.doc_upload_status',
                'adv_active_selfadvertisements.application_type',
                'adv_active_selfadvertisements.parked',
                'adv_active_selfadvertisements.workflow_id',
                DB::raw("TO_CHAR(adv_active_selfadvertisements.application_date, 'DD-MM-YYYY') as application_date"),
                'wr.role_name',
                'um.ulb_name',
            )
            ->join('wf_roles as wr', 'wr.id', '=', 'adv_active_selfadvertisements.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'adv_active_selfadvertisements.ulb_id')
            ->orderByDesc('adv_active_selfadvertisements.id')
            ->get();
    }

    /**
     * | Get Application Details by id
     * | @param SelfAdvertisements id
     */
    public function getDetailsById($id, $type = NULL)
    {
        $details = array();
        if ($type == 'Active' || $type == NULL) {
            $details = DB::table('adv_active_selfadvertisements')
                ->select(
                    'adv_active_selfadvertisements.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'dp.string_parameter as m_display_type',
                    'il.string_parameter as m_installation_location',
                    'r.role_name as m_current_role',
                    'cat.type',
                )
                ->where('adv_active_selfadvertisements.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_active_selfadvertisements.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_active_selfadvertisements.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_active_selfadvertisements.ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'adv_active_selfadvertisements.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'adv_active_selfadvertisements.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as dp', 'dp.id', '=', 'adv_active_selfadvertisements.display_type')
                ->leftJoin('ref_adv_paramstrings as il', 'il.id', '=', 'adv_active_selfadvertisements.installation_location')
                ->leftJoin('wf_roles as r', 'r.id', '=', 'adv_active_selfadvertisements.current_role_id')
                ->leftJoin('adv_selfadv_categories as cat', 'cat.id', '=', 'adv_active_selfadvertisements.advt_category')
                ->first();
        } elseif ($type == 'Reject') {
            $details = DB::table('adv_rejected_selfadvertisements')
                ->select(
                    'adv_rejected_selfadvertisements.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'dp.string_parameter as m_display_type',
                    'il.string_parameter as m_installation_location',
                    'r.role_name as m_current_role',
                    'cat.type',
                )
                ->where('adv_rejected_selfadvertisements.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_rejected_selfadvertisements.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_rejected_selfadvertisements.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_rejected_selfadvertisements.ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'adv_rejected_selfadvertisements.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'adv_rejected_selfadvertisements.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as dp', 'dp.id', '=', 'adv_rejected_selfadvertisements.display_type')
                ->leftJoin('ref_adv_paramstrings as il', 'il.id', '=', 'adv_rejected_selfadvertisements.installation_location')
                ->leftJoin('wf_roles as r', 'r.id', '=', 'adv_rejected_selfadvertisements.current_role_id')
                ->leftJoin('adv_selfadv_categories as cat', 'cat.id', '=', 'adv_rejected_selfadvertisements.advt_category')
                ->first();
        } elseif ($type == 'Approve') {
            $details = DB::table('adv_selfadvertisements')
                ->select(
                    'adv_selfadvertisements.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'dp.string_parameter as m_display_type',
                    'il.string_parameter as m_installation_location',
                    'r.role_name as m_current_role',
                    'cat.type',
                )
                ->where('adv_selfadvertisements.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_selfadvertisements.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_selfadvertisements.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_selfadvertisements.ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'adv_selfadvertisements.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'adv_selfadvertisements.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as dp', 'dp.id', '=', 'adv_selfadvertisements.display_type')
                ->leftJoin('ref_adv_paramstrings as il', 'il.id', '=', 'adv_selfadvertisements.installation_location')
                ->leftJoin('wf_roles as r', 'r.id', '=', 'adv_selfadvertisements.current_role_id')
                ->leftJoin('adv_selfadv_categories as cat', 'cat.id', '=', 'adv_selfadvertisements.advt_category')
                ->first();
        }
        return json_decode(json_encode($details), true);            // Convert Std Class to Array
    }

    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds, $ulbId)
    {
        $inbox = DB::table('adv_active_selfadvertisements')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'payment_status',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
            ->whereIn('current_role_id', $roleIds);
        // ->get();
        return $inbox;
    }

    /**
     * | Get Application Outbox List by Role Ids
     */
    public function listOutbox($roleIds, $ulbId)
    {
        $outbox = DB::table('adv_active_selfadvertisements')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'payment_status',
                'application_type',
            )
            ->orderByDesc('id')
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
            ->whereNotIn('current_role_id', $roleIds);
        // ->get();
        return $outbox;
    }


    /**
     * | Get Jsk Applied applications
     * | @param userId
     */
    public function getJSKApplications($userId)
    {
        return AdvActiveSelfadvertisement::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'payment_status'
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Application Details By Id
     */
    public function getSelfAdvertNo($appId)
    {
        return AdvActiveSelfadvertisement::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Self Advertesement List ULB Wise
     */
    public function getSelfAdvertisementList($ulbId)
    {
        return AdvActiveSelfadvertisement::select('*')
            ->where('adv_active_selfadvertisements.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.SELF_ADVET_RELATIVE_PATH');

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
        return AdvActiveSelfadvertisement::all();
    }

    /**
     * | Get Pending Application for Report
     */
    public function allPendingListForReport()
    {
        return AdvActiveSelfadvertisement::select(
            '*',
            DB::raw("'Pending' as applicationStatus"),
        )->get();
    }

    /**
     * | Pending List For Report
     */
    public function pendingListForReport()
    {
        return AdvActiveSelfadvertisement::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'ulb_id', 'license_year', 'display_type', DB::raw("'Active' as application_status"));
    }
}
