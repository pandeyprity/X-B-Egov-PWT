<?php

namespace App\Traits\Property;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 20-11-2022 
 * | Created By - Mrinal Kumar
 * | Created for the Objection Workflow Trait
 */
trait Objection
{

    // Get Concession List
    public function getObjectionList($workflowIds)
    {
        return DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.id',
                'prop_active_objections.workflow_id',
                'pt_no',
                'prop_active_objections.objection_no as application_no',
                'p.ward_mstr_id as old_ward_id',
                'u.ward_name as old_ward_no',
                'p.new_ward_mstr_id',
                DB::raw("string_agg(owner_name,',') as applicant_name"),
                'p.new_holding_no',
                'p.holding_no',
                DB::raw("TO_CHAR(date, 'DD-MM-YYYY') as apply_date"),
                'p.balance',
                't.property_type',
                'p.assessment_type',
                'objection_for'
            )
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->leftJoin('ref_prop_types as t', 't.id', '=', 'p.prop_type_mstr_id')
            ->join('prop_owners', 'prop_owners.property_id', 'p.id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'p.ward_mstr_id')
            ->whereIn('workflow_id', $workflowIds)
            ->groupBy(
                'prop_active_objections.id',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_no',
                'p.ward_mstr_id',
                'u.ward_name',
                'p.new_ward_mstr_id',
                'p.new_holding_no',
                'p.holding_no',
                'date',
                'p.balance',
                't.property_type',
                'p.assessment_type',
                'objection_for',
                'pt_no'
            );
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
            case $wfLevels['SI']:                       // SI Condition
                if ($concession->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                break;
        }
    }
}
