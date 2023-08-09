<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-19-11-2022 
 * | Created By-Sandeep Bara
 **/

interface IPropertyBifurcation
{
    public function addRecord(Request $request);
    public function inbox(Request $request);
    public function outbox(Request $request);
    public function postNextLevel(Request $request);
    public function readSafDtls($id);
    public function getDocList($request);
    public function documentUpload(Request $request);
    public function safDocumentUpload(Request $request);
    public function getUploadDocuments(Request $request);
    public function CitizenPymentHistory(Request $request);
}
