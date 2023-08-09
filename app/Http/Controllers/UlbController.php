<?php

namespace App\Http\Controllers;

use App\Models\Masters\MCity;
use App\Models\UlbMaster;
use Illuminate\Http\Request;
use App\Repository\Ulbs\EloquentUlbRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Created On-02-07-2022 
 * Created By-Anshu Kumar
 * --------------------------------------------------------------------------------
 * Saving, viewing and editing the Ulbs
 */

class UlbController extends Controller
{
    protected $eloquentUlb;
    // Initializing Construct function
    public function __construct(EloquentUlbRepository $eloquentUlb)
    {
        $this->EloquentUlb = $eloquentUlb;
    }
    // Storing 
    public function store(Request $request)
    {
        return $this->EloquentUlb->store($request);
    }

    // Updating
    public function edit(Request $request, $id)
    {
        return $this->EloquentUlb->edit($request, $id);
    }

    // View Ulbs by Id
    public function view($id)
    {
        return $this->EloquentUlb->view($id);
    }

    // Get All Ulbs
    public function getAllUlb()
    {
        return $this->EloquentUlb->getAllUlb();
    }

    // Delete Ulb
    public function deleteUlb($id)
    {
        return $this->EloquentUlb->deleteUlb($id);
    }

    // Get City State by Ulb Id
    public function getCityStateByUlb(Request $req)
    {
        if (!$req->bearerToken()) {
            $req->validate([
                'ulbId' => 'required|integer'
            ]);
        }
        try {
            $ulbId = $req->ulbId ?? authUser($req)->ulb_id;
            $mCity = new MCity();
            $data = $mCity->getCityStateByUlb($ulbId);
            return responseMsgs(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | list of ulb by district code
     */
    public function districtWiseUlb(Request $req)
    {
        $req->validate([
            'districtCode' => 'required'
        ]);
        $mUlbMaster = new UlbMaster();
        $ulbList = $mUlbMaster->getUlbsByDistrictCode($req->districtCode);
        return responseMsgs(true, "", remove_null($ulbList));
    }

    /**
     * | District List
     */
    public function districtList(Request $req)
    {
        return DB::table('district_masters')
            ->orderBy('district_code')
            ->get();
    }
}
