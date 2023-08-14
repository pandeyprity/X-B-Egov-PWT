<?php

namespace App\Models\Markets;

use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\WfActiveDocument;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MarActiveBanquteHall extends Model
{
    use HasFactory;

    use WorkflowTrait;

    protected $guarded = [];
    protected $_applicationDate;

    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d');
    }
    
    /**
     * | Make meta request for renew and store
     */
    public function metaReqs($req)
    {
        return [
            'rule' => $req->rule,
            'applicant' => $req->applicantName,
            'license_year' => $req->licenseYear,
            'father' => $req->fatherName,
            'residential_address' => $req->residentialAddress,
            'residential_ward_id' => $req->residentialWardId,
            'permanent_address' => $req->permanentAddress,
            'permanent_ward_id' => $req->permanentWardId,
            'email' => $req->email,
            'mobile' => $req->mobile,
            'entity_name' => $req->entityName,
            'entity_address' => $req->entityAddress,
            'entity_ward_id' => $req->entityWardId,
            'hall_type' => $req->hallType,
            'holding_no' => $req->holdingNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'organization_type' => $req->organizationType,
            'floor_area' => $req->floorArea,
            'land_deed_type' => $req->landDeedType,



            'water_supply_type' => $req->waterSupplyType,
            'electricity_type' => $req->electricityType,
            'security_type' => $req->securityType,
            'cctv_camera' => $req->cctvCamera,
            'fire_extinguisher' => $req->fireExtinguisher,
            'entry_gate' => $req->entryGate,
            'exit_gate' => $req->exitGate,
            'two_wheelers_parking' => $req->twoWheelersParking,
            'four_wheelers_parking' => $req->fourWheelersParking,
            'aadhar_card' => $req->aadharCard,
            'pan_card' => $req->panCard,
            'application_no' => $req->application_no,
        ];
    }

    /**
     * | Make Update Metarequest
     */
    public function updateMetaReqs($req)
    {
        return [
            'entity_name' => $req->entityName,
            'entity_address' => $req->entityAddress,
            'entity_ward_id' => $req->entityWardId,
            'hall_type' => $req->hallType,
            'holding_no' => $req->holdingNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'organization_type' => $req->organizationType,
            'floor_area' => $req->floorArea,
            'land_deed_type' => $req->landDeedType,

            'water_supply_type' => $req->waterSupplyType,
            'electricity_type' => $req->electricityType,
            'security_type' => $req->securityType,
            'cctv_camera' => $req->cctvCamera,
            'fire_extinguisher' => $req->fireExtinguisher,
            'entry_gate' => $req->entryGate,
            'exit_gate' => $req->exitGate,
            'two_wheelers_parking' => $req->twoWheelersParking,
            'four_wheelers_parking' => $req->fourWheelersParking,
            'aadhar_card' => $req->aadharCard,
            'pan_card' => $req->panCard,
        ];
    }

    // Store Application Banqute-Marrige Hall(1)
    public function addNew($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.BANQUTE_MARRIGE_HALL');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
         $ulbWorkflowReqs = [                                                                             // Workflow Meta Requests
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
                'application_type' => "New Apply"
            ],
            $this->metaReqs($req),
            // $mApplicationNo,
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = MarActiveBanquteHall::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments,$req->auth);

        return $req->application_no;
    }


    // Renew  Application For Banqute-Marrige Hall(1)
    public function renewApplication($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.BANQUTE_MARRIGE_HALL');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
        // $ipAddress = getClientIpAddress();
        $mRenewNo = ['renew_no' => 'BMHALL/REN-' . random_int(100000, 999999)];                  // Generate Application No
        $details = MarBanquteHall::find($req->applicationId);                              // Find Previous Application No
        $mLicenseNo = ['license_no' => $details->license_no];
        $ulbWorkflowReqs = [                                                                             // Workflow Meta Requests
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
            $this->metaReqs($req),
            $mLicenseNo,
            $mRenewNo,
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = MarActiveBanquteHall::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments,$req->auth);

        return $mRenewNo['renew_no'];
    }

    /**
     * upload Document By Citizen At the time of Registration
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents,$auth)
    {
        $docUpload = new DocumentUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $mMarActiveBanquteHall = new MarActiveBanquteHall();
        $relativePath = Config::get('constants.BANQUTE_MARRIGE_HALL.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($tempId, $docUpload, $mWfActiveDocument, $mMarActiveBanquteHall, $relativePath,$auth) {
            $metaReqs = array();
            $getApplicationDtls = $mMarActiveBanquteHall->getApplicationDtls($tempId);
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);
            $metaReqs['moduleId'] = Config::get('workflow-constants.MARKET_MODULE_ID');
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
     * | Get application Details By Id
     */
    public function getApplicationDtls($appId)
    {

        return MarActiveBanquteHall::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds,$ulbId)
    {
        $inbox = DB::table('mar_active_banqute_halls')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
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
     * | Get Application Outbox List by Role Ids
     */
    public function listOutbox($roleIds,$ulbId)
    {
        $outbox = DB::table('mar_active_banqute_halls')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
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
     * | Get Application Details by id
     * | @param SelfAdvertisements id
     */
    public function getDetailsById($id, $type = NULL)
    {
        $details = array();
        if ($type == 'Active' || $type == NULL) {
            $details = DB::table('mar_active_banqute_halls')
                ->select(
                    'mar_active_banqute_halls.*',
                    'u.ulb_name',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as halltype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                    'ly.string_parameter as licenseYear',
                )
                ->where('mar_active_banqute_halls.id', $id)
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_active_banqute_halls.hall_type')
                ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_active_banqute_halls.license_year::int'))
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_active_banqute_halls.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_banqute_halls.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_banqute_halls.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_active_banqute_halls.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_active_banqute_halls.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_active_banqute_halls.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_active_banqute_halls.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_active_banqute_halls.land_deed_type')
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_active_banqute_halls.ulb_id')
                ->first();
        } elseif ($type == 'Reject') {
            // $details = DB::table('mar_rejected_banqute_halls')
            $details = DB::table('mar_rejected_banqute_halls')
                ->select(
                    'mar_rejected_banqute_halls.*',
                    'u.ulb_name',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as halltype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                    'ly.string_parameter as licenseYear',
                )
                ->where('mar_rejected_banqute_halls.id', $id)
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_rejected_banqute_halls.hall_type')
                ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_rejected_banqute_halls.license_year::int'))
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_rejected_banqute_halls.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_rejected_banqute_halls.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_rejected_banqute_halls.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_rejected_banqute_halls.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_rejected_banqute_halls.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_rejected_banqute_halls.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_rejected_banqute_halls.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_rejected_banqute_halls.land_deed_type')
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_rejected_banqute_halls.ulb_id')
                ->first();
        } elseif ($type == 'Approve') {
            // $details = DB::table('mar_banqute_halls')
            $details = DB::table('mar_banqute_halls')
                ->select(
                    'mar_banqute_halls.*',
                    'u.ulb_name',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ht.string_parameter as halltype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                    'ly.string_parameter as licenseYear',
                )
                ->where('mar_banqute_halls.id', $id)
                ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', 'mar_banqute_halls.hall_type')
                ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_banqute_halls.license_year::int'))
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_banqute_halls.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_banqute_halls.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_banqute_halls.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_banqute_halls.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_banqute_halls.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_banqute_halls.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_banqute_halls.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_banqute_halls.land_deed_type')
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_banqute_halls.ulb_id')
                ->first();
        }
        return json_decode(json_encode($details), true);            // Convert Std Class to Array
    }

    /**
     * | Get Citizen Applied applications
     * | @param citizenId
     */
    public function listAppliedApplications($citizenId)
    {
        return MarActiveBanquteHall::where('mar_active_banqute_halls.citizen_id', $citizenId)
            ->select(
                'mar_active_banqute_halls.id',
                'mar_active_banqute_halls.application_no',
                DB::raw("TO_CHAR(mar_active_banqute_halls.application_date, 'DD-MM-YYYY') as application_date"),
                'mar_active_banqute_halls.applicant',
                'mar_active_banqute_halls.entity_name',
                'mar_active_banqute_halls.entity_address',
                'mar_active_banqute_halls.doc_upload_status',
                'mar_active_banqute_halls.application_type',
                'mar_active_banqute_halls.parked',
                'um.ulb_name as ulb_name',
                'wr.role_name',
            )
            ->join('wf_roles as wr', 'wr.id', '=', 'mar_active_banqute_halls.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'mar_active_banqute_halls.ulb_id')
            ->orderByDesc('mar_active_banqute_halls.id')
            ->get();
    }


    public function getBanquetMarriageHallDetails($appId)
    {
        return MarActiveBanquteHall::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Banquet Marriage hall List ULB wise
     */
    public function getBanquetMarriageHallList($ulbId)
    {
        return MarActiveBanquteHall::select('*')
            ->where('mar_active_banqute_halls.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.BANQUTE_MARRIGE_HALL.RELATIVE_PATH');

        $refImageName = $docDetails['doc_code'];
        $refImageName = $docDetails['active_id'] . '-' . $refImageName;
        $documentImg = $req->image;
        $imageName = $docUpload->upload($refImageName, $documentImg, $relativePath);

        $metaReqs['moduleId'] = Config::get('workflow-constants.MARKET_MODULE_ID');
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
     * | Get Application Details For Edit or Update
     */
    public function getApplicationDetailsForEdit($appId)
    {
        $details = MarActiveBanquteHall::select(
            'mar_active_banqute_halls.*',
            'mar_active_banqute_halls.organization_type as organization_type_id',
            'mar_active_banqute_halls.land_deed_type as land_deed_type_id',
            'mar_active_banqute_halls.water_supply_type as water_supply_type_id',
            'mar_active_banqute_halls.hall_type as hall_type_id',
            'mar_active_banqute_halls.electricity_type as electricity_type_id',
            'mar_active_banqute_halls.security_type as security_type_id',
            'ly.string_parameter as license_year_name',
            'rw.ward_name as resident_ward_name',
            'ew.ward_name as entity_ward_name',
            'ot.string_parameter as organization_type_name',
            'ldt.string_parameter as land_deed_type_name',
            'ldt.string_parameter as water_supply_type_name',
            'ht.string_parameter as hall_type_name',
            'et.string_parameter as electricity_type_name',
            'st.string_parameter as security_type_name',
            'pw.ward_name as permanent_ward_name',
            'ulb.ulb_name',
            DB::raw("'Banquet/Marriage Hall' as headerTitle")
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_active_banqute_halls.license_year::int'))
            ->leftJoin('ulb_ward_masters as rw', 'rw.id', '=', DB::raw('mar_active_banqute_halls.entity_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', DB::raw('mar_active_banqute_halls.organization_type::int'))
            ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', DB::raw('mar_active_banqute_halls.land_deed_type::int'))
            ->leftJoin('ref_adv_paramstrings as ht', 'ht.id', '=', DB::raw('mar_active_banqute_halls.hall_type::int'))
            ->leftJoin('ref_adv_paramstrings as wt', 'wt.id', '=', DB::raw('mar_active_banqute_halls.water_supply_type::int'))
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', DB::raw('mar_active_banqute_halls.electricity_type::int'))
            ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', DB::raw('mar_active_banqute_halls.security_type::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_banqute_halls.entity_ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_banqute_halls.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'mar_active_banqute_halls.ulb_id')
            ->where('mar_active_banqute_halls.id', $appId)
            ->first();
        if (!empty($details)) {
            $mWfActiveDocument = new WfActiveDocument();
            $documents = $mWfActiveDocument->uploadDocumentsViewById($appId, $details->workflow_id);
            $details['documents'] = $documents;
        }
        return $details;
    }

    /**
     * | Update Application
     */
    public function updateApplication($req)
    {
        $mMarActiveBanquteHall = MarActiveBanquteHall::findorfail($req->applicationId);
        $mMarActiveBanquteHall->remarks = $req->remarks;
        $mMarActiveBanquteHall->hall_type = $req->hallType;
        $mMarActiveBanquteHall->organization_type = $req->organizationType;
        $mMarActiveBanquteHall->floor_area = $req->floorArea;
        $mMarActiveBanquteHall->land_deed_type = $req->landDeedType;
        $mMarActiveBanquteHall->water_supply_type = $req->waterSupplyType;
        $mMarActiveBanquteHall->electricity_type = $req->electricityType;
        $mMarActiveBanquteHall->security_type = $req->securityType;
        $mMarActiveBanquteHall->cctv_camera = $req->cctvCamera;
        $mMarActiveBanquteHall->fire_extinguisher = $req->fireExtinguisher;
        $mMarActiveBanquteHall->entry_gate = $req->entryGate;
        $mMarActiveBanquteHall->exit_gate = $req->exitGate;
        $mMarActiveBanquteHall->two_wheelers_parking = $req->twoWheelersParking;
        $mMarActiveBanquteHall->four_wheelers_parking = $req->fourWheelersParking;
        $mMarActiveBanquteHall->save();
        // dd($mMarActiveBanquteHall);
        return $mMarActiveBanquteHall;
    }

    /**
     * | Get Pending applications
     * | @param citizenId
     */
    public function allPendingList()
    {
        return MarActiveBanquteHall::all();
    }

    /**
     * | Pending List For Report
     */
    public function pendingListForReport()
    {
        return MarActiveBanquteHall::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'hall_type', 'ulb_id', 'license_year', 'organization_type', DB::raw("'Active' as application_status"));
    }
}
