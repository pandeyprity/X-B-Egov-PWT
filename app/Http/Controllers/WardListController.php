<?php

namespace App\Http\Controllers;

use App\Models\UlbWardMaster;
use App\Models\WardList;
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
 * |Fuctions for CRUD Operation in ULB Ward Master
 * 1. index()
 * 2. store()
 * 3. show()
 * 4. update()
 * 5. deactivate()
 */

class WardListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $mward_list=new WardList();
            $ulbWardMaster= [
                'ulb_id'=>$request->ulbId,
                'ward_name' => $request->wardName,
                'old_ward_name' => $request->oldwardName,
                'created_at' => Carbon::now(),
                 ];

                $mward_list->store($ulbWardMaster);
                return $ulbWardMaster;
    
            return response()->json([
             'message' => 'Application Submitted Successfully',
                'status' => 'success',
                'data' => $ulbWardMaster
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
     * Display the specified resource.
     */
    public function show(Request $request)
   {
    try {
        $ward_list = WardList::where('id', $request->id)
            ->where('status', 1)
            ->first();

        if (!$ward_list) {
            return response()->json([
                'message' => 'Ward List Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Ward List Retrieved Successfully',
            'status' => 'success',
            'data' => $ward_list
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Ward List',
            'status' => 'error'
        ], 500);
    }
   }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $ward_list = [
                'ulb_id'=>$request->ulbId,
                'ward_name'=>$request->wardName,
                'old_ward_name'=>$request->oldwardName,
            ];
            
            $ulb = WardList::findOrFail($request->id);
            $ulb->update($ward_list);
                                                                                                                                                                     
            return response()->json([
                'message' => 'Application Updated Successfully',
                'status' => 'success',
                'data' => $ward_list
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
     * Remove the specified resource from storage.
     */
    public function deactivate(Request $request)
    {
      $ward_list = WardList::findOrFail($request->id);  
      $ward_list->status = 0;
      $ward_list->save();

     
      return response()->json([
        'message' => 'Data deactivated successfully',
        'status' => 'success'
      ]);
    
  }

}



