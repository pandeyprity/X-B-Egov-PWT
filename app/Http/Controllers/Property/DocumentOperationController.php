<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iDocumentOperationRepo;
use Illuminate\Http\Request;

class DocumentOperationController extends Controller
{
    /**
     * | Created On-27-11-2022 
     * | Created By-Sam kerketta
     * --------------------------------------------------------------------------------------
     * | Controller for Property Document Operation
     */

    // Initializing function for Repository
    protected $DocumentOperationRepo;
    public function __construct(iDocumentOperationRepo $DocumentOperationRepo)
    {
        $this->DocumentOperationRepo = $DocumentOperationRepo;
    }

    // Get all Details Of the Document According to workflow and application ID
    public function getAllDocuments(Request $request)
    {
        return $this->DocumentOperationRepo->getAllDocuments($request);
    }
}
