<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApplicantDoc extends Model
{
    use HasFactory;

     /**
     * |---------------------------------- Calling function for the doc details from database -------------------------------|
     * | @param applicationId
     * | @var docDetails
     * | @return docDetails : listed doc details according to application Id
        | Serial No : 09.01
        | Working / Dhift to model
     */
    public function getWaterDocuments($applicationId)
    {
        $docDetails = WaterApplicantDoc::select(
            "water_applicant_docs.id",
            "water_applicant_docs.doc_name",
            "water_applicant_docs.doc_for",
            "water_applicant_docs.remarks",
            "water_applicant_docs.document_id",
            "water_applicant_docs.verify_status",
            'water_param_document_types.document_name',

        )
            ->join('water_param_document_types', 'water_param_document_types.id', '=', 'water_applicant_docs.document_id')
            ->where('water_applicant_docs.application_id', $applicationId)
            ->where('water_applicant_docs.status', 1)
            ->where('water_param_document_types.status', 1)
            ->orderBy('water_applicant_docs.id', 'desc')
            ->get();
        return remove_null($docDetails);
    }
}
