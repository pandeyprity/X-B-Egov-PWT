<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceReopenApplicantionDetail extends Model
{
    use HasFactory;

    /**
     * | Save the reopen Grievance Detials 
     */
    public function saveReopenDetails($request, $applicationDetails, $applicationNo)
    {
        $user = authUser($request);
        $now = Carbon::now();
        $mGrievanceReopenApplicantionDetail = new GrievanceReopenApplicantionDetail();
        $mGrievanceReopenApplicantionDetail->reason         = $request->reason;
        $mGrievanceReopenApplicantionDetail->remarks        = $request->remarks;
        $mGrievanceReopenApplicantionDetail->grievance_head = $applicationDetails->grievance_head;
        $mGrievanceReopenApplicantionDetail->department     = $applicationDetails->department;
        $mGrievanceReopenApplicantionDetail->application_no = $applicationNo;
        $mGrievanceReopenApplicantionDetail->reopen_by      = $user->id;
        $mGrievanceReopenApplicantionDetail->reopen_date    = $now;
        $mGrievanceReopenApplicantionDetail->user_type      = $user->user_type;
        $mGrievanceReopenApplicantionDetail->solved_id      = $applicationDetails->id;
        $mGrievanceReopenApplicantionDetail->workflow_id    = $applicationDetails->workflow_id;
        $mGrievanceReopenApplicantionDetail->save();
    }
}
