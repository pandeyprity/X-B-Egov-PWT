<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\RefPropDocsRequired;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Http\Request;

class ConcessionDocController extends Controller
{
    /**
     * | Created On-19/01/2022 
     * | Created by-Anshu Kumar
     * | Created for the document uploadation section for Concession
     */


    /**
     * | Get Document List of Concession (1)
     */
    public function docList(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);

        try {
            $array['documentsList'] = array();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropDocRequired = new RefPropDocsRequired();
            $mWfActiveDocuments = new WfActiveDocument();
            $applicationId = $req->applicationId;
            $concessionDtls = $mPropActiveConcession->getConcessionById($applicationId);

            if (!$concessionDtls) {
                throw new Exception("Application Not Found");
            }

            if ($concessionDtls->gender != 'Male' && !is_null($concessionDtls->gender)) {
                $document = $mPropDocRequired->getDocByDocType("gender_document");
                $docMstrIds = $this->readDocIds($document);
                $document = [
                    'docName' => 'gender_document',
                    'isMandatory' => 1,
                    'docVal' => $document,
                    'uploadDoc' => $mWfActiveDocuments->getAppByAppNoDocId($concessionDtls->application_no, $docMstrIds)
                ];
                array_push($array['documentsList'], $document);
            }

            if ($concessionDtls->is_specially_abled == true && !is_null($concessionDtls->is_specially_abled)) {
                $document = $mPropDocRequired->getDocByDocType("handicaped_document");
                $docMstrIds = $this->readDocIds($document);
                $document = [
                    'docName' => 'handicaped_document',
                    'isMandatory' => 1,
                    'docVal' => $document,
                    'uploadDoc' =>  $mWfActiveDocuments->getAppByAppNoDocId($concessionDtls->application_no, $docMstrIds)
                ];
                array_push($array['documentsList'], $document);
            }

            if ($concessionDtls->is_armed_force == true && !is_null($concessionDtls->is_armed_force)) {
                $document = $mPropDocRequired->getDocByDocType("armed_force_document");
                $docMstrIds = $this->readDocIds($document);
                $document = [
                    'docName' => 'armed_force_document',
                    'isMandatory' => 1,
                    'docVal' => $document,
                    'uploadDoc' =>  $mWfActiveDocuments->getAppByAppNoDocId($concessionDtls->application_no, $docMstrIds)
                ];
                array_push($array['documentsList'], $document);
            }

            if (!is_null($concessionDtls->dob)) {
                $document = $mPropDocRequired->getDocByDocType("dob_document");
                $docMstrIds = $this->readDocIds($document);
                $document = [
                    'docName' => 'dob_document',
                    'isMandatory' => 1,
                    'docVal' => $document,
                    'uploadDoc' =>  $mWfActiveDocuments->getAppByAppNoDocId($concessionDtls->application_no, $docMstrIds)
                ];
                array_push($array['documentsList'], $document);
            }

            return responseMsgs(true, "document list", remove_null($array), "011601", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011601", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read Document Ids from collection
     */
    public function readDocIds($documents)
    {
        return collect($documents)->map(function ($document) {
            return $document['id'];
        });
    }
}
