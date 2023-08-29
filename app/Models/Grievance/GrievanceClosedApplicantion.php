<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceClosedApplicantion extends Model
{
    use HasFactory;

    /**
     * | Save the Closed Grievance details 
     */
    public function saveClosedGrievance($solvedGrievanceDetails, $request)
    {
        $now = Carbon::now();
        $mGrievanceClosedApplicantion = new GrievanceClosedApplicantion();
        $mGrievanceClosedApplicantion->mobile_no                = $solvedGrievanceDetails->mobile_no;
        $mGrievanceClosedApplicantion->email                    = $solvedGrievanceDetails->email;
        $mGrievanceClosedApplicantion->applicant_name           = $solvedGrievanceDetails->applicant_name;
        $mGrievanceClosedApplicantion->uid                      = $solvedGrievanceDetails->uid;
        $mGrievanceClosedApplicantion->created_at               = $now;
        $mGrievanceClosedApplicantion->updated_at               = $now;
        $mGrievanceClosedApplicantion->description              = $solvedGrievanceDetails->description;
        $mGrievanceClosedApplicantion->grievance_head           = $solvedGrievanceDetails->grievance_head;
        $mGrievanceClosedApplicantion->department               = $solvedGrievanceDetails->department;
        $mGrievanceClosedApplicantion->gender                   = $solvedGrievanceDetails->gender;
        $mGrievanceClosedApplicantion->disability               = $solvedGrievanceDetails->disability;
        $mGrievanceClosedApplicantion->address                  = $solvedGrievanceDetails->address;
        $mGrievanceClosedApplicantion->district_id              = $solvedGrievanceDetails->district_id;
        $mGrievanceClosedApplicantion->ulb_id                   = $solvedGrievanceDetails->ulb_id;
        $mGrievanceClosedApplicantion->ward_id                  = $solvedGrievanceDetails->ward_id;
        $mGrievanceClosedApplicantion->application_no           = $solvedGrievanceDetails->application_no;
        $mGrievanceClosedApplicantion->current_role             = $solvedGrievanceDetails->current_role;
        $mGrievanceClosedApplicantion->initiator_id             = $solvedGrievanceDetails->initiator_id;
        $mGrievanceClosedApplicantion->finisher_id              = $solvedGrievanceDetails->finisher_id;
        $mGrievanceClosedApplicantion->last_role_id             = $solvedGrievanceDetails->last_role_id;
        $mGrievanceClosedApplicantion->workflow_id              = $solvedGrievanceDetails->workflow_id;
        $mGrievanceClosedApplicantion->parked                   = $solvedGrievanceDetails->parked;
        $mGrievanceClosedApplicantion->is_escalate              = $solvedGrievanceDetails->is_escalate;
        $mGrievanceClosedApplicantion->escalate_by              = $solvedGrievanceDetails->escalate_by;
        $mGrievanceClosedApplicantion->in_inner_workflow        = $solvedGrievanceDetails->in_inner_workflow;
        $mGrievanceClosedApplicantion->doc_upload_status        = $solvedGrievanceDetails->doc_upload_status;
        $mGrievanceClosedApplicantion->doc_verify_status        = $solvedGrievanceDetails->doc_verify_status;
        $mGrievanceClosedApplicantion->inner_workflow_id        = $solvedGrievanceDetails->inner_workflow_id;
        $mGrievanceClosedApplicantion->is_doc                   = $solvedGrievanceDetails->is_doc;
        $mGrievanceClosedApplicantion->apply_date               = $solvedGrievanceDetails->apply_date;
        $mGrievanceClosedApplicantion->other_info               = $solvedGrievanceDetails->other_info;
        $mGrievanceClosedApplicantion->reopen_count             = $solvedGrievanceDetails->reopen_count;
        $mGrievanceClosedApplicantion->application_id           = $solvedGrievanceDetails->application_id;
        $mGrievanceClosedApplicantion->ranking                  = $solvedGrievanceDetails->ranking;
        $mGrievanceClosedApplicantion->approve_no               = $solvedGrievanceDetails->approve_no;
        $mGrievanceClosedApplicantion->approved_date            = $solvedGrievanceDetails->approved_date;
        $mGrievanceClosedApplicantion->user_id                  = $solvedGrievanceDetails->user_id;
        $mGrievanceClosedApplicantion->user_type                = $solvedGrievanceDetails->user_type;
        $mGrievanceClosedApplicantion->user_apply_through       = $solvedGrievanceDetails->user_apply_through;
        $mGrievanceClosedApplicantion->agency_approved_by       = $solvedGrievanceDetails->agency_approved_by;
        $mGrievanceClosedApplicantion->agency_approve_date      = $solvedGrievanceDetails->agency_approve_date;
        $mGrievanceClosedApplicantion->inner_wf_current_role    = $solvedGrievanceDetails->inner_wf_current_role;
        $mGrievanceClosedApplicantion->remarks                  = $solvedGrievanceDetails->remarks;
        $mGrievanceClosedApplicantion->agency_closed_by         = $request->userId;
        $mGrievanceClosedApplicantion->agency_closed_date       = $now;
        $mGrievanceClosedApplicantion->agency_closed_rank       = $request->rank ?? 1;
        $mGrievanceClosedApplicantion->ref_solved_id            = $solvedGrievanceDetails->id;
        $mGrievanceClosedApplicantion->agency_closed_remarks    = $request->remarks;
        $mGrievanceClosedApplicantion->save();
    }

    /**
     * | Get Closed Grievance according to the solved ref id
     */
    public function getClosedGrievnaceByRefId($solvedId)
    {
        return GrievanceClosedApplicantion::where('ref_solved_id', $solvedId);
    }
}
