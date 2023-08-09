<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\Property\reqBifurcation;
use App\Repository\Property\Concrete\PropertyDeactivate;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use Illuminate\Http\Request;

class PropertyBifurcationController extends Controller
{
    /**
     * | Created On-23-11-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Bifurcation Procese)
     */

    private $Repository;
    private $Property;
    public function __construct(IPropertyBifurcation $Repository)
    {
        $this->Repository = $Repository;
        $this->Property = new PropertyDeactivate();
    }
    public function readHoldigbyNo(Request $request)
    {
        return $this->Property->readHoldigbyNo($request);
    }
    public function addRecord(reqBifurcation $request)
    {
        return $this->Repository->addRecord($request);
    }
    public function inbox(Request $request)
    {
        return $this->Repository->inbox($request);
    }
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }
    public function postNextLevel(Request $request)
    {
        return $this->Repository->postNextLevel($request);
    }
    public function readSafDtls(Request $request)
    {
        return $this->Repository->readSafDtls($request->id);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    //saf
    public function safDocumentUpload(Request $request)
    {
        return $this->Repository->safDocumentUpload($request);
    }
    //saf
    public function getDocList(Request $request)
    {
        return $this->Repository->getDocList($request);
    }
    //saf
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
    public function CitizenPymentHistory(Request $request)
    {
        return $this->Repository->CitizenPymentHistory($request);
    }
}
