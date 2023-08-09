<?php

namespace App\Traits\Property;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 20-11-2022 
 * | Created By - Anshu Kumar
 * | Created for the Concession Workflow Trait
 */
trait Concession
{
    // Get Concession List
    public function getConcessionList($worklowIds)
    {
        return DB::table('prop_active_concessions')
            ->select(
                'prop_active_concessions.id',
                'prop_active_concessions.workflow_id',
                'prop_active_concessions.application_no',
                'prop_active_concessions.applicant_name as owner_name',
                'new_holding_no',
                DB::raw("TO_CHAR(date, 'DD-MM-YYYY') as apply_date"),
                'pt_no',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type',
                'prop_active_concessions.workflow_id',
                'prop_active_concessions.current_role as role_id'
            )
            ->leftJoin('prop_properties as a', 'a.id', '=', 'prop_active_concessions.property_id')
            ->leftjoin('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->whereIn('workflow_id', $worklowIds)
            ->where('prop_active_concessions.status', 1);
    }

    /**
     * | check Post Condition for backward forward
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $concession)
    {
        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($concession->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;
            case $wfLevels['DA']:                       // DA Condition
                if ($concession->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                break;
        }
    }
}
