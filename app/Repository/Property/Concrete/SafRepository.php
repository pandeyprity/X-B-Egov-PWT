<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Support\Facades\DB;

/**
 * | Repository for SAF
 */
class SafRepository implements iSafRepository
{
    /**
     * | Meta Saf Details To Be Used in Various Common Functions
     */
    public function metaSafDtls($workflowIds)
    {
        return DB::table('prop_active_safs')
            ->leftJoin('prop_active_safs_owners as o', 'o.saf_id', '=', 'prop_active_safs.id')
            ->join('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
            ->select(
                'prop_active_safs.payment_status',
                'prop_active_safs.saf_no',
                'prop_active_safs.id',
                'prop_active_safs.workflow_id',
                'prop_active_safs.ward_mstr_id',
                'prop_active_safs.is_agency_verified',
                'prop_active_safs.is_field_verified',
                'prop_active_safs.is_geo_tagged',
                'ward.ward_name as ward_no',
                'prop_active_safs.prop_type_mstr_id',
                'prop_active_safs.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                DB::raw("string_agg(o.mobile_no,',') as mobile_no"),
                'p.property_type',
                'prop_active_safs.assessment_type as assessment',
                DB::raw("TO_CHAR(prop_active_safs.application_date, 'DD-MM-YYYY') as apply_date"),
                'prop_active_safs.parked',
                'prop_active_safs.prop_address',
                'prop_active_safs.applicant_name',
                'prop_active_safs.citizen_id',
            )
            ->whereIn('workflow_id', $workflowIds)
            ->where('is_gb_saf', false);
    }

    /**
     * | Get Saf Details
     */
    public function getSaf($workflowIds)
    {
        $data = $this->metaSafDtls($workflowIds)
            ->where('payment_status', 1);
        return $data;
    }

    /**
     * | Get Property and Saf Transaction
     */
    public function getPropTransByCitizenUserId($userId, $userType)
    {
        $query = "SELECT 
                        prop_transactions.*,
                        TO_CHAR(prop_transactions.tran_date,'dd-mm-YYYY') as tran_date,
                        s.saf_no,
                        p.holding_no,
                        CASE 
                            WHEN (prop_transactions.saf_id IS NULL) THEN 'PROPERTY'
                            WHEN (prop_transactions.property_id IS NULL) THEN 'SAF'
                        END AS application_type
                        
                        FROM 
                        prop_transactions 
                        LEFT JOIN (
                        SELECT 
                            pas.id, 
                            pas.saf_no 
                        FROM 
                            prop_active_safs as pas
                            JOIN prop_transactions
                            ON (pas.id=prop_transactions.saf_id)
                            WHERE prop_transactions.$userType=$userId
                        UNION 
                        SELECT 
                            ps.id, 
                            ps.saf_no 
                        FROM 
                            prop_safs as ps
                            JOIN prop_transactions
                            ON (ps.id=prop_transactions.saf_id)
                            WHERE prop_transactions.$userType=$userId
                        ) AS s ON s.id = prop_transactions.saf_id 
                        LEFT JOIN prop_properties AS p ON p.id=prop_transactions.property_id
                        WHERE 
                        prop_transactions.$userType = $userId
                    ORDER BY prop_transactions.id DESC";
        $result = DB::select($query);
        return $result;
    }
}
