<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfActiveDocument extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getDocByRefIds($activeId, $workflowId, $moduleId)
    {
        return WfActiveDocument::select(
            DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Upload document funcation
     */
    public function postDocuments($req, $auth)
    {
        $metaReqs = new WfActiveDocument();
        $metaReqs->active_id            = $req->activeId;
        $metaReqs->workflow_id          = $req->workflowId;
        $metaReqs->ulb_id               = $req->ulbId;
        $metaReqs->module_id            = $req->moduleId;
        $metaReqs->relative_path        = $req->relativePath;
        $metaReqs->document             = $req->document;
        $metaReqs->uploaded_by          = $auth['id'];
        $metaReqs->uploaded_by_type     = $auth['user_type'];
        $metaReqs->remarks              = $req->remarks ?? null;
        $metaReqs->doc_code             = $req->docCode;
        $metaReqs->owner_dtl_id         = $req->ownerDtlId;
        $metaReqs->save();
    }


    /**
     * | Post Workflow Document
     */
    public function postPetDocuments($req)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $mWfActiveDocument->active_id         = $req->activeId;
        $mWfActiveDocument->workflow_id       = $req->workflowId;
        $mWfActiveDocument->ulb_id            = $req->ulbId;
        $mWfActiveDocument->module_id         = $req->moduleId;
        $mWfActiveDocument->relative_path     = $req->relativePath;
        $mWfActiveDocument->document          = $req->document;
        $mWfActiveDocument->uploaded_by       = authUser()->id;
        $mWfActiveDocument->uploaded_by_type  = authUser()->user_type;
        $mWfActiveDocument->remarks           = $req->remarks ?? null;
        $mWfActiveDocument->doc_code          = $req->docCode;
        $mWfActiveDocument->owner_dtl_id      = $req->ownerDtlId;
        $mWfActiveDocument->doc_category      = $req->docCategory ?? null;
        if (isset($req->verifyStatus)) {
            $mWfActiveDocument->verify_status = $req->verifyStatus;
        }
        $mWfActiveDocument->save();
    }

    /**
     * | view Uploaded documents
     */
    public function uploadDocumentsViewById($appId, $workflowId)
    {
        $data = WfActiveDocument::select('*', DB::raw("replace(doc_code,'_',' ') as doc_val"), DB::raw("CONCAT(wf_active_documents.relative_path,'/',wf_active_documents.document) as doc_path"))
            ->where(['active_id' => $appId, 'workflow_id' => $workflowId])
            ->where('current_status', '1')
            ->get();
        return $data;
    }

    /**
     * | view Uploaded documents Active
     */
    public function uploadedActiveDocumentsViewById($appId, $workflowId)
    {
        $data = WfActiveDocument::select('*', DB::raw("replace(doc_code,'_',' ') as doc_val"), DB::raw("CONCAT(wf_active_documents.relative_path,'/',wf_active_documents.document) as doc_path"))
            ->where(['active_id' => $appId, 'workflow_id' => $workflowId])
            ->where('current_status', '1')
            ->get();
        return $data;
    }

    /**
     * | Document Verify Reject
     */
    public function docVerifyReject($id, $req)
    {
        $document = WfActiveDocument::find($id);
        $document->remarks = $req['remarks'];
        $document->verify_status = $req['verify_status'];
        $document->action_taken_by = $req['action_taken_by'];
        $document->save();
    }

    /**
     * | Get Uploaded documents
     */
    public function getDocsByActiveId($req)
    {
        return WfActiveDocument::where('active_id', $req->activeId)
            ->select(
                'doc_code',
                'owner_dtl_id',
                'verify_status'
            )
            ->where('workflow_id', $req->workflowId)
            ->where('module_id', $req->moduleId)
            ->where('verify_status', '!=', 2)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Total no of document for upload
     */
    public function totalNoOfDocs($docCode)
    {
        $noOfDocs = RefRequiredDocument::select('requirements')
            ->where('code', $docCode)
            ->first();
        $totalNoOfDocs = explode("#", $noOfDocs);
        return count($totalNoOfDocs);
    }

    /**
     * | Get total uploaded documents
     */
    public function totalUploadedDocs($applicationId, $workflowId, $moduleId)
    {
        return WfActiveDocument::where('active_id', $applicationId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('current_status', '1')
            ->where('verify_status', '!=', 2)
            ->count();
    }

    /**
     * | Check if the Doc Category already Existing or not
     */
    public function isDocCategoryExists($activeId, $workflowId, $moduleId, $docCategory, $ownerId = null)
    {
        return WfActiveDocument::where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('doc_category', $docCategory)
            ->where('owner_dtl_id', $ownerId)
            ->where('verify_status', 0)
            ->where('status', 1)
            ->first();
    }

    /**
     * | Edit Existing Document
     */
    public function editDocuments($wfActiveDocument, $req)
    {
        $wfActiveDocument->update([
            'active_id'         => $req->activeId,
            'workflow_id'       => $req->workflowId,
            'ulb_id'            => $req->ulbId,
            'module_id'         => $req->moduleId,
            'relative_path'     => $req->relativePath,
            'document'          => $req->document,
            'uploaded_by'       => authUser()->id,
            'uploaded_by_type'  => authUser()->user_type,
            'remarks'           => $req->remarks ?? null,
            'doc_code'          => $req->docCode,
            'owner_dtl_id'      => $req->ownerDtlId,
            'doc_category'      => $req->docCategory ?? null
        ]);
    }

    /**
     * | Deactivate the Rejected Document 
     * | @param metaReqs
       | Use for deactivate the rejected document
     */
    public function deactivateRejectedDoc($metaReqs)
    {
        WfActiveDocument::where('active_id', $metaReqs->activeId)
            ->where('workflow_id', $metaReqs->workflowId)
            ->where('module_id', $metaReqs->moduleId)
            ->where('doc_code', $metaReqs->docCode)
            ->where('verify_status', 2)
            ->update([
                "status" => 0
            ]);
    }

    /**
     * | Get document which are active as well rejected 
     * | @param applicationId
     * | @param workflowId
     * | @param moduleId
     */
    public function getWaterDocsByAppNo($applicationId, $workflowId, $moduleId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                'o.applicant_name as owner_name'
            )
            ->leftJoin('pet_active_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId);
    }

    /**
     * | Meta Request function for updation and post the request
     */
    public function metaReqs($req)
    {
        return [
            "active_id" => $req->activeId,
            "workflow_id" => $req->workflowId,
            "ulb_id" => $req->ulbId,
            "module_id" => $req->moduleId,
            "relative_path" => $req->relativePath,
            "document" => $req->document,
            "uploaded_by" => authUser()->id,
            "uploaded_by_type" => authUser()->user_type,
            "remarks" => $req->remarks ?? null,
            "doc_code" => $req->docCode,
            "owner_dtl_id" => $req->ownerDtlId,
            "doc_category" => $req->docCategory ?? null
        ];
    }

    /**
     * | Delete the document for the application before payment
     */
    public function deleteDocuments($applicationId, $workflowId, $moduleId)
    {
        WfActiveDocument::where('active_id', $applicationId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->delete();
    }

    /**
     * | Get Application Details by Application No
     */
    public function getDocsByAppId($applicationId, $workflowId, $moduleId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'o.owner_name'
            )
            ->leftJoin('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->get();
    }
}
