<?php

namespace App\Http\Controllers\Grievance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Grievance\Interfaces\iGrievance;

class GrievaceController extends Controller
{
    private iGrievance $newGrievance;
    public function __construct(iGrievance $newGrievance)
    {
        $this->newGrievance = $newGrievance;
    }

    //
    public function postFileComplain(Request $request)
    {
        return $this->newGrievance->saveFileComplain($request);
    }

    //
    public function getAllComplainById(Request $req)
    {
        return $this->newGrievance->getAllComplainById($req);
    }

    //
    public function updateRateComplaintById(Request $req, $id)
    {
        return $this->newGrievance->updateRateComplaintById($req, $id);
    }

    //
    public function getAllComplaintList($id)
    {
        return $this->newGrievance->getAllComplaintList($id);
    }

     //
     public function putReopenComplaintById(Request $req, $id)
     {
         return $this->newGrievance->putReopenComplaintById($req, $id);
     }
}
