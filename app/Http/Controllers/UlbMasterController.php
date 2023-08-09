<?php

namespace App\Http\Controllers;

use App\Models\UlbMaster;
use Exception;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * | Creation of Reference APIs
 * | Created By-Tannu Verma
 * | Created On-23-05-2023 
 * | Status-Open
 */

/**
 * |Fuctions for CRUD Operation in ULB Master
 * 1. storeulbmaster()
 * 2. showulbmaster()
 * 3. updateulbmaster()
 * 4. deactivateulbmaster()
 */

class UlbMasterController extends Controller
{

    public function listulbmaster()
    {
    // try {
        $ulbMaster = UlbMaster::select('ulb_masters.id','ulb_name', 'district_name', 'ulb_masters.district_code', 'ulb_masters.state_id', 'category', 'code')
        ->join('district_masters', 'district_masters.id','=', 'ulb_masters.district_id')
        ->orderBy('id')
        
        ->get();
        
    return response()->json($ulbMaster);
    // } catch (\Exception $e) {
    //     return response()->json([
    //         'message' => 'Error retrieving Ulb Master',
    //         'status' => 'error'
    //     ], 500);
    // }
   }
    

    /**
     * 2. showulbmaster()
     * Display the specified resource.
     */
    public function showulbmaster(Request $request)
    {
        try {
            $ulbMaster = UlbMaster::where('id',$request->id)
            ->where('status',1) ;
            $ulbMaster = UlbMaster::find($request->id);
            if (!$ulbMaster) {
                return response()->json([
                    'message' => 'Ulb Master Not Found',
                    'status' => 'error'
                ], 404);
            }
            return response()->json([
                'message' => 'Ulb Master Retrieved Successfully',
                'status' => 'success',
                'data' => $ulbMaster
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving Ulb Master',
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * 3. updateulbmaster()
     * Update the specified resource in storage.
     */
    public function updateulbmaster(Request $request)
    {
        try {
            $ulbMaster = [
                'ulb_name' => $request->ulbName,
                'ulb_type' => $request->ulbType,
                'city_id' => $request->cityId,
                'remarks' => $request->remarks,
                'deleted_at' => $request->deleted_at,
                'incorporation_date' => $request->incorporation_date,
                //'created_at' => $request->created_at,
                'updated_at' => Carbon::Now(),
                'department_id' => $request->department_id,
                'has_zone' => $request->has_zone,
                'district_code' => $request->district_code,
                'category' => $request->category,
                'code' => $request->code
            ];
            
            $ulb = UlbMaster::findOrFail($request->id);
            $ulb->update($ulbMaster);
    
            return response()->json([
                'message' => 'Application Updated Successfully',
                'status' => 'success',
                'data' => $ulbMaster
            ]);
        } 
        catch (Exception $e) {
            return response()->json([
                'message' => 'Error storing ulb_master',
                'status' => 'error'
            ], 500);
        }
    }
     
    
    /**
     * 4. deactivateulbmaster()
     * Remove the specified resource from storage.
     */
    public function deactivateulbmaster(Request $request)
   {
    try {
        $mUlbMaster = UlbMaster::find($request->id);
        {
             
            $mUlbMaster->status = 0;
            $mUlbMaster->save();
      
           
            return response()->json([
              'message' => 'Data deactivated successfully',
              'status' => 'success'
            ]);
          
        }
        
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error deactivating data',
            'status' => 'error'
        ], 500);
    }
}


}   

