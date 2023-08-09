<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Http\Request;

class WfDocumentController extends Controller
{
    /**
     * | Created On=01-02-2023 
     * | Created By=Anshu Kumar
     * | Created for=Document Upload 
     * | Status-Closed
     */

    /**
     * | Approve Or Reject Document 
     */
    public function docVerifyReject(Request $req)
    {
        $req->validate([
            'id' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);

        try {
            $mWfDocument = new WfActiveDocument();
            $wfDocId = $req->id;
            $userId = authUser()->id;
            if ($req->docStatus == "Verified")
                $status = 1;
            if ($req->docStatus == "Rejected")
                $status = 2;
            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            return responseMsgs(true, $req->docStatus . " Successfully", "", "1001", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "1001", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
