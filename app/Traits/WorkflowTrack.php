<?php

namespace App\Traits;

/**
 *| @desc-Workflow Tracking Messages Trait 
 *| Created On-29-07-2022 
 *| Created By-Anshu Kumar
 *------------------------------------------------------------------------------------------
 *| Code Tested By-
 *| Code Testing Date-
 */

trait WorkflowTrack
{
    // Query references for required fields for workflow tracking

    public function refQuery()
    {
        $query = "SELECT 
                    workflow_tracks.id,
                    workflow_tracks.user_id,
                    workflow_tracks.citizen_id,
                    workflow_tracks.module_id,
                    module_masters.module_name,
                    workflow_tracks.ref_table_dot_id,
                    workflow_tracks.ref_table_id_value,
                    workflow_tracks.message,
                    workflow_tracks.track_date,
                    users.user_name
                    
            FROM workflow_tracks 
            LEFT JOIN users  on users.id=workflow_tracks.user_id
            LEFT JOIN module_masters on module_masters.id=workflow_tracks.module_id";
        return $query;
    }

    // Fetching Data in array format
    public function fetchData($arr, $track)
    {
        foreach ($track as $tracks) {
            $val['id'] = $tracks->id ?? '';
            $val['user_id'] = $tracks->user_id ?? '';
            $val['user_name'] = $tracks->user_name ?? '';
            $val['citizen_id'] = $tracks->citizen_id ?? '';
            $val['module_id'] = $tracks->module_id ?? '';
            $val['module_name'] = $tracks->module_name ?? '';
            $val['ref_table_dot_id'] = $tracks->ref_table_dot_id ?? '';
            $val['ref_table_id_value'] = $tracks->ref_table_id_value ?? '';
            $val['message'] = $tracks->message ?? '';
            $val['track_date'] = $tracks->track_date ?? '';
            array_push($arr, $val);
        }
        return response()->json($arr, 200);
    }
}
