<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkflowTrack;
use App\Traits\WorkflowTrack as TrackTrait;
use Exception;

/**
 * Created On-21-12-2022 
 * Created By-Anshu Kumar
 * ---------------------------------------------------------------------------------------------
 * Saving, Fetching the workflow track messages
 */

class WorkflowTrackController extends Controller
{

    use TrackTrait;

    /**
     * Save workflow Track
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required',
            ]);

            $track = new WorkflowTrack;
            $track->saveTrack($request);

            return responseMsg(true, 'Successfully Saved The Remarks', '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     *| Get Workflow Track by its Id
     *| @param WorkflowTrackId $id
     *| @return response
     */
    public function getWorkflowTrackByID(Request $req)
    {
        $detail = new WorkflowTrack();
        $list =  $detail->details()
            ->where('workflow_tracks.id', $req->id)
            ->first();

        return responseMsg(true, 'Data retrieved', $list);
    }

    /**
     *| Get WorkflowTrack By Reference Table ID and Reference Table Value
     *| @param ReferenceTableID $ref_table_id
     *| @param ReferenceTableValue $refereceTableValue
     */
    public function getWorkflowTrackByTableIDValue(Request $req)
    {
        $detail = new WorkflowTrack();
        $list =  $detail->details()
            ->where('workflow_tracks.ref_table_dot_id', $req->refTableId)
            ->where('workflow_tracks.ref_table_id_value', $req->refTableValue)
            ->get();

        return responseMsg(true, 'Data retrieved', $list);
    }


    //notification by citixen id
    public function getNotificationByCitizenId(Request $request)
    {
        $citizen_id = Auth()->user()->id;
        $notification  = WorkflowTrack::select(
            'workflow_id',
            'module_id',
            'ref_table_dot_id',
            'ref_table_id_value',
            'message',
            'workflow_tracks.created_at as track_date',
            'user_name'
        )
            ->join('users', 'users.id', '=', 'workflow_tracks.user_id')
            ->where('citizen_id', $citizen_id)
            ->where('workflow_id', $request->workflowId)
            ->where('ref_table_id_value', $request->id)
            ->get();
        return responseMsg(true, "Data Retrived", $notification);
    }
}
