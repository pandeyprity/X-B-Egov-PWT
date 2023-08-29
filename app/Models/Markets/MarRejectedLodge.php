<?php

namespace App\Models\Markets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarRejectedLodge extends Model
{
    use HasFactory;

    /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return MarRejectedLodge::where('mar_rejected_lodges.citizen_id', $citizenId)
            ->select(
                'mar_rejected_lodges.id',
                'mar_rejected_lodges.application_no',
                'mar_rejected_lodges.application_date',
                'mar_rejected_lodges.entity_address',
                'mar_rejected_lodges.rejected_date',
                'mar_rejected_lodges.citizen_id',
                'um.ulb_name as ulb_name',
            )
            ->join('ulb_masters as um', 'um.id', '=', 'mar_rejected_lodges.ulb_id')
            ->orderByDesc('mar_rejected_lodges.id')
            ->get();
    }

    /**
     * | Get All Application Reject List
     */
    public function rejectedApplication()
    {
        return MarRejectedLodge::select(
            'id',
            'application_no',
            'application_date',
            'entity_address',
            'rejected_date',
            'citizen_id',
            'ulb_id',
        )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Rejected List For Report
     */
    public function rejectedListForReport()
    {
        return MarRejectedLodge::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type', 'lodge_type', 'license_year', 'ulb_id', DB::raw("'Reject' as application_status"));
    }
}
