<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvRejectedPrivateland extends Model
{
    use HasFactory;

    /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return AdvRejectedPrivateland::where('adv_rejected_privatelands.citizen_id', $citizenId)
            ->select(
                'adv_rejected_privatelands.id',
                'adv_rejected_privatelands.application_no',
                DB::raw("TO_CHAR(adv_rejected_privatelands.application_date, 'DD-MM-YYYY') as application_date"),
                'adv_rejected_privatelands.rejected_date',
                'um.ulb_name as ulb_name',
            )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_rejected_privatelands.ulb_id')
            ->orderByDesc('adv_rejected_privatelands.id')
            ->get();
    }

    /**
     * | Get Application Reject List by Login JSK
     */
    public function listJskRejectedApplication($userId)
    {
        return AdvRejectedPrivateland::where('user_id', $userId)
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
        return AdvRejectedPrivateland::select(
            'id',
            'application_no',
            DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            // 'entity_address',
            // 'old_application_no',
            // 'payment_status',
            'rejected_date',
            'ulb_id',
        )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Reject List For Report
     */
    public function rejectListForReport()
    {
        return AdvRejectedPrivateland::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'ulb_id', 'display_type', DB::raw("'Reject' as application_status"));
    }
}
