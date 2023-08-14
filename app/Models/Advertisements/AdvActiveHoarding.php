<?php

namespace App\Models\Advertisements;

use App\MicroServices\DocumentUpload;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdvActiveHoarding extends Model
{
    use HasFactory;

    use WorkflowTrait;
    protected $guarded = [];
    protected $_applicationDate;
    protected $_workflowId;

    // Initializing construction
    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * | Store request for Hoarding application
     */
    public function MetaReqs($req)
    {
        $metaReqs = [
            'zone_id' => $req->zoneId,
            'license_year' => $req->licenseYear,
            'application_no' => $req->application_no,
            'typology' => $req->HordingType,               // Hording Type is Convert Into typology
            'display_location' => $req->displayLocation,
            'width' => $req->width,
            'length' => $req->length,
            'display_area' => $req->displayArea,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'material' => $req->material,
            'illumination' => $req->illumination,
            'indicate_facing' => $req->indicateFacing,
            'property_type' => $req->propertyType,
            'display_land_mark' => $req->displayLandMark,
            'property_owner_name' => $req->propertyOwnerName,
            'property_owner_address' => $req->propertyOwnerAddress,
            'property_owner_city' => $req->propertyOwnerCity,
            'property_owner_whatsapp_no' => $req->propertyOwnerWhatsappNo,
            'property_owner_mobile_no' => $req->propertyOwnerMobileNo,
            'user_id' => $req->userId,


        ];
        return $metaReqs;
    }

    /**
     * | Renewal Request For Hoarding Application
     */
    public function RenewMetaReqs($req)
    {
        $metaReqs = [
            'zone_id' => $req->zoneId,
            'license_year' => $req->licenseYear,
            'typology' => $req->HordingType,               // Hording Type is Convert Into typology
            'display_location' => $req->displayLocation,
            'width' => $req->width,
            'length' => $req->length,
            'display_area' => $req->displayArea,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'material' => $req->material,
            'illumination' => $req->illumination,
            'indicate_facing' => $req->indicateFacing,
            'property_type' => $req->propertyType,
            'display_land_mark' => $req->displayLandMark,
            'property_owner_name' => $req->propertyOwnerName,
            'property_owner_address' => $req->propertyOwnerAddress,
            'property_owner_city' => $req->propertyOwnerCity,
            'property_owner_whatsapp_no' => $req->propertyOwnerWhatsappNo,
            'property_owner_mobile_no' => $req->propertyOwnerMobileNo,
            'user_id' => $req->userId,
            'application_no' => $req->application_no,
        ];
        return $metaReqs;
    }

    /**
     * | Store function to Hording apply
     * | @param request 
     */
    public function addNew($req)
    {
        // Variable Initializing
        $bearerToken = $req->bearerToken();
        $LicencesMetaReqs = $this->MetaReqs($req);
        // $workflowId = $this->_workflowId;
        // $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);        // Workflow Trait Function
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        $ulbWorkflows = $ulbWorkflows['data'];
        // $ipAddress = getClientIpAddress();
        // $mLecenseNo = ['license_no' => 'LICENSE-' . random_int(100000, 999999)];                  // Generate Lecence No
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];

        // $LicencesMetaReqs=$this->uploadLicenseDocument($req,$LicencesMetaReqs);

        $LicencesMetaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'application_type' => "New Apply"
            ],
            $this->MetaReqs($req),
            $ulbWorkflowReqs
        );


        $licenceId = AdvActiveHoarding::create($LicencesMetaReqs)->id;
        // $licenceId = 5;

        $mDocuments = $req->documents;
        // $mDocuments = str_replace(']"'," ",$mDocuments);
        $this->uploadDocument($licenceId, $mDocuments, $req->auth);

        return $req->application_no;
    }

    /**
     * | Store function to Licence apply
     * | @param request 
     */
    public function renewalHording($req)
    {
        // Variable Initializing
        $bearerToken = $req->bearerToken();
        $LicencesMetaReqs = $this->RenewMetaReqs($req);
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        $ulbWorkflows = $ulbWorkflows['data'];
        // $ipAddress = getClientIpAddress();
        $mRenewNo = ['renew_no' => 'HORDING/REN-' . random_int(100000, 999999)];                  // Generate Lecence No
        $details = AdvHoarding::find($req->applicationId);                              // Find Previous Application No
        $mLicenseNo = ['license_no' => $details->license_no];
        $ulbWorkflowReqs = [                                                                           // Workflow Meta Requests
            'workflow_id' => $ulbWorkflows['id'],
            'initiator_role_id' => $ulbWorkflows['initiator_role_id'],
            'last_role_id' => $ulbWorkflows['initiator_role_id'],
            'current_role_id' => $ulbWorkflows['initiator_role_id'],
            'finisher_role_id' => $ulbWorkflows['finisher_role_id'],
        ];

        $LicencesMetaReqs = array_merge(
            [
                'ulb_id' => $req->ulbId,
                'citizen_id' => $req->citizenId,
                'application_date' => $this->_applicationDate,
                'ip_address' => $req->ipAddress,
                'application_type' => "Renew"
            ],
            $this->RenewMetaReqs($req),
            $mRenewNo,
            $mLicenseNo,
            $ulbWorkflowReqs
        );

        $licenceId = AdvActiveHoarding::create($LicencesMetaReqs)->id;
        $mDocuments = $req->documents;
        $this->uploadDocument($licenceId, $mDocuments,$req->auth);
        return $mRenewNo['renew_no'];
    }

    /** 
     * upload Document
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents,$auth)
    {
        collect($documents)->map(function ($doc) use ($tempId,$auth) {
            $metaReqs = array();
            $docUpload = new DocumentUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mAdvActiveHoarding = new AdvActiveHoarding();
            $relativePath = Config::get('constants.AGENCY_ADVET.RELATIVE_PATH');
            $getApplicationDtls = $mAdvActiveHoarding->getHoardingDetails($tempId);
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
     * | Get Hoarding Details By ID
     */
    public function getHoardingDetails($appId)
    {
        return AdvActiveHoarding::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Application License Details by id
     * | @param Hoarding id
     */
    public function getDetailsById($id, $type)
    {
        $details = array();
        if ($type == "Active" || $type == NULL) {
            $details = DB::table('adv_active_hoardings')
                ->select(
                    'adv_active_hoardings.*',
                    'u.ulb_name',
                    'tm.type_inner as hoardingCategory',
                    'p.string_parameter as licenseYear',
                )
                ->where('adv_active_hoardings.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_active_hoardings.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_active_hoardings.license_year')
                ->leftJoin('adv_typology_mstrs as tm', 'tm.id', '=', DB::raw('adv_active_hoardings.typology::int'))
                ->first();
        } elseif ($type == "Reject") {
            $details = DB::table('adv_rejected_hoardings')
                ->select(
                    'adv_rejected_hoardings.*',
                    'u.ulb_name',
                    'tm.type_inner as hoardingCategory',
                    'p.string_parameter as licenseYear',
                )
                ->where('adv_rejected_hoardings.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_rejected_hoardings.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_rejected_hoardings.license_year')
                ->leftJoin('adv_typology_mstrs as tm', 'tm.id', '=', DB::raw('adv_rejected_hoardings.typology::int'))
                ->first();
        } elseif ($type == "Approve") {
            $details = DB::table('adv_hoardings')
                ->select(
                    'adv_hoardings.*',
                    'u.ulb_name',
                    'tm.type_inner as hoardingCategory',
                    'p.string_parameter as licenseYear',
                )
                ->where('adv_hoardings.id', $id)
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'adv_hoardings.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'adv_hoardings.license_year')
                ->leftJoin('adv_typology_mstrs as tm', 'tm.id', '=', DB::raw('adv_hoardings.typology::int'))
                ->first();
        }
        $details = json_decode(json_encode($details), true);            // Convert Std Class to Array
        return $details;
    }


    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds,$ulbId)
    {
        $inbox = DB::table('adv_active_hoardings')
            ->select(
                'id',
                'application_no',
                'license_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'license_no',
                'application_type',
                // 'bank_name',
                // 'account_no',
                // 'ifsc_code',
                // 'total_charge'
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
        return AdvActiveHoarding::where('citizen_id', $citizenId)
            ->select(
                'id',
                'application_no',
                'license_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'license_no',
                'application_type',
                'parked',
                // 'bank_name',
                // 'account_no',
                // 'ifsc_code',
                // 'total_charge',
                'doc_upload_status',
            )
            ->orderByDesc('id');
            // ->get();
    }


    /**
     * | Get Application Outbox List by Role Ids
     */
    public function listOutbox($roleIds,$ulbId)
    {
        $outbox = DB::table('adv_active_hoardings')
            ->select(
                'id',
                'application_no',
                'license_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'license_no',
                'application_type',
                // 'bank_name',
                // 'account_no',
                // 'ifsc_code',
                // 'total_charge'
            )
            ->orderByDesc('id')
            ->where('parked',NULL)
            ->where('ulb_id',$ulbId)
            ->whereNotIn('current_role_id', $roleIds);
            // ->get();
        return $outbox;
    }


    /**
     * | Get Jsk Applied License  applications
     * | @param userId
     */
    public function getJskApplications($userId)
    {
        return AdvActiveHoarding::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Hoarding application Details By application Id
     */
    public function getHoardingNo($appId)
    {
        return AdvActiveHoarding::select('*')
            ->where('id', $appId)
            ->first();
    }


    /**
     * | Get All Hoarding List ulbwise
     */
    public function getHoardingList($ulbId)
    {
        return AdvActiveHoarding::select('*')
            ->where('adv_active_Hoardings.ulb_id', $ulbId);
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
        return AdvActiveVehicle::all();
    }

    /**
     * | Pending List For Report
     */
    public function pendingListForReport()
    {
        return AdvActiveVehicle::select('id', 'application_no', 'application_date', 'application_type', 'license_year', 'ulb_id', DB::raw("'Active' as application_status"));
    }
}