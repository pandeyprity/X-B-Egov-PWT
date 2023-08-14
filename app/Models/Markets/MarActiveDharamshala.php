<?php

namespace App\Models\Markets;

use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\WfActiveDocument;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MarActiveDharamshala extends Model
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
     * | Make Meta request for store
     */
    public function metaReqs($req)
    {
        return [
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
            'holding_no' => $req->holdingNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'organization_type' => $req->organizationType,
            'land_deed_type' => $req->landDeedType,
            'no_of_beds' => $req->noOfBeds,
            'no_of_rooms' => $req->noOfRooms,
            'application_no' => $req->application_no,
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
            'floor_area' => $req->floorArea,
            'rule' => $req->rule,
        ];
    }

    // Store Application For Dharamshala(1)
    public function addNew($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.DHARAMSHALA');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
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
            //  $mApplicationNo,
            $ulbWorkflowReqs
        );                                                                                          // Add Relative Path as Request and Client Ip Address etc.
        $tempId = MarActiveDharamshala::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments, $req->auth);

        return $req->application_no;
    }


    //Renewal Application For Dharamshala(1)
    public function renewApplication($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.DHARAMSHALA');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);                 // Workflow Trait Function
        $ulbWorkflows = $ulbWorkflows['data'];
        $details = MarDharamshala::find($req->applicationId);                              // Find Previous Application No
        $mLicenseNo = ['license_no' => $details->license_no];
        $mRenewNo = ['renew_no' => 'DHARAMSHALA/REN-' . random_int(100000, 999999)];                  // Generate Application No
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
        $tempId = MarActiveDharamshala::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments, $req->auth);

        return $mRenewNo['renew_no'];
    }

    /**
     * upload Document By Citizen At the time of Registration
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents, $auth)
    {
        $docUpload = new DocumentUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $mMarActiveDharamshala = new MarActiveDharamshala();
        $relativePath = Config::get('constants.DHARAMSHALA.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($tempId, $docUpload, $mWfActiveDocument, $mMarActiveDharamshala, $relativePath, $auth) {
            $metaReqs = array();
            $getApplicationDtls = $mMarActiveDharamshala->getApplicationDtls($tempId);
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
            $mWfActiveDocument->postDocuments($a, $auth);
        });
    }

    /**
     * | Get application details by Id
     */
    public function getApplicationDtls($appId)
    {

        return MarActiveDharamshala::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds, $ulbId)
    {
        $inbox = DB::table('mar_active_dharamshalas')
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
        $outbox = DB::table('mar_active_dharamshalas')
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
            ->where('parked', NULL)
            ->where('ulb_id', $ulbId)
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
            $details = DB::table('mar_active_dharamshalas')
                ->select(
                    'mar_active_dharamshalas.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_active_dharamshalas.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_active_dharamshalas.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_active_dharamshalas.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_dharamshalas.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_dharamshalas.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_active_dharamshalas.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_active_dharamshalas.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_active_dharamshalas.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_active_dharamshalas.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_active_dharamshalas.land_deed_type')
                // ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_active_dharamshalas.organization_type')
                ->where('mar_active_dharamshalas.id', $id)
                ->first();
        } elseif ($type == 'Reject') {
            $details = DB::table('mar_rejected_dharamshalas')
                ->select(
                    'mar_rejected_dharamshalas.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_rejected_dharamshalas.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_rejected_dharamshalas.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_rejected_dharamshalas.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_rejected_dharamshalas.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_rejected_dharamshalas.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_rejected_dharamshalas.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_rejected_dharamshalas.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_rejected_dharamshalas.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_rejected_dharamshalas.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_rejected_dharamshalas.land_deed_type')
                ->where('mar_rejected_dharamshalas.id', $id)
                ->first();
        } elseif ($type == 'Approve') {
            $details = DB::table('mar_dharamshalas')
                ->select(
                    'mar_dharamshalas.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_dharamshalas.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', 'mar_dharamshalas.license_year')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_dharamshalas.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_dharamshalas.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_dharamshalas.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_dharamshalas.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_dharamshalas.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_dharamshalas.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_dharamshalas.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_dharamshalas.land_deed_type')
                ->where('mar_dharamshalas.id', $id)
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
        return MarActiveDharamshala::where('mar_active_dharamshalas.citizen_id', $citizenId)
            ->select(
                'mar_active_dharamshalas.id',
                'mar_active_dharamshalas.application_no',
                DB::raw("TO_CHAR(mar_active_dharamshalas.application_date, 'DD-MM-YYYY') as application_date"),
                'mar_active_dharamshalas.applicant',
                'mar_active_dharamshalas.entity_name',
                'mar_active_dharamshalas.entity_address',
                'mar_active_dharamshalas.doc_upload_status',
                'mar_active_dharamshalas.application_type',
                'mar_active_dharamshalas.parked',
                'um.ulb_name as ulb_name',
                'wr.role_name',
            )
            ->join('wf_roles as wr', 'wr.id', '=', 'mar_active_dharamshalas.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'mar_active_dharamshalas.ulb_id')
            ->orderByDesc('mar_active_dharamshalas.id')
            ->get();
    }

    /**
     * | Get Application Details By ID
     */
    public function getDharamshalaDetails($appId)
    {
        return MarActiveDharamshala::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get All Application According to ULBs
     */
    public function getDharamshalaList($ulbId)
    {
        return MarActiveDharamshala::select('*')
            ->where('mar_active_dharamshalas.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.DHARAMSHALA.RELATIVE_PATH');

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
        $mWfActiveDocument->postDocuments($a, $req->auth);
        $docDetails->current_status = '0';
        $docDetails->save();
        return $docDetails['active_id'];
    }


    /**
     * | Get Application Details For Update 
     */
    public function getApplicationDetailsForEdit($appId)
    {
        return MarActiveDharamshala::select(
            'mar_active_dharamshalas.*',
            'mar_active_dharamshalas.organization_type as organization_type_id',
            'mar_active_dharamshalas.land_deed_type as land_deed_type_id',
            'mar_active_dharamshalas.water_supply_type as water_supply_type_id',
            'mar_active_dharamshalas.electricity_type as electricity_type_id',
            'mar_active_dharamshalas.security_type as security_type_id',
            'mar_active_dharamshalas.no_of_rooms as noOfRooms',
            'mar_active_dharamshalas.no_of_beds as noOfBeds',
            'ly.string_parameter as license_year_name',
            'ot.string_parameter as organization_type_name',
            'ldt.string_parameter as land_deed_type_name',
            'wt.string_parameter as water_supply_type_name',
            'et.string_parameter as electricity_type_name',
            'st.string_parameter as security_type_name',
            'pw.ward_name as permanent_ward_name',
            'ew.ward_name as entity_ward_name',
            'rw.ward_name as residential_ward_name',
            'ulb.ulb_name',
            DB::raw("'Dharamshala' as headerTitle")
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_active_dharamshalas.license_year::int'))
            ->leftJoin('ulb_ward_masters as rw', 'rw.id', '=', DB::raw('mar_active_dharamshalas.residential_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', DB::raw('mar_active_dharamshalas.organization_type::int'))
            ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', DB::raw('mar_active_dharamshalas.land_deed_type::int'))
            ->leftJoin('ref_adv_paramstrings as wt', 'wt.id', '=', DB::raw('mar_active_dharamshalas.water_supply_type::int'))
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', DB::raw('mar_active_dharamshalas.electricity_type::int'))
            ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', DB::raw('mar_active_dharamshalas.security_type::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_dharamshalas.entity_ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_dharamshalas.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'mar_active_dharamshalas.ulb_id')
            ->where('mar_active_dharamshalas.id', $appId)->first();
    }

    /**
     * | update application details
     */
    public function updateApplication($req)
    {
        $mMarActiveDharamshala = MarActiveDharamshala::findorfail($req->applicationId);
        $mMarActiveDharamshala->remarks = $req->remarks;
        $mMarActiveDharamshala->organization_type = $req->organizationType;
        $mMarActiveDharamshala->land_deed_type = $req->landDeedType;
        $mMarActiveDharamshala->water_supply_type = $req->waterSupplyType;
        $mMarActiveDharamshala->electricity_type = $req->electricityType;
        $mMarActiveDharamshala->security_type = $req->securityType;
        $mMarActiveDharamshala->cctv_camera = $req->cctvCamera;
        $mMarActiveDharamshala->fire_extinguisher = $req->fireExtinguisher;
        $mMarActiveDharamshala->entry_gate = $req->entryGate;
        $mMarActiveDharamshala->exit_gate = $req->exitGate;
        $mMarActiveDharamshala->two_wheelers_parking = $req->twoWheelersParking;
        $mMarActiveDharamshala->four_wheelers_parking = $req->fourWheelersParking;
        $mMarActiveDharamshala->no_of_beds = $req->noOfBeds;
        $mMarActiveDharamshala->no_of_rooms = $req->noOfRooms;
        $mMarActiveDharamshala->floor_area = $req->floorArea;
        $mMarActiveDharamshala->save();
        // dd($mMarActiveBanquteHall);
        return $mMarActiveDharamshala;
    }


    /**
     * | Get Pending applications
     * | @param citizenId
     */
    public function allPendingList()
    {
        return MarActiveDharamshala::all();
    }


    /**
     * | Get Pending List For Application
     */
    public function pendingListForReport()
    {
        return MarActiveDharamshala::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type', 'ulb_id', 'license_year', DB::raw("'Active' as application_status"));
    }
}
