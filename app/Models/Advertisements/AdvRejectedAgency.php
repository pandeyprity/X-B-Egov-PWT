<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvRejectedAgency extends Model
{
    use HasFactory;

    /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return AdvRejectedAgency::where('citizen_id', $citizenId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'entity_name',
                // 'entity_address',
                // 'old_application_no',
                // 'payment_status',
                'rejected_date',
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Application Reject List by Login JSK
     */
    public function listJskRejectedApplication($userId)
    {
        return AdvRejectedAgency::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                // 'entity_address',
                // 'old_application_no',
                // 'payment_status',
                'rejected_date',
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get All Application Reject List
     */
    public function rejectedApplication()
    {
        return AdvRejectedAgency::select(
            'id',
            'application_no',
            DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            'entity_name',
            'ulb_id',
            'rejected_date',
        )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Reject List For Report
     */
    public function rejectListForReport()
    {
        return AdvRejectedAgency::select('id', 'application_no', 'entity_name', 'application_date', 'application_type', 'ulb_id', DB::raw("'Reject' as application_status"));
    }
    
}
