<?php

namespace App\Traits;

use App\Models\UlbWardMaster;
use App\Models\Ward\WardUser;

/**
 * | Created On-19-08-2022 
 * | Created By-Anshu Kumar
 * ---------------------------------------------------------------------------
 * | Trait Used For the Ward operations
 */
trait Ward
{
    // Store ulb ward
    public function store($ulb_ward, $request)
    {
        $ulb_ward->ulb_id = $request->ulbID;
        $ulb_ward->ward_name = $request->wardName;
        $ulb_ward->old_ward_name = $request->oldWardName;
    }

    // Check Existance for Ulb and Ward
    public function checkExistance($request)
    {
        return UlbWardMaster::where('ulb_id', $request->ulbID)
            ->where('ward_name', $request->wardName)
            ->first();
    }

    // Fetch Ulb Ward
    public function fetchUlbWard($ulb_ward)
    {
        return $ulb_ward
            ->select('ulb_ward_masters.*', 'ulb_masters.ulb_name')
            ->join("ulb_masters", "ulb_masters.id", "=", "ulb_ward_masters.ulb_id");
    }

    // //////////////////////////////////////////////////////////////////////////////
    // Check Ward User Existing
    public function checkWardUserExisting($request)
    {
        return WardUser::where('user_id', $request->userID)
            ->where('ulb_ward_id', $request->ulbWardID)
            ->first();
    }

    // get all ward master data
    public function getAllWard($ulbId)
    {
        return UlbWardMaster::select(
            'id',
            'ward_name AS ward'
        )
            ->where('ulb_id', $ulbId)
            ->where('deleted_at',false)
            ->get();
    }
}
