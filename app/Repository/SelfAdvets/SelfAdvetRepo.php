<?php

namespace App\Repository\SelfAdvets;

use App\Models\Advertisements\AdvActiveSelfadvertisement;
use App\Repository\SelfAdvets\iSelfAdvetRepo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * | Repository for the Self Advertisements
 * | Created On-15-12-2022 
 * | Created By-Anshu Kumar
 */

class SelfAdvetRepo implements iSelfAdvetRepo
{
    public function specialInbox($workflowIds)
    {
        $specialInbox = DB::table('adv_active_selfadvertisements')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'application_type',
                'payment_status',
                'workflow_id',
                'ward_id'
            )
            ->orderByDesc('id');
        // ->where('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialVehicleInbox($workflowIds)
    {
        $specialInbox = DB::table('adv_active_vehicles')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY') as application_date"),
                'applicant',
                'entity_name',
                'application_type',
            )
            ->orderByDesc('id');
        // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }


    public function specialAgencyInbox($workflowIds)
    {
        $specialInbox = DB::table('adv_active_agencies')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY') as application_date"),
                'entity_name',
                'application_type',
            )
            ->orderByDesc('id');
        // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialPrivateLandInbox($workflowIds)
    {
        $specialInbox = DB::table('adv_active_privatelands')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY') as application_date"),
                'entity_name',
                'application_type',
            )
            ->orderByDesc('id');
        // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialAgencyLicenseInbox($workflowIds)
    {
        $specialInbox = DB::table('adv_active_agency_licenses')
            ->select(
                'id',
                'application_no',
                'license_no',
                DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY') as application_date"),
                'license_no',
                'bank_name',
                'account_no',
                'ifsc_code',
                'total_charge'
            )
            ->orderByDesc('id');
        // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }
}
