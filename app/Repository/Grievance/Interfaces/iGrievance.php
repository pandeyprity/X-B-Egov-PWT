<?php

namespace App\Repository\Grievance\Interfaces;

use Illuminate\Http\Request;


interface iGrievance
{
    // grievance
    public function saveFileComplain(Request $request);
    public function getAllComplainById(Request $req);
    public function updateRateComplaintById(Request $req, $id);
    public function getAllComplaintList($id);
    public function putReopenComplaintById(Request $req, $id);
}
