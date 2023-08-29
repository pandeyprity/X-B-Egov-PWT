<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrievanceSolvedApplicantion extends Model
{
    use HasFactory;

    /**
     * | Get the Solved Application using applicationId
     */
    public function getSolvedApplication($applicationId)
    {
        return GrievanceSolvedApplicantion::where('application_id', $applicationId);
    }

    /**
     * | Save the Approved Grievance Detials
     */
    public function saveGrievanceDetials($activeGrievance, $refDetails)
    {
        $now = Carbon::now();
        $mGrievanceSolvedApplicantion = new GrievanceSolvedApplicantion();
        $mGrievanceSolvedApplicantion->mobile_no                = $activeGrievance->mobile_no;
        $mGrievanceSolvedApplicantion->email                    = $activeGrievance->email;
        $mGrievanceSolvedApplicantion->applicant_name           = $activeGrievance->applicant_name;
        $mGrievanceSolvedApplicantion->uid                      = $activeGrievance->uid;
        $mGrievanceSolvedApplicantion->description              = $activeGrievance->description;
        $mGrievanceSolvedApplicantion->grievance_head           = $activeGrievance->grievance_head;
        $mGrievanceSolvedApplicantion->department               = $activeGrievance->department;
        $mGrievanceSolvedApplicantion->gender                   = $activeGrievance->gender;
        $mGrievanceSolvedApplicantion->disability               = $activeGrievance->disability;
        $mGrievanceSolvedApplicantion->address                  = $activeGrievance->address;
        $mGrievanceSolvedApplicantion->district_id              = $activeGrievance->district_id;
        $mGrievanceSolvedApplicantion->ulb_id                   = $activeGrievance->ulb_id;
        $mGrievanceSolvedApplicantion->ward_id                  = $activeGrievance->ward_id;
        $mGrievanceSolvedApplicantion->application_no           = $activeGrievance->application_no;
        $mGrievanceSolvedApplicantion->current_role             = $activeGrievance->current_role;
        $mGrievanceSolvedApplicantion->initiator_id             = $activeGrievance->initiator_id;
        $mGrievanceSolvedApplicantion->finisher_id              = $activeGrievance->finisher_id;
        $mGrievanceSolvedApplicantion->last_role_id             = $activeGrievance->last_role_id;
        $mGrievanceSolvedApplicantion->workflow_id              = $activeGrievance->workflow_id;
        $mGrievanceSolvedApplicantion->parked                   = $activeGrievance->parked;
        $mGrievanceSolvedApplicantion->is_escalate              = $activeGrievance->is_escalate;
        $mGrievanceSolvedApplicantion->escalate_by              = $activeGrievance->escalate_by;
        $mGrievanceSolvedApplicantion->in_inner_workflow        = $activeGrievance->in_inner_workflow;
        $mGrievanceSolvedApplicantion->doc_upload_status        = $activeGrievance->doc_upload_status;
        $mGrievanceSolvedApplicantion->doc_verify_status        = $activeGrievance->doc_verify_status;
        $mGrievanceSolvedApplicantion->inner_workflow_id        = $activeGrievance->inner_workflow_id;
        $mGrievanceSolvedApplicantion->is_doc                   = $activeGrievance->is_doc;
        $mGrievanceSolvedApplicantion->apply_date               = $activeGrievance->apply_date;
        $mGrievanceSolvedApplicantion->other_info               = $activeGrievance->other_info;
        $mGrievanceSolvedApplicantion->reopen_count             = $refDetails['reopenCount'];
        $mGrievanceSolvedApplicantion->application_id           = $activeGrievance->id;
        $mGrievanceSolvedApplicantion->approved_date            = $now;
        $mGrievanceSolvedApplicantion->ranking                  = 1;                                        // Static
        $mGrievanceSolvedApplicantion->approve_no               = $refDetails['approvalNo'];
        $mGrievanceSolvedApplicantion->user_id                  = $activeGrievance->user_id;
        $mGrievanceSolvedApplicantion->user_type                = $activeGrievance->user_type;
        $mGrievanceSolvedApplicantion->user_apply_through       = $activeGrievance->user_apply_through;
        $mGrievanceSolvedApplicantion->agency_approved_by       = $activeGrievance->agency_approved_by;
        $mGrievanceSolvedApplicantion->agency_approve_date      = $activeGrievance->agency_approve_date;
        $mGrievanceSolvedApplicantion->inner_wf_current_role    = $activeGrievance->inner_wf_current_role;
        $mGrievanceSolvedApplicantion->save();
        return $mGrievanceSolvedApplicantion->id;
    }

    /**
     * | Get list of Soleved Applications 
     */
    public function getWfSolvedGrievance()
    {
        return GrievanceSolvedApplicantion::select(
            'grievance_solved_applicantions.id',
            'grievance_solved_applicantions.mobile_no',
            'grievance_solved_applicantions.applicant_name',
            'grievance_solved_applicantions.application_no',
            'grievance_solved_applicantions.apply_date',
            'grievance_solved_applicantions.user_apply_through',
            'grievance_solved_applicantions.workflow_id',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name'
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'grievance_solved_applicantions.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'grievance_solved_applicantions.ulb_id')
            ->where('grievance_solved_applicantions.status', 1)
            ->orderByDesc('id');
    }

    /**
     * | 
     */
    public function getSolvedGriavanceDetails($moduleId)
    {
        return GrievanceSolvedApplicantion::select(
            'grievance_solved_applicantions.id',
            'grievance_solved_applicantions.mobile_no',
            'grievance_solved_applicantions.applicant_name',
            'grievance_solved_applicantions.application_no',
            'grievance_solved_applicantions.apply_date',
            'grievance_solved_applicantions.user_apply_through',
            'grievance_solved_applicantions.workflow_id',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("CONCAT('" . config('app.url') . "', '/', wf_active_documents.relative_path, '/', wf_active_documents.document) as full_url")
        )
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'grievance_solved_applicantions.application_id')
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_solved_applicantions.user_apply_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'grievance_solved_applicantions.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'grievance_solved_applicantions.ward_id')

            ->where('grievance_solved_applicantions.status', 1)
            ->where('in_inner_workflow', false)
            ->whereColumn('wf_active_documents.ulb_id', 'grievance_solved_applicantions.ulb_id')
            ->where('wf_active_documents.module_id', $moduleId)
            ->whereColumn('wf_active_documents.workflow_id', 'grievance_solved_applicantions.workflow_id')
            ->where('wf_active_documents.status', 1)
            ->orderByDesc('grievance_solved_applicantions.id');
    }

    /**
     * | Get solved grievance according to id
     */
    public function getSolvedGrievance($id)
    {
        return GrievanceSolvedApplicantion::where('id', $id);
    }

    /**
     * | Update the Status 
     */
    public function updateStatus($id, $status)
    {
        GrievanceSolvedApplicantion::where('id', $id)
            ->update([
                "status" => $status
            ]);
    }
}
