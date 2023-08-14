<?php

namespace App\Models\Markets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarRejectedBanquteHall extends Model
{
    use HasFactory;
 
     /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return MarRejectedBanquteHall::where('mar_rejected_banqute_halls.citizen_id', $citizenId)
            ->select(
                'mar_rejected_banqute_halls.id',
                'mar_rejected_banqute_halls.application_no',
                'mar_rejected_banqute_halls.application_date',
                'mar_rejected_banqute_halls.rejected_date',
                'um.ulb_name as ulb_name',
            )
            ->join('ulb_masters as um', 'um.id', '=', 'mar_rejected_banqute_halls.ulb_id')
            ->orderByDesc('mar_rejected_banqute_halls.id')
            ->get();
    }

    /**
     * | Get Rejected application list
     */
    public function rejectedApplication()
    {
        return MarRejectedBanquteHall::select(
                'id',
                'application_no',
                'application_date',
                'rejected_date',
                'ulb_id',
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Reject List For Report
     */
    public function rejectListForReport(){
        return MarRejectedBanquteHall::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule','hall_type', 'ulb_id','license_year','organization_type',DB::raw("'Reject' as application_status"));
    }
}
