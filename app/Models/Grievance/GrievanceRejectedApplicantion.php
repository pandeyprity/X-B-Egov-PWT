<?php

namespace App\Models\Grievance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrievanceRejectedApplicantion extends Model
{
    use HasFactory;

    /**
     * | Get rejected grievance according to id 
     */
    public function getGrievanceById($applicationId)
    {
        return GrievanceRejectedApplicantion::where('id', $applicationId);
    }

    /**
     * | get the rejected grievance details 
     */
    public function rejectedGrievanceFullDetails($moduleId)
    {
        return GrievanceRejectedApplicantion::select(
            'grievance_rejected_applicantions.id',
            'grievance_rejected_applicantions.mobile_no',
            'grievance_rejected_applicantions.applicant_name',
            'grievance_rejected_applicantions.application_no',
            'grievance_rejected_applicantions.apply_date',
            'grievance_rejected_applicantions.user_apply_through',
            'grievance_rejected_applicantions.workflow_id',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("CONCAT('" . config('app.url') . "', '/', wf_active_documents.relative_path, '/', wf_active_documents.document) as full_url")
        )
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'grievance_rejected_applicantions.id')
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_rejected_applicantions.user_apply_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'grievance_rejected_applicantions.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'grievance_rejected_applicantions.ward_id')

            ->where('grievance_rejected_applicantions.status', 1)
            ->whereColumn('wf_active_documents.ulb_id', 'grievance_rejected_applicantions.ulb_id')
            ->where('wf_active_documents.module_id', $moduleId)
            ->whereColumn('wf_active_documents.workflow_id', 'grievance_rejected_applicantions.workflow_id')
            ->where('wf_active_documents.status', 1)
            ->orderByDesc('grievance_rejected_applicantions.id');
    }


    /**
     * | Search the Rejecetd Application 
     */
    public function searchRejectedGrievance()
    {
        return GrievanceRejectedApplicantion::select(
            'grievance_rejected_applicantions.id',
            'grievance_rejected_applicantions.mobile_no',
            'grievance_rejected_applicantions.applicant_name',
            'grievance_rejected_applicantions.application_no',
            'grievance_rejected_applicantions.apply_date',
            'grievance_rejected_applicantions.user_apply_through',
            'grievance_rejected_applicantions.inner_workflow_id',
            'grievance_rejected_applicantions.workflow_id',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_rejected_applicantions.workflow_id) as workflow_mstr_id"),
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_rejected_applicantions.inner_workflow_id) as inner_workflow_mstr_id")
        )
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_rejected_applicantions.user_apply_through')
            ->where('grievance_rejected_applicantions.status', 1);
    }
}
