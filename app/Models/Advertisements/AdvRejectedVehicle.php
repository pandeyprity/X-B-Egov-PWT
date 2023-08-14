<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvRejectedVehicle extends Model
{
    use HasFactory;

    /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return AdvRejectedVehicle::where('adv_rejected_vehicles.citizen_id', $citizenId)
            ->select(
                'adv_rejected_vehicles.id',
                'adv_rejected_vehicles.application_no',
                DB::raw("TO_CHAR(adv_rejected_vehicles.application_date, 'DD-MM-YYYY') as application_date"),
                'adv_rejected_vehicles.applicant',
                'adv_rejected_vehicles.entity_name',
                'adv_rejected_vehicles.rejected_date',
                'um.ulb_name as ulb_name',
            )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_rejected_vehicles.ulb_id')
            ->orderByDesc('adv_rejected_vehicles.id')
            ->get();
    }

    /**
     * | Get Application Reject List by Login JSK
     */
    public function listJskRejectedApplication($userId)
    {
        return AdvRejectedVehicle::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
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
     * | Get All Application Reject List
     */
    public function rejectedApplication()
    {
        return AdvRejectedVehicle::select(
            'id',
            'application_no',
            DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            'applicant',
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
        return AdvRejectedVehicle::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'ulb_id', DB::raw("'Reject' as application_status"));
    }
}
