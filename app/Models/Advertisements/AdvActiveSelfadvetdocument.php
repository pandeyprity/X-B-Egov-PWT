<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvActiveSelfadvetdocument extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Helpers Requests for Store and Update
     */
    public function metaReqs($req)
    {
        return [
            'temp_id' => $req->tempId,
            'doc_type_code' => $req->docTypeCode,
            'document_id' => $req->documentId,
            'relative_path' => $req->relativePath,
            'doc_name' => $req->docName,
            'workflow_id' => $req->workflowId
        ];
    }
    
    /**
     * | Store
     * | @param Request $req
     */
    public function store($req)
    {
        $metaReqs = $this->metaReqs($req);
        AdvActiveSelfadvetdocument::create($metaReqs);
    }
}
