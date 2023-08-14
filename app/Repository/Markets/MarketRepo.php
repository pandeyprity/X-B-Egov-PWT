<?php

namespace App\Repository\Markets;

use App\Repository\Markets\iMarketRepo;
use Illuminate\Support\Facades\DB;

/**
 * | Repository for the Markets
 * | Created On-13-02-2023
 * | Created By-Bikash Kumar
 */

class MarketRepo implements iMarketRepo
{
    public function specialInbox($workflowIds){
        $specialInbox = DB::table('mar_active_banqute_halls')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address'
            )
            ->orderByDesc('id');
            // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialInboxHostel($workflowIds){
        $specialInbox = DB::table('mar_active_hostels')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address'
            )
            ->orderByDesc('id');
            // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialInboxLodge($workflowIds){
        $specialInbox = DB::table('mar_active_lodges')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address'
            )
            ->orderByDesc('id');
            // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }

    public function specialInboxmDharamshala($workflowIds){
        $specialInbox = DB::table('mar_active_dharamshalas')
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address'
            )
            ->orderByDesc('id');
            // ->whereIn('workflow_id', $workflowIds);
        return $specialInbox;
    }



}
