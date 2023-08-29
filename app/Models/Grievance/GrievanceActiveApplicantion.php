<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrievanceActiveApplicantion extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Save the grievance request  
     */
    public function saveGrievanceDetails($req, $refRequest)
    {
        $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
        $mGrievanceActiveApplicantion->mobile_no            = $req->mobileNo;
        $mGrievanceActiveApplicantion->email                = $req->email;
        $mGrievanceActiveApplicantion->applicant_name       = $req->applicantName;
        $mGrievanceActiveApplicantion->uid                  = $req->aadhar;
        $mGrievanceActiveApplicantion->description          = $req->description;
        $mGrievanceActiveApplicantion->grievance_head       = $req->grievanceHead;
        $mGrievanceActiveApplicantion->department           = $req->department;
        $mGrievanceActiveApplicantion->gender               = $req->gender;
        $mGrievanceActiveApplicantion->disability           = $req->disability;
        $mGrievanceActiveApplicantion->address              = $req->address;
        $mGrievanceActiveApplicantion->district_id          = $req->districtId;
        $mGrievanceActiveApplicantion->ulb_id               = $req->ulbId;
        $mGrievanceActiveApplicantion->ward_id              = $req->wardId;
        $mGrievanceActiveApplicantion->other_info           = $req->otherInfo;
        $mGrievanceActiveApplicantion->user_apply_through   = $req->applyThrough ?? $refRequest['applyThrough'];
        $mGrievanceActiveApplicantion->application_no       = $refRequest['applicationNo'];
        $mGrievanceActiveApplicantion->initiator_id         = $refRequest['initiatorRoleId'];
        $mGrievanceActiveApplicantion->finisher_id          = $refRequest['finisherRoleId'];
        $mGrievanceActiveApplicantion->workflow_id          = $refRequest['workflowId'];
        $mGrievanceActiveApplicantion->is_doc               = $req->isDoc ?? false;                                // Static
        $mGrievanceActiveApplicantion->apply_date           = Carbon::now();                        // Static
        $mGrievanceActiveApplicantion->user_id              = $refRequest['userId'];
        $mGrievanceActiveApplicantion->user_type            = $refRequest['userType'];
        $mGrievanceActiveApplicantion->reopen_count         = $refRequest['initiatorRoleId'] ?? 0;
        $mGrievanceActiveApplicantion->current_role         = $mGrievanceActiveApplicantion->save();
        return [
            "id" => $mGrievanceActiveApplicantion->id,
        ];
    }

    /**
     * | Get the active aplication list 
     */
    public function getActiveGrievance($applicationNo, $mobileNo)
    {
        return GrievanceActiveApplicantion::where('application_no', $applicationNo)
            ->where('mobile_no', $mobileNo)
            ->orderByDesc('id');
    }

    /**
     * | Get application details by id
     */
    public function getActiveGrievanceById($id)
    {
        return GrievanceActiveApplicantion::where('id', $id)
            ->where('status', 1);
    }

    /**
     * | Save the doc status 
     */
    public function updateDocStatus($applicationId, $status)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'is_doc' => $status
            ]);
    }

    /**
     * | Save the doc verify status
     */
    public function updateAppliVerifyStatus($applicationId, $status)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'doc_verify_status' => $status
            ]);
    }

    /**
     * | Grievance Detial 
     */
    public function getGrievanceFullDetails($applicationId, $database)
    {
        return DB::table($database)
            ->select($database . '.*', 'ulb_masters.ulb_name', 'ulb_ward_masters.ward_name')
            // ->leftJoin('wf_roles', 'wf_roles.id', '=', $database . '.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', $database . '.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', $database . '.ulb_id')
            ->where($database . '.id', $applicationId)
            ->where($database . '.status', 1);
    }

    /**
     * | Delete the applications record
        | Caution
     */
    public function deleteRecord($applicationId)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->delete();
    }

    /**
     * | Update the current role details in active table
     */
    public function updateCurrentRole($applicationId, $roleId)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'current_role' => $roleId
            ]);
    }


    /**
     * | Get list of grievance that are not in workflow
     */
    public function getGriavanceDetails($moduleId)
    {
        return GrievanceActiveApplicantion::select(
            'grievance_active_applicantions.id',
            'grievance_active_applicantions.mobile_no',
            'grievance_active_applicantions.applicant_name',
            'grievance_active_applicantions.application_no',
            'grievance_active_applicantions.apply_date',
            'grievance_active_applicantions.user_apply_through',
            'grievance_active_applicantions.workflow_id',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("CONCAT('" . config('app.url') . "', '/', wf_active_documents.relative_path, '/', wf_active_documents.document) as full_url")
        )
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'grievance_active_applicantions.id')
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_active_applicantions.user_apply_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'grievance_active_applicantions.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'grievance_active_applicantions.ward_id')

            ->where('grievance_active_applicantions.status', 1)
            ->where('in_inner_workflow', false)
            ->whereColumn('wf_active_documents.ulb_id', 'grievance_active_applicantions.ulb_id')
            ->where('wf_active_documents.module_id', $moduleId)
            ->whereColumn('wf_active_documents.workflow_id', 'grievance_active_applicantions.workflow_id')
            ->where('wf_active_documents.status', 1)
            ->orderByDesc('grievance_active_applicantions.id');
    }


    /**
     * | Search grievance list for Agency
     */
    public function searchActiveGrievance()
    {
        return GrievanceActiveApplicantion::select(
            'grievance_active_applicantions.id',
            'grievance_active_applicantions.mobile_no',
            'grievance_active_applicantions.applicant_name',
            'grievance_active_applicantions.application_no',
            'grievance_active_applicantions.apply_date',
            'grievance_active_applicantions.user_apply_through',
            'grievance_active_applicantions.inner_workflow_id',
            'grievance_active_applicantions.workflow_id',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_active_applicantions.workflow_id) as workflow_mstr_id"),
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_active_applicantions.inner_workflow_id) as inner_workflow_mstr_id")
        )
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_active_applicantions.user_apply_through')
            ->where('grievance_active_applicantions.status', 1);
    }


    /**
     * | Save the Grievance to the associated wf
     */
    public function saveGrievanceInAssociatedWf($refApplication, $database, $refMetaReq)
    {
        $now = Carbon::now();
        DB::table($database)->insert([
            "mobile_no"             => $refApplication->mobile_no,
            "email"                 => $refApplication->email,
            "applicant_name"        => $refApplication->applicant_name,
            "uid"                   => $refApplication->uid,
            "created_at"            => $now,
            "updated_at"            => $now,
            "description"           => $refApplication->description,
            "grievance_head"        => $refApplication->grievance_head,
            "department"            => $refApplication->department,
            "gender"                => $refApplication->gender,
            "disability"            => $refApplication->disability,
            "address"               => $refApplication->address,
            "district_id"           => $refApplication->district_id,
            "ulb_id"                => $refApplication->ulb_id,
            "ward_id"               => $refApplication->ward_id,
            "application_no"        => $refApplication->application_no,
            "current_role"          => $refMetaReq['initiatorRoleId'],
            "initiator_id"          => $refMetaReq['initiatorRoleId'],
            "finisher_id"           => $refMetaReq['finisherRoleId'],
            "workflow_id"           => $refMetaReq['workflowId'],
            "doc_upload_status"     => $refApplication->doc_upload_status,
            "is_doc"                => $refApplication->is_doc,
            "apply_date"            => $refApplication->apply_date,
            "other_info"            => $refApplication->other_info,
            "user_id"               => $refApplication->user_id,
            "user_type"             => $refApplication->user_type,
            "user_apply_through"    => $refApplication->user_apply_through,
            "agency_approved_by"    => $refApplication->agency_approved_by,
            "agency_approve_date"   => $refApplication->agency_approve_date,
            "wf_send_by"            => $refMetaReq['userId'],
            "wf_send_by_role"       => $refMetaReq['senderRoleId'],
            "wf_send_by_date"       => $now,
            "reopen_count"          => $refApplication->reopen_count,
            "parent_wf_id"          => $refApplication->workflow_id,
        ]);
    }

    /**
     * | Update the Parent application 
     */
    public function updateParentAppForInnerWf($request, $wfDatabaseDetial, $refUlbWorkflowId, $refMetaReq)
    {
        DB::table($wfDatabaseDetial['databaseType'])
            ->where($wfDatabaseDetial['databaseType'] . '.id', $request->applicationId)
            ->where($wfDatabaseDetial['databaseType'] . '.status', 1)
            ->update([
                'in_inner_workflow'     => true,
                'inner_workflow_id'     => $refUlbWorkflowId,
                'inner_wf_current_role' => $refMetaReq['initiatorRoleId']
            ]);
    }

    /**
     * | Save the agency edited data in the citizen application
     */
    public function editCitizenGrievance($request)
    {
        $mGrievanceActiveApplicantion = GrievanceActiveApplicantion::findorfail($request->id);
        $mGrievanceActiveApplicantion->mobile_no      =  $request->mobileNo      ?? $mGrievanceActiveApplicantion->mobile_no;
        $mGrievanceActiveApplicantion->email          =  $request->email         ?? $mGrievanceActiveApplicantion->email;
        $mGrievanceActiveApplicantion->applicant_name =  $request->applicantName ?? $mGrievanceActiveApplicantion->applicant_name;
        $mGrievanceActiveApplicantion->uid            =  $request->uid           ?? $mGrievanceActiveApplicantion->uid;
        $mGrievanceActiveApplicantion->description    =  $request->description   ?? $mGrievanceActiveApplicantion->description;
        $mGrievanceActiveApplicantion->grievance_head =  $request->grievanceHead ?? $mGrievanceActiveApplicantion->grievance_head;
        $mGrievanceActiveApplicantion->department     =  $request->department    ?? $mGrievanceActiveApplicantion->department;
        $mGrievanceActiveApplicantion->gender         =  $request->gender        ?? $mGrievanceActiveApplicantion->gender;
        $mGrievanceActiveApplicantion->disability     =  $request->disability    ?? $mGrievanceActiveApplicantion->disability;
        $mGrievanceActiveApplicantion->address        =  $request->address       ?? $mGrievanceActiveApplicantion->address;
        $mGrievanceActiveApplicantion->save();
    }

    /**
     * | Update the parent Wf inner worflow status
        | Use the application id
     */
    public function updateWfParent($applicationNo)
    {
        GrievanceActiveApplicantion::where('application_no', $applicationNo)
            ->where('in_inner_workflow', true)
            ->where('status', 1)
            ->update([
                "in_inner_workflow" => false,
                "inner_workflow_id" => null
            ]);
    }

    /**
     * | Save the associated wf status for back to parent workflow
        | Check
     */
    public function updateAssociatedDbStatus($database, $request)
    {
        return DB::table($database)
            ->where($database . '.id', $request->applicationId)
            ->where($database . '.status', 1)
            ->update([
                "status" => $request->status
            ]);
    }
}
