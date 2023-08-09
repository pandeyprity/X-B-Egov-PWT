<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class DistrictWiseDataController extends Controller
{

    /**
     * | Get Collection by District code
     */
    public function districtWiseCollection(Request $req)
    {
        $req->validate([
            'districtCode' => 'required|integer'
        ]);
        try {
            return $req->all();
            return responseMsgs(false, "Demand Collection", "", "0301", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0301", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
