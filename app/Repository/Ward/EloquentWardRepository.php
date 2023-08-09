<?php

namespace App\Repository\Ward;

use App\Http\Requests\Ward\UlbWardRequest;
use App\Repository\Ward\WardRepository;
use App\Models\UlbWardMaster;
use Exception;
use App\Traits\Ward;

/**
 * | Created On-19-08-2022 
 * | Created By-Anshu Kumar
 * ------------------------------------------------------------------------------------
 * | Repository for Ward Operations
 */

class EloquentWardRepository implements WardRepository
{
    use Ward;
    /**
     * | Store Ulb Ward 
     * | @param Request 
     * | @param Request $request
     * -------------------------------------------------
     * | #check_existance > Checks the existance of the Ulb Ward
     */
    public function storeUlbWard(UlbWardRequest $request)
    {
        try {
            $check_existance = $this->checkExistance($request);
            if ($check_existance) {
                return responseMsg(false, "Ward Name Already Existing For this Ulb", "");
            }
            $ulb_ward = new UlbWardMaster();
            $this->store($ulb_ward, $request);                                  // Save Using Trait
            $ulb_ward->save();
            return responseMsg(true, "Successfully Saved the Ward", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Edit Ulb Ward
     * | @param UlbWardRequest 
     * | @param UlbWardRequest $request
     * ---------------------------------------------------------------------------------------
     * | #stmt > Statement for the condition if the ulb and ward is same as previous data then update
     * | if the statement does not satisfy then first check the existing 
     * | $check_existing > Checks if the ward already existing for the ulb id or not
     */
    public function editUlbWard(UlbWardRequest $request, $id)
    {
        try {
            $ulb_ward = UlbWardMaster::find($id);
            $stmt = $ulb_ward->ulb_id == $request->ulbID && $ulb_ward->ward_name == $request->wardName;
            if ($stmt) {
                $this->store($ulb_ward, $request);                                  // Save Using Trait
                return responseMsg(true, "Succcessfully Updated The Ward", "");
            }
            $check_existance = $this->checkExistance($request);
            if ($check_existance) {
                return responseMsg(false, "Ward Name Already Existing For this Ulb", "");
            } else {
                $this->store($ulb_ward, $request);                                  // Save Using Trait
                $ulb_ward->save();
                return responseMsg(true, "Succcessfully Updated The Ward", "");
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Get Ulb Ward By Ulb ID
     * | @param UlbWardID
     * | @return response
     */
    public function getUlbWardByID($id)
    {
        $ulb_ward = UlbWardMaster::find($id);
        if ($ulb_ward) {
            $data = $this->fetchUlbWard($ulb_ward)
                ->first();
            return responseMsg(true, "Data Fetched", remove_null($data));
        } else
            return responseMsg(false, "Data not available", "");
    }

    /**
     * | Get All Ulb Ward
     */
    public function getAllUlbWards()
    {
        $ulb_ward = UlbWardMaster::orderByDesc('id');
        $data = $this->fetchUlbWard($ulb_ward)->get();
        return responseMsg(true, "Data Fetched", remove_null($data));
    }
}
