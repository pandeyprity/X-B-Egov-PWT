<?php

namespace App\Models\Markets;

use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\WfActiveDocument;
use App\Traits\WorkflowTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MarActiveLodge extends Model
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
     * | Make metarequest for store 
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
            'lodge_type' => $req->lodgeType,
            'holding_no' => $req->holdingNo,
            'trade_license_no' => $req->tradeLicenseNo,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'organization_type' => $req->organizationType,
            'land_deed_type' => $req->landDeedType,
            'mess_type' => $req->messType,
            'no_of_beds' => $req->noOfBeds,
            'no_of_rooms' => $req->noOfRooms,

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
            'rule' => $req->rule,
            'application_no' => $req->application_no,
        ];
    }

    // Store Application For Lodge(1)
    public function addNew($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.LODGE');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
        //  $mApplicationNo = ['application_no' => 'LODGE-' . random_int(100000, 999999)];                  // Generate Application No
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
        $tempId = MarActiveLodge::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments, $req->auth);

        return $req->application_no;
    }


    // Store Application Foe Lodge(1)
    public function renewApplication($req)
    {
        $bearerToken = $req->bearerToken();
        // $workflowId = Config::get('workflow-constants.LODGE');                            // 350
        $ulbWorkflows = $this->getUlbWorkflowId($bearerToken, $req->ulbId, $req->WfMasterId);
        $ulbWorkflows = $ulbWorkflows['data'];
        $mRenewNo = ['renew_no' => 'LODGE/REN-' . random_int(100000, 999999)];                  // Generate Renewal No
        $details = MarLodge::find($req->applicationId);                              // Find Previous Application No
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
        $tempId = MarActiveLodge::create($metaReqs)->id;
        $this->uploadDocument($tempId, $mDocuments,$req->auth);

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
        $mMarActiveLodge = new MarActiveLodge();
        $relativePath = Config::get('constants.LODGE.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($tempId, $docUpload, $mWfActiveDocument, $mMarActiveLodge, $relativePath, $auth) {
            $metaReqs = array();
            $getApplicationDtls = $mMarActiveLodge->getApplicationDtls($tempId);
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
     * | Get Application details by Id
     */
    public function getApplicationDtls($appId)
    {

        return MarActiveLodge::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get Application Inbox List by Role Ids
     * | @param roleIds $roleIds
     */
    public function listInbox($roleIds, $ulbId)
    {
        $inbox = DB::table('mar_active_lodges')
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
        $outbox = DB::table('mar_active_lodges')
            ->select(
                'id',
                'application_no',
                'application_date',
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
     * | @param Application id
     */
    public function getDetailsById($id, $type = NULL)
    {
        $details = array();
        if ($type == 'Active' || $type == NULL) {
            $details = DB::table('mar_active_lodges')
                ->select(
                    'mar_active_lodges.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'lt.string_parameter as lodgetype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_active_lodges.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', DB::raw('mar_active_lodges.license_year'))
                ->leftJoin('ref_adv_paramstrings as lt', 'lt.id', '=', 'mar_active_lodges.lodge_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_active_lodges.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_active_lodges.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_lodges.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_lodges.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_active_lodges.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_active_lodges.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_active_lodges.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_active_lodges.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_active_lodges.land_deed_type')
                ->where('mar_active_lodges.id', $id)
                ->first();
        } elseif ($type == 'Reject') {
            // $details = DB::table('mar_rejected_lodges')
            $details = DB::table('mar_rejected_lodges')
                ->select(
                    'mar_rejected_lodges.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'lt.string_parameter as lodgetype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_rejected_lodges.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', DB::raw('mar_rejected_lodges.license_year'))
                ->leftJoin('ref_adv_paramstrings as lt', 'lt.id', '=', 'mar_rejected_lodges.lodge_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_rejected_lodges.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_rejected_lodges.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_rejected_lodges.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_rejected_lodges.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_rejected_lodges.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_rejected_lodges.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_rejected_lodges.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_rejected_lodges.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_rejected_lodges.land_deed_type')
                ->where('mar_rejected_lodges.id', $id)
                ->first();
        } elseif ($type == 'Approve') {
            // $details = DB::table('mar_lodges')
            $details = DB::table('mar_lodges')
                ->select(
                    'mar_lodges.*',
                    'u.ulb_name',
                    'p.string_parameter as m_license_year',
                    'w.ward_name as ward_no',
                    'pw.ward_name as permanent_ward_no',
                    'ew.ward_name as entity_ward_no',
                    'lt.string_parameter as lodgetype',
                    'mt.string_parameter as messtype',
                    'ot.string_parameter as organizationtype',
                    'st.string_parameter as securitytype',
                    'et.string_parameter as electricitytype',
                    'wst.string_parameter as watersupplytype',
                    'ldt.string_parameter as landDeedType',
                )
                ->leftJoin('ulb_masters as u', 'u.id', '=', 'mar_lodges.ulb_id')
                ->leftJoin('ref_adv_paramstrings as p', 'p.id', '=', DB::raw('mar_lodges.license_year'))
                ->leftJoin('ref_adv_paramstrings as lt', 'lt.id', '=', 'mar_lodges.lodge_type')
                ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', 'mar_lodges.mess_type')
                ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'mar_lodges.residential_ward_id')
                ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_lodges.permanent_ward_id')
                ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_lodges.entity_ward_id')
                ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', 'mar_lodges.organization_type')
                ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', 'mar_lodges.security_type')
                ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'mar_lodges.electricity_type')
                ->leftJoin('ref_adv_paramstrings as wst', 'wst.id', '=', 'mar_lodges.water_supply_type')
                ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', 'mar_lodges.land_deed_type')
                ->where('mar_lodges.id', $id)
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
        return MarActiveLodge::where('mar_active_lodges.citizen_id', $citizenId)
            ->select(
                'mar_active_lodges.id',
                'mar_active_lodges.application_no',
                DB::raw("TO_CHAR(mar_active_lodges.application_date, 'DD-MM-YYYY') as application_date"),
                'mar_active_lodges.applicant',
                'mar_active_lodges.entity_name',
                'mar_active_lodges.entity_address',
                'mar_active_lodges.doc_upload_status',
                'mar_active_lodges.application_type',
                'mar_active_lodges.parked',
                'um.ulb_name as ulb_name',
                'wr.role_name',
            )
            ->join('wf_roles as wr', 'wr.id', '=', 'mar_active_lodges.current_role_id')
            ->join('ulb_masters as um', 'um.id', '=', 'mar_active_lodges.ulb_id')
            ->orderByDesc('mar_active_lodges.id')
            ->get();
    }

    /**
     * | Get Application details by Application Id
     */
    public function getLodgeDetails($appId)
    {
        return MarActiveLodge::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * | Get All application List Ulb Wise
     */
    public function getLodgeList($ulbId)
    {
        return MarActiveLodge::select('*')
            ->where('mar_active_lodges.ulb_id', $ulbId);
    }

    /**
     * | Reupload Documents
     */
    public function reuploadDocument($req)
    {
        $docUpload = new DocumentUpload;
        $docDetails = WfActiveDocument::find($req->id);
        $relativePath = Config::get('constants.LODGE.RELATIVE_PATH');

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
     * | Get Application Details For Update 
     */
    public function getApplicationDetailsForEdit($appId)
    {
        return MarActiveLodge::select(
            'mar_active_lodges.*',
            'mar_active_lodges.lodge_type as lodge_type_id',
            'mar_active_lodges.organization_type as organization_type_id',
            'mar_active_lodges.land_deed_type as land_deed_type_id',
            'mar_active_lodges.mess_type as mess_type_id',
            'mar_active_lodges.water_supply_type as water_supply_type_id',
            'mar_active_lodges.electricity_type as electricity_type_id',
            'mar_active_lodges.security_type as security_type_id',
            'mar_active_lodges.no_of_rooms as noOfRooms',
            'mar_active_lodges.no_of_beds as noOfBeds',
            'ly.string_parameter as license_year_name',
            'lt.string_parameter as lodge_type_name',
            'ot.string_parameter as organization_type_name',
            'ldt.string_parameter as land_deed_type_name',
            'mt.string_parameter as mess_type_name',
            'wt.string_parameter as water_supply_type_name',
            'et.string_parameter as electricity_type_name',
            'st.string_parameter as security_type_name',
            'pw.ward_name as permanent_ward_name',
            'ew.ward_name as entity_ward_name',
            'rw.ward_name as residential_ward_name',
            'ulb.ulb_name',
            DB::raw("'Lodge' as headerTitle")
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_active_lodges.license_year::int'))
            ->leftJoin('ulb_ward_masters as rw', 'rw.id', '=', DB::raw('mar_active_lodges.residential_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as lt', 'lt.id', '=', DB::raw('mar_active_lodges.lodge_type::int'))
            ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', DB::raw('mar_active_lodges.organization_type::int'))
            ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', DB::raw('mar_active_lodges.land_deed_type::int'))
            ->leftJoin('ref_adv_paramstrings as mt', 'mt.id', '=', DB::raw('mar_active_lodges.mess_type::int'))
            ->leftJoin('ref_adv_paramstrings as wt', 'wt.id', '=', DB::raw('mar_active_lodges.water_supply_type::int'))
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', DB::raw('mar_active_lodges.electricity_type::int'))
            ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', DB::raw('mar_active_lodges.security_type::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_active_lodges.entity_ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_active_lodges.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'mar_active_lodges.ulb_id')
            ->where('mar_active_lodges.id', $appId)->first();
    }

    /**
     * | Update or edit applictaion
     */
    public function updateApplication($req)
    {
        $mMarActiveLodge = MarActiveLodge::findorfail($req->applicationId);
        $mMarActiveLodge->remarks = $req->remarks;
        $mMarActiveLodge->organization_type = $req->organizationType;
        $mMarActiveLodge->land_deed_type = $req->landDeedType;
        $mMarActiveLodge->water_supply_type = $req->waterSupplyType;
        $mMarActiveLodge->electricity_type = $req->electricityType;
        $mMarActiveLodge->security_type = $req->securityType;
        $mMarActiveLodge->cctv_camera = $req->cctvCamera;
        $mMarActiveLodge->fire_extinguisher = $req->fireExtinguisher;
        $mMarActiveLodge->entry_gate = $req->entryGate;
        $mMarActiveLodge->exit_gate = $req->exitGate;
        $mMarActiveLodge->two_wheelers_parking = $req->twoWheelersParking;
        $mMarActiveLodge->four_wheelers_parking = $req->fourWheelersParking;
        $mMarActiveLodge->no_of_beds = $req->noOfBeds;
        $mMarActiveLodge->no_of_rooms = $req->noOfRooms;
        $mMarActiveLodge->save();
        // dd($mMarActiveBanquteHall);
        return $mMarActiveLodge;
    }

    /**
     * | Get Pending applications
     * | @param citizenId
     */
    public function allPendingList()
    {
        return MarActiveLodge::all();
    }

    /**
     * | Get Pending List For Report
     */
    public function pendingListForReport()
    {
        return MarActiveLodge::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type', 'lodge_type', 'license_year', 'ulb_id', DB::raw("'Active' as application_status"));
    }
}
